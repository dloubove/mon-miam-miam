import { useState } from 'react'
import { GlassWater, Search, SquarePen, UtensilsCrossed } from 'lucide-react'
import { useApp } from '../../context/AppContext.jsx'
import { Modal, Toggle } from '../../components/ui.jsx'
import { fcfa } from '../../utils/format.js'

function Row({ dish }) {
  const { toggleAvailability, toast } = useApp()
  const off = !dish.available
  return (
    <li className={`flex items-center gap-4 rounded-lg border bg-surface p-3.5 ${off ? 'border-danger/25' : 'border-line'}`}>
      {dish.image ? (
        <img src={dish.image} alt="" className={`h-12 w-12 rounded-md object-cover ${off ? 'grayscale' : ''}`} />
      ) : (
        <span className="grid h-12 w-12 place-items-center rounded-md bg-surface-3 text-muted"><GlassWater size={16} /></span>
      )}
      <div className="flex-1">
        <p className={`text-sm font-bold ${off ? 'text-[#999]' : ''}`}>{dish.name}</p>
        <p className="text-xs text-muted">{fcfa(dish.price)}</p>
      </div>
      <span className={`text-[11px] font-extrabold uppercase tracking-wide ${off ? 'text-danger' : 'text-ok'}`}>{off ? 'Rupture' : 'Disponible'}</span>
      <Toggle checked={dish.available} onChange={async () => { const result = await toggleAvailability(dish.id); if (result?.ok) toast('Disponibilité mise à jour') }} label={`Disponibilité ${dish.name}`} />
    </li>
  )
}

export default function StaffMenu() {
  const { menu, setMenuSelection, toast } = useApp()
  const [q, setQ] = useState('')
  const [open, setOpen] = useState(false)
  const [sel, setSel] = useState([])

  const filtered = menu.filter((d) => d.name.toLowerCase().includes(q.toLowerCase()))
  const hot = filtered.filter((d) => d.category === 'Plat Chaud')
  const others = filtered.filter((d) => d.category !== 'Plat Chaud')

  const openModal = () => { setSel(menu.filter((d) => d.inDailyMenu).map((d) => d.id)); setOpen(true) }
  const toggleSel = (id) => setSel((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]))
  const apply = async () => { const result = await setMenuSelection(sel); if (result?.ok) { setOpen(false); toast('Menu du jour mis à jour') } }

  return (
    <main className="mx-auto max-w-[800px] px-5 py-10">
      <div className="mb-10 flex flex-wrap items-start justify-between gap-4">
        <div className="max-w-sm">
          <h1 className="text-3xl font-bold">Le Menu d'Aujourd'hui</h1>
          <p className="mt-2 text-sm text-muted">Gère les ruptures de stock en direct ou ajoute de nouveaux plats pour le service.</p>
        </div>
        <div className="flex items-center gap-3">
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
            <input className="field !w-[200px] !bg-surface !py-2.5 pl-9" placeholder="Chercher un plat..." value={q} onChange={(e) => setQ(e.target.value)} aria-label="Chercher un plat" />
          </div>
          <button className="btn-gold !px-4 !py-2.5 text-left leading-tight" onClick={openModal}><SquarePen size={14} /> Modifier le<br />menu</button>
        </div>
      </div>

      {hot.length > 0 && (
        <section>
          <h2 className="mb-5 flex items-center gap-2.5 border-b border-line pb-3 text-lg font-bold"><UtensilsCrossed size={16} className="text-gold" /> Plats Chauds</h2>
          <ul className="space-y-3">{hot.map((d) => <Row key={d.id} dish={d} />)}</ul>
        </section>
      )}
      {others.length > 0 && (
        <section className="mt-10">
          <h2 className="mb-5 border-b border-line pb-3 pl-4 text-lg font-bold">Boissons &amp; Snacks</h2>
          <ul className="space-y-3">{others.map((d) => <Row key={d.id} dish={d} />)}</ul>
        </section>
      )}
      {!filtered.length && <p className="py-16 text-center text-muted">Aucun plat ne correspond à « {q} ».</p>}

      <Modal open={open} onClose={() => setOpen(false)} title="Composer le Menu" size="lg">
        <p className="-mt-2 mb-6 max-w-xs text-sm text-muted">Cochez les plats qui seront disponibles à la vente aujourd'hui.</p>
        <ul className="space-y-3">
          {menu.map((d) => {
            const on = sel.includes(d.id)
            return (
              <li key={d.id}>
                <label className={`flex cursor-pointer items-center gap-3 rounded-lg border bg-black px-4 py-3 text-sm ${on ? 'border-gold/50' : 'border-line'}`}>
                  <input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={on} onChange={() => toggleSel(d.id)} />
                  <span className={`flex-1 ${on ? 'font-medium' : 'text-muted'}`}>{d.name}</span>
                  <span className={on ? 'font-bold text-gold' : 'text-muted'}>{d.price} F</span>
                </label>
              </li>
            )
          })}
        </ul>
        <button className="btn-gold mt-7 w-full !py-3" onClick={apply}>Mettre à jour le menu ({sel.length} plat{sel.length > 1 ? 's' : ''})</button>
      </Modal>
    </main>
  )
}
