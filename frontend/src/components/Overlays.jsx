import { useNavigate } from 'react-router-dom'
import { CircleCheck, CheckCircle2, AlertCircle } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { Modal } from './ui.jsx'

/** Bandeau de consentement aux cookies (RGPD) — affiché à la première visite. */
export function CookieBanner() {
  const { cookieConsent, answerCookies } = useApp()
  if (cookieConsent) return null
  return (
    <div className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface px-4 py-4 shadow-[0_-8px_30px_rgba(0,0,0,.6)]">
      <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-4">
        <p className="max-w-xl text-sm text-muted">
          <strong className="font-semibold text-white">On utilise des cookies.</strong> Juste pour mémoriser votre panier et vous garder connecté. Rien de méchant.
        </p>
        <div className="flex gap-3">
          <button className="btn-dark" onClick={() => answerCookies(false)}>Refuser</button>
          <button className="btn-gold" onClick={() => answerCookies(true)}>D'accord pour moi</button>
        </div>
      </div>
    </div>
  )
}

/** Notifications éphémères */
export function ToastHost() {
  const { toasts } = useApp()
  return (
    <div className="pointer-events-none fixed right-4 top-4 z-[60] flex flex-col gap-2">
      {toasts.map((t) => (
        <div key={t.id} className="anim-pop pointer-events-auto flex items-center gap-2 rounded-lg border border-line bg-surface px-4 py-3 text-sm shadow-xl">
          {t.type === 'error' ? <AlertCircle size={16} className="text-danger" /> : <CheckCircle2 size={16} className="text-ok" />}
          {t.message}
        </div>
      ))}
    </div>
  )
}

/** Modale « [Ce plat] a été ajouté à votre panier ! » */
export function AddedToCartModal() {
  const { lastAdded, setLastAdded } = useApp()
  const navigate = useNavigate()
  const close = () => setLastAdded(null)
  return (
    <Modal open={!!lastAdded} onClose={close} size="md">
      <div className="flex min-h-[260px] flex-col items-center justify-between gap-8 py-6">
        <div className="flex flex-1 items-center gap-4 text-center">
          <CircleCheck size={44} className="shrink-0 text-ok" strokeWidth={1.6} />
          <p className="text-xl text-gold sm:text-2xl">{lastAdded?.name} a été ajouté à votre panier !</p>
        </div>
        <div className="flex w-56 flex-col gap-3">
          <button className="btn-gold" onClick={() => { close(); navigate('/panier') }}>Voir Panier</button>
          <button className="btn-outline" onClick={close}>Continuer mes achats</button>
        </div>
      </div>
    </Modal>
  )
}
