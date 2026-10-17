import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import {
  DEMO_USERS, INITIAL_CLAIMS, INITIAL_EVENTS, INITIAL_MENU, INITIAL_ORDERS,
  INITIAL_RULES, INITIAL_TEAM,
} from '../data/mock.js'
import { generateReferralCode } from '../utils/format.js'

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

/**
 * Store global de l'application (état local de démonstration).
 * Chaque bloc est isolé pour être remplacé facilement par des appels à l'API Laravel
 * (VITE_API_URL) : auth, menu, commandes, réclamations, équipe, événements, règles.
 */
export function AppProvider({ children }) {
  const [user, setUser] = useState(() => read('mmm_user', null))
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
  const [registered, setRegistered] = useState(() => read('mmm_registered', []))

  useEffect(() => write('mmm_user', user), [user])
  useEffect(() => write('mmm_cart', cart), [cart])
  useEffect(() => write('mmm_registered', registered), [registered])

  /* ---------- Toasts ---------- */
  const toast = useCallback((message, type = 'success') => {
    const id = Date.now() + Math.random()
    setToasts((t) => [...t, { id, message, type }])
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 3200)
  }, [])

  /* ---------- Cookies (RGPD) ---------- */
  const answerCookies = (accepted) => {
    setCookieConsent(accepted ? 'accepted' : 'refused')
    write('mmm_cookies', accepted ? 'accepted' : 'refused')
  }

  /* ---------- Authentification ---------- */
  const login = (email, password) => {
    const e = email.trim().toLowerCase()
    const found = [...DEMO_USERS, ...registered].find((u) => u.email.toLowerCase() === e)
    if (found) {
      if (found.password && found.password !== password) return { ok: false, error: 'Email ou mot de passe incorrect.' }
      setUser(found)
      setLoggedOut(false)
      return { ok: true, user: found }
    }
    return { ok: false, error: 'Email ou mot de passe incorrect.' }
  }

  const register = (data) => {
    const e = data.email.trim().toLowerCase()
    if ([...DEMO_USERS, ...registered].some((u) => u.email.toLowerCase() === e)) {
      return { ok: false, error: 'Cet email est déjà utilisé.' }
    }
    const [firstName, ...rest] = data.name.trim().split(' ')
    const created = {
      id: 'u-' + Date.now(), role: 'etudiant', firstName, lastName: rest.join(' '),
      email: e, phone: data.phone, place: data.place, residence: '',
      password: data.password, points: 0, referralCode: generateReferralCode(), referred: 0,
      referredBy: data.sponsorCode || null,
    }
    setRegistered((r) => [...r, created])
    setUser(created)
    setLoggedOut(false)
    return { ok: true, user: created }
  }

  const logout = () => {
    setLoggedOut(true)
    setUser(null)
    setCart([])
  }

  const updateUser = (patch) => {
    setUser((u) => ({ ...u, ...patch }))
    setRegistered((r) => r.map((x) => (x.id === user?.id ? { ...x, ...patch } : x)))
  }

  const deleteAccount = () => {
    setRegistered((r) => r.filter((x) => x.id !== user?.id))
    logout()
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
  const placeOrder = ({ mode, arrival, note, usePoints, payment }) => {
    const discountUnits = usePoints ? Math.min(Math.floor((user?.points || 0) / rules.pointsForDiscount), Math.floor(cartSubtotal / rules.discountFcfa)) : 0
    const usedPoints = discountUnits * rules.pointsForDiscount
    const discount = discountUnits * rules.discountFcfa
    const total = cartSubtotal - discount
    const earned = Math.floor(total / rules.fcfaPerBlock) * rules.pointsPerBlock
    const id = Math.max(0, ...orders.map((o) => o.id)) + 1
    const order = {
      id, userId: user.id, customer: `${user.firstName} ${user.lastName || ''}`.trim(),
      mode, arrival, note, payment, total, discount, status: 'a_preparer', createdAt: Date.now(),
      items: cartLines.map((l) => ({ name: l.dish.name, qty: l.qty, done: false })),
    }
    setOrders((o) => [...o, order])
    updateUser({ points: (user.points || 0) - usedPoints + earned })
    clearCart()
    return order
  }
  const setOrderStatus = (id, status) => setOrders((o) => o.map((x) => (x.id === id ? { ...x, status } : x)))
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
    user, loggedOut, login, register, logout, updateUser, deleteAccount,
    cart, cartLines, cartCount, cartSubtotal, addToCart, setQty, clearCart, lastAdded, setLastAdded,
    menu, toggleAvailability, setMenuSelection, saveDish, deleteDish,
    orders, placeOrder, setOrderStatus, toggleOrderItem,
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
