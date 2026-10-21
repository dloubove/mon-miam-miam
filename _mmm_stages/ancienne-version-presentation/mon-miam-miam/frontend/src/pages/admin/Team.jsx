import { useState } from 'react'
import { Pencil, Trash2, UserPlus } from 'lucide-react'
import { Field, Modal, PageTitle } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { isEmail } from '../../utils/format.js'

const ROLE_LABEL = { gerant: 'Gérant', employe: 'Employé', administrateur: 'Administrateur' }

function MemberModal({ member, open, onClose }) {
  const { addMember, updateMember, removeMember, toast, user } = useApp()
  const [f, setF] = useState({ firstName: '', lastName: '', email: '', phone: '', role: 'employe' })
  const [key, setKey] = useState(null)
  const k = open ? (member?.id ?? 'new') : null
  if (k !== key) {
    setKey(k)
    if (open) setF(member ? { firstName: member.name, lastName: '', email: member.email, phone: member.phone || '', role: member.role } : { firstName: '', lastName: '', email: '', phone: '', role: 'employe' })
  }
  const submit = async (e) => {
    e.preventDefault()
    if (!f.firstName.trim() || !isEmail(f.email) || !f.phone.trim()) return toast('Nom, téléphone et email valides requis', 'error')
    const result = member
      ? await updateMember(member.id, { name: `${f.firstName} ${f.lastName}`.trim(), email: f.email, phone: f.phone, role: f.role })
      : await addMember(f)
    if (!result?.ok) return toast(result?.error || 'La modification a échoué.', 'error')
    toast(member ? 'Membre modifié' : 'Compte créé. Le lien de définition du mot de passe dépend de la configuration mail.')
    onClose()
  }
  return (
    <Modal open={open} onClose={onClose} title={member ? 'Modifier le membre' : 'Ajouter un membre'} subtitle={member ? null : 'Crée un accès pour un nouvel employé.'} size="lg">
      <form onSubmit={submit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Prénom"><input className="field" placeholder="Prénom" value={f.firstName} onChange={(e) => setF({ ...f, firstName: e.target.value })} /></Field>
          <Field label="Nom"><input className="field" placeholder="Nom" value={f.lastName} onChange={(e) => setF({ ...f, lastName: e.target.value })} /></Field>
        </div>
        <Field label="Adresse E-mail pro"><input className="field" type="email" placeholder="employe@zeduc.com" value={f.email} onChange={(e) => setF({ ...f, email: e.target.value })} /></Field>
        <Field label="Téléphone"><input className="field" type="tel" placeholder="+237 6XX XX XX XX" value={f.phone} onChange={(e) => setF({ ...f, phone: e.target.value })} /></Field>
        <div>
          <span className="field-label">Rôle dans le restaurant</span>
          <div className="grid gap-4 sm:grid-cols-2">
            {[['employe', 'Staff (Cuisine)'], ...(user?.role === 'administrateur' ? [['gerant', 'Gérant'], ['administrateur', 'Administrateur']] : [])].map(([k2, l]) => (
              <label key={k2} className={`flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm ${f.role === k2 ? 'border-gold/60 bg-[#1b1912] font-bold text-gold' : 'border-line bg-black'}`}>
                <input type="radio" name="role" className="accent-[#cfbd97]" checked={f.role === k2} onChange={() => setF({ ...f, role: k2 })} /> {l}
              </label>
            ))}
          </div>
        </div>
        <button className="btn-gold w-full !py-3">{member ? 'Enregistrer' : "Créer l'accès"}</button>
        {member && user?.role === 'administrateur' && (
          <button type="button" className="btn-danger w-full" onClick={async () => { if (confirm(`Désactiver le compte de ${member.name} ? Son historique sera conservé.`)) { const result = await removeMember(member.id); if (result?.ok) { toast('Compte désactivé'); onClose() } else toast(result?.error || 'La désactivation a échoué.', 'error') } }}><Trash2 size={14} /> Désactiver ce compte</button>
        )}
      </form>
    </Modal>
  )
}

export default function Team() {
  const { team, user } = useApp()
  const [editing, setEditing] = useState(undefined)
  return (
    <>
      <PageTitle title="L'équipe du restau" subtitle="Gérez les accès de vos employés et gérants." action={<button className="btn-gold" onClick={() => setEditing(null)}><UserPlus size={15} /> Nouveau membre</button>} />
      <div className="panel overflow-x-auto">
        <table className="w-full min-w-[620px] text-sm">
          <thead><tr className="border-b border-line bg-surface-2 text-muted"><th className="px-6 py-4 text-left font-normal">Employé</th><th className="px-6 py-4 text-left font-normal">Rôle</th><th className="px-6 py-4 text-left font-normal">Statut</th><th className="px-6 py-4 text-right font-normal">Actions</th></tr></thead>
          <tbody>
            {team.map((m) => (
              <tr key={m.id} className="border-b border-line last:border-0">
                <td className="px-6 py-4"><p>{m.name}</p><p className="text-xs text-muted">{m.email}</p></td>
                <td className="px-6 py-4"><span className={`rounded px-2.5 py-1 text-xs font-bold ${m.role === 'gerant' ? 'bg-[#2a251a] text-gold' : 'bg-surface-3'}`}>{ROLE_LABEL[m.role]}</span></td>
                <td className={`px-6 py-4 text-xs ${m.online ? 'text-ok' : 'text-ok/80'}`}>{m.presence}</td>
                <td className="px-6 py-4 text-right">{user?.role === 'administrateur' && <button aria-label={`Modifier ${m.name}`} onClick={() => setEditing(m)} className="text-muted hover:text-white"><Pencil size={14} /></button>}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <MemberModal open={editing !== undefined} member={editing} onClose={() => setEditing(undefined)} />
    </>
  )
}
