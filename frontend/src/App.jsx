import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom'
import { useEffect } from 'react'
import { useApp } from './context/AppContext.jsx'
import { HOME_BY_ROLE } from './data/mock.js'
import { AddedToCartModal, CookieBanner, ToastHost } from './components/Overlays.jsx'

import StudentLayout from './layouts/StudentLayout.jsx'
import StaffLayout from './layouts/StaffLayout.jsx'
import DirectionLayout from './layouts/DirectionLayout.jsx'

import Home from './pages/Home.jsx'
import AuthPage from './pages/auth/AuthPage.jsx'
import ResetPasswordPage from './pages/auth/ResetPasswordPage.jsx'
import CartPage from './pages/student/CartPage.jsx'
import StudentSpace from './pages/student/StudentSpace.jsx'
import ProfilePage from './pages/ProfilePage.jsx'
import GamesHome from './pages/games/GamesHome.jsx'
import WheelGame from './pages/games/WheelGame.jsx'
import GuessGame from './pages/games/GuessGame.jsx'
import StaffOrders from './pages/staff/StaffOrders.jsx'
import StaffMenu from './pages/staff/StaffMenu.jsx'
import StaffClaims from './pages/staff/StaffClaims.jsx'
import StaffStats from './pages/staff/StaffStats.jsx'
import ReferralsPage from './pages/student/ReferralsPage.jsx'
import Dashboard from './pages/admin/Dashboard.jsx'
import Catalogue from './pages/admin/Catalogue.jsx'
import Team from './pages/admin/Team.jsx'
import Marketing from './pages/admin/Marketing.jsx'
import MenuPage from './pages/MenuPage.jsx'
import NotFound from './pages/NotFound.jsx'

/** Route protégée : redirige vers la connexion, ou vers l'espace du rôle si accès refusé (403). */
function RequireRole({ roles }) {
  const { user, loggedOut } = useApp()
  const location = useLocation()
  if (!user && loggedOut) return <Navigate to="/" replace />
  if (!user) return <Navigate to="/connexion" replace state={{ from: location.pathname }} />
  if (!roles.includes(user.role)) return <Navigate to={HOME_BY_ROLE[user.role]} replace />
  return <Outlet />
}

function ScrollToTop() {
  const { pathname, hash } = useLocation()
  useEffect(() => {
    if (hash) {
      setTimeout(() => document.getElementById(hash.slice(1))?.scrollIntoView({ behavior: 'smooth' }), 50)
    } else {
      window.scrollTo(0, 0)
    }
  }, [pathname, hash])
  return null
}

export default function App() {
  return (
    <>
      <ScrollToTop />
      <Routes>
        {/* Public */}
        <Route path="/" element={<Home />} />
        <Route path="/connexion" element={<AuthPage mode="login" />} />
        <Route path="/inscription" element={<AuthPage mode="register" />} />
        <Route path="/reinitialisation" element={<ResetPasswordPage />} />
        <Route path="/menu/:type" element={<MenuPage />} />
        <Route path="/panier" element={<CartPage />} />

        {/* Étudiant */}
        <Route element={<RequireRole roles={['etudiant']} />}>
          <Route element={<StudentLayout />}>
            <Route path="/mon-espace" element={<StudentSpace />} />
            <Route path="/mon-espace/profil" element={<ProfilePage variant="etudiant" />} />
            <Route path="/mon-espace/parrainage" element={<ReferralsPage />} />
            <Route path="/jeux" element={<GamesHome />} />
          </Route>
          <Route path="/jeux/roue" element={<WheelGame />} />
          <Route path="/jeux/devine-le-plat" element={<GuessGame />} />
        </Route>

        {/* Employé */}
        <Route element={<RequireRole roles={['employe']} />}>
          <Route element={<StaffLayout />}>
            <Route path="/staff" element={<StaffOrders />} />
            <Route path="/staff/menu" element={<StaffMenu />} />
            <Route path="/staff/reclamations" element={<StaffClaims />} />
            <Route path="/staff/statistiques" element={<StaffStats />} />
            <Route path="/staff/profil" element={<ProfilePage variant="employe" />} />
          </Route>
        </Route>

        {/* Gérant + administrateur */}
        <Route element={<RequireRole roles={['gerant', 'administrateur']} />}>
          <Route element={<DirectionLayout />}>
            <Route path="/direction" element={<Dashboard />} />
            <Route path="/direction/commandes" element={<StaffOrders />} />
            <Route path="/direction/statistiques" element={<StaffStats />} />
            <Route path="/direction/reclamations" element={<StaffClaims />} />
            <Route path="/direction/catalogue" element={<Catalogue />} />
            <Route path="/direction/marketing" element={<Marketing />} />
            <Route path="/direction/equipe" element={<Team />} />
            <Route path="/direction/profil" element={<ProfilePage variant="direction" />} />
          </Route>
        </Route>

        <Route path="*" element={<NotFound />} />
      </Routes>

      <AddedToCartModal />
      <CookieBanner />
      <ToastHost />
    </>
  )
}
