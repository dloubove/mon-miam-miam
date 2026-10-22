// Inertia sert React depuis Laravel : utiliser la même origine évite un appel
// cross-origin (localhost vs 127.0.0.1) et donc un blocage CORS en développement.
const API_URL = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`).replace(/\/$/, '')

export function getApiToken() {
  return sessionStorage.getItem('mmm_api_token') || localStorage.getItem('mmm_api_token')
}

export function saveApiSession(token, user, remember = false) {
  const keep = remember ? localStorage : sessionStorage
  const other = remember ? sessionStorage : localStorage
  other.removeItem('mmm_api_token')
  other.removeItem('mmm_api_user')
  keep.setItem('mmm_api_token', token)
  keep.setItem('mmm_api_user', JSON.stringify(user))
}

export function saveApiUser(user) {
  const storage = localStorage.getItem('mmm_api_token') ? localStorage : sessionStorage
  if (user) storage.setItem('mmm_api_user', JSON.stringify(user))
  else storage.removeItem('mmm_api_user')
}

export function clearApiSession() {
  for (const storage of [sessionStorage, localStorage]) {
    storage.removeItem('mmm_api_token')
    storage.removeItem('mmm_api_user')
  }
}

export function readApiUser() {
  try {
    return JSON.parse(sessionStorage.getItem('mmm_api_user') || localStorage.getItem('mmm_api_user') || 'null')
  } catch {
    return null
  }
}

export async function apiRequest(path, options = {}) {
  const headers = { Accept: 'application/json', ...(options.headers || {}) }
  if (options.body && !(options.body instanceof FormData)) headers['Content-Type'] = 'application/json'
  const token = getApiToken()
  if (token) headers.Authorization = `Bearer ${token}`

  let response
  try {
    response = await fetch(`${API_URL}${path}`, {
      ...options,
      headers,
      body: options.body && !(options.body instanceof FormData) ? JSON.stringify(options.body) : options.body,
    })
  } catch {
    throw new Error('Le serveur Laravel ne répond pas. Vérifiez qu’il est lancé sur le port 8000.')
  }

  const payload = response.status === 204 ? null : await response.json().catch(() => ({}))
  if (!response.ok) {
    const validation = payload?.errors ? Object.values(payload.errors).flat()[0] : null
    const error = new Error(validation || payload?.message || `Erreur API (${response.status}).`)
    error.status = response.status
    error.payload = payload
    throw error
  }
  return payload
}

export function mapUtilisateur(user) {
  const [firstName, ...rest] = (user.nom || '').trim().split(/\s+/)
  return {
    id: String(user.id),
    role: user.role,
    firstName: firstName || '',
    lastName: rest.join(' '),
    email: user.email,
    phone: user.telephone,
    place: user.etudiant?.localisation || '',
    points: user.etudiant?.solde_points || 0,
    referralCode: user.etudiant?.code_parrainage || null,
    referred: user.etudiant?.nombre_filleuls || 0,
  }
}

// Les photos livrées avec le projet servent de secours si l'API ne possède pas
// d'image pour un article ou si une ancienne URL /storage/... n'est plus accessible.
const IMAGE_PAR_DEFAUT = [
  [/poisson/i, '/images/poisson.jpg'],
  [/poulet/i, '/images/poulet.jpg'],
  [/boulette/i, '/images/boulettes.jpg'],
  [/ndol/i, '/images/ndole.jpg'],
  [/burger/i, '/images/burger.jpg'],
  [/pizza/i, '/images/pizza.jpg'],
  [/fol[eé]r/i, '/images/folere.jpg'],
  [/cocktail/i, '/images/cocktail.jpg'],
  [/gingembre|orange|jus/i, '/images/jus-orange.jpg'],
]

function imageLocaleDeSecours(nom) {
  const correspondance = IMAGE_PAR_DEFAUT.find(([motif]) => motif.test(nom || ''))
  return correspondance?.[1] || null
}

export function mapArticle(article) {
  const label = article.categorie?.libelle
  const imageLocale = imageLocaleDeSecours(article.nom)
  return {
    id: article.id,
    name: article.nom,
    description: article.description || '',
    category: label === 'boisson' ? 'Boisson' : label === 'snack' ? 'Snack' : label === 'dessert' ? 'Dessert' : 'Plat Chaud',
    categoryId: article.categorie?.id,
    price: article.prix,
    oldPrice: article.prix_normal,
    promo: Boolean(article.promotion),
    image: article.image_url || imageLocale,
    fallbackImage: imageLocale,
    available: article.disponible,
    inDailyMenu: article.dans_menu ?? true,
  }
}

export function mapEvenement(event) {
  const date = new Date(event.date_debut)
  const badge = Number.isNaN(date.getTime()) ? '' : date.toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })
  return {
    id: event.id,
    title: event.titre,
    heading: event.titre,
    text: event.description || '',
    hook: badge,
    badge,
    icon: 'tv',
    cta: 'Je participe',
    active: Boolean(event.actif),
    poster: event.affiche_url || null,
    start: event.date_debut,
    end: event.date_fin,
    points: event.points_recompense || 0,
  }
}

export function mapPromotion(promotion) {
  return {
    id: promotion.id,
    title: promotion.titre,
    description: promotion.description || '',
    rate: Number(promotion.taux),
    start: promotion.date_debut,
    end: promotion.date_fin,
    status: promotion.statut,
    poster: promotion.affiche_url || null,
    articles: (promotion.articles || []).map((article) => article.id),
  }
}
