import { useState } from 'react'
import { ImageIcon, Lock, Pencil, Trash2, Tv } from 'lucide-react'
import { BilliardIcon, Field, Modal, PageTitle } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'

function PosterModal({ ev, open, onClose }) {
  const { saveEvent, toast } = useApp()
  const [f, setF] = useState({ title: '', badge: '', active: true, poster: null })
  const [key, setKey] = useState(null)
  const k = open ? (ev?.id ?? 'new') : null
  if (k !== key) { setKey(k); if (open) setF(ev ? { ...ev } : { title: '', badge: '', active: true, poster: null }) }

  const onFile = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    if (file.size > 2 * 1024 * 1024) return toast('Image trop lourde (2 Mo max)', 'error')
    const r = new FileReader()
    r.onload = () => setF((s) => ({ ...s, poster: r.result }))
    r.readAsDataURL(file)
  }
  const submit = (e) => {
    e.preventDefault()
    if (!f.title.trim()) return toast("Donnez un titre à l'événement", 'error')
    saveEvent({ ...f, hook: f.hook || f.badge, heading: f.heading || f.title, text: f.text || f.badge })
    toast('Affiche enregistrée')
    onClose()
  }
  return (
    <Modal open={open} onClose={onClose} title={ev ? "Modifier l'événement" : 'Nouvel Événement'} size="lg">
      <form onSubmit={submit} className="space-y-4">
        <label className="grid h-[110px] cursor-pointer place-items-center overflow-hidden rounded-lg border border-dashed border-[#444] bg-surface-2 text-sm text-muted hover:border-gold">
          {f.poster ? <img src={f.poster} alt="" className="h-full w-full object-cover" /> : <span className="flex flex-col items-center gap-2"><ImageIcon size={20} />Importer l'affiche (PNG/JPG)</span>}
          <input type="file" accept="image/png,image/jpeg" className="hidden" onChange={onFile} />
        </label>
        <Field label="Titre de l'événement"><input className="field" placeholder="Ex: Soirée Fin d'Exams" value={f.title} onChange={(e) => setF({ ...f, title: e.target.value })} /></Field>
        <Field label="Date (ou texte d'accroche)"><input className="field" placeholder="Ex: Ce vendredi à 20h" value={f.badge} onChange={(e) => setF({ ...f, badge: e.target.value })} /></Field>
        <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-gold/40 bg-surface-3 px-4 py-3.5 text-sm font-medium">
          <input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={f.active} onChange={(e) => setF({ ...f, active: e.target.checked })} /> Activer et afficher aux étudiants
        </label>
        <button className="btn-gold w-full !py-3">Enregistrer l'affiche</button>
      </form>
    </Modal>
  )
}

export default function Marketing() {
  const { user, rules, setRules, events, deleteEvent, toast } = useApp()
  const admin = user.role === 'administrateur'
  const [r, setR] = useState(rules)
  const [editing, setEditing] = useState(undefined)
  const num = (k) => (e) => setR((s) => ({ ...s, [k]: Number(e.target.value) }))

  const save = () => {
    if ([r.fcfaPerBlock, r.pointsPerBlock, r.pointsForDiscount, r.discountFcfa].some((v) => !(v > 0))) return toast('Toutes les valeurs doivent être positives', 'error')
    setRules(r)
    toast('Règles de fidélité sauvegardées')
  }

  return (
    <>
      <PageTitle title="Marketing & Règles" subtitle={admin ? 'Gérez ce que voient les étudiants et les règles de fidélité.' : 'Gérez les affiches promotionnelles.'} />
      <div className="grid items-start gap-6 xl:grid-cols-2">
        <section className="panel relative p-6">
          <div className={admin ? '' : 'pointer-events-none select-none opacity-25 blur-[1px]'} aria-hidden={!admin}>
            <h2 className="border-b border-line pb-4 font-bold">Règles de Fidélité</h2>
            <p className="mb-3 mt-5 text-sm text-muted">Conversion des achats en points</p>
            <div className="flex flex-wrap items-center gap-3 text-sm text-muted">
              <input className="field !w-24" type="number" value={r.fcfaPerBlock} onChange={num('fcfaPerBlock')} disabled={!admin} /> FCFA dépensés =
              <input className="field !w-20" type="number" value={r.pointsPerBlock} onChange={num('pointsPerBlock')} disabled={!admin} /> point gagné
            </div>
            <p className="mb-3 mt-6 text-sm text-muted">Valeur d'un point (Réduction)</p>
            <div className="flex flex-wrap items-center gap-3 text-sm text-muted">
              <input className="field !w-20" type="number" value={r.pointsForDiscount} onChange={num('pointsForDiscount')} disabled={!admin} /> points valent =
              <input className="field !w-24" type="number" value={r.discountFcfa} onChange={num('discountFcfa')} disabled={!admin} /> FCFA de remise
            </div>
            <button className="btn-dark mt-6 w-full !py-3" onClick={save} disabled={!admin}>Sauvegarder les règles</button>
          </div>
          {!admin && (
            <div className="absolute inset-0 grid place-items-center text-center">
              <div><Lock size={30} className="mx-auto text-muted" /><p className="mt-3 text-sm">Réservé au Super Administrateur</p></div>
            </div>
          )}
        </section>

        <section className="panel p-6">
          <div className="flex items-center justify-between border-b border-line pb-4">
            <h2 className="font-bold">Événements (Accueil Étudiants)</h2>
            <button className="text-xs font-bold text-gold hover:underline" onClick={() => setEditing(null)}>+ Ajouter une affiche</button>
          </div>
          <ul className="mt-5 space-y-4">
            {events.map((ev) => (
              <li key={ev.id} className="flex items-center gap-4 rounded-lg border border-line bg-black p-3">
                <span className="grid h-12 w-12 place-items-center overflow-hidden rounded-md bg-surface-3 text-gold">
                  {ev.poster ? <img src={ev.poster} alt="" className="h-full w-full object-cover" /> : ev.icon === 'billard' ? <BilliardIcon size={18} /> : <Tv size={18} />}
                </span>
                <div className="flex-1">
                  <p className="text-sm font-bold">{ev.title}</p>
                  <p className="text-xs text-muted">{ev.active ? `Affiche activée (${ev.hook || ev.badge})` : 'Affiche désactivée'}</p>
                </div>
                <button aria-label="Modifier" className="text-muted hover:text-white" onClick={() => setEditing(ev)}><Pencil size={14} /></button>
                <button aria-label="Supprimer" className="text-muted hover:text-danger" onClick={() => { if (confirm(`Supprimer « ${ev.title} » ?`)) { deleteEvent(ev.id); toast('Affiche supprimée') } }}><Trash2 size={14} /></button>
              </li>
            ))}
            {!events.length && <li className="py-8 text-center text-sm text-muted">Aucune affiche pour le moment.</li>}
          </ul>
        </section>
      </div>
      <PosterModal open={editing !== undefined} ev={editing} onClose={() => setEditing(undefined)} />
    </>
  )
}
