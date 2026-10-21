import { Download } from 'lucide-react'
import { useEffect, useState } from 'react'
import { PageTitle } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { apiRequest } from '../../services/api.js'
import { fcfa } from '../../utils/format.js'

export default function Dashboard() {
  const { toast } = useApp()
  const [report, setReport] = useState(null)
  const [topClients, setTopClients] = useState([])
  const [periode, setPeriode] = useState('mois')
  const [error, setError] = useState('')
  useEffect(() => {
    let actif = true
    apiRequest('/administration/tableau-de-bord')
      .then((data) => { if (actif) { setReport(data); setError('') } })
      .catch((problem) => { if (actif) setError(problem.message) })
    return () => { actif = false }
  }, [])
  useEffect(() => {
    let actif = true
    apiRequest(`/administration/top-clients?periode=${periode}`)
      .then((rows) => { if (actif) setTopClients(rows || []) })
      .catch((problem) => { if (actif) toast(problem.message, 'error') })
    return () => { actif = false }
  }, [periode, toast])
  const revenue = report?.chiffre_affaires_fcfa || 0
  const orders = report?.commandes_aujourdhui || 0
  const pts = report?.points_utilises || 0
  const offered = report?.reduction_points_fcfa || 0
  const popularDishes = report?.plats_populaires || []

  const download = () => {
    const rows = [['Indicateur', 'Valeur'], ["Chiffre d'affaires du jour (FCFA)", revenue], ['Commandes du jour', orders], ['Points fidélité utilisés', pts], ['FCFA de réduction accordés', offered], [], ['Plat', 'Catégorie', 'Ventes (mois)'], ...popularDishes.map((d) => [d.name, d.category, d.sales]), [], ['Client', 'Commandes', 'Montant (FCFA)'], ...topClients.map((c) => [c.name, c.orders, c.spent])]
    const csv = rows.map((r) => r.join(';')).join('\n')
    const a = document.createElement('a')
    a.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' }))
    a.download = `rapport-zeduc-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(a.href)
    toast('Rapport téléchargé')
  }

  return (
    <>
      <PageTitle title="Les chiffres du jour" subtitle="Vue d'ensemble calculée depuis les commandes et paiements enregistrés." action={<button className="btn-dark" onClick={download} disabled={!report}><Download size={14} /> Télécharger le rapport</button>} />
      {error && <p role="alert" className="mb-5 rounded-lg border border-danger/40 bg-danger/10 p-4 text-sm text-danger">Impossible de charger les statistiques : {error}</p>}
      <div className="grid gap-5 md:grid-cols-3">
        <div className="panel p-6">
          <p className="kicker text-muted">Chiffre d'affaires</p>
          <p className="mt-3 text-3xl font-bold">{report ? fcfa(revenue) : '—'}</p>
        </div>
        <div className="panel p-6">
          <p className="kicker text-muted">Commandes validées</p>
          <p className="mt-3 text-3xl font-bold">{report ? orders : '—'} <span className="ml-1 text-xs font-normal text-muted">Aujourd'hui</span></p>
        </div>
        <div className="rounded-xl border border-gold/40 bg-[#15130e] p-6">
          <p className="kicker text-gold">Points fidélité utilisés</p>
          <p className="mt-3 text-3xl font-bold text-gold">{report ? pts : '—'} pts <span className="ml-1 text-xs font-normal text-muted">Soit {fcfa(offered)} accordés</span></p>
        </div>
      </div>
      <section className="panel mt-6 overflow-hidden">
        <h2 className="px-6 py-6 font-bold">Les plats qui marchent le mieux</h2>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[520px] text-sm">
            <thead><tr className="border-y border-line bg-surface-2 text-muted"><th className="px-6 py-4 text-left font-normal">Plat</th><th className="px-6 py-4 text-left font-normal">Catégorie</th><th className="px-6 py-4 text-right font-normal">Ventes (Mois)</th></tr></thead>
            <tbody>
              {popularDishes.map((d) => (
                <tr key={d.name} className="border-b border-line last:border-0"><td className="px-6 py-4">{d.name}</td><td className="px-6 py-4 text-muted">{d.category}</td><td className="px-6 py-4 text-right font-bold">{d.sales}</td></tr>
              ))}
              {report && !popularDishes.length && <tr><td colSpan="3" className="px-6 py-8 text-center text-muted">Aucune vente comptabilisée ce mois-ci.</td></tr>}
            </tbody>
          </table>
        </div>
      </section>
      <section className="panel mt-6 overflow-hidden">
        <div className="flex flex-wrap items-center justify-between gap-4 px-6 py-5">
          <h2 className="font-bold">Top 10 des clients</h2>
          <select className="field !w-auto" value={periode} onChange={(event) => setPeriode(event.target.value)} aria-label="Période du classement">
            <option value="jour">Aujourd'hui</option><option value="semaine">Cette semaine</option><option value="mois">Ce mois</option>
          </select>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[520px] text-sm">
            <thead><tr className="border-y border-line bg-surface-2 text-muted"><th className="px-6 py-4 text-left font-normal">Rang</th><th className="px-6 py-4 text-left font-normal">Étudiant</th><th className="px-6 py-4 text-right font-normal">Commandes</th><th className="px-6 py-4 text-right font-normal">Achats</th></tr></thead>
            <tbody>
              {topClients.map((client, index) => <tr key={client.id} className="border-b border-line last:border-0"><td className="px-6 py-4">{index + 1}</td><td className="px-6 py-4">{client.name}</td><td className="px-6 py-4 text-right">{client.orders}</td><td className="px-6 py-4 text-right text-gold">{fcfa(client.spent)}</td></tr>)}
              {!topClients.length && <tr><td colSpan="4" className="px-6 py-8 text-center text-muted">Aucune commande pour cette période.</td></tr>}
            </tbody>
          </table>
        </div>
      </section>
    </>
  )
}
