import { useMemo, useState } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import { ArrowLeft, Search, SlidersHorizontal, X } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { HomeNavbar, SiteFooter } from '../layouts/StudentLayout.jsx'
import DishCard from '../components/DishCard.jsx'
import { fcfa } from '../utils/format.js'

const TYPES = {
  plats: { title: 'Tous nos plats', subtitle: 'Plats chauds, snacks : trouve ce qui te fait envie.', match: (d) => d.category !== 'Boisson' },
  boissons: { title: 'Toutes nos boissons', subtitle: 'Jus frais, cocktails : de quoi se rafraîchir.', match: (d) => d.category === 'Boisson' },
}
const SORTS = [
  ['default', 'Pertinence'],
  ['price_asc', 'Prix croissant'],
  ['price_desc', 'Prix décroissant'],
  ['name', 'Nom (A → Z)'],
]
const INITIAL = { q: '', category: 'all', min: '', max: '', onlyAvailable: false, onlyPromo: false, sort: 'default' }

export default function MenuPage() {
  const { type } = useParams()
  const { menu } = useApp()
  const [f, setF] = useState(INITIAL)
  const [showFilters, setShowFilters] = useState(false)
  const conf = TYPES[type]

  const base = useMemo(() => (conf ? menu.filter(conf.match) : []), [menu, conf])
  const categories = useMemo(() => [...new Set(base.map((d) => d.category))], [base])

  const results = useMemo(() => {
    const q = f.q.trim().toLowerCase()
    const min = f.min === '' ? 0 : Number(f.min)
    const max = f.max === '' ? Infinity : Number(f.max)
    let list = base.filter((d) =>
      (!q || d.name.toLowerCase().includes(q) || (d.description || '').toLowerCase().includes(q)) &&
      (f.category === 'all' || d.category === f.category) &&
      d.price >= min && d.price <= max &&
      (!f.onlyAvailable || d.available) &&
      (!f.onlyPromo || d.promo),
    )
    if (f.sort === 'price_asc') list = [...list].sort((a, b) => a.price - b.price)
    if (f.sort === 'price_desc') list = [...list].sort((a, b) => b.price - a.price)
    if (f.sort === 'name') list = [...list].sort((a, b) => a.name.localeCompare(b.name, 'fr'))
    return list
  }, [base, f])

  if (!conf) return <Navigate to="/menu/plats" replace />

  const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.type === 'checkbox' ? e.target.checked : e.target.value }))
  const active = JSON.stringify(f) !== JSON.stringify(INITIAL)

  const filters = (
    <form onSubmit={(e) => e.preventDefault()} className="space-y-6" aria-label="Filtres">
      <div>
        <label htmlFor="q" className="field-label">Rechercher</label>
        <div className="relative">
          <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
          <input id="q" className="field pl-10" placeholder={type === 'plats' ? 'Ex: poulet, burger…' : 'Ex: foléré, cocktail…'} value={f.q} onChange={set('q')} />
        </div>
      </div>

      {categories.length > 1 && (
        <div>
          <span className="field-label">Catégorie</span>
          <div className="flex flex-wrap gap-2">
            {['all', ...categories].map((c) => (
              <button key={c} type="button" onClick={() => setF((s) => ({ ...s, category: c }))}
                className={`cursor-pointer rounded-lg px-3.5 py-2 text-xs font-semibold transition ${f.category === c ? 'bg-gold text-black' : 'border border-line bg-black hover:border-gold/60'}`}>
                {c === 'all' ? 'Toutes' : c}
              </button>
            ))}
          </div>
        </div>
      )}

      <div>
        <span className="field-label">Prix (FCFA)</span>
        <div className="flex items-center gap-2">
          <input className="field" type="number" min="0" placeholder="Min" aria-label="Prix minimum" value={f.min} onChange={set('min')} />
          <span className="text-muted">–</span>
          <input className="field" type="number" min="0" placeholder="Max" aria-label="Prix maximum" value={f.max} onChange={set('max')} />
        </div>
      </div>

      <div className="space-y-3">
        <label className="flex cursor-pointer items-center gap-3 text-sm"><input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={f.onlyAvailable} onChange={set('onlyAvailable')} /> Disponibles uniquement</label>
        <label className="flex cursor-pointer items-center gap-3 text-sm"><input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={f.onlyPromo} onChange={set('onlyPromo')} /> En promo uniquement</label>
      </div>

      <div>
        <label htmlFor="sort" className="field-label">Trier par</label>
        <select id="sort" className="field" value={f.sort} onChange={set('sort')}>{SORTS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</select>
      </div>

      <button type="button" className="btn-dark w-full" disabled={!active} onClick={() => setF(INITIAL)}><X size={14} /> Réinitialiser les filtres</button>
    </form>
  )

  return (
    <div className="min-h-screen bg-black">
      <HomeNavbar />
      <main className="mx-auto max-w-[1360px] px-5 py-12 lg:px-16">
        <Link to="/#menu" className="mb-6 inline-flex items-center gap-2 text-sm text-muted hover:text-white"><ArrowLeft size={15} /> Retour à l'accueil</Link>
        <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
          <div>
            <h1 className="text-4xl font-bold tracking-tight">{conf.title}</h1>
            <p className="mt-2 text-muted">{conf.subtitle}</p>
          </div>
          <div role="tablist" className="inline-flex rounded-xl border border-line bg-black p-1">
            {[['plats', 'Plats'], ['boissons', 'Boissons']].map(([k, l]) => (
              <Link key={k} role="tab" aria-selected={type === k} to={`/menu/${k}`} onClick={() => setF(INITIAL)}
                className={`rounded-lg px-6 py-2.5 text-sm font-bold transition ${type === k ? 'bg-gold text-black' : 'text-white hover:text-gold'}`}>{l}</Link>
            ))}
          </div>
        </div>

        <div className="grid items-start gap-8 lg:grid-cols-[280px_1fr]">
          <aside className="lg:sticky lg:top-24">
            <button type="button" className="btn-dark mb-4 w-full lg:hidden" onClick={() => setShowFilters((s) => !s)} aria-expanded={showFilters}>
              <SlidersHorizontal size={14} /> Filtres {active && '(actifs)'}
            </button>
            <div className={`panel p-6 ${showFilters ? 'block' : 'hidden'} lg:block`}>
              <h2 className="mb-5 flex items-center gap-2 font-bold"><SlidersHorizontal size={15} className="text-gold" /> Filtres</h2>
              {filters}
            </div>
          </aside>

          <section aria-live="polite">
            <p className="mb-5 text-sm text-muted">
              <strong className="text-white">{results.length}</strong> résultat{results.length > 1 ? 's' : ''}
              {f.max !== '' && <> · jusqu'à {fcfa(Number(f.max))}</>}
            </p>
            {results.length ? (
              <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">{results.map((d) => <DishCard key={d.id} dish={d} />)}</div>
            ) : (
              <div className="panel grid place-items-center gap-4 py-20 text-center">
                <Search size={34} className="text-gold" />
                <p className="text-lg font-semibold">Aucun résultat pour ces filtres</p>
                <p className="text-sm text-muted">Essaie d'élargir ta recherche ou de réinitialiser les filtres.</p>
                <button className="btn-gold" onClick={() => setF(INITIAL)}>Réinitialiser les filtres</button>
              </div>
            )}
          </section>
        </div>
      </main>
      <SiteFooter full />
    </div>
  )
}
