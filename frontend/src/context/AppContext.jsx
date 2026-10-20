import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import {
  INITIAL_CLAIMS, INITIAL_EVENTS, INITIAL_MENU, INITIAL_ORDERS,
  INITIAL_RULES, INITIAL_TEAM,
} from '../data/mock.js'
import { apiRequest, clearApiSession, getApiToken, mapArticle, mapUtilisateur, readApiUser, saveApiSession, saveApiUser } from '../services/api.js'

const AppContext = createContext(null)

const read = (key, fallback) => {
  try {
    const v = localStorage.getItem(key)
    return v ? JSON.parse(v) : fallback
  } catch {
    return fallback
  }
}
const write = (key, value) => {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch {
    /* stockage indisponible : on ignore */
  }
}

const mapStaffOrder = (order, status) => ({
  id: order.id,
  customer: order.client?.nom || 'Client',
  mode: order.mode_service,
  arrival: order.heure_arrivee,
  note: order.commentaire,
  payment: order.paiement?.mode_paiement,
  paymentStatus: order.paiement?.statut_paiement,
  total: order.total,
  status,
  createdAt: Date.parse(order.date_commande),
  items: (order.lignes || []).map((line) => ({ name: line.nom, qty: line.quantite, done: false })),
})

/**
 * Store global de l'application (état local de démonstration).
 * Chaque bloc est isolé pour être remplacé facilement par des appels à l'API Laravel
 * (VITE_API_URL) : auth, menu, commandes, réclamations, équipe, événements, règles.
 */
export function AppProvider({ children }) {
  const [user, setUser] = useState(() => readApiUser() || read('mmm_user', null))
  const [cart, setCart] = useState(() => read('mmm_cart', []))
  const [cookieConsent, setCookieConsent] = useState(() => read('mmm_cookies', null))
  const [menu, setMenu] = useState(INITIAL_MENU)
  const [orders, setOrders] = useState(INITIAL_ORDERS)
  const [claims, setClaims] = useState(INITIAL_CLAIMS)
  const [team, setTeam] = useState(INITIAL_TEAM)
  const [events, setEvents] = useState(INITIAL_EVENTS)
  const [rules, setRules] = useState(INITIAL_RULES)
  const [toasts, setToasts] = useState([])
  const [lastAdded, setLastAdded] = useState(null)
  const [loggedOut, setLoggedOut] = useState(false) // déconnexion volontaire (évite la redirection vers /connexion)

  useEffect(() => {
    if (getApiToken()) saveApiUser(user)
    else write('mmm_user', user)
  }, [user])
  useEffect(() => write('mmm_cart', cart), [cart])

  // Recharge le profil depuis Laravel et synchronise le menu public lorsqu'il est disponible.
  useEffect(() => {
    let actif = true
    if (getApiToken()) {
      apiRequest('/moi').then((profil) => {
        if (actif) setUser(mapUtilisateur(profil))
      }).catch(() => {
        if (actif) { clearApiSession(); setUser(null) }
      })
    }
    apiRequest('/menu-du-jour').then((reponse) => {
      const articles = reponse?.data || []
      if (actif && articles.length) setMenu(articles.map(mapArticle))
    }).catch(() => {})
    return () => { actif = false }
  }, [])

  /* ---------- Toasts ---------- */
  const toast = useCallback((message, type = 'success') => {
    const id = Date.now() + Math.random()
    setToasts((t) => [...t, { id, message, type }])
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 3200)
  }, [])

  const chargerCommandesPersonnel = useCallback(async () => {
    const resultat = await apiRequest('/staff/commandes')
    setOrders([
      ...(resultat.a_preparer || []).map((order) => mapStaffOrder(order, 'a_preparer')),
      ...(resultat.en_cuisine || []).map((order) => mapStaffOrder(order, 'en_cuisine')),
      ...(resultat.pret || []).map((order) => mapStaffOrder(order, 'pret')),
    ])
  }, [])

  useEffect(() => {
    if (!getApiToken() || !['employe', 'gerant', 'administrateur'].includes(user?.role)) return
    chargerCommandesPersonnel().catch((error) => toast(error.message, 'error'))
  }, [user?.role, chargerCommandesPersonnel, toast])

  /* ---------- Cookies (RGPD) ---------- */
  const answerCookies = (accepted) => {
    setCookieConsent(accepted ? 'accepted' : 'refused')
    write('mmm_cookies', accepted ? 'accepted' : 'refused')
  }

  /* ---------- Authentification ---------- */
  const login = async (email, password, remember = false) => {
    try {
      const reponse = await apiRequest('/auth/connexion', {
        method: 'POST', auth: false,
        body: { email: email.trim().toLowerCase(), mot_de_passe: password, se_souvenir: remember },
      })
      const compte = mapUtilisateur(reponse.utilisateur)
      saveApiSession(reponse.jeton, compte, remember)
      setUser(compte)
      setLoggedOut(false)
      return { ok: true, user: compte }
    } catch (error) {
      return { ok: false, error: error.message, code: error.payload?.code }
    }
  }

  const register = async (data) => {
    try {
      const reponse = await apiRequest('/auth/inscription', {
        method: 'POST',
        body: {
          nom: data.name.trim(), email: data.email.trim().toLowerCase(), telephone: data.phone,
          localisation: data.place, mot_de_passe: data.password, code_parrain: data.sponsorCode || null,
        },
      })
      return {
        ok: true,
        verificationRequired: Boolean(reponse.verification_requise),
        email: reponse.email,
        message: reponse.message,
      }
    } catch (error) {
      return { ok: false, error: error.message }
    }
  }

  const logout = async () => {
    const wasConnected = Boolean(getApiToken())
    clearApiSession()
    setLoggedOut(true)
    setUser(null)
    setCart([])
    if (wasConnected) apiRequest('/auth/deconnexion', { method: 'POST' }).catch(() => {})
  }

  // Les points ne sont jamais modifiables depuis le profil : seul le backend les crédite.
  const updateUser = async (patch) => {
    if (!getApiToken()) {
      const localPatch = { ...patch }
      delete localPatch.points
      setUser((u) => ({ ...u, ...localPatch }))
      return { ok: true }
    }
    const donnees = {}
    if (patch.firstName !== undefined || patch.lastName !== undefined) {
      donnees.nom = `${patch.firstName ?? user.firstName} ${patch.lastName ?? user.lastName ?? ''}`.trim()
    }
    if (patch.email !== undefined) donnees.email = patch.email
    if (patch.phone !== undefined) donnees.telephone = patch.phone
    if (patch.place !== undefined) donnees.localisation = patch.place
    if (Object.keys(donnees).length === 0) return { ok: true }
    try {
      const profil = await apiRequest('/moi', { method: 'PATCH', body: donnees })
      const compte = mapUtilisateur(profil)
      setUser((u) => ({ ...u, ...compte, residence: patch.residence ?? u.residence }))
      return { ok: true }
    } catch (error) {
      return { ok: false, error: error.message }
    }
  }

  const changePassword = async (current, next) => {
    if (!getApiToken()) return { ok: false, error: 'Connectez-vous pour modifier votre mot de passe.' }
    try {
      await apiRequest('/moi/mot-de-passe', {
        method: 'PUT',
        body: {
          mot_de_passe_actuel: current,
          nouveau_mot_de_passe: next,
          nouveau_mot_de_passe_confirmation: next,
        },
      })
      return { ok: true }
    } catch (error) {
      return { ok: false, error: error.message }
    }
  }

  const deleteAccount = async (password) => {
    if (getApiToken()) {
      try { await apiRequest('/moi', { method: 'DELETE', body: { mot_de_passe: password } }) } catch (error) { return { ok: false, error: error.message } }
    }
    logout()
    return { ok: true }
  }

  /* ---------- Panier ---------- */
  const addToCart = (dish) => {
    setCart((c) => {
      const i = c.findIndex((x) => x.id === dish.id)
      if (i >= 0) return c.map((x, idx) => (idx === i ? { ...x, qty: x.qty + 1 } : x))
      return [...c, { id: dish.id, qty: 1 }]
    })
    setLastAdded(dish)
  }
  const setQty = (id, qty) =>
    setCart((c) => (qty <= 0 ? c.filter((x) => x.id !== id) : c.map((x) => (x.id === id ? { ...x, qty } : x))))
  const clearCart = () => setCart([])

  const cartLines = useMemo(
    () =>
      cart
        .map((l) => {
          const dish = menu.find((m) => m.id === l.id)
          return dish ? { ...l, dish } : null
        })
        .filter(Boolean),
    [cart, menu],
  )
  const cartCount = cartLines.reduce((s, l) => s + l.qty, 0)
  const cartSubtotal = cartLines.reduce((s, l) => s + l.qty * l.dish.price, 0)

  /* ---------- Commandes ---------- */
  const placeOrder = async ({ mode, arrival, note, usePoints, payment, telephone }) => {
    const commande = await apiRequest('/commandes', {
      method: 'POST',
      body: {
        lignes: cartLines.map((line) => ({ article_id: line.id, quantite: line.qty })),
        mode_service: mode,
        heure_arrivee: mode === 'sur_place' ? arrival : null,
        adresse_livraison: mode === 'livraison' ? user?.place : null,
        commentaire: note || null,
        utiliser_points: usePoints,
      },
    })
    let demarrage
    try {
      demarrage = payment === 'especes'
        ? await apiRequest(`/commandes/${commande.id}/paiement-especes`, { method: 'POST' })
        : await apiRequest(`/commandes/${commande.id}/paiement`, {
            method: 'POST',
            body: { telephone, operateur: payment },
          })
    } catch (error) {
      try { await apiRequest(`/commandes/${commande.id}/annuler`, { method: 'POST' }) } catch { /* commande déjà annulée ou API indisponible */ }
      throw error
    }
    let statut = demarrage.paiement.statut_paiement
    if (demarrage.mode_simulation && demarrage.paiement.id_paiement) {
      const confirmation = await apiRequest(`/paiements/${demarrage.paiement.id_paiement}/simulation`, {
        method: 'POST', body: { status: 'success' },
      })
      statut = confirmation.statut
    }
    const compte = await apiRequest('/moi')
    setUser(mapUtilisateur(compte))
    const commandeFinale = demarrage.commande || commande
    const status = ({ en_attente: 'a_preparer', en_preparation: 'en_cuisine', prete: 'pret', servie: 'terminee', livree: 'terminee' })[commandeFinale.statut] || 'a_preparer'
    setOrders((o) => [...o, {
      id: commandeFinale.id, userId: user.id, customer: `${user.firstName} ${user.lastName || ''}`.trim(),
      mode: commandeFinale.mode_service, arrival: commandeFinale.heure_arrivee, note: commandeFinale.commentaire,
      payment, total: commandeFinale.total, status, createdAt: Date.parse(commandeFinale.date_commande),
      items: commandeFinale.lignes.map((line) => ({ name: line.nom, qty: line.quantite, done: false })),
    }])
    clearCart()
    return {
      id: commandeFinale.id,
      pending: statut === 'en_attente' && !demarrage.mode_simulation,
      cash: demarrage.paiement.mode_paiement === 'especes',
      redirectUrl: demarrage.paiement.url_paiement,
    }
  }
  const setOrderStatus = async (id, status) => {
    const routes = { en_cuisine: 'demarrer', pret: 'prete', cloture: 'cloturer' }
    if (getApiToken() && routes[status]) {
      try {
        await apiRequest(`/staff/commandes/${id}/${routes[status]}`, { method: 'POST' })
      } catch (error) {
        toast(error.message, 'error')
        return
      }
    }
    setOrders((o) => o.map((x) => (x.id === id ? { ...x, status } : x)))
  }
  const encaisserEspeces = async (id) => {
    try {
      await apiRequest(`/staff/commandes/${id}/encaisser-especes`, { method: 'POST' })
      setOrders((o) => o.map((x) => (x.id === id ? { ...x, paymentStatus: 'reussi' } : x)))
      toast(`Espèces encaissées pour la commande #${id}`)
    } catch (error) {
      toast(error.message, 'error')
    }
  }
  const notifierCommande = async (id) => {
    if (!getApiToken()) return toast(`Client de la commande #${id} notifié`)
    try {
      await apiRequest(`/staff/commandes/${id}/notifier`, { method: 'POST' })
      toast(`Client de la commande #${id} notifié`)
    } catch (error) {
      toast(error.message, 'error')
    }
  }
  const toggleOrderItem = (id, idx) =>
    setOrders((o) => o.map((x) => (x.id === id ? { ...x, items: x.items.map((it, i) => (i === idx ? { ...it, done: !it.done } : it)) } : x)))

  /* ---------- Menu / catalogue ---------- */
  const toggleAvailability = (id) => setMenu((m) => m.map((d) => (d.id === id ? { ...d, available: !d.available } : d)))
  const setMenuSelection = (ids) => setMenu((m) => m.map((d) => ({ ...d, available: ids.includes(d.id) })))
  const saveDish = (dish) =>
    setMenu((m) => (dish.id ? m.map((d) => (d.id === dish.id ? { ...d, ...dish } : d)) : [...m, { ...dish, id: Math.max(0, ...m.map((d) => d.id)) + 1, available: true }]))
  const deleteDish = (id) => setMenu((m) => m.filter((d) => d.id !== id))

  /* ---------- Réclamations ---------- */
  const createClaim = ({ orderId, motifs, message }) => {
    const claim = {
      id: Date.now(), orderId, customer: `${user.firstName} ${user.lastName || ''}`.trim(),
      when: "Aujourd'hui", motif: motifs.join(', '), message: message || 'Aucun commentaire.', status: 'nouveau', note: '',
    }
    setClaims((c) => [claim, ...c])
  }
  const updateClaim = (id, patch) => setClaims((c) => c.map((x) => (x.id === id ? { ...x, ...patch } : x)))

  /* ---------- Équipe ---------- */
  const addMember = ({ firstName, lastName, email, role }) =>
    setTeam((t) => [...t, { id: Date.now(), name: `${firstName} ${lastName}`.trim(), email, role, presence: 'Invitation envoyée', online: false }])
  const updateMember = (id, patch) => setTeam((t) => t.map((m) => (m.id === id ? { ...m, ...patch } : m)))
  const removeMember = (id) => setTeam((t) => t.filter((m) => m.id !== id))

  /* ---------- Événements / affiches ---------- */
  const saveEvent = (ev) =>
    setEvents((e) => (ev.id ? e.map((x) => (x.id === ev.id ? { ...x, ...ev } : x)) : [...e, { icon: 'tv', cta: 'Je participe', ...ev, id: Date.now() }]))
  const deleteEvent = (id) => setEvents((e) => e.filter((x) => x.id !== id))

  const value = {
    user, loggedOut, login, register, logout, updateUser, changePassword, deleteAccount,
    cart, cartLines, cartCount, cartSubtotal, addToCart, setQty, clearCart, lastAdded, setLastAdded,
    menu, toggleAvailability, setMenuSelection, saveDish, deleteDish,
    orders, placeOrder, setOrderStatus, toggleOrderItem, encaisserEspeces, notifierCommande,
    claims, createClaim, updateClaim,
    team, addMember, updateMember, removeMember,
    events, saveEvent, deleteEvent,
    rules, setRules,
    toasts, toast, cookieConsent, answerCookies,
  }
  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export const useApp = () => useContext(AppContext)
