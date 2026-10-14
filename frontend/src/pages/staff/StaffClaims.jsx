import { useState } from 'react'
import { Check, Clock, Gift, Reply, User } from 'lucide-react'
import { useApp } from '../../context/AppContext.jsx'
import { Modal } from '../../components/ui.jsx'

const BADGE = {
  nouveau: 'bg-danger/20 text-danger',
  en_cours: 'bg-gold/20 text-gold',
  classe: 'bg-surface-3 text-muted',
}
const LABEL = { nouveau: 'Nouveau', en_cours: 'En cours', classe: 'Classé' }

function TreatModal({ claim, onClose }) {
  const { updateClaim, toast, user, rules } = useApp()
  const [action, setAction] = useState('points')
  const [pts, setPts] = useState(15)
  const [msg, setMsg] = useState('')
  const [lastId, setLastId] = useState(null)
  if (claim && claim.id !== lastId) {
    setLastId(claim.id)
    setAction('points'); setPts(15)
    setMsg("Désolé pour le désagrément ! L'équipe s'excuse. Voici 15 points offerts sur ton compte pour ta prochaine commande.")
  }
  const finish = (status) => {
    const note = status === 'classe'
      ? (action === 'points' ? `Résolu : ${pts} points de fidélité offerts en compensation.` : 'Résolu : message d\'excuse envoyé.')
      : `${user.firstName} a mis la demande en attente.`
    updateClaim(claim.id, { status, note })
    toast(status === 'classe' ? 'Ticket clôturé' : 'Ticket mis en attente')
    onClose()
  }
  const chooseAction = (a) => {
    setAction(a)
    setMsg(a === 'points'
      ? `Désolé pour le désagrément ! L'équipe s'excuse. Voici ${pts} points offerts sur ton compte pour ta prochaine commande.`
      : "Désolé pour le désagrément ! L'équipe s'excuse et fait le nécessaire pour que cela ne se reproduise pas.")
  }
  return (
    <Modal open={!!claim} onClose={onClose} title="Traiter la demande" size="lg">
      <p className="-mt-3 text-sm text-muted">Action pour la commande <strong className="text-white">#{claim?.orderId}</strong> de <strong className="text-white">{claim?.customer}</strong>.</p>
      <p className="mb-3 mt-5 text-sm text-muted">Quelle action entreprendre ?</p>
      <div className="space-y-3">
        {[['simple', "Envoyer un message d'excuse simple"], ['points', 'Offrir des points de fidélité (Dédommagement)']].map(([k, l]) => (
          <label key={k} className={`flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3.5 text-sm ${action === k ? 'border-gold/60 bg-[#1b1912]' : 'border-line bg-black'}`}>
            <input type="radio" name="act" className="accent-[#cfbd97]" checked={action === k} onChange={() => chooseAction(k)} /> {l}
          </label>
        ))}
      </div>
      {action === 'points' && (
        <>
          <p className="mb-2 mt-5 text-sm text-muted">Combien de points offrir ?</p>
          <div className="flex items-center gap-3">
            <input type="number" min="1" className="field !w-24" value={pts} onChange={(e) => setPts(Math.max(1, Number(e.target.value) || 1))} />
            <span className="inline-flex items-center gap-2 rounded-md border border-line bg-surface-3 px-3 py-2 text-xs text-gold"><Gift size={12} /> {rules.pointsForDiscount} pts = {rules.discountFcfa} FCFA de réduction</span>
          </div>
        </>
      )}
      <p className="mb-2 mt-5 text-sm text-muted">Message pour l'étudiant</p>
      <textarea className="field h-24 resize-none" value={msg} onChange={(e) => setMsg(e.target.value)} />
      <div className="mt-6 flex gap-4">
        <button className="btn-outline" onClick={() => finish('en_cours')}>Mettre en attente</button>
        <button className="btn-gold" onClick={() => finish('classe')}>Clôturer le ticket</button>
      </div>
    </Modal>
  )
}

export default function StaffClaims() {
  const { claims } = useApp()
  const [filter, setFilter] = useState('all')
  const [active, setActive] = useState(null)

  const unread = claims.filter((c) => c.status === 'nouveau').length
  const waiting = claims.filter((c) => c.status === 'en_cours').length
  const solved = 13 + claims.filter((c) => c.status === 'classe').length
  const list = filter === 'unread' ? claims.filter((c) => c.status === 'nouveau') : claims

  return (
    <main className="mx-auto max-w-[1180px] px-5 py-12">
      <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-4xl font-bold">Les soucis à régler</h1>
          <p className="mt-2 text-muted">Gérez les retours des étudiants pour garder une bonne ambiance sur la terrasse.</p>
        </div>
        <div className="flex gap-3">
          <button onClick={() => setFilter('all')} className={`rounded-lg px-5 py-2 text-sm font-bold ${filter === 'all' ? 'bg-gold text-black' : 'border border-line bg-surface'}`}>Toutes</button>
          <button onClick={() => setFilter('unread')} className={`rounded-lg px-5 py-2 text-sm ${filter === 'unread' ? 'bg-gold font-bold text-black' : 'border border-line bg-surface'}`}>Non lues ({unread})</button>
        </div>
      </div>

      <div className="mb-8 grid gap-5 md:grid-cols-3">
        {[['À traiter urgemment', unread, 'border-l-danger text-danger'], ['En attente de retour', waiting, 'border-l-gold text-gold'], ['Résolues ce mois', solved, 'border-l-ok text-ok']].map(([l, v, c]) => (
          <div key={l} className={`rounded-xl border border-line border-l-[3px] bg-surface p-6 ${c.split(' ')[0]}`}>
            <p className={`kicker ${c.split(' ')[1]}`}>{l}</p>
            <p className="mt-3 text-3xl font-bold text-white">{v}</p>
          </div>
        ))}
      </div>

      <div className="space-y-5">
        {list.map((c) => (
          <article key={c.id} className={`grid gap-6 rounded-xl border bg-surface p-6 md:grid-cols-[220px_1fr] ${c.status === 'nouveau' ? 'border-danger/40' : 'border-line'} ${c.status === 'classe' ? 'opacity-45' : ''}`}>
            <div className="space-y-2 border-line text-sm md:border-r">
              <p className="flex items-center gap-3 font-bold">Commande #{c.orderId} <span className={`rounded px-2 py-0.5 text-[11px] font-semibold ${BADGE[c.status]}`}>{LABEL[c.status]}</span></p>
              <p className="flex items-center gap-2 text-muted"><Clock size={13} /> {c.when}</p>
              <p className="flex items-center gap-2 text-muted"><User size={13} /> {c.customer}</p>
            </div>
            <div>
              <h2 className="font-bold">Motif : {c.motif}</h2>
              <p className="mt-3 rounded-lg border border-line bg-black px-4 py-3.5 text-sm text-[#ccc]">"{c.message}"</p>
              {c.status === 'classe' ? (
                <p className="mt-4 flex items-center gap-2 text-xs text-ok"><Check size={13} /> {c.note}</p>
              ) : (
                <div className="mt-4 flex items-center justify-between gap-4">
                  <p className="flex items-center gap-2 text-xs italic text-gold/80">{c.note && <><Reply size={12} /> {c.note}</>}</p>
                  <button className={c.status === 'nouveau' ? 'btn-gold' : 'btn-outline'} onClick={() => setActive(c)}>{c.status === 'nouveau' ? 'Répondre & Traiter' : 'Mettre à jour'}</button>
                </div>
              )}
            </div>
          </article>
        ))}
        {!list.length && <p className="py-16 text-center text-muted">Aucune réclamation non lue. Bravo l'équipe !</p>}
      </div>
      <TreatModal claim={active} onClose={() => setActive(null)} />
    </main>
  )
}
