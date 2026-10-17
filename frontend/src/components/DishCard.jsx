import { GlassWater, UtensilsCrossed } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { fcfa } from '../utils/format.js'

export default function DishCard({ dish }) {
  const { addToCart } = useApp()
  const Placeholder = dish.category === 'Boisson' ? GlassWater : UtensilsCrossed
  return (
    <article className="overflow-hidden rounded-xl border border-line bg-[#111]">
      <div className="relative flex h-[190px] items-center justify-center bg-[#161616]">
        {dish.image ? (
          <img src={dish.image} alt={dish.name} className="h-full object-contain" loading="lazy" />
        ) : (
          <Placeholder size={40} className="text-[#3a3a3a]" />
        )}
        {dish.promo && <span className="absolute right-3 top-3 rounded bg-white px-2 py-1 text-[11px] font-bold text-black">Promo</span>}
      </div>
      <div className="px-6 pb-5 pt-5">
        <h3 className="min-h-[3.25rem] pl-1 text-[17px] font-bold">{dish.name}</h3>
        <div className="mt-2 flex items-end justify-between gap-3 border-t border-line pt-4">
          <div>
            {dish.oldPrice ? <p className="text-[11px] text-muted line-through">{fcfa(dish.oldPrice)}</p> : <p className="text-[11px] text-muted">Prix</p>}
            <p className="text-[18px] font-bold text-gold">{fcfa(dish.price)}</p>
          </div>
          {dish.available ? (
            <button className="btn-gold !px-4 !py-2" onClick={() => addToCart(dish)}>Ajouter</button>
          ) : (
            <button className="btn-dark !px-4 !py-2" disabled>Rupture</button>
          )}
        </div>
      </div>
    </article>
  )
}
