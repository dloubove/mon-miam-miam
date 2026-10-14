import { useEffect, useState } from 'react'
import { Bell, CircleCheck, MessageCircle } from 'lucide-react'
import { useApp } from '../../context/AppContext.jsx'
import { minutesAgo } from '../../utils/format.js'

const ModeBadge = ({ mode }) =>
  mode === 'livraison' ? (
    <span className="rounded bg-gold px-2.5 py-1 text-[11px] font-bold text-black">À Livrer (Campus)</span>
  ) : (
    <span className="rounded border border-line px-2.5 py-1 text-[11px] text-[#ddd]">Sur place (Table)</span>
  )

function Items({ order, interactive }) {
  const { toggleOrderItem } = useApp()
  return (
    <ul className="space-y-1.5 text-sm">
      {order.items.map((it, i) => (
        <li key={i}>
          <button
            type="button"
            disabled={!interactive}
            onClick={() => toggleOrderItem(order.id, i)}
            className={`flex gap-3 text-left ${interactive ? 'cursor-pointer' : 'cursor-default'} ${it.done ? 'text-[#666] line-through' : ''}`}
          >
            <span className="w-5 text-muted">{it.qty}x</span>
            <span>{it.name}</span>
          </button>
        </li>
      ))}
    </ul>
  )
}

function Column({ icon, title, count, children }) {
  return (
    <section className="flex min-h-[70vh] flex-col rounded-xl border border-line bg-surface-2">
      <header className="flex items-center justify-between border-b border-line px-5 py-4">
        <h2 className="flex items-center gap-2.5 font-bold">{icon}{title}</h2>
        <span className="grid h-6 min-w-6 place-items-center rounded bg-surface-3 px-1.5 text-xs font-bold">{count}</span>
      </header>
      <div className="flex flex-col gap-4 p-4">{children}</div>
    </section>
  )
}

export default function StaffOrders() {
  const { orders, setOrderStatus, toast } = useApp()
  const [, tick] = useState(0)
  useEffect(() => {
    const t = setInterval(() => tick((n) => n + 1), 30000)
    return () => clearInterval(t)
  }, [])

  const by = (s) => orders.filter((o) => o.status === s).sort((a, b) => a.createdAt - b.createdAt)
  const todo = by('a_preparer')
  const cooking = by('en_cuisine')
  const ready = by('pret')

  return (
    <main className="mx-auto max-w-[1500px] px-5 py-8 lg:px-7">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-3xl font-bold">Service en cours</h1>
        <p className="flex items-center gap-2.5 text-sm text-[#b5b5b5]"><span className="h-2.5 w-2.5 rounded-full bg-ok" /> Réception des commandes active</p>
      </div>
      <div className="grid gap-6 lg:grid-cols-3">
        <Column icon={<Bell size={16} className="text-danger" fill="currentColor" />} title="À Préparer" count={todo.length}>
          {todo.map((o) => {
            const m = minutesAgo(o.createdAt)
            const urgent = m >= 2
            return (
              <article key={o.id} className={`rounded-lg border border-line bg-surface p-4 border-l-[3px] ${urgent ? 'border-l-danger/70' : 'border-l-[#555]'}`}>
                <div className="mb-3 flex items-start justify-between gap-2">
                  <p className="flex items-center gap-2.5 text-lg font-bold">#{o.id}
                    {urgent ? <span className="rounded bg-danger/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-danger">Il y a {m} min</span>
                      : <span className="text-[9px] font-bold uppercase tracking-wide text-muted">À l'instant</span>}
                  </p>
                  <ModeBadge mode={o.mode} />
                </div>
                <Items order={o} />
                {o.note && <p className="mt-4 flex items-center gap-2 rounded-md border border-line bg-black px-3 py-2.5 text-xs text-[#ccc]"><MessageCircle size={11} className="text-muted" /> "{o.note}"</p>}
                <button className={`mt-4 w-full rounded-lg py-3 text-sm font-bold transition ${urgent ? 'bg-danger text-white hover:bg-[#dc3a3a]' : 'bg-surface-3 text-white hover:bg-[#2a2a2a]'}`} onClick={() => setOrderStatus(o.id, 'en_cuisine')}>Lancer la préparation</button>
              </article>
            )
          })}
          {!todo.length && <p className="py-8 text-center text-sm text-muted">Aucune commande en attente.</p>}
        </Column>

        <Column icon={null} title="En Cuisine" count={cooking.length}>
          {cooking.map((o) => (
            <article key={o.id} className="rounded-lg border border-[#3a3a3a] bg-surface p-4">
              <div className="mb-3 flex items-start justify-between gap-2">
                <p className="flex items-center gap-2.5 text-lg font-bold">#{o.id} <span className="text-[9px] font-bold uppercase tracking-wide text-gold">En cours</span></p>
                <ModeBadge mode={o.mode} />
              </div>
              <Items order={o} interactive />
              <button className="btn-gold mt-4 w-full !py-3 font-bold" onClick={() => setOrderStatus(o.id, 'pret')}>Marquer comme PRÊT</button>
            </article>
          ))}
          {!cooking.length && <p className="py-8 text-center text-sm text-muted">Rien en cuisine.</p>}
        </Column>

        <Column icon={<CircleCheck size={17} className="text-ok" fill="#22c55e" stroke="#0a0a0a" />} title="Prêt" count={ready.length}>
          {ready.map((o) => (
            <article key={o.id} className="rounded-lg border border-line bg-surface p-4 text-[#aaa]">
              <div className="mb-3 flex items-start justify-between gap-2">
                <p className="flex items-center gap-2.5 text-lg font-bold">#{o.id} <span className="text-[9px] font-bold uppercase tracking-wide text-ok">En attente retrait</span></p>
                <ModeBadge mode={o.mode} />
              </div>
              <Items order={o} />
              <div className="mt-4 grid grid-cols-2 gap-3">
                <button className="btn-outline !py-2.5" onClick={() => toast(`Client de la commande #${o.id} notifié`)}><Bell size={13} fill="currentColor" /> Notifier</button>
                <button className="btn-dark !py-2.5 font-bold" onClick={() => setOrderStatus(o.id, 'cloture')}>Clôturer</button>
              </div>
            </article>
          ))}
          {!ready.length && <p className="py-8 text-center text-sm text-muted">Aucune commande prête.</p>}
        </Column>
      </div>
    </main>
  )
}
