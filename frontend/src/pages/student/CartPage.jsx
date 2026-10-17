import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Lock, Minus, Plus, ShoppingCart } from 'lucide-react'
import { useApp } from '../../context/AppContext.jsx'
import { Avatar, Logo, Modal } from '../../components/ui.jsx'
import UserMenu from '../../components/UserMenu.jsx'
import { fcfa } from '../../utils/format.js'

const ARRIVALS = ['Dès que c\'est prêt (env. 15min)', 'Dans 30 minutes', 'Dans 1 heure', 'Ce soir (20h00)']

export default function CartPage() {
  const { user, cartLines, cartSubtotal, setQty, placeOrder, rules, toast } = useApp()
  const navigate = useNavigate()
  const [mode, setMode] = useState('sur_place')
  const [arrival, setArrival] = useState(ARRIVALS[0])
  const [note, setNote] = useState('')
  const [usePoints, setUsePoints] = useState(false)
  const [payment, setPayment] = useState('momo')
  const [done, setDone] = useState(false)

  const points = user?.points || 0
  const units = Math.min(Math.floor(points / rules.pointsForDiscount), Math.floor(cartSubtotal / rules.discountFcfa))
  const discount = usePoints ? units * rules.discountFcfa : 0
  const fees = 0
  const total = Math.max(0, cartSubtotal - discount + fees)

  const pay = () => {
    if (!user) {
      toast('Connectez-vous pour valider votre commande', 'error')
      return navigate('/connexion', { state: { from: '/panier' } })
    }
    if (user.role !== 'etudiant') return toast('Seuls les étudiants peuvent commander', 'error')
    if (!cartLines.length) return
    placeOrder({ mode, arrival, note, usePoints, payment })
    setDone(true)
  }

  return (
    <div className="min-h-screen bg-black">
      <header className="border-b border-line">
        <div className="mx-auto flex h-[68px] max-w-[1150px] items-center justify-between px-5">
          <Logo />
          {user ? (
            <UserMenu><Avatar name={`${user.firstName} ${user.lastName || ''}`} />{user.firstName}</UserMenu>
          ) : (
            <Link to="/connexion" className="text-sm hover:text-gold">Mon compte</Link>
          )}
        </div>
      </header>

      <main className="mx-auto max-w-[1150px] px-5 py-12">
        <h1 className="text-4xl font-bold">Ton Panier</h1>
        <p className="mt-2 text-muted">Vérifie ta commande avant de valider.</p>

        {cartLines.length === 0 ? (
          <div className="panel mt-10 grid place-items-center gap-4 py-20 text-center">
            <ShoppingCart size={36} className="text-gold" />
            <p className="text-lg font-semibold">Ton panier est vide</p>
            <Link to="/#menu" className="btn-gold">Voir le menu du jour</Link>
          </div>
        ) : (
          <div className="mt-10 grid items-start gap-6 lg:grid-cols-[1.15fr_1fr]">
            <div className="space-y-6">
              <section className="panel p-6">
                <h2 className="mb-5 text-lg font-bold">Ce que tu vas manger</h2>
                <ul className="divide-y divide-line">
                  {cartLines.map(({ dish, qty }) => (
                    <li key={dish.id} className="flex items-center gap-4 py-4">
                      <img src={dish.image} alt="" className="h-14 w-14 rounded-md bg-surface-3 object-cover" />
                      <span className="flex-1 text-sm font-bold">{dish.name}</span>
                      <div className="text-right">
                        <p className="mb-2 text-sm font-bold text-gold">{fcfa(dish.price)}</p>
                        <div className="inline-flex items-center rounded-lg border border-line bg-black text-xs">
                          <button aria-label="Moins" className="px-3 py-1.5 text-muted hover:text-white" onClick={() => setQty(dish.id, qty - 1)}><Minus size={12} /></button>
                          <span className="w-6 text-center font-bold">{qty}</span>
                          <button aria-label="Plus" className="px-3 py-1.5 text-muted hover:text-white" onClick={() => setQty(dish.id, qty + 1)}><Plus size={12} /></button>
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              </section>

              <section className="panel p-6">
                <h2 className="mb-5 text-lg font-bold">Où et quand ?</h2>
                <div className="grid grid-cols-2 gap-4">
                  {[['sur_place', 'Sur Place (Terrasse)'], ['livraison', 'À livrer (Campus)']].map(([k, l]) => (
                    <button key={k} onClick={() => setMode(k)} className={`rounded-lg px-4 py-3.5 text-sm font-medium transition ${mode === k ? 'bg-gold text-black' : 'border border-line bg-black text-white hover:border-gold/60'}`}>{l}</button>
                  ))}
                </div>
                <label className="field-label mt-5">{mode === 'sur_place' ? 'À quelle heure tu arrives ?' : 'Livraison souhaitée'}</label>
                <select className="field" value={arrival} onChange={(e) => setArrival(e.target.value)}>
                  {ARRIVALS.map((a) => <option key={a}>{a}</option>)}
                </select>
                <label className="field-label mt-5">Un commentaire pour le chef ? (Facultatif)</label>
                <input className="field" placeholder="Ex: Beaucoup de sauce s'il vous plaît" value={note} onChange={(e) => setNote(e.target.value)} />
              </section>
            </div>

            <section className="panel p-6">
              <h2 className="mb-5 text-lg font-bold">Total à payer</h2>
              <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-line bg-black p-4">
                <input type="checkbox" className="mt-1 h-4 w-4 accent-[#cfbd97]" checked={usePoints} disabled={units === 0} onChange={(e) => setUsePoints(e.target.checked)} />
                <span>
                  <span className="block text-sm font-medium">Utiliser mes points de fidélité</span>
                  <span className="mt-1 block text-xs text-muted">Tu as {points} points. Utilise {rules.pointsForDiscount} points pour déduire {fcfa(rules.discountFcfa)}.</span>
                </span>
              </label>
              <dl className="mt-5 space-y-3 text-sm">
                <div className="flex justify-between text-muted"><dt>Sous-total plats</dt><dd className="text-white">{fcfa(cartSubtotal)}</dd></div>
                {discount > 0 && <div className="flex justify-between text-muted"><dt>Points de fidélité</dt><dd className="text-ok">- {fcfa(discount)}</dd></div>}
                <div className="flex justify-between text-muted"><dt>Frais de service ({mode === 'sur_place' ? 'Sur place' : 'Livraison'})</dt><dd className="text-white">{fcfa(fees)}</dd></div>
              </dl>
              <div className="mt-5 flex items-center justify-between border-t border-line pt-5">
                <span className="font-bold">Total</span>
                <span className="text-2xl font-bold text-gold">{fcfa(total)}</span>
              </div>
              <p className="mb-3 mt-6 text-sm text-muted">Comment tu paies ?</p>
              <div className="space-y-3">
                {[['momo', 'Mobile Money (Orange / MTN)'], ['card', 'Carte Bancaire']].map(([k, l]) => (
                  <label key={k} className={`flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm transition ${payment === k ? 'border-gold bg-[#1a1812]' : 'border-line bg-black'}`}>
                    <input type="radio" name="pay" className="accent-[#cfbd97]" checked={payment === k} onChange={() => setPayment(k)} /> {l}
                  </label>
                ))}
              </div>
              <button className="btn-gold mt-6 w-full !py-3.5" onClick={pay}>Payer et Confirmer</button>
              <p className="mt-4 flex items-center justify-center gap-2 text-xs text-muted"><Lock size={12} /> Paiement sécurisé via agrégateur</p>
            </section>
          </div>
        )}
      </main>

      <Modal open={done} onClose={() => { setDone(false); navigate('/mon-espace') }} size="md">
        <div className="flex min-h-[260px] flex-col items-center justify-between gap-8 py-6">
          <p className="flex flex-1 items-center gap-4 text-2xl text-gold">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#22c55e" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"><path d="M4 13l5 5L20 6" /></svg>
            Ta commande a été validée !
          </p>
          <button className="btn-gold px-10" onClick={() => { setDone(false); navigate('/mon-espace') }}>Voir ma commande</button>
        </div>
      </Modal>
    </div>
  )
}
