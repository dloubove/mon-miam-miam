import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import {
  INITIAL_RULES,
} from '../data/mock.js'
import { apiRequest, clearApiSession, getApiToken, mapArticle, mapEvenement, mapPromotion, mapUtilisateur, readApiUser, saveApiSession, saveApiUser } from '../services/api.js'

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
  client: order.client,
  mode: order.mode_service,
  arrival: order.heure_arrivee,
  address: order.adresse_livraison,
  note: order.commentaire,
  payment: order.paiement?.mode_paiement,
  paymentStatus: order.paiement?.statut_paiement,
  total: order.total,
  status,
  createdAt: Date.parse(order.date_commande),
  items: (order.lignes || []).map((line) => ({ name: line.nom, qty: line.quantite, done: false })),
})

const mapStudentOrder = (order, userId) => ({
  id: order.id,
  userId,
  mode: order.mode_service,
  arrival: order.heure_arrivee,
  address: order.adresse_livraison,
  note: order.commentaire,
  total: order.total,
  status: ({ panier: 'a_preparer', en_attente: 'a_preparer', en_preparation: 'en_cuisine', prete: 'pret', servie: 'terminee', livree: 'terminee', annulee: 'annulee' })[order.statut] || order.statut,
  createdAt: Date.parse(order.date_commande),
  items: (order.lignes || []).map((line) => ({ name: line.nom, qty: line.quantite, done: false })),
})

const mapClaim = (claim) => ({
  id: claim.id_reclamation,
  orderId: claim.id_commande,
  customer: claim.etudiant?.utilisateur?.nom || 'Vous',
  when: claim.date_reclamation ? new Date(claim.date_reclamation).toLocaleString('fr-FR') : '',
  motif: claim.objet,
  message: claim.description_reclamation,
  status: ({ ouverte: 'nouveau', repondue: 'en_cours', validee: 'classe', rejetee: 'classe' })[claim.statut_reclamation] || 'nouveau',
  response: claim.reponse_proposee,
  decision: claim.decision_gerant,
  note: claim.motif_decision || claim.reponse_proposee || '',
})

/**
 * État d'interface partagé. Les données métier sont chargées et modifiées via l'API Laravel ;
 * seuls le panier temporaire et le consentement aux cookies restent dans le navigateur.
 */
export function AppProvider({ children }) {
  const [user, setUser] = useState(() => readApiUser())
  const [cart, setCart] = useState(() => read('mmm_cart', []))
  const [cookieConsent, setCookieConsent] = useState(() => read('mmm_cookies', null))
  const [menu, setMenu] = useState([])
  const [orders, setOrders] = useState([])
  const [claims, setClaims] = useState([])
  const [pointsHistory, setPointsHistory] = useState([])
  const [referrals, setReferrals] = useState([])
  const [team, setTeam] = useState([])
  const [events, setEvents] = useState([])
  const [promotions, setPromotions] = useState([])
  const [rules, setRules] = useState(INITIAL_RULES)
  const [toasts, setToasts] = useState([])
  const [lastAdded, setLastAdded] = useState(null)
  const [loggedOut, setLoggedOut] = useState(false) // déconnexion volontaire (évite la redirection vers /connexion)

  /* ---------- Toasts ---------- */
  const toast = useCallback((message, type = 'success') => {
    const id = Date.now() + Math.random()
    setToasts((t) => [...t, { id, message, type }])
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 3200)
  }, [])

  useEffect(() => {
    if (getApiToken()) saveApiUser(user)
    else localStorage.removeItem('mmm_user')
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
      if (actif) setMenu(articles.map(mapArticle))
    }).catch(() => {})
    return () => { actif = false }
  }, [])

  useEffect(() => {
    let actif = true
    apiRequest('/regles-fidelite').then((params) => {
      if (actif) setRules((current) => ({ ...current,
        fcfaPerBlock: 1000,
        pointsPerBlock: params.points_par_1000_fcfa ?? current.pointsPerBlock,
        pointsForDiscount: params.points_par_tranche_reduction ?? current.pointsForDiscount,
        discountFcfa: params.fcfa_pour_15_points ?? current.discountFcfa,
        minPoints: params.seuil_min_points_utilisation ?? current.minPoints,
        expiryMonths: params.duree_validite_points_mois ?? current.expiryMonths,
      }))
    }).catch((error) => { if (actif) toast(error.message, 'error') })
    apiRequest('/evenements').then((rows) => { if (actif) setEvents((rows || []).map(mapEvenement)) }).catch(() => {})
    return () => { actif = false }
  }, [toast])

  // Le catalogue complet et le menu quotidien sont différents : le catalogue sert à
  // créer/modifier des plats, tandis que le menu public ne contient que la sélection du jour.
  useEffect(() => {
    let actif = true
    if (!getApiToken()) return () => { actif = false }
    const role = user?.role
    const url = ['employe', 'gerant', 'administrateur'].includes(role)
      ? '/staff/menu'
      : role === 'etudiant' ? '/menu-du-jour' : null
    if (!url) return () => { actif = false }
    apiRequest(url).then((response) => {
      const rows = response?.articles || response?.data || []
      if (actif) setMenu(rows.map(mapArticle))
    }).catch((error) => { if (actif) toast(error.message, 'error') })
    return () => { actif = false }
  }, [user?.role, toast])

  useEffect(() => {
    if (!getApiToken() || !['gerant', 'administrateur'].includes(user?.role)) return
    let actif = true
    Promise.all([apiRequest('/gestion/evenements'), apiRequest('/gestion/promotions')]).then(([rows, promoRows]) => {
      if (actif) {
        setEvents((rows || []).map(mapEvenement))
        setPromotions((promoRows?.data || []).map(mapPromotion))
      }
    }).catch((error) => { if (actif) toast(error.message, 'error') })
    return () => { actif = false }
  }, [user?.role, toast])

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
    let actif = true
    const premierChargement = window.setTimeout(() => {
      chargerCommandesPersonnel().catch((error) => { if (actif) toast(error.message, 'error') })
    }, 0)
    const timer = window.setInterval(() => {
      if (document.visibilityState === 'visible') chargerCommandesPersonnel().catch(() => {})
    }, 15000)
    return () => { actif = false; window.clearTimeout(premierChargement); window.clearInterval(timer) }
  }, [user?.role, chargerCommandesPersonnel, toast])

  useEffect(() => {
    if (!getApiToken() || user?.role !== 'etudiant') return
    let actif = true
    const refresh = () => apiRequest('/commandes?par_page=50').then((response) => {
      if (actif) setOrders((response.data || []).map((order) => mapStudentOrder(order, user.id)))
    })
    const initial = window.setTimeout(() => refresh().catch((error) => { if (actif) toast(error.message, 'error') }), 0)
    const timer = window.setInterval(() => {
      if (document.visibilityState === 'visible') refresh().catch(() => {})
    }, 15000)
    return () => { actif = false; window.clearTimeout(initial); window.clearInterval(timer) }
  }, [user?.id, user?.points, user?.role, toast])

  useEffect(() => {
    if (!getApiToken() || user?.role !== 'etudiant') return
    let actif = true
    Promise.all([apiRequest('/fidelite/historique'), apiRequest('/parrainages')])
      .then(([history, referred]) => {
        if (!actif) return
        setPointsHistory(history.data || [])
        setReferrals(referred || [])
      })
      .catch((error) => { if (actif) toast(error.message, 'error') })
    return () => { actif = false }
  }, [user?.id, user?.points, user?.role, toast])

  useEffect(() => {
    if (!getApiToken() || !['gerant', 'administrateur'].includes(user?.role)) return
    let actif = true
    apiRequest('/gestion/equipe').then((rows) => {
      if (actif) setTeam((rows || []).map((person) => ({
        id: person.id, name: person.name, email: person.email, phone: person.telephone,
        role: person.role, presence: person.status === 'actif' ? 'Compte actif' : 'Compte désactivé', online: false,
      })))
    }).catch((error) => { if (actif) toast(error.message, 'error') })
    return () => { actif = false }
  }, [user?.role, toast])

  useEffect(() => {
    if (!getApiToken() || user?.role !== 'administrateur') return
    let actif = true
    apiRequest('/administration/parametres-fidelite').then((rows) => {
      if (!actif) return
      const params = Object.fromEntries((rows || []).map((row) => [row.cle_parametre, Number(row.valeur_parametre)]))
      setRules((current) => ({
        ...current,
        fcfaPerBlock: 1000,
        pointsPerBlock: params.points_par_1000_fcfa ?? current.pointsPerBlock,
        pointsForDiscount: params.points_par_tranche_reduction ?? current.pointsForDiscount,
        discountFcfa: params.fcfa_pour_15_points ?? current.discountFcfa,
        minPoints: params.seuil_min_points_utilisation ?? current.minPoints,
        expiryMonths: params.duree_validite_points_mois ?? current.expiryMonths,
      }))
    }).catch((error) => { if (actif) toast(error.message, 'error') })
    return () => { actif = false }
  }, [user?.role, toast])

  useEffect(() => {
    if (!getApiToken()) return
    const role = user?.role
    const route = role === 'etudiant' ? '/reclamations' : ['employe', 'gerant', 'administrateur'].includes(role) ? '/staff/reclamations' : null
    if (!route) return
    let actif = true
    apiRequest(route).then((response) => {
      if (actif) setClaims((response.data || response || []).map(mapClaim))
    }).catch((error) => { if (actif) toast(error.message, 'error') })
    return () => { actif = false }
  }, [user?.role, toast])

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
        nom: data.name.trim(),
        email: data.email.trim().toLowerCase(),
        telephone: data.phone,
        localisation: data.place,
        mot_de_passe: data.password,
        code_parrain: data.sponsorCode || null,
      },
    })

    // Même traitement qu'après une connexion classique.
    const compte = mapUtilisateur(reponse.utilisateur)

    // Conserve la session pendant la navigation.
    saveApiSession(reponse.jeton, compte, false)
    setUser(compte)
    setLoggedOut(false)

    return {
      ok: true,
      user: compte,
      message: 'Compte créé avec succès ! Bienvenue sur Mon Miam Miam.',
    }
  } catch (error) {
    return {
      ok: false,
      error: error.message,
    }
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

  const refreshProfile = async () => {
    try {
      const profile = await apiRequest('/moi')
      const account = mapUtilisateur(profile)
      setUser(account)
      return account
    } catch (error) { toast(error.message, 'error'); return null }
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
  const placeOrder = async ({ mode, arrival, address, note, usePoints, payment, telephone }) => {
    const commande = await apiRequest('/commandes', {
      method: 'POST',
      body: {
        lignes: cartLines.map((line) => ({ article_id: line.id, quantite: line.qty })),
        mode_service: mode,
        heure_arrivee: mode === 'sur_place' ? arrival : null,
        adresse_livraison: mode === 'livraison' ? address?.trim() || user?.place : null,
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
  const toggleAvailability = async (id) => {
    const article = menu.find((item) => item.id === id)
    if (!article) return { ok: false, error: 'Article introuvable.' }
    try {
      const updated = await apiRequest(`/staff/articles/${id}/disponibilite`, {
        method: 'PATCH', body: { disponible: !article.available },
      })
      setMenu((items) => items.map((item) => item.id === id ? mapArticle(updated) : item))
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const setMenuSelection = async (ids) => {
    try {
      const response = await apiRequest('/staff/menu-du-jour', { method: 'PUT', body: { articles: ids } })
      setMenu((response.articles || []).map(mapArticle))
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const saveDish = async (dish) => {
    try {
      const categories = await apiRequest('/categories')
      const wantedCategory =
      ({ 'Plat Chaud': 'plat' })[dish.category] || dish.category.toLowerCase()

      const categoryRows = Array.isArray(categories)
      ? categories
      : (categories.data || [])

      const category = categoryRows.find(
      (item) => item.libelle?.toLowerCase() === wantedCategory
      )
      if (!category) throw new Error(`Catégorie « ${dish.category} » introuvable dans la base.`)
      const body = new FormData()
      body.append('nom', dish.name.trim())
      body.append('description', dish.description || '')
      body.append('prix', String(dish.price))
      body.append('categorie_id', String(category.id))
      if (dish.imageFile) body.append('image', dish.imageFile)
      if (dish.id) body.append('_method', 'PATCH')
      const response = await apiRequest(dish.id ? `/gestion/articles/${dish.id}` : '/gestion/articles', { method: 'POST', body })
      const article = mapArticle(response.data || response)
      setMenu((items) => dish.id ? items.map((item) => item.id === dish.id ? article : item) : [...items, article])
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const deleteDish = async (id) => {
    try {
      await apiRequest(`/gestion/articles/${id}`, { method: 'DELETE' })
      setMenu((items) => items.filter((item) => item.id !== id))
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }

  const setRulesFromApi = async (nextRules) => {
    try {
      await apiRequest('/administration/parametres-fidelite', {
        method: 'PUT',
        body: { parametres: {
          points_par_1000_fcfa: nextRules.pointsPerBlock,
          points_par_tranche_reduction: nextRules.pointsForDiscount,
          fcfa_pour_15_points: nextRules.discountFcfa,
          seuil_min_points_utilisation: nextRules.pointsForDiscount,
        } },
      })
      setRules((current) => ({ ...current, ...nextRules, fcfaPerBlock: 1000, minPoints: nextRules.pointsForDiscount }))
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }

  /* ---------- Réclamations ---------- */
  const createClaim = async ({ orderId, motifs, message }) => {
    try {
      const result = await apiRequest('/reclamations', { method: 'POST', body: {
        commande_id: orderId, objet: motifs.join(', '), description: message?.trim() || motifs.join(', '),
      } })
      setClaims((current) => [mapClaim(result), ...current])
      return { ok: true }
    } catch (error) { return { ok: false, error: error.message } }
  }
  const answerClaim = async (id, reponse) => {
    try {
      const result = await apiRequest(`/staff/reclamations/${id}/reponse`, { method: 'POST', body: { reponse } })
      setClaims((current) => current.map((claim) => claim.id === id ? mapClaim(result) : claim))
      return { ok: true }
    } catch (error) { return { ok: false, error: error.message } }
  }
  const decideClaim = async (id, decision, motif, points = 0) => {
    try {
      const result = await apiRequest(`/gestion/reclamations/${id}/decision`, { method: 'POST', body: { decision, motif, points } })
      setClaims((current) => current.map((claim) => claim.id === id ? mapClaim(result) : claim))
      return { ok: true }
    } catch (error) { return { ok: false, error: error.message } }
  }

  /* ---------- Équipe ---------- */
  const addMember = async ({ firstName, lastName, email, phone, role }) => {
    try {
      await apiRequest('/gestion/equipe', { method: 'POST', body: { nom: `${firstName} ${lastName}`.trim(), email, telephone: phone, role } })
      const rows = await apiRequest('/gestion/equipe')
      setTeam((rows || []).map((person) => ({ id: person.id, name: person.name, email: person.email, phone: person.telephone, role: person.role, presence: person.status === 'actif' ? 'Compte actif' : 'Compte désactivé', online: false })))
      return { ok: true }
    } catch (error) { return { ok: false, error: error.message } }
  }
  const updateMember = async (id, patch) => {
    try {
      const data = {}
      if (patch.name !== undefined) data.nom = patch.name
      if (patch.email !== undefined) data.email = patch.email
      if (patch.phone !== undefined) data.telephone = patch.phone
      if (patch.role !== undefined) data.role = patch.role
      await apiRequest(`/gestion/equipe/${id}`, { method: 'PATCH', body: data })
      const rows = await apiRequest('/gestion/equipe')
      setTeam((rows || []).map((person) => ({ id: person.id, name: person.name, email: person.email, phone: person.telephone, role: person.role, presence: person.status === 'actif' ? 'Compte actif' : 'Compte désactivé', online: false })))
      return { ok: true }
    } catch (error) { return { ok: false, error: error.message } }
  }
  const removeMember = async (id) => {
    try {
      await apiRequest(`/gestion/equipe/${id}`, { method: 'DELETE' })
      setTeam((members) => members.map((member) => member.id === id ? { ...member, presence: 'Compte désactivé', status: 'inactif' } : member))
      return { ok: true }
    } catch (error) { return { ok: false, error: error.message } }
  }

  /* ---------- Événements / affiches ---------- */
  const saveEvent = async (event) => {
    try {
      const body = new FormData()
      body.append('titre', event.title.trim())
      body.append('description', event.text || event.badge || '')
      body.append('date_debut', event.start)
      body.append('date_fin', event.end)
      body.append('points_recompense', String(event.points || 0))
      if (event.posterFile) body.append('affiche', event.posterFile)
      if (event.id) body.append('_method', 'PATCH')
      const response = await apiRequest(event.id ? `/gestion/evenements/${event.id}` : '/gestion/evenements', { method: 'POST', body })
      const mapped = mapEvenement(response)
      setEvents((current) => event.id ? current.map((item) => item.id === event.id ? mapped : item) : [mapped, ...current])
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const deleteEvent = async (id) => {
    try {
      await apiRequest(`/gestion/evenements/${id}`, { method: 'DELETE' })
      setEvents((current) => current.filter((event) => event.id !== id))
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const savePromotion = async (promotion) => {
    try {
      const body = new FormData()
      body.append('titre', promotion.title.trim())
      body.append('description', promotion.description || '')
      body.append('taux', String(promotion.rate))
      body.append('date_debut', promotion.start)
      body.append('date_fin', promotion.end)
      ;(promotion.articles || []).forEach((id) => body.append('articles[]', String(id)))
      if (promotion.posterFile) body.append('affiche', promotion.posterFile)
      if (promotion.id) body.append('_method', 'PATCH')
      const response = await apiRequest(promotion.id ? `/gestion/promotions/${promotion.id}` : '/gestion/promotions', { method: 'POST', body })
      const mapped = mapPromotion(response.data || response)
      setPromotions((current) => promotion.id ? current.map((item) => item.id === promotion.id ? mapped : item) : [mapped, ...current])
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const deletePromotion = async (id) => {
    try {
      await apiRequest(`/gestion/promotions/${id}`, { method: 'DELETE' })
      setPromotions((current) => current.filter((promotion) => promotion.id !== id))
      return { ok: true }
    } catch (error) { toast(error.message, 'error'); return { ok: false, error: error.message } }
  }
  const participateEvent = async (id) => {
    try { return await apiRequest(`/evenements/${id}/participer`, { method: 'POST' }) }
    catch (error) { return { error: error.message } }
  }

  const value = {
    user, loggedOut, login, register, logout, updateUser, refreshProfile, changePassword, deleteAccount,
    cart, cartLines, cartCount, cartSubtotal, addToCart, setQty, clearCart, lastAdded, setLastAdded,
    menu, toggleAvailability, setMenuSelection, saveDish, deleteDish,
    orders, placeOrder, setOrderStatus, toggleOrderItem, encaisserEspeces, notifierCommande,
    claims, createClaim, answerClaim, decideClaim, pointsHistory, referrals,
    team, addMember, updateMember, removeMember,
    events, saveEvent, deleteEvent, participateEvent, promotions, savePromotion, deletePromotion,
    rules, setRules: setRulesFromApi,
    toasts, toast, cookieConsent, answerCookies,
  }
  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export const useApp = () => useContext(AppContext)
