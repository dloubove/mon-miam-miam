import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ChevronDown, LayoutDashboard, LogOut, User } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { HOME_BY_ROLE } from '../data/mock.js'

const PROFILE_BY_ROLE = {
  etudiant: '/mon-espace/profil',
  employe: '/staff/profil',
  gerant: '/direction/profil',
  administrateur: '/direction/profil',
}
const SPACE_LABEL = { etudiant: 'Mon espace', employe: 'Espace staff', gerant: 'Tableau de bord', administrateur: 'Tableau de bord' }

/** Bouton de déconnexion rapide : accessible d'un clic sur l'avatar, pour tous les profils. */
export default function UserMenu({ children, align = 'right' }) {
  const { user, logout, toast } = useApp()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    if (!open) return
    const onDown = (e) => !ref.current?.contains(e.target) && setOpen(false)
    const onKey = (e) => e.key === 'Escape' && setOpen(false)
    document.addEventListener('mousedown', onDown)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onDown)
      document.removeEventListener('keydown', onKey)
    }
  }, [open])

  const signOut = () => {
    setOpen(false)
    navigate('/', { replace: true })
    logout()
    toast('Vous êtes déconnecté. À bientôt !')
  }

  const item = 'flex w-full items-center gap-3 px-4 py-2.5 text-sm text-[#d0d0d0] transition hover:bg-surface-3 hover:text-white'

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        aria-haspopup="menu"
        aria-expanded={open}
        className="flex cursor-pointer items-center gap-2.5 rounded-lg text-sm text-white"
      >
        {children}
        <ChevronDown size={14} className={`text-muted transition ${open ? 'rotate-180' : ''}`} />
      </button>
      {open && (
        <div role="menu" className={`anim-pop absolute top-[calc(100%+10px)] z-50 w-56 overflow-hidden rounded-xl border border-line bg-surface shadow-2xl shadow-black ${align === 'right' ? 'right-0' : 'left-0'}`}>
          <div className="border-b border-line px-4 py-3">
            <p className="truncate text-sm font-semibold">{user.firstName} {user.lastName}</p>
            <p className="truncate text-xs text-muted">{user.email}</p>
          </div>
          <Link role="menuitem" to={HOME_BY_ROLE[user.role]} onClick={() => setOpen(false)} className={item}><LayoutDashboard size={14} /> {SPACE_LABEL[user.role]}</Link>
          <Link role="menuitem" to={PROFILE_BY_ROLE[user.role]} onClick={() => setOpen(false)} className={item}><User size={14} /> Mon profil</Link>
          <button role="menuitem" onClick={signOut} className={`${item} cursor-pointer border-t border-line !text-danger hover:!bg-danger/10`}><LogOut size={14} /> Se déconnecter</button>
        </div>
      )}
    </div>
  )
}
