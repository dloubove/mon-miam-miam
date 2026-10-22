import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Eye, EyeOff, X } from 'lucide-react'
import { initials } from '../utils/format.js'

/* ---------- Logo ---------- */
export function Logo({ subtitle = 'LA TERRASSE', to = '/', variant = 'plain', gold = false }) {
  return (
    <Link to={to} aria-label="ZeDuc@Space — accueil" className="flex min-w-0 items-center gap-3">
      <img
        src="/images/logo-zeduc-space.png"
        alt="Logo ZeDuc@Space"
        className={`shrink-0 object-contain ${variant === 'ring' ? 'h-14 w-14' : 'h-12 w-14'}`}
        width="56"
        height="56"
      />
      <span className="min-w-0 leading-tight">
        <span className="block text-[15px] font-bold tracking-wide text-white">ZEDUC@SPACE</span>
        <span className={`block text-[10px] tracking-wider ${gold ? 'text-gold' : 'text-muted'}`}>{subtitle}</span>
      </span>
    </Link>
  )
}

/* ---------- Avatar à initiales ---------- */
export function Avatar({ name, size = 'md', gold = false, square = false }) {
  const sizes = { sm: 'h-7 w-7 text-[10px]', md: 'h-8 w-8 text-[11px]', lg: 'h-20 w-20 text-3xl' }
  return (
    <span
      className={`grid shrink-0 place-items-center border font-bold ${sizes[size]} ${square ? 'rounded-md' : 'rounded-full'} ${
        gold ? 'border-gold bg-gold text-black' : 'border-line bg-surface-3 text-gold'
      }`}
    >
      {initials(name)}
    </span>
  )
}

/* ---------- Modale générique ---------- */
export function Modal({ open, onClose, title, subtitle, children, tone, size = 'md', hideClose = false }) {
  useEffect(() => {
    if (!open) return
    const onKey = (e) => e.key === 'Escape' && onClose?.()
    document.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
    }
  }, [open, onClose])

  if (!open) return null
  const widths = { sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-2xl' }
  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/75 p-4 backdrop-blur-sm"
      onMouseDown={(e) => e.target === e.currentTarget && onClose?.()}
      role="dialog"
      aria-modal="true"
    >
      <div
        className={`anim-pop relative my-auto w-full ${widths[size]} rounded-xl border bg-surface p-7 shadow-2xl shadow-black ${
          tone === 'danger' ? 'border-danger' : 'border-line'
        }`}
      >
        {!hideClose && (
          <button onClick={onClose} aria-label="Fermer" className="absolute right-5 top-5 text-muted transition hover:text-white">
            <X size={20} />
          </button>
        )}
        {title && (
          <h2 className={`pr-8 text-xl font-bold ${tone === 'danger' ? 'text-danger' : 'text-white'}`}>{title}</h2>
        )}
        {subtitle && <p className="mt-1 text-sm text-muted">{subtitle}</p>}
        <div className={title ? 'mt-5' : ''}>{children}</div>
      </div>
    </div>
  )
}

/* ---------- Interrupteur ---------- */
export function Toggle({ checked, onChange, label }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      aria-label={label}
      onClick={() => onChange(!checked)}
      className={`relative h-5 w-10 shrink-0 cursor-pointer rounded-full transition-colors ${checked ? 'bg-ok' : 'bg-[#3a3a3a]'}`}
    >
      <span className={`absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all ${checked ? 'left-[22px]' : 'left-0.5'}`} />
    </button>
  )
}

/* ---------- Champs ---------- */
export function Field({ label, hint, error, children, className = '' }) {
  return (
    <label className={`block ${className}`}>
      {label && <span className="field-label">{label}</span>}
      {children}
      {hint && !error && <span className="mt-1.5 block text-xs text-gold/80">{hint}</span>}
      {error && <span className="mt-1.5 block text-xs text-danger">{error}</span>}
    </label>
  )
}

export function PasswordInput({ value, onChange, placeholder = '••••••••', icon: Icon, autoComplete, className = '' }) {
  const [show, setShow] = useState(false)
  return (
    <div className="relative">
      {Icon && <Icon size={15} className="absolute left-4 top-1/2 -translate-y-1/2 text-muted" />}
      <input
        type={show ? 'text' : 'password'}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        autoComplete={autoComplete}
        className={`field ${Icon ? 'pl-10' : ''} pr-11 ${className}`}
      />
      <button
        type="button"
        onClick={() => setShow((s) => !s)}
        aria-label={show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
        className="absolute right-4 top-1/2 -translate-y-1/2 text-muted transition hover:text-white"
      >
        {show ? <EyeOff size={16} /> : <Eye size={16} />}
      </button>
    </div>
  )
}

/* ---------- Billard (pas d'icône lucide) ---------- */
export function BilliardIcon({ size = 20, className = '' }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" className={className} aria-hidden="true">
      <circle cx="12" cy="12" r="11" fill="currentColor" />
      <circle cx="12" cy="9.5" r="3.6" fill="#111" />
      <circle cx="10.2" cy="8.6" r="0.9" fill="currentColor" />
      <circle cx="13.6" cy="8.6" r="0.9" fill="currentColor" />
    </svg>
  )
}

/* ---------- Titre de page ---------- */
export function PageTitle({ title, subtitle, action }) {
  return (
    <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">{title}</h1>
        {subtitle && <p className="mt-2 max-w-2xl text-muted">{subtitle}</p>}
      </div>
      {action}
    </div>
  )
}
