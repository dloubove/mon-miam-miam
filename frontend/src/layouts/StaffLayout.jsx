import { Link, NavLink, Outlet } from 'react-router-dom'
import { User } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'

const LINKS = [
  { to: '/staff', label: 'Commandes en cours', end: true },
  { to: '/staff/menu', label: 'Menu du jour' },
  { to: '/staff/reclamations', label: 'Réclamations' },
]

/** Layout de l'espace employé : barre supérieure « STAFF ZEDUC » */
export default function StaffLayout() {
  const { user } = useApp()
  return (
    <div className="min-h-screen bg-black">
      <header className="sticky top-0 z-30 border-b border-line bg-black/90 backdrop-blur">
        <div className="mx-auto flex h-[68px] max-w-[1500px] items-center justify-between gap-4 px-5 lg:px-8">
          <div className="flex items-center gap-8">
            <Link to="/staff" className="leading-tight">
              <span className="block text-[17px] font-extrabold tracking-wide">STAFF ZEDUC</span>
              <span className="block text-[10px] font-bold uppercase tracking-[0.16em] text-gold">Cuisine &amp; Comptoir</span>
            </Link>
            <span className="hidden h-8 w-px bg-line md:block" />
            <nav className="hidden items-center gap-6 md:flex">
              {LINKS.map((l) => (
                <NavLink key={l.to} to={l.to} end={l.end} className="nav-link">{l.label}</NavLink>
              ))}
            </nav>
          </div>
          <Link to="/staff/profil" className="flex items-center gap-3" aria-label="Mon profil">
            <span className="text-right leading-tight">
              <span className="block text-sm font-semibold">{user?.firstName}</span>
              <span className="block text-xs text-muted">{user?.shift || 'Service du midi'}</span>
            </span>
            <span className="grid h-10 w-10 place-items-center rounded-lg border border-line bg-surface-3 text-gold">
              <User size={17} />
            </span>
          </Link>
        </div>
        {/* navigation mobile */}
        <nav className="flex gap-5 overflow-x-auto border-t border-line px-5 py-2.5 md:hidden">
          {LINKS.map((l) => (
            <NavLink key={l.to} to={l.to} end={l.end} className="nav-link whitespace-nowrap">{l.label}</NavLink>
          ))}
        </nav>
      </header>
      <Outlet />
    </div>
  )
}
