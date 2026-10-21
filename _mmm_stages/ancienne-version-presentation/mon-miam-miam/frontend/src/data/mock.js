// Référentiels d'interface non personnels. Les menus, comptes, commandes,
// événements, jeux, points, statistiques et équipe sont chargés depuis l'API.
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

export const CATEGORIES = ['Plat Chaud', 'Snack', 'Boisson', 'Dessert']

// Valeurs initiales d'affichage, remplacées au chargement par /api/regles-fidelite.
export const INITIAL_RULES = { fcfaPerBlock: 1000, pointsPerBlock: 1, pointsForDiscount: 15, discountFcfa: 1000 }
