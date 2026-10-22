import { useState } from 'react'
import { NavLink, Outlet, Link, useNavigate } from 'react-router-dom'
import { Activity, BookOpen, ChartPie, ChevronRight, ClipboardList, LogOut, Megaphone, MessageSquareWarning, Menu, UserCog, Users, X } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { Avatar } from '../components/ui.jsx'

/** Sidebar « DIRECTION » partagée par l'administrateur et le gérant (accès restreint). */
export default function DirectionLayout() {
  const { user, logout, toast } = useApp()
  const navigate = useNavigate()
  const signOut = () => { navigate('/', { replace: true }); logout(); toast('Vous êtes déconnecté. À bientôt !') }
  const isAdmin = user?.role === 'administrateur'
  const [open, setOpen] = useState(false)

  const links = [
    { to: '/direction', label: 'Tableau de bord', icon: ChartPie, end: true },
    { to: '/direction/commandes', label: 'Supervision des commandes', icon: ClipboardList },
    { to: '/direction/statistiques', label: 'Statistiques', icon: Activity },
    { to: '/direction/reclamations', label: 'Réclamations', icon: MessageSquareWarning },
    { to: '/direction/catalogue', label: 'Le Grand Catalogue', icon: BookOpen },
    { to: '/direction/equipe', label: "L'Équipe", icon: Users },
    { to: '/direction/marketing', label: 'Marketing & Règles', icon: Megaphone },
  ]

  const sidebar = (
    <aside className="flex h-full w-[260px] flex-col border-r border-line bg-surface-2">
      <div className="flex items-center gap-3 border-b border-line px-5 py-5">
        <img src="/images/logo-zeduc-space.png" alt="Logo ZeDuc@Space" className="h-12 w-12 shrink-0 object-contain" width="48" height="48" />
        <div className="leading-tight">
          <p className="text-[15px] font-bold">DIRECTION</p>
          <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-gold">ZeDuc@Space</p>
        </div>
      </div>
      <nav className="flex flex-1 flex-col gap-1.5 p-3">
        {links.map(({ to, label, icon: Icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            onClick={() => setOpen(false)}
            className={({ isActive }) =>
              `flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm transition ${
                isActive ? 'bg-gold font-semibold text-black' : 'text-[#b5b5b5] hover:bg-surface-3 hover:text-white'
              }`
            }
          >
            <Icon size={16} /> {label}
          </NavLink>
        ))}
        <div className="mt-2 border-t border-line pt-3">
          <button onClick={signOut} className="flex w-full cursor-pointer items-center border border-danger/30 bg-danger/5 gap-3 rounded-lg px-4 py-2.5 text-sm font-medium text-danger transition hover:bg-danger/10">
          <LogOut size={16} /> Se déconnecter
        </button>
        </div>
      </nav>
      <div className="p-3">
        <Link
          to="/direction/profil"
          onClick={() => setOpen(false)}
          className="flex items-center gap-3 rounded-lg border border-line bg-surface p-3 transition hover:border-gold/60"
        >
          <Avatar name={`${user?.firstName} ${user?.lastName || ''}`} square gold />
          <span className="flex-1 leading-tight">
            <span className="block text-sm font-semibold">{isAdmin ? 'Administrateur' : `${user?.firstName} ${user?.lastName}`}</span>
            <span className="flex items-center gap-1 text-[11px] text-gold">
              {!isAdmin && <UserCog size={11} />}
              {isAdmin ? 'Accès total' : 'Gérant'}
            </span>
          </span>
          <ChevronRight size={15} className="text-muted" />
        </Link>
      </div>
    </aside>
  )

  return (
    <div className="flex min-h-screen bg-black">
      <div className="sticky top-0 hidden h-screen lg:block">{sidebar}</div>

      {/* tiroir mobile */}
      {open && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-black/70" onClick={() => setOpen(false)} />
          <div className="relative h-full w-[260px]">{sidebar}</div>
        </div>
      )}

      <div className="min-w-0 flex-1">
        <div className="flex items-center justify-between border-b border-line px-4 py-3 lg:hidden">
          <button onClick={() => setOpen((o) => !o)} aria-label="Menu" className="text-white">
            {open ? <X size={22} /> : <Menu size={22} />}
          </button>
          <span className="text-sm font-bold">DIRECTION</span>
          <button onClick={signOut} aria-label="Se déconnecter" className="text-danger"><LogOut size={20} /></button>
        </div>
        <main className="mx-auto max-w-[1200px] px-5 py-10 lg:px-10">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
