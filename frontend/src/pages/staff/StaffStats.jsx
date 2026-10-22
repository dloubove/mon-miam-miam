import { useEffect, useState } from 'react'
import { Activity, CalendarDays, CircleDollarSign, ShoppingBag } from 'lucide-react'
import { apiRequest } from '../../services/api.js'
import { fcfa } from '../../utils/format.js'

export default function StaffStats() {
  const [stats, setStats] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  useEffect(() => {
    let active = true
    const load = async () => {
      try { const data = await apiRequest('/staff/statistiques'); if (active) { setStats(data); setError('') } }
      catch (e) { if (active) setError(e.message || 'Impossible de charger les statistiques.') }
      finally { if (active) setLoading(false) }
    }
    load()
    const timer = window.setInterval(load, 60000)
    return () => { active = false; window.clearInterval(timer) }
  }, [])
  const cards = [
    { label: 'Commandes payées cette semaine', value: stats?.commandes_payees ?? '—', icon: ShoppingBag },
    { label: 'Chiffre d’affaires hebdomadaire', value: stats ? fcfa(stats.chiffre_affaires_fcfa || 0) : '—', icon: CircleDollarSign },
    { label: 'Début de semaine', value: stats?.semaine_debut || '—', icon: CalendarDays },
  ]
  return <section className="mx-auto max-w-[1180px] px-5 py-10">
    <div className="mb-8 flex items-center gap-3"><Activity className="text-gold" size={26}/><div><h1 className="text-3xl font-bold">Statistiques hebdomadaires</h1><p className="mt-2 text-sm text-muted">Données calculées à partir des paiements enregistrés. Actualisation chaque minute.</p></div></div>
    {error && <p role="alert" className="mb-5 rounded-lg border border-danger/40 bg-danger/10 p-4 text-sm text-danger">{error}</p>}
    {loading && <p className="mb-5 text-sm text-muted">Chargement des statistiques…</p>}
    <div className="grid gap-5 md:grid-cols-3">{cards.map(({label,value,icon:Icon}) => <article key={label} className="rounded-xl border border-line bg-surface p-6"><Icon size={20} className="text-gold"/><p className="mt-5 text-sm text-muted">{label}</p><p className="mt-2 text-2xl font-bold">{value}</p></article>)}</div>
  </section>
}
