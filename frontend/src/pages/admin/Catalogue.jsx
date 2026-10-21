import { useState } from 'react'
import { ImageIcon, Lock, Plus, Trash2 } from 'lucide-react'
import { Field, Modal, PageTitle } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { CATEGORIES } from '../../data/mock.js'
import { fcfa } from '../../utils/format.js'

const EMPTY = { name: '', category: 'Plat Chaud', price: '', description: '', image: null, imageFile: null }

function DishModal({ dish, open, onClose }) {
  const { saveDish, toast } = useApp()
  const [f, setF] = useState(EMPTY)
  const [key, setKey] = useState(null)
  const k = open ? (dish?.id ?? 'new') : null
  if (k !== key) { setKey(k); if (open) setF(dish ? { ...dish, price: String(dish.price) } : EMPTY) }

  const onFile = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    if (file.size > 2 * 1024 * 1024) return toast('Image trop lourde (2 Mo max)', 'error')
    const r = new FileReader()
    r.onload = () => setF((s) => ({ ...s, image: r.result, imageFile: file }))
    r.readAsDataURL(file)
  }
  const submit = async (e) => {
    e.preventDefault()
    const price = Number(f.price)
    if (!f.name.trim() || !price || price <= 0) return toast('Nom et prix valides requis', 'error')
    const result = await saveDish({ ...f, price })
    if (result?.ok) {
      toast(dish ? 'Plat modifié' : 'Plat ajouté au catalogue')
      onClose()
    }
  }
  return (
    <Modal open={open} onClose={onClose} title={dish ? 'Modifier le plat' : 'Ajouter un nouveau plat'} size="lg">
      <form onSubmit={submit} className="space-y-4">
        <label className="grid h-[104px] cursor-pointer place-items-center overflow-hidden rounded-lg border border-dashed border-[#444] bg-surface-2 text-sm text-muted hover:border-gold">
          {f.image ? <img src={f.image} alt="" className="h-full w-full object-cover" /> : <span className="flex flex-col items-center gap-2"><ImageIcon size={18} />Clique pour ajouter une photo du plat</span>}
          <input type="file" accept="image/png,image/jpeg" className="hidden" onChange={onFile} />
        </label>
        <Field label="Nom du plat"><input className="field" placeholder="Ex: Poulet DG" value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} /></Field>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Catégorie"><select className="field" value={f.category} onChange={(e) => setF({ ...f, category: e.target.value })}>{CATEGORIES.map((c) => <option key={c}>{c}</option>)}</select></Field>
          <Field label="Prix (FCFA)"><input className="field" type="number" min="0" placeholder="2500" value={f.price} onChange={(e) => setF({ ...f, price: e.target.value })} /></Field>
        </div>
        <Field label="Description courte"><textarea className="field h-20 resize-none" placeholder="Entrer les ingrédients du plat..." value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} /></Field>
        <button className="btn-gold w-full !py-3">{dish ? 'Enregistrer' : 'Ajouter au catalogue'}</button>
      </form>
    </Modal>
  )
}

export default function Catalogue() {
  const { user, menu, deleteDish, toast } = useApp()
  const admin = user.role === 'administrateur'
  const [editing, setEditing] = useState(undefined) // undefined = fermé, null = nouveau

  return (
    <>
      <PageTitle
        title="Le Grand Catalogue"
        subtitle={admin ? "C'est ici qu'on crée les plats qui seront cuisinés par l'équipe." : <>Modifiez les plats disponibles. <span className="text-danger">Suppression restreinte.</span></>}
        action={<button className="btn-gold" onClick={() => setEditing(null)}><Plus size={14} /> Ajouter un plat</button>}
      />
      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        {menu.map((d) => (
          <article key={d.id} className="panel p-5">
            <div className="flex items-start justify-between">
              {d.image ? <img src={d.image} alt="" className="h-14 w-14 rounded-md object-cover" /> : <span className="h-14 w-14 rounded-md bg-surface-3" />}
              <span className="rounded bg-ok/15 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-ok">Actif</span>
            </div>
            <h2 className="mt-4 font-bold">{d.name}</h2>
            <p className="mt-1 text-sm font-bold text-gold">{fcfa(d.price)}</p>
            <div className="mt-4 flex gap-3 border-t border-line pt-4">
              <button className="btn-dark flex-1 !py-2 !text-xs" onClick={() => setEditing(d)}>{admin ? 'Modifier' : 'Modifier détails'}</button>
              {admin ? (
                <button aria-label={`Supprimer ${d.name}`} className="grid h-9 w-10 place-items-center rounded-lg border border-line bg-black text-danger hover:border-danger/50" onClick={async () => { if (confirm(`Supprimer « ${d.name} » du catalogue ?`)) { const result = await deleteDish(d.id); if (result?.ok) toast('Plat supprimé') } }}><Trash2 size={14} /></button>
              ) : (
                <span title="Suppression réservée à l'administrateur" className="grid h-9 w-10 place-items-center rounded-lg border border-line bg-black text-muted"><Lock size={13} /></span>
              )}
            </div>
          </article>
        ))}
      </div>
      <DishModal open={editing !== undefined} dish={editing} onClose={() => setEditing(undefined)} />
    </>
  )
}
