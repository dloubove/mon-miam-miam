import { Link } from 'react-router-dom'
import { ArrowLeft, CheckCircle2, Clock3, Gift, Users } from 'lucide-react'
import { useApp } from '../../context/AppContext.jsx'

export default function ReferralsPage() {
  const { referrals } = useApp()
  const waiting = referrals.filter((r) => r.premiere_commande_payee && !r.recompense_attribuee).length
  const rewarded = referrals.filter((r) => r.recompense_attribuee).length
  return <main className="mx-auto max-w-[1100px] px-5 py-10">
    <Link to="/mon-espace" className="mb-6 inline-flex items-center gap-2 text-sm text-muted hover:text-gold"><ArrowLeft size={15}/> Retour à mon espace</Link>
    <div className="mb-8 flex items-center gap-3"><Users size={28} className="text-gold"/><div><h1 className="text-3xl font-bold">Mes filleuls</h1><p className="mt-2 text-sm text-muted">Suivez les inscriptions, la première commande et les récompenses de parrainage.</p></div></div>
    <div className="mb-7 grid gap-4 sm:grid-cols-3">{[["Filleuls inscrits", referrals.length, Users],["Première commande payée", referrals.filter((r) => r.premiere_commande_payee).length, CheckCircle2],["Récompenses attribuées", rewarded, Gift]].map(([label,value,Icon]) => <div key={label} className="rounded-xl border border-line bg-surface p-5"><Icon size={18} className="text-gold"/><p className="mt-3 text-sm text-muted">{label}</p><p className="mt-1 text-2xl font-bold">{value}</p></div>)}</div>
    {waiting > 0 && <p className="mb-5 rounded-lg border border-gold/40 bg-[#15130e] p-4 text-sm text-gold">{waiting} filleul(s) ont payé leur première commande ; vérifiez que la récompense est bien attribuée.</p>}
    <div className="overflow-hidden rounded-xl border border-line bg-surface">
      <div className="border-b border-line px-5 py-4 font-bold">Liste des filleuls</div>
      {referrals.length ? <div className="overflow-x-auto"><table className="w-full min-w-[600px] text-sm"><thead><tr className="border-b border-line bg-surface-2 text-left text-muted"><th className="px-5 py-3 font-medium">Filleul</th><th className="px-5 py-3 font-medium">Inscription</th><th className="px-5 py-3 font-medium">Première commande</th><th className="px-5 py-3 font-medium">Récompense</th></tr></thead><tbody>{referrals.map((r) => <tr key={r.id} className="border-b border-line last:border-0"><td className="px-5 py-4 font-semibold">{r.nom}</td><td className="px-5 py-4 text-muted">{r.date_parrainage ? new Date(r.date_parrainage).toLocaleDateString('fr-FR') : '—'}</td><td className="px-5 py-4">{r.premiere_commande_payee ? <span className="inline-flex items-center gap-1 text-ok"><CheckCircle2 size={14}/> Payée</span> : <span className="inline-flex items-center gap-1 text-muted"><Clock3 size={14}/> En attente</span>}</td><td className="px-5 py-4">{r.recompense_attribuee ? <span className="text-ok">+{r.points_recompense ?? 0} points attribués</span> : <span className="text-gold">{r.premiere_commande_payee ? 'À vérifier' : 'Condition non remplie'}</span>}</td></tr>)}</tbody></table></div> : <div className="px-6 py-12 text-center"><Users size={28} className="mx-auto text-muted"/><p className="mt-3 font-semibold">Aucun filleul pour le moment</p><p className="mt-2 text-sm text-muted">Partagez votre code de parrainage depuis votre espace étudiant pour commencer.</p></div>}
    </div>
  </main>
}
