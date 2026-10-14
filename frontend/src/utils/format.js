const nf = new Intl.NumberFormat('fr-FR')

/** 3500 -> "3 500 FCFA" (espace insécable pour éviter les retours à la ligne) */
export const fcfa = (n) => `${nf.format(Math.round(n)).replace(/[\u202f\u00a0]/g, '\u00a0')}\u00a0FCFA`

export const initials = (name = '') =>
  name
    .replace(/\(.*?\)/g, '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0].toUpperCase())
    .join('')

export const minutesAgo = (ts) => Math.max(0, Math.floor((Date.now() - ts) / 60000))

/** Règles du mot de passe (cahier des charges) : 8 caractères, 1 majuscule, 1 chiffre */
export const isStrongPassword = (pwd = '') => pwd.length >= 8 && /[A-Z]/.test(pwd) && /\d/.test(pwd)

export const isEmail = (v = '') => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)

export const generateReferralCode = () =>
  'ZEDUC-' + Math.random().toString(36).slice(2, 7).toUpperCase()
