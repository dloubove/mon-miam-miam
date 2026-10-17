import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ArrowLeft, IdCard, Info, Lock, MapPin, Power, Shield, Trash2, TriangleAlert, User } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { Field, Modal } from '../components/ui.jsx'
import { DELIVERY_PLACES } from '../data/mock.js'
import { isEmail, isStrongPassword } from '../utils/format.js'

function Card({ icon: Icon, title, subtitle, badge, children }) {
  return (
    <section className="panel mb-6 overflow-hidden">
      <div className="flex items-center justify-between border-b border-line bg-surface-2 px-6 py-4">
        <div>
          <h2 className="flex items-center gap-2.5 text-[15px] font-bold">{Icon && <Icon size={15} className="text-gold" />}{title}</h2>
          {subtitle && <p className="mt-1 text-xs text-muted">{subtitle}</p>}
        </div>
        {badge && <span className="rounded border border-line bg-surface-3 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider">{badge}</span>}
      </div>
      <div className="p-6">{children}</div>
    </section>
  )
}

function SecurityForm({ confirmLabel = 'Confirmer le nouveau mot de passe', newPlaceholder = '8 caractères minimum', buttonLabel = 'Mettre à jour le mot de passe' }) {
  const { user, updateUser, toast } = useApp()
  const [cur, setCur] = useState('')
  const [n1, setN1] = useState('')
  const [n2, setN2] = useState('')
  const [err, setErr] = useState('')
  const submit = (e) => {
    e.preventDefault()
    if (user.password && cur !== user.password) return setErr('Mot de passe actuel incorrect.')
    if (!cur) return setErr('Saisissez votre mot de passe actuel.')
    if (!isStrongPassword(n1)) return setErr('8 caractères minimum, 1 majuscule et 1 chiffre.')
    if (n1 !== n2) return setErr('Les deux mots de passe ne correspondent pas.')
    updateUser({ password: n1 })
    setCur(''); setN1(''); setN2(''); setErr('')
    toast('Mot de passe mis à jour')
  }
  return (
    <form onSubmit={submit} className="space-y-4">
      <Field label="Mot de passe actuel"><input type="password" className="field" value={cur} onChange={(e) => setCur(e.target.value)} placeholder="••••••••" autoComplete="current-password" /></Field>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Nouveau mot de passe"><input type="password" className="field" value={n1} onChange={(e) => setN1(e.target.value)} placeholder={newPlaceholder} autoComplete="new-password" /></Field>
        <Field label={confirmLabel}><input type="password" className="field" value={n2} onChange={(e) => setN2(e.target.value)} placeholder="Répéter le mot de passe" autoComplete="new-password" /></Field>
      </div>
      {err && <p className="text-sm text-danger" role="alert">{err}</p>}
      <button className="btn-gold">{buttonLabel}</button>
    </form>
  )
}

export default function ProfilePage({ variant }) {
  const { user, updateUser, logout, deleteAccount, toast } = useApp()
  const navigate = useNavigate()
  const [info, setInfo] = useState({ firstName: user.firstName, lastName: user.lastName || '', email: user.email, phone: user.phone || '' })
  const [addr, setAddr] = useState({ place: user.place || DELIVERY_PLACES[0], residence: user.residence || '' })
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [agree, setAgree] = useState(false)
  const set = (k) => (e) => setInfo((s) => ({ ...s, [k]: e.target.value }))

  const saveInfo = (e) => {
    e.preventDefault()
    if (!info.firstName.trim() || !isEmail(info.email)) return toast('Vérifiez le prénom et l\'email', 'error')
    updateUser(info)
    toast('Informations enregistrées')
  }
  const out = () => { navigate('/', { replace: true }); logout(); toast('Vous êtes déconnecté. À bientôt !') }

  const infoForm = ({ phoneLabel, emailLabel = 'Adresse E-mail (Identifiant)', btn = 'Enregistrer les infos', hint }) => (
    <form onSubmit={saveInfo} className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Prénom"><input className="field" value={info.firstName} onChange={set('firstName')} /></Field>
        <Field label="Nom"><input className="field" value={info.lastName} onChange={set('lastName')} /></Field>
      </div>
      <Field label={emailLabel}>
        <input className="field" type="email" value={info.email} onChange={set('email')} />
        {hint && <span className="mt-1.5 flex items-center gap-1.5 text-xs text-muted"><Info size={12} />{hint}</span>}
      </Field>
      <Field label={phoneLabel}><input className="field" value={info.phone} onChange={set('phone')} /></Field>
      <button className="btn-dark">{btn}</button>
    </form>
  )

  if (variant === 'direction') {
    const admin = user.role === 'administrateur'
    return (
      <div className="mx-auto max-w-[640px]">
        <h1 className="text-3xl font-bold">Paramètres du Compte</h1>
        <p className="mb-8 mt-2 text-muted">Gérez vos informations de connexion et la sécurité du compte {admin ? 'direction' : 'gérant'}.</p>
        <Card icon={IdCard} title="Informations du Gérant" badge={admin ? 'Super Admin' : 'Accès restreint'}>
          {infoForm({ emailLabel: "Adresse E-mail (Identifiant de connexion)", hint: "C'est l'e-mail utilisé pour vous connecter au tableau de bord.", phoneLabel: admin ? 'Numéro de téléphone (Contact pro)' : "Numéro de téléphone (Contact en cas d'urgence)", btn: 'Enregistrer les modifications' })}
        </Card>
        <Card icon={Shield} title="Sécurité">
          <SecurityForm confirmLabel="Confirmer le mot de passe" newPlaceholder={admin ? 'Au moins 12 caractères recommandés' : 'Nouveau mot de passe'} buttonLabel="Mettre à jour la sécurité" />
        </Card>
        <section className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-danger/30 bg-surface p-6">
          <div><h2 className="font-bold">Fin de session</h2><p className="mt-1 text-sm text-muted">{admin ? 'Déconnectez-vous toujours si vous utilisez un ordinateur partagé.' : 'Déconnectez-vous toujours après votre service.'}</p></div>
          <button className="btn-danger" onClick={out}><Power size={14} /> Me déconnecter</button>
        </section>
      </div>
    )
  }

  const staff = variant === 'employe'
  return (
    <div className={`mx-auto max-w-[580px] px-5 ${staff ? 'py-12' : 'py-12'}`}>
      <div className="mb-8 flex items-start gap-4">
        <Link to={staff ? '/staff' : '/mon-espace'} aria-label="Retour" className="grid h-10 w-10 place-items-center rounded-lg border border-line bg-surface hover:border-gold"><ArrowLeft size={16} /></Link>
        <div>
          <h1 className="text-3xl font-bold">Mon Profil</h1>
          <p className="mt-1 text-sm text-muted">{staff ? 'Gère tes informations personnelles et la sécurité de ton compte.' : 'Gère tes informations, tes adresses et la sécurité de ton compte.'}</p>
        </div>
      </div>

      <Card icon={staff ? null : User} title="Informations Personnelles" badge={staff ? (user.title || 'Cuisinier') : null}>
        {infoForm({ phoneLabel: staff ? 'Numéro de téléphone' : 'Numéro WhatsApp' })}
      </Card>

      {!staff && (
        <Card icon={MapPin} title="Préférences de livraison" subtitle="On pré-remplira ton panier avec ces informations.">
          <form onSubmit={(e) => { e.preventDefault(); updateUser(addr); toast('Adresse mise à jour') }} className="space-y-4">
            <Field label="Où manges-tu le plus souvent ?">
              <select className="field" value={addr.place} onChange={(e) => setAddr((a) => ({ ...a, place: e.target.value }))}>
                {DELIVERY_PLACES.map((p) => <option key={p}>{p}</option>)}
              </select>
            </Field>
            <Field label="Précisions (Nom de la résidence)" hint="Note : La livraison se fait uniquement à l'entrée de la résidence.">
              <input className="field" value={addr.residence} onChange={(e) => setAddr((a) => ({ ...a, residence: e.target.value }))} />
            </Field>
            <button className="btn-dark">Mettre à jour l'adresse</button>
          </form>
        </Card>
      )}

      <Card icon={staff ? null : Lock} title="Sécurité"><SecurityForm /></Card>

      {staff ? (
        <section className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-danger/30 bg-surface p-6">
          <div><h2 className="font-bold">Fin de service</h2><p className="mt-1 max-w-xs text-sm text-muted">N'oublie pas de te déconnecter si tu utilises un appareil partagé en cuisine.</p></div>
          <button className="btn-danger" onClick={out}><Power size={14} /> Me déconnecter</button>
        </section>
      ) : (
        <section className="rounded-xl border border-danger/30 bg-surface p-6">
          <h2 className="mb-4 flex items-center gap-2 font-bold text-danger"><TriangleAlert size={16} /> Zone de Danger</h2>
          <div className="flex flex-wrap gap-3">
            <button className="btn-dark" onClick={out}><Power size={14} /> Me déconnecter</button>
            <button className="btn-danger" onClick={() => setConfirmDelete(true)}><Trash2 size={14} /> Supprimer mon compte</button>
          </div>
        </section>
      )}

      <Modal open={confirmDelete} onClose={() => { setConfirmDelete(false); setAgree(false) }} tone="danger" title="Supprimer Compte">
        <p className="text-sm font-bold">Êtes-vous sûr de supprimer votre compte (vous risquez de perdre {user.points || 0} points) :</p>
        <label className="mt-5 flex cursor-pointer items-center gap-3 text-sm text-[#bbb]">
          <input type="checkbox" className="h-4 w-4 accent-[#cfbd97]" checked={agree} onChange={(e) => setAgree(e.target.checked)} /> Oui, je veux supprimer mon compte
        </label>
        <div className="mt-24 flex justify-center gap-6">
          <button className="btn-gold" onClick={() => { setConfirmDelete(false); setAgree(false) }}>Annuler</button>
          <button className="btn-outline !text-gold disabled:opacity-40" disabled={!agree} onClick={() => { navigate('/', { replace: true }); deleteAccount() }}>Valider</button>
        </div>
      </Modal>
    </div>
  )
}
