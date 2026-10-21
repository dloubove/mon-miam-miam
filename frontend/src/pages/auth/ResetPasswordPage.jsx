import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Field, Logo } from '../../components/ui.jsx'
import { apiRequest } from '../../services/api.js'
import { isStrongPassword } from '../../utils/format.js'

export default function ResetPasswordPage() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const email = params.get('email') || ''
  const token = params.get('jeton') || ''

  const submit = async (event) => {
    event.preventDefault()
    setError('')
    if (!email || !token) return setError('Lien incomplet. Demandez un nouveau lien de réinitialisation.')
    if (!isStrongPassword(password)) return setError('8 caractères minimum, une majuscule et un chiffre sont requis.')
    if (password !== confirmation) return setError('Les deux mots de passe ne correspondent pas.')
    setBusy(true)
    try {
      await apiRequest('/auth/reinitialiser-mot-de-passe', { method: 'POST', body: { email, jeton: token, mot_de_passe: password, mot_de_passe_confirmation: confirmation } })
      navigate('/connexion', { replace: true, state: { message: 'Mot de passe modifié. Vous pouvez vous connecter.' } })
    } catch (problem) { setError(problem.message) }
    finally { setBusy(false) }
  }

  return <main className="grid min-h-screen place-items-center bg-black px-5 py-12">
    <section className="panel w-full max-w-md p-7">
      <Logo />
      <h1 className="mt-8 text-2xl font-bold">Nouveau mot de passe</h1>
      <p className="mt-2 text-sm text-muted">Choisissez un mot de passe pour {email || 'votre compte'}.</p>
      {error && <p role="alert" className="mt-5 rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger">{error}</p>}
      <form onSubmit={submit} className="mt-6 space-y-4">
        <Field label="Nouveau mot de passe"><input className="field" type="password" autoComplete="new-password" value={password} onChange={(event) => setPassword(event.target.value)} required /></Field>
        <Field label="Confirmer le mot de passe"><input className="field" type="password" autoComplete="new-password" value={confirmation} onChange={(event) => setConfirmation(event.target.value)} required /></Field>
        <button className="btn-gold w-full" disabled={busy}>{busy ? 'Modification…' : 'Modifier le mot de passe'}</button>
      </form>
      <Link to="/connexion" className="mt-5 block text-center text-sm text-gold hover:underline">Retour à la connexion</Link>
    </section>
  </main>
}
