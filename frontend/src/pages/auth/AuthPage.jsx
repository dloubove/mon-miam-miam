import { useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { ArrowRight, Lock, Mail } from 'lucide-react'
import AuthLayout from '../../layouts/AuthLayout.jsx'
import { Field, Modal, PasswordInput } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { DELIVERY_PLACES, DEMO_USERS, HOME_BY_ROLE } from '../../data/mock.js'
import { isEmail, isStrongPassword } from '../../utils/format.js'

function Tabs({ mode, onChange }) {
  const base = 'flex-1 rounded-lg py-2.5 text-xs font-semibold transition cursor-pointer'
  return (
    <div className="flex rounded-xl border border-line bg-black p-1">
      <button type="button" className={`${base} ${mode === 'login' ? 'bg-gold text-black' : 'text-gold'}`} onClick={() => onChange('login')}>Connexion</button>
      <button type="button" className={`${base} ${mode === 'register' ? 'bg-gold text-black' : 'text-white'}`} onClick={() => onChange('register')}>Créer un compte</button>
    </div>
  )
}

function LoginForm({ onSwitch }) {
  const { login } = useApp()
  const navigate = useNavigate()
  const location = useLocation()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(false)
  const [error, setError] = useState('')
  const [forgot, setForgot] = useState(false)

  const submit = (e) => {
    e.preventDefault()
    if (!isEmail(email) || !password) return setError('Renseignez un email valide et votre mot de passe.')
    const res = login(email, password)
    if (!res.ok) return setError(res.error)
    navigate(location.state?.from || HOME_BY_ROLE[res.user.role], { replace: true })
  }
  const demo = (u) => {
    const res = login(u.email, 'Demo1234')
    if (res.ok) navigate(HOME_BY_ROLE[u.role])
  }

  return (
    <>
      <form onSubmit={submit} className="space-y-5" noValidate>
        <Field label="Adresse Email">
          <div className="relative">
            <Mail size={15} className="absolute left-4 top-1/2 -translate-y-1/2 text-muted" />
            <input className="field pl-10" type="email" placeholder="etudiant@ucac-icam.com" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" />
          </div>
        </Field>
        <div>
          <div className="mb-2 flex items-center justify-between">
            <span className="text-sm text-muted">Mot de passe</span>
            <button type="button" onClick={() => setForgot(true)} className="text-xs text-gold hover:underline">Mot de passe oublié ?</button>
          </div>
          <PasswordInput icon={Lock} value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" />
        </div>
        <label className="flex cursor-pointer items-center gap-2.5 text-sm text-muted">
          <input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
          Se souvenir de moi
        </label>
        {error && <p className="rounded-lg border border-danger/40 bg-danger/10 px-3 py-2 text-sm text-danger" role="alert">{error}</p>}
        <button className="btn-gold w-full !py-3.5 text-xs font-bold uppercase tracking-wider shadow-[0_0_24px_rgba(207,189,151,.25)]">
          Se connecter <ArrowRight size={15} />
        </button>
        <p className="text-center text-xs text-muted">
          Pas encore de compte ? <button type="button" onClick={() => onSwitch('register')} className="font-bold text-gold">Inscrivez-vous ici</button>
        </p>
      </form>

      {import.meta.env.DEV && (
        <div className="mt-6 border-t border-line pt-4">
          <p className="mb-2 text-[11px] uppercase tracking-wider text-muted">Accès démo (mode dev)</p>
          <div className="flex flex-wrap gap-2">
            {DEMO_USERS.map((u) => (
              <button key={u.id} type="button" onClick={() => demo(u)} className="rounded-md border border-line px-2.5 py-1 text-[11px] text-muted hover:border-gold hover:text-gold">{u.role}</button>
            ))}
          </div>
        </div>
      )}

      <Modal open={forgot} onClose={() => setForgot(false)} title="Mot de passe oublié" subtitle="Nous vous envoyons un lien valable 1 heure." size="sm">
        <ForgotForm onDone={() => setForgot(false)} />
      </Modal>
    </>
  )
}

function ForgotForm({ onDone }) {
  const { toast } = useApp()
  const [email, setEmail] = useState('')
  const send = (e) => {
    e.preventDefault()
    if (!isEmail(email)) return toast('Email invalide', 'error')
    toast('Lien de récupération envoyé (si le compte existe).')
    onDone()
  }
  return (
    <form onSubmit={send} className="space-y-4">
      <input className="field" type="email" placeholder="etudiant@ucac-icam.com" value={email} onChange={(e) => setEmail(e.target.value)} />
      <button className="btn-gold w-full">Envoyer le lien</button>
    </form>
  )
}

function RegisterForm() {
  const { register } = useApp()
  const navigate = useNavigate()
  const [f, setF] = useState({ name: '', phone: '', email: '', place: '', password: '', sponsorCode: '' })
  const [errors, setErrors] = useState({})
  const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }))

  const submit = (e) => {
    e.preventDefault()
    const err = {}
    if (f.name.trim().length < 2) err.name = 'Indiquez votre nom.'
    if (f.phone.replace(/\D/g, '').length < 9) err.phone = 'Numéro invalide.'
    if (!isEmail(f.email)) err.email = 'Email invalide.'
    if (!f.place) err.place = 'Choisissez un lieu.'
    if (!isStrongPassword(f.password)) err.password = '8 caractères minimum, 1 majuscule et 1 chiffre.'
    setErrors(err)
    if (Object.keys(err).length) return
    const res = register(f)
    if (!res.ok) return setErrors({ email: res.error })
    navigate('/mon-espace')
  }

  return (
    <form onSubmit={submit} className="space-y-5" noValidate>
      <div className="grid grid-cols-2 gap-4">
        <Field label="Comment tu t'appelles ?" error={errors.name}><input className="field" placeholder="Marc Dupont" value={f.name} onChange={set('name')} /></Field>
        <Field label="Ton numéro WhatsApp" error={errors.phone}><input className="field" placeholder="699 00 00 00" value={f.phone} onChange={set('phone')} inputMode="tel" /></Field>
      </div>
      <Field label="Ton e-mail étudiant" error={errors.email}><input className="field" type="email" placeholder="etudiant@ucac-icam.com" value={f.email} onChange={set('email')} /></Field>
      <Field label="Où tu veux être livré ?" error={errors.place}>
        <select className="field" value={f.place} onChange={set('place')}>
          <option value="">Choisis un lieu...</option>
          {DELIVERY_PLACES.map((p) => <option key={p}>{p}</option>)}
        </select>
      </Field>
      <Field label="Un mot de passe solide" error={errors.password}>
        <PasswordInput value={f.password} onChange={set('password')} placeholder="8 caractères, 1 majuscule, 1 chiffre" autoComplete="new-password" />
      </Field>
      <Field label={<>Code de ton parrain <span className="text-[#6b6b6b]">(facultatif)</span></>}>
        <input className="field" placeholder="EX: ZEDUC-A98X2" value={f.sponsorCode} onChange={set('sponsorCode')} />
      </Field>
      <button className="btn-gold w-full !py-3.5">Rejoindre la famille</button>
    </form>
  )
}

export default function AuthPage({ mode: initial }) {
  const { user } = useApp()
  const navigate = useNavigate()
  const mode = initial
  if (user) return <Navigate to={HOME_BY_ROLE[user.role]} replace />
  const change = (m) => navigate(m === 'login' ? '/connexion' : '/inscription')

  return (
    <AuthLayout>
      <div className="w-full max-w-[380px] rounded-2xl border border-line bg-[#141414] p-7 shadow-2xl shadow-black">
        {mode === 'login' ? (
          <>
            <h2 className="text-2xl font-bold">Bienvenue</h2>
            <p className="mt-2 text-xs leading-relaxed text-muted">Connectez-vous à votre espace étudiant ou créez votre compte en 1 minute.</p>
          </>
        ) : (
          <>
            <h2 className="text-xl font-bold">Bienvenue à la Terrasse</h2>
            <p className="mt-2 text-xs text-muted">Connecte-toi ou rejoins la famille ZeDuc.</p>
          </>
        )}
        <div className="my-6"><Tabs mode={mode} onChange={change} /></div>
        {mode === 'login' ? <LoginForm onSwitch={change} /> : <RegisterForm />}
      </div>
      <Link to="/" className="sr-only">Accueil</Link>
    </AuthLayout>
  )
}
