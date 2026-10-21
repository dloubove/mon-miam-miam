import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { Bike, Check, Copy, MessageCircle, Phone, Star, UserPlus } from 'lucide-react'
import { useApp } from '../../context/AppContext.jsx'
import { Modal } from '../../components/ui.jsx'
import { getApiToken } from '../../services/api.js'
import { fcfa } from '../../utils/format.js'

const STATUS_TEXT = { a_preparer: 'est en attente de préparation', en_cuisine: 'est en préparation', pret: 'est prête' }
const PROBLEMS = ['Retard du livreur', 'Mauvaise commande', 'Oubli de boisson', "Problème d'emballage"]

function TrackingModal({ order, onClose }) {
  const done = { a_preparer: 1, en_cuisine: 1, pret: 2, cloture: 4 }[order?.status] ?? 1
  const steps = [
    ['Commande validée', '12:15 - Prise en charge par le staff'],
    ['Préparation terminée', '12:30 - Emballé et chaud'],
    ['En route vers toi', 'Arrivée prévue dans ~5 minutes'],
    ['Livrée', 'En attente de remise en main propre'],
  ]
  return (
    <Modal open={!!order} onClose={onClose} size="sm" hideClose={false}>
      <h2 className="text-lg font-bold">Commande <span className="text-gold">#{order?.id}</span></h2>
      <ol className="relative mt-6 space-y-6 pl-1">
        <span className="absolute left-[15px] top-3 h-[calc(100%-1.5rem)] w-px bg-line" aria-hidden="true" />
        {steps.map(([t, d], i) => {
          const ok = i < done
          const active = i === done
          return (
            <li key={t} className="relative flex gap-4">
              <span className={`relative z-10 grid h-7 w-7 shrink-0 place-items-center rounded-full border ${ok ? 'border-ok text-ok' : active ? 'border-gold bg-gold text-black shadow-[0_0_14px_rgba(207,189,151,.5)]' : 'border-line text-[#444]'} bg-surface`}>
                {ok ? <Check size={14} /> : active ? <Bike size={13} /> : null}
              </span>
              <div className={ok || active ? '' : 'opacity-40'}>
                <p className={`text-sm font-semibold ${active ? 'text-gold' : ''}`}>{t}</p>
                <p className="text-[11px] text-muted">{d}</p>
              </div>
            </li>
          )
        })}
      </ol>
      <div className="mt-7 flex items-center gap-4 rounded-lg border border-line bg-black p-4">
        <span className="grid h-11 w-11 place-items-center rounded-full bg-surface-3 text-muted"><Bike size={18} /></span>
        <div><p className="text-[11px] text-muted">Ton livreur</p><p className="text-sm font-bold">Samuel <span className="ml-1 text-xs font-normal text-gold"><Star size={11} className="mr-0.5 inline" fill="currentColor" />4.9</span></p></div>
      </div>
      <div className="mt-4 flex gap-3">
        <a href="tel:+237699000000" className="btn-outline"><Phone size={14} /> Appeler</a>
        <a href="https://wa.me/237699000000" target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-lg bg-[#25d366] px-5 py-2.5 text-sm font-bold text-black"><MessageCircle size={14} /> WhatsApp</a>
      </div>
    </Modal>
  )
}

function IncidentModal({ order, onClose }) {
  const { createClaim, toast } = useApp()
  const [sel, setSel] = useState([])
  const [msg, setMsg] = useState('')
  const toggle = (p) => setSel((s) => (s.includes(p) ? s.filter((x) => x !== p) : [...s, p]))
  const send = async () => {
    if (!sel.length) return toast('Sélectionnez au moins un problème', 'error')
    const result = await createClaim({ orderId: order.id, motifs: sel, message: msg })
    if (!result?.ok) return toast(result?.error || 'La réclamation n’a pas été enregistrée.', 'error')
    toast('Réclamation envoyée. Nous revenons vers vous vite !')
    setSel([]); setMsg(''); onClose()
  }
  return (
    <Modal open={!!order} onClose={onClose} tone="danger" title="Signaler un incident" size="md">
      <p className="mb-3 text-lg">Sélectionne un problème :</p>
      <div className="space-y-2.5 pl-3">
        {PROBLEMS.map((p) => (
          <label key={p} className="flex cursor-pointer items-center gap-3 text-lg">
            <input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={sel.includes(p)} onChange={() => toggle(p)} /> {p}
          </label>
        ))}
      </div>
      <p className="mb-2 mt-6 text-lg">Commentaire :</p>
      <textarea className="field h-24 resize-none border-gold/50" placeholder="préciser votre problème ici..." value={msg} onChange={(e) => setMsg(e.target.value)} />
      <div className="mt-6 flex justify-center gap-6">
        <button className="btn-outline !text-gold" onClick={onClose}>Annuler</button>
        <button className="btn-gold" onClick={send}>Envoyer</button>
      </div>
    </Modal>
  )
}

export default function StudentSpace() {
  const { user, orders, rules, toast, pointsHistory, referrals, claims } = useApp()
  const [track, setTrack] = useState(null)
  const [incident, setIncident] = useState(null)

  const current = useMemo(
    () => [...orders].filter((o) => o.userId === user.id && ['a_preparer', 'en_cuisine', 'pret'].includes(o.status)).sort((a, b) => b.createdAt - a.createdAt)[0],
    [orders, user.id],
  )
  const pastOrders = useMemo(() => {
    if (!getApiToken()) return []
    return orders
      .filter((order) => order.userId === user.id && order.status === 'terminee')
      .sort((a, b) => b.createdAt - a.createdAt)
      .map((order) => ({
        ...order,
        date: Number.isNaN(order.createdAt) ? 'Date inconnue' : new Date(order.createdAt).toLocaleString('fr-FR'),
        dishes: order.items.map((item) => `${item.name} (x${item.qty})`).join(', '),
        claim: claims.find((claim) => claim.orderId === order.id) || null,
      }))
  }, [orders, claims, user.id])
  const points = user.points || 0
  const deductible = Math.floor(points / rules.pointsForDiscount) * rules.discountFcfa

  const copy = async () => {
    try { await navigator.clipboard.writeText(user.referralCode); toast('Code copié !') } catch { toast(user.referralCode) }
  }

  return (
    <div className="mx-auto max-w-[1100px] px-5 py-12">
      <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-4xl font-bold">Salut {user.firstName},</h1>
          <p className="mt-2 text-muted">Voici ce qui se passe dans ton espace étudiant aujourd'hui.</p>
        </div>
        <Link to="/mon-espace/profil" className="btn-dark">Modifier compte</Link>
      </div>

      {current && (
        <section className="flex flex-wrap items-center gap-5 rounded-xl border border-gold/70 bg-[#15130e] p-5">
          <span className="h-14 w-14 rounded-md bg-gold" aria-hidden="true" />
          <div className="flex-1">
            <p className="kicker text-gold">En cours</p>
            <h2 className="mt-1 text-lg font-bold">Ta commande #{current.id} {STATUS_TEXT[current.status]}</h2>
            <p className="text-sm text-gold/80">
              {current.mode === 'livraison' ? "Livraison prévue au Campus Yansoki d'ici 15 minutes." : "À retirer sur la terrasse dès qu'elle est prête."}
            </p>
          </div>
          {current.mode === 'livraison' && <button className="btn-dark !bg-black" onClick={() => setTrack(current)}>Suivre le livreur</button>}
        </section>
      )}

      <div className="mt-6 grid gap-6 md:grid-cols-2">
        <section className="panel flex flex-col p-7">
          <div className="flex items-start justify-between"><h2 className="text-lg font-bold">Ta fidélité</h2><Star size={18} className="text-gold" fill="currentColor" /></div>
          <p className="mt-4 text-sm text-muted">Tu cumules des points à chaque commande. Sers-t'en pour payer tes prochains repas.</p>
          <p className="mt-5 text-5xl font-bold">{points} <span className="text-lg font-normal text-muted">Points dispos</span></p>
          <div className="mt-auto border-t border-line pt-5">
            <p className="mt-5 text-sm text-muted">Tu peux déduire <strong className="text-white">{fcfa(deductible)}</strong> sur ta prochaine commande ({rules.pointsForDiscount} pts = {rules.discountFcfa}F).</p>
            <Link to="/panier" className="mt-3 inline-block text-sm text-gold hover:underline">Utiliser mes points maintenant →</Link>
          </div>
        </section>
        <section className="panel flex flex-col p-7">
          <div className="flex items-start justify-between"><h2 className="text-lg font-bold">Invite tes potes</h2><UserPlus size={18} className="text-muted" /></div>
          <p className="mt-4 text-sm text-muted">Partage ce code. S'ils l'utilisent à l'inscription, tu gagnes <strong className="text-gold">10 points direct</strong> à leur première commande.</p>
          <p className="mb-2 mt-5 text-xs text-muted">Ton code de parrainage</p>
          <div className="flex overflow-hidden rounded-lg border border-line bg-black">
            <span className="flex-1 px-4 py-3 text-xs font-bold tracking-wide text-gold">{user.referralCode}</span>
            <button onClick={copy} className="flex items-center gap-2 bg-surface-3 px-5 text-sm hover:bg-[#262626]"><Copy size={13} /> Copier</button>
          </div>
          <p className="mt-auto border-t border-line pt-5 text-sm text-muted"><span className="block pt-5">Tu as déjà parrainé <strong className="text-white">{user.referred || 0}</strong> étudiants.</span></p>
          <div className="mt-4 space-y-2 border-t border-line pt-4">
            <p className="text-xs font-bold uppercase tracking-wide text-muted">Suivi des filleuls</p>
            {referrals.map((friend) => <div key={friend.id} className="flex items-center justify-between gap-3 text-sm">
              <span>{friend.nom}</span>
              <span className="text-right text-xs text-muted">{friend.recompense_attribuee ? `Récompense versée (${friend.points_recompense} pts)` : friend.premiere_commande_payee ? 'Première commande payée' : 'En attente de première commande'}</span>
            </div>)}
            {!referrals.length && <p className="text-xs text-muted">Aucun filleul enregistré pour le moment.</p>}
          </div>
        </section>
      </div>

      <section className="panel mt-8 overflow-x-auto">
        <h2 className="px-6 py-5 font-bold">Historique des points</h2>
        <table className="w-full min-w-[520px] text-sm">
          <thead><tr className="border-y border-line bg-surface-2 text-muted"><th className="px-6 py-4 text-left font-normal">Date</th><th className="px-6 py-4 text-left font-normal">Mouvement</th><th className="px-6 py-4 text-right font-normal">Points</th></tr></thead>
          <tbody>
            {pointsHistory.map((movement) => <tr key={movement.id_mouvement} className="border-b border-line last:border-0">
              <td className="px-6 py-4 text-muted">{new Date(movement.date_mouvement).toLocaleString('fr-FR')}</td>
              <td className="px-6 py-4">{({ gain_commande: 'Commande', gain_parrainage: 'Parrainage', gain_evenement: 'Événement', gain_mini_jeu: 'Mini-jeu', utilisation: 'Utilisation', expiration: 'Expiration', ajustement: 'Ajustement' })[movement.type_mouvement] || movement.type_mouvement}</td>
              <td className={`px-6 py-4 text-right font-bold ${movement.nombre_points > 0 ? 'text-ok' : 'text-danger'}`}>{movement.nombre_points > 0 ? '+' : ''}{movement.nombre_points}</td>
            </tr>)}
            {!pointsHistory.length && <tr><td colSpan="3" className="px-6 py-8 text-center text-muted">Aucun mouvement de points enregistré.</td></tr>}
          </tbody>
        </table>
      </section>

      <h2 className="mb-4 mt-12 text-xl font-bold">Tes anciennes commandes</h2>
      <div className="panel overflow-x-auto">
        <table className="w-full min-w-[640px] text-sm">
          <thead>
            <tr className="border-b border-line bg-surface-2 text-muted">
              {['Date', 'Plats', 'Total', 'Statut'].map((h) => <th key={h} className="px-5 py-4 text-left font-normal">{h}</th>)}
              <th className="px-5 py-4 text-right font-normal">Action</th>
            </tr>
          </thead>
          <tbody>
            {pastOrders.map((o) => (
              <tr key={o.id} className="border-b border-line last:border-0">
                <td className="px-5 py-4 text-muted">{o.date}</td>
                <td className="px-5 py-4">{o.dishes}</td>
                <td className="px-5 py-4 text-gold">{fcfa(o.total)}</td>
                <td className="px-5 py-4"><span className="tag !text-[11px]">{o.status}</span></td>
                <td className="px-5 py-4 text-right text-xs">
                  {o.claim ? <span className="text-gold">{o.claim.status === 'classe' ? `Réclamation ${o.claim.decision === 'rejetee' ? 'rejetée' : 'validée'}` : o.claim.status === 'en_cours' ? 'Réponse en attente de décision' : 'Réclamation enregistrée'}</span>
                    : <button className="text-muted underline hover:text-white" onClick={() => setIncident(o)}>Signaler un souci</button>}
                </td>
              </tr>
            ))}
            {!pastOrders.length && <tr><td colSpan="5" className="px-5 py-8 text-center text-muted">Aucune commande terminée pour le moment.</td></tr>}
          </tbody>
        </table>
      </div>

      <TrackingModal order={track} onClose={() => setTrack(null)} />
      <IncidentModal order={incident} onClose={() => setIncident(null)} />
    </div>
  )
}
