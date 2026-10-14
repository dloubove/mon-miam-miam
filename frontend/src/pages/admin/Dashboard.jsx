import { ArrowUp, Download } from 'lucide-react'
import { PageTitle } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { TOP_DISHES } from '../../data/mock.js'
import { fcfa } from '../../utils/format.js'

export default function Dashboard() {
  const { rules, toast } = useApp()
  const revenue = 142500
  const orders = 48
  const pts = 120
  const offered = (pts / rules.pointsForDiscount) * rules.discountFcfa

  const download = () => {
    const rows = [['Indicateur', 'Valeur'], ["Chiffre d'affaires (FCFA)", revenue], ['Commandes validées', orders], ['Points fidélité utilisés', pts], ['FCFA offerts', offered], [], ['Plat', 'Catégorie', 'Ventes (mois)'], ...TOP_DISHES.map((d) => [d.name, d.category, d.sales])]
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
      <PageTitle title="Les chiffres du jour" subtitle="Vue d'ensemble de l'activité du restaurant aujourd'hui." action={<button className="btn-dark" onClick={download}><Download size={14} /> Télécharger le rapport</button>} />
      <div className="grid gap-5 md:grid-cols-3">
        <div className="panel p-6">
          <p className="kicker text-muted">Chiffre d'affaires</p>
          <p className="mt-3 flex items-baseline gap-3 text-3xl font-bold">{fcfa(revenue)} <span className="flex items-center text-xs font-semibold text-ok"><ArrowUp size={12} />+12%</span></p>
        </div>
        <div className="panel p-6">
          <p className="kicker text-muted">Commandes validées</p>
          <p className="mt-3 text-3xl font-bold">{orders} <span className="ml-1 text-xs font-normal text-muted">Aujourd'hui</span></p>
        </div>
        <div className="rounded-xl border border-gold/40 bg-[#15130e] p-6">
          <p className="kicker text-gold">Points fidélité utilisés</p>
          <p className="mt-3 text-3xl font-bold text-gold">{pts} pts <span className="ml-1 text-xs font-normal text-muted">Soit {offered} FCFA offerts</span></p>
        </div>
      </div>
      <section className="panel mt-6 overflow-hidden">
        <h2 className="px-6 py-6 font-bold">Les plats qui marchent le mieux</h2>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[520px] text-sm">
            <thead><tr className="border-y border-line bg-surface-2 text-muted"><th className="px-6 py-4 text-left font-normal">Plat</th><th className="px-6 py-4 text-left font-normal">Catégorie</th><th className="px-6 py-4 text-right font-normal">Ventes (Mois)</th></tr></thead>
            <tbody>
              {TOP_DISHES.map((d) => (
                <tr key={d.name} className="border-b border-line last:border-0"><td className="px-6 py-4">{d.name}</td><td className="px-6 py-4 text-muted">{d.category}</td><td className="px-6 py-4 text-right font-bold">{d.sales}</td></tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </>
  )
}
