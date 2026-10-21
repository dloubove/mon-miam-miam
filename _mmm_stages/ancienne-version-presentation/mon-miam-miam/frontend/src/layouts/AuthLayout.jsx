import { Link } from 'react-router-dom'
import { ArrowLeft, Bike, Coins, Gift, MapPin, Tv } from 'lucide-react'
import { Logo } from '../components/ui.jsx'

const PERKS = [
  { icon: Coins, title: '1000 FCFA = 1 pt', text: 'Cumulez des réductions automatiques sur chaque plat.' },
  { icon: Gift, title: 'Parrainage direct', text: 'Invitez des camarades et gagnez des bonus crédités.' },
  { icon: Tv, title: 'Soirées Canal+', text: 'Diffusion de la Champions League sur grand écran.' },
  { icon: Bike, title: 'Livraison rapide', text: 'Directement au campus ou en résidence universitaire.' },
]

/** Écran scindé : présentation à gauche, formulaire à droite (connexion / inscription). */
export default function AuthLayout({ children }) {
  return (
    <div className="flex min-h-screen flex-col bg-black font-display">
      <header className="flex h-[58px] items-center justify-between border-b border-line px-5 lg:px-10">
        <Logo variant="ring" subtitle="MON MIAM MIAM" />
        <Link to="/" className="btn-dark !px-4 !py-2 !text-xs">
          <ArrowLeft size={13} /> Retour à la carte
        </Link>
      </header>

      <div className="grid flex-1 lg:grid-cols-[1.15fr_1fr]">
        <section className="relative hidden overflow-hidden border-r border-line lg:block">
          <div
            className="absolute inset-0 bg-[radial-gradient(ellipse_at_30%_45%,#2a241a_0%,#0b0a08_55%,#000_100%)]"
            aria-hidden="true"
          />
          <div className="relative flex h-full flex-col px-14 py-14">
            <span className="inline-flex w-fit items-center gap-2 rounded-full border border-gold/40 bg-black/50 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gold">
              <span className="h-1.5 w-1.5 rounded-full bg-gold" /> Espace restauration &amp; détente UCAC-ICAM
            </span>
            <h1 className="mt-7 text-5xl font-extrabold leading-[1.05] tracking-tight">
              The Climate of
              <br />
              <span className="text-gold">Taste &amp; Chill</span>
            </h1>
            <p className="mt-6 max-w-md text-[17px] leading-relaxed text-[#b8b8b8]">
              Accédez à votre compte pour commander vos plats préférés au sommet de la résidence La Terrasse, suivre la livraison et débloquer vos récompenses.
            </p>

            <div className="mt-auto grid max-w-[430px] grid-cols-2 gap-3 pt-12">
              {PERKS.map(({ icon: Icon, title, text }) => (
                <div key={title} className="rounded-xl border border-line bg-black/55 p-4 backdrop-blur-sm">
                  <span className="mb-3 grid h-8 w-8 place-items-center rounded-lg bg-surface-3 text-gold">
                    <Icon size={15} />
                  </span>
                  <p className="text-[13px] font-semibold">{title}</p>
                  <p className="mt-1 text-xs leading-snug text-muted">{text}</p>
                </div>
              ))}
            </div>
            <div className="mt-10 flex items-center gap-3 border-t border-line pt-5 text-xs text-[#b0b0b0]">
              <MapPin size={14} className="text-gold" /> Yansoki, Sommet de la Résidence La Terrasse — Vue sur la Dibamba
            </div>
          </div>
        </section>

        <section className="flex items-center justify-center px-5 py-10">{children}</section>
      </div>

      <footer className="border-t border-line py-4 text-center text-xs text-muted">
        © 2026 ZeDuc@Space — Mon Miam Miam. Espace étudiant UCAC-ICAM.
      </footer>
    </div>
  )
}
