// Données de démonstration (remplacées par l'API Laravel plus tard).
// Les clés de rôle reprennent celles de la base : etudiant, employe, gerant, administrateur.

export const ROLES = {
  etudiant: 'etudiant',
  employe: 'employe',
  gerant: 'gerant',
  administrateur: 'administrateur',
}

export const HOME_BY_ROLE = {
  etudiant: '/mon-espace',
  employe: '/staff',
  gerant: '/direction',
  administrateur: '/direction',
}

export const DELIVERY_PLACES = [
  'Campus UCAC-ICAM (Yansoki)',
  'Résidence La Terrasse',
  'Résidence Les Mangueries',
  'Cité universitaire',
  'Autre lieu (précisez à la commande)',
]

export const CATEGORIES = ['Plat Chaud', 'Snack', 'Boisson']

export const DEMO_USERS = [
  {
    id: 'u-etu', role: 'etudiant', firstName: 'Dan', lastName: 'Loubove',
    email: 'etudiant@ucac-icam.com', phone: '699 12 34 56',
    place: 'Campus UCAC-ICAM (Yansoki)', residence: 'Résidence Les Mangueries',
    points: 45, referralCode: 'ZEDUC-A98X2', referred: 2,
  },
  {
    id: 'u-emp', role: 'employe', firstName: 'Paul', lastName: 'Zogo',
    email: 'paul@zeduc.com', phone: '699 12 34 56', title: 'Cuisinier', shift: 'Service du midi',
  },
  {
    id: 'u-ger', role: 'gerant', firstName: 'Marc', lastName: 'Dupont',
    email: 'marc@zeduc.com', phone: '699 45 87 12',
  },
  {
    id: 'u-adm', role: 'administrateur', firstName: 'Jean', lastName: 'Directeur',
    email: 'admin@zeduc.com', phone: '+237 600 00 00 00',
  },
]

export const INITIAL_MENU = [
  { id: 1, name: 'Poisson Braisé', category: 'Plat Chaud', price: 3500, image: '/images/poisson.jpg', available: true, description: 'Poisson braisé, bâtons de manioc et sauce pimentée.' },
  { id: 2, name: 'Poulet Braisé', category: 'Plat Chaud', price: 2500, image: '/images/poulet.jpg', available: true, description: 'Poulet braisé mariné aux épices maison.' },
  { id: 3, name: 'Boulettes de viande', category: 'Plat Chaud', price: 1500, image: '/images/boulettes.jpg', available: false, description: 'Boulettes de viande en sauce crémeuse.' },
  { id: 4, name: 'Foléré', category: 'Boisson', price: 500, oldPrice: 1250, promo: true, image: '/images/folere.jpg', available: true, description: "Jus d'hibiscus frais, citron vert et épices." },
]

const now = Date.now()
export const INITIAL_ORDERS = [
  { id: 485, userId: 'u-x1', customer: 'Marc Dupont', mode: 'livraison', status: 'a_preparer', createdAt: now - 2 * 60000, note: 'Beaucoup de piment SVP !', items: [{ name: 'Poulet Braisé', qty: 2, done: false }, { name: 'Foléré', qty: 1, done: false }] },
  { id: 486, userId: 'u-x2', customer: 'Sophie L.', mode: 'sur_place', status: 'a_preparer', createdAt: now, note: '', items: [{ name: 'Poisson braisé', qty: 1, done: false }] },
  { id: 482, userId: 'u-etu', customer: 'Dan Loubove', mode: 'livraison', status: 'en_cuisine', createdAt: now - 12 * 60000, note: '', items: [{ name: 'Poulet Braisé', qty: 1, done: true }, { name: 'Boulette de viande', qty: 1, done: false }] },
  { id: 480, userId: 'u-x3', customer: 'Arthur T.', mode: 'sur_place', status: 'pret', createdAt: now - 25 * 60000, note: '', items: [{ name: 'Poisson Braisé', qty: 2, done: false }] },
]

export const PAST_ORDERS = [
  { id: 470, date: 'Hier, 13h40', dishes: 'Poulet Braisé (x2)', total: 3000, status: 'Livrée', claim: 'none' },
  { id: 462, date: '12 Sept, 19h15', dishes: 'Poisson + Boisson', total: 4000, status: 'Servie sur place', claim: 'none' },
  { id: 450, date: '08 Sept, 12h30', dishes: 'Poulet Braisé', total: 2500, status: 'Livrée', claim: 'resolved' },
]

export const INITIAL_CLAIMS = [
  { id: 1, orderId: 481, customer: 'Marc Dupont', when: "Aujourd'hui, 13h40", motif: 'Le plat est arrivé froid', message: "Salut l'équipe. J'ai commandé 2 burgers pour le campus Yansoki. Le livreur a mis presque 1h et c'était totalement froid. Impossible de manger les frites.", status: 'nouveau', note: '' },
  { id: 2, orderId: 476, customer: 'Léa M.', when: "Aujourd'hui, 12h10", motif: 'Mauvaise commande', message: "J'ai reçu du poisson braisé alors que j'avais commandé du poulet.", status: 'nouveau', note: '' },
  { id: 3, orderId: 478, customer: 'Sophie L.', when: 'Hier, 19h15', motif: 'Il manque un truc', message: "Je n'ai pas reçu la boisson (Cocktail Frais) avec ma Pizza.", status: 'en_cours', note: 'Paul a demandé au livreur de vérifier.' },
  { id: 4, orderId: 450, customer: 'Arthur T.', when: '08 Sept, 12h30', motif: 'Autre chose', message: "Erreur sur la sauce, j'avais demandé sans piment.", status: 'classe', note: 'Résolu : 10 points de fidélité offerts en compensation.' },
]

export const INITIAL_TEAM = [
  { id: 1, name: 'Marc Gérant', email: 'marc@zeduc.com', role: 'gerant', presence: 'Connecté hier', online: false },
  { id: 2, name: 'Paul (Cuisine)', email: 'paul@zeduc.com', role: 'employe', presence: 'En ligne', online: true },
]

export const INITIAL_EVENTS = [
  { id: 1, title: 'Match Champions League', hook: 'Soirée', badge: 'Ce soir - 20h00', icon: 'tv', heading: 'Soirée Match sur grand écran', text: "On diffuse le match en direct. Installez-vous, prenez à boire, l'ambiance est garantie.", cta: 'Je réserve ma table', active: true, poster: null },
  { id: 2, title: 'Tournoi de Billard', hook: 'Vendredi', badge: 'Vendredi - 16h00', icon: 'billard', heading: 'Tournoi de Billard', text: 'Affrontez vos potes. Le gagnant repart avec des points bonus et une conso offerte.', cta: "M'inscrire au tournoi", active: true, poster: null },
]

export const INITIAL_RULES = { fcfaPerBlock: 1000, pointsPerBlock: 1, pointsForDiscount: 15, discountFcfa: 1000 }

export const LEADERBOARD = [
  { rank: 1, name: 'Dan L', orders: 24, points: 3450 },
  { rank: 2, name: 'Nathan A', orders: 19, points: 2800 },
  { rank: 3, name: 'Loise K', orders: 15, points: 2100 },
]

export const TOP_DISHES = [
  { name: 'Poulet Braisé', category: 'Plat Chaud', sales: 342 },
  { name: 'Poisson Braisé', category: 'Snack', sales: 289 },
  { name: 'Foléré', category: 'Boisson', sales: 156 },
]

export const WHEEL_PRIZES = ['Perdu', '500 FCFA', 'Boisson', '100 FCFA', '+1 Point', '50 FCFA']

export const GUESS_QUESTION = {
  image: '/images/poulet.jpg',
  options: ['Poisson Braisé', 'Poulet Braisé', 'Poulet Pané', 'Rôti de Poulet'],
  answer: 1,
}
