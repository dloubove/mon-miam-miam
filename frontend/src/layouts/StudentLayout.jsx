import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { ShoppingCart } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { Avatar, Logo } from '../components/ui.jsx'

const HOME_LINKS = [
  { label: 'Menu', href: '/#menu' },
  { label: 'Événements', href: '/#evenements' },
  { label: 'Fidélité', href: '/#fidelite' },
  { label: 'Habitués', href: '/#habitues' },
  { label: 'Mini-Jeux', to: '/jeux' },
]

function CartButton() {
  const { cartCount } = useApp()
  return (
    <Link to="/panier" aria-label="Panier" className="relative grid h-9 w-9 place-items-center rounded-lg border border-line bg-surface text-white transition hover:border-gold">
      <ShoppingCart size={16} />
      <span className="absolute -right-1.5 -top-2 grid h-4 min-w-4 place-items-center rounded-full bg-gold px-1 text-[10px] font-bold text-black">
        {cartCount}
      </span>
    </Link>
  )
}

/** Barre de navigation publique / étudiant (accueil) */
export function HomeNavbar() {
  const { user } = useApp()
  const goto = (e, href) => {
    // navigation par ancre sur la page d'accueil
    if (window.location.pathname === '/') {
      e.preventDefault()
      document.getElementById(href.split('#')[1])?.scrollIntoView({ behavior: 'smooth' })
    }
  }
  return (
    <header className="sticky top-0 z-30 border-b border-line bg-black/90 backdrop-blur">
      <div className="mx-auto flex h-[78px] max-w-[1440px] items-center justify-between gap-4 px-5 lg:px-10">
        <Logo />
        <nav className="hidden items-center gap-9 lg:flex">
          {HOME_LINKS.map((l) =>
            l.to ? (
              <Link key={l.label} to={l.to} className="nav-link">{l.label}</Link>
            ) : (
              <a key={l.label} href={l.href} onClick={(e) => goto(e, l.href)} className="nav-link">{l.label}</a>
            ),
          )}
        </nav>
        <div className="flex items-center gap-3 sm:gap-4">
          {user ? (
            <Link to={user.role === 'etudiant' ? '/mon-espace' : user.role === 'employe' ? '/staff' : '/direction'} className="flex items-center gap-2.5 text-sm text-white">
              <Avatar name={`${user.firstName} ${user.lastName || ''}`} />
              <span className="hidden sm:inline">{user.firstName}</span>
            </Link>
          ) : (
            <Link to="/connexion" className="text-sm text-white hover:text-gold">Mon compte</Link>
          )}
          <Link to="/#menu" className="btn-gold hidden sm:inline-flex">Commander</Link>
          <CartButton />
        </div>
      </div>
    </header>
  )
}

/** Barre de navigation de l'espace étudiant (Accueil / Mon Espace / Jeux) */
export function StudentNavbar({ gamesLabel = 'La Récré (Jeux)' }) {
  const { user } = useApp()
  const loc = useLocation()
  const inGames = loc.pathname.startsWith('/jeux')
  return (
    <header className="sticky top-0 z-30 border-b border-line bg-black/90 backdrop-blur">
      <div className="mx-auto flex h-[68px] max-w-[1200px] items-center justify-between gap-4 px-5 lg:px-8">
        <Logo />
        <nav className="hidden items-center gap-10 md:flex">
          <NavLink to="/" end className="nav-link">Accueil</NavLink>
          <NavLink to="/mon-espace" className="nav-link">Mon Espace</NavLink>
          {inGames && <NavLink to="/jeux" className="nav-link">{gamesLabel}</NavLink>}
        </nav>
        <div className="flex items-center gap-3">
          <Link to="/mon-espace/profil" className="flex items-center gap-2.5 text-sm font-medium text-white">
            <Avatar name={`${user?.firstName} ${user?.lastName || ''}`} gold={inGames} square={inGames} />
            <span className="hidden sm:inline">{user?.firstName}</span>
          </Link>
          <CartButton />
        </div>
      </div>
    </header>
  )
}

export function SiteFooter({ full = false }) {
  if (full) {
    return (
      <footer className="border-t border-line py-10 text-center">
        <p className="text-lg font-bold tracking-wide">ZEDUC@SPACE</p>
        <p className="mt-2 text-sm text-muted">Créé pour les étudiants de l'UCAC-ICAM.</p>
      </footer>
    )
  }
  return (
    <footer className="mt-20 border-t border-line py-9 text-center text-sm text-muted">
      © 2026 ZeDuc@Space.
    </footer>
  )
}

/** Layout des pages étudiantes (Mon espace, profil, jeux…) */
export default function StudentLayout() {
  return (
    <div className="flex min-h-screen flex-col bg-black">
      <StudentNavbar />
      <main className="flex-1">
        <Outlet />
      </main>
      <SiteFooter />
    </div>
  )
}
