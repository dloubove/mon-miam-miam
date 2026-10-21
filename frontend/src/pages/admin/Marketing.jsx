import { useState } from 'react'
import { ImageIcon, Lock, Pencil, Plus, Trash2, Tv } from 'lucide-react'
import { BilliardIcon, Field, Modal, PageTitle } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'

const localDateInput = (value) => {
  const date = value ? new Date(value) : new Date()
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}
const emptyEvent = () => ({ title: '', badge: '', text: '', active: true, poster: null, posterFile: null, start: localDateInput(), end: localDateInput(new Date(Date.now() + 7 * 86400000)), points: 0 })
const emptyPromotion = () => ({ title: '', description: '', rate: 10, start: new Date().toISOString().slice(0, 10), end: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10), articles: [], poster: null, posterFile: null })

function PromotionModal({ promotion, open, onClose, articles }) {
  const { savePromotion, toast } = useApp()
  const [form, setForm] = useState(emptyPromotion)
  const [key, setKey] = useState(null)
  const currentKey = open ? (promotion?.id ?? 'new-promo') : null
  if (currentKey !== key) {
    setKey(currentKey)
    if (open) setForm(promotion ? { ...promotion, posterFile: null } : emptyPromotion())
  }
  const submit = async (event) => {
    event.preventDefault()
    if (!form.title.trim() || !form.articles.length) return toast('Un titre et au moins un article sont requis.', 'error')
    if (form.end < form.start) return toast('La fin doit être postérieure au début.', 'error')
    const result = await savePromotion(form)
    if (result?.ok) { toast('Promotion enregistrée dans la base.'); onClose() }
  }
  const onFile = (event) => {
    const file = event.target.files?.[0]
    if (!file) return
    if (file.size > 2 * 1024 * 1024) return toast('Image trop lourde (2 Mo max)', 'error')
    const reader = new FileReader()
    reader.onload = () => setForm((value) => ({ ...value, poster: reader.result, posterFile: file }))
    reader.readAsDataURL(file)
  }
  const toggleArticle = (id) => setForm((value) => ({ ...value, articles: value.articles.includes(id) ? value.articles.filter((item) => item !== id) : [...value.articles, id] }))
  return <Modal open={open} onClose={onClose} title={promotion ? 'Modifier la promotion' : 'Nouvelle promotion'} size="lg">
    <form onSubmit={submit} className="space-y-4">
      <label className="grid h-24 cursor-pointer place-items-center overflow-hidden rounded-lg border border-dashed border-line bg-black text-sm text-muted">
        {form.poster ? <img src={form.poster} alt="" className="h-full w-full object-cover" /> : <span className="flex items-center gap-2"><ImageIcon size={16} /> Affiche promotionnelle</span>}
        <input type="file" accept="image/png,image/jpeg,image/webp" className="hidden" onChange={onFile} />
      </label>
      <Field label="Titre"><input className="field" value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} required /></Field>
      <Field label="Description"><textarea className="field h-20 resize-none" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></Field>
      <div className="grid gap-4 sm:grid-cols-3">
        <Field label="Réduction (%)"><input className="field" type="number" min="1" max="100" value={form.rate} onChange={(event) => setForm({ ...form, rate: Number(event.target.value) })} required /></Field>
        <Field label="Début"><input className="field" type="date" value={form.start} onChange={(event) => setForm({ ...form, start: event.target.value })} required /></Field>
        <Field label="Fin"><input className="field" type="date" value={form.end} onChange={(event) => setForm({ ...form, end: event.target.value })} required /></Field>
      </div>
      <fieldset className="max-h-44 space-y-2 overflow-y-auto rounded-lg border border-line p-3">
        <legend className="px-2 text-sm font-semibold">Articles concernés</legend>
        {articles.map((article) => <label key={article.id} className="flex items-center gap-3 text-sm"><input type="checkbox" className="accent-[#cfbd97]" checked={form.articles.includes(article.id)} onChange={() => toggleArticle(article.id)} />{article.name} <span className="text-muted">({article.price} FCFA)</span></label>)}
        {!articles.length && <p className="text-sm text-muted">Aucun article chargé depuis le catalogue.</p>}
      </fieldset>
      <button className="btn-gold w-full" disabled={!articles.length}><Plus size={14} /> Enregistrer la promotion</button>
    </form>
  </Modal>
}

function PosterModal({ ev, open, onClose }) {
  const { saveEvent, toast } = useApp()
  const [f, setF] = useState(emptyEvent)
  const [key, setKey] = useState(null)
  const k = open ? (ev?.id ?? 'new') : null
  if (k !== key) { setKey(k); if (open) setF(ev ? { ...ev, start: localDateInput(ev.start), end: localDateInput(ev.end), posterFile: null } : emptyEvent()) }

  const onFile = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    if (file.size > 2 * 1024 * 1024) return toast('Image trop lourde (2 Mo max)', 'error')
    const r = new FileReader()
    r.onload = () => setF((s) => ({ ...s, poster: r.result, posterFile: file }))
    r.readAsDataURL(file)
  }
  const submit = async (e) => {
    e.preventDefault()
    if (!f.title.trim()) return toast("Donnez un titre à l'événement", 'error')
    if (new Date(f.end) < new Date(f.start)) return toast('La fin doit être après le début.', 'error')
    const inactiveEnd = new Date(Date.now() - 60000)
    const event = f.active ? f : { ...f, start: localDateInput(new Date(inactiveEnd.getTime() - 3600000)), end: localDateInput(inactiveEnd) }
    const result = await saveEvent(event)
    if (!result?.ok) return
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
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Début"><input className="field" type="datetime-local" value={f.start} onChange={(e) => setF({ ...f, start: e.target.value })} /></Field>
          <Field label="Fin"><input className="field" type="datetime-local" value={f.end} onChange={(e) => setF({ ...f, end: e.target.value })} /></Field>
        </div>
        <Field label="Description"><textarea className="field h-24 resize-none" value={f.text} onChange={(e) => setF({ ...f, text: e.target.value })} /></Field>
        <Field label="Points de participation"><input className="field" type="number" min="0" max="1000" value={f.points} onChange={(e) => setF({ ...f, points: Number(e.target.value) })} /></Field>
        <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-gold/40 bg-surface-3 px-4 py-3.5 text-sm font-medium">
          <input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={f.active} onChange={(e) => setF({ ...f, active: e.target.checked })} /> Activer et afficher aux étudiants
        </label>
        <button className="btn-gold w-full !py-3">Enregistrer l'affiche</button>
      </form>
    </Modal>
  )
}

export default function Marketing() {
  const { user, rules, setRules, events, deleteEvent, promotions, deletePromotion, menu, toast } = useApp()
  const admin = user.role === 'administrateur'
  const [draftRules, setDraftRules] = useState(null)
  const r = draftRules || rules
  const [editing, setEditing] = useState(undefined)
  const [editingPromotion, setEditingPromotion] = useState(undefined)
  const num = (k) => (e) => setDraftRules((s) => ({ ...(s || rules), [k]: Number(e.target.value) }))

  const save = async () => {
    if ([r.fcfaPerBlock, r.pointsPerBlock, r.pointsForDiscount, r.discountFcfa].some((v) => !(v > 0))) return toast('Toutes les valeurs doivent être positives', 'error')
    const result = await setRules(r)
    if (!result?.ok) return
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
              <input className="field !w-24" type="number" value={1000} disabled /> FCFA dépensés =
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
                <button aria-label="Supprimer" className="text-muted hover:text-danger" onClick={async () => { if (confirm(`Supprimer « ${ev.title} » ?`)) { const result = await deleteEvent(ev.id); if (result?.ok) toast('Affiche supprimée') } }}><Trash2 size={14} /></button>
              </li>
            ))}
            {!events.length && <li className="py-8 text-center text-sm text-muted">Aucune affiche pour le moment.</li>}
          </ul>
        </section>
      </div>
      <section className="panel mt-6 p-6">
        <div className="flex items-center justify-between border-b border-line pb-4">
          <div><h2 className="font-bold">Promotions de la carte</h2><p className="mt-1 text-xs text-muted">Les réductions actives apparaissent dans le menu calculé par le serveur.</p></div>
          <button className="btn-gold" onClick={() => setEditingPromotion(null)}><Plus size={14} /> Ajouter</button>
        </div>
        <div className="mt-5 grid gap-3 md:grid-cols-2">
          {promotions.map((promotion) => <article key={promotion.id} className="flex items-center gap-4 rounded-lg border border-line bg-black p-3">
            {promotion.poster ? <img src={promotion.poster} alt="" className="h-12 w-12 rounded object-cover" /> : <span className="grid h-12 w-12 place-items-center rounded bg-surface-3 text-gold">−{promotion.rate}%</span>}
            <div className="flex-1"><p className="text-sm font-bold">{promotion.title}</p><p className="text-xs text-muted">−{promotion.rate}% · {promotion.start} → {promotion.end} · {promotion.status}</p></div>
            <button aria-label="Modifier la promotion" className="text-muted hover:text-white" onClick={() => setEditingPromotion(promotion)}><Pencil size={14} /></button>
            <button aria-label="Supprimer la promotion" className="text-muted hover:text-danger" onClick={async () => { if (confirm(`Supprimer « ${promotion.title} » ?`)) { const result = await deletePromotion(promotion.id); if (result?.ok) toast('Promotion supprimée') } }}><Trash2 size={14} /></button>
          </article>)}
          {!promotions.length && <p className="py-6 text-center text-sm text-muted">Aucune promotion enregistrée.</p>}
        </div>
      </section>
      <PosterModal open={editing !== undefined} ev={editing} onClose={() => setEditing(undefined)} />
      <PromotionModal open={editingPromotion !== undefined} promotion={editingPromotion} onClose={() => setEditingPromotion(undefined)} articles={menu} />
    </>
  )
}
