import { Link, useNavigate } from 'react-router-dom'
import { Gift, Tv } from 'lucide-react'
import { useApp } from '../context/AppContext.jsx'
import { HomeNavbar, SiteFooter } from '../layouts/StudentLayout.jsx'
import { BilliardIcon } from '../components/ui.jsx'
import { LEADERBOARD } from '../data/mock.js'
import { fcfa } from '../utils/format.js'

const scrollTo = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })

function DishCard({ dish }) {
  const { addToCart } = useApp()
  return (
    <article className="overflow-hidden rounded-xl border border-line bg-[#111]">
      <div className="relative flex h-[190px] items-center justify-center bg-[#161616]">
        <img src={dish.image} alt={dish.name} className="h-full object-contain" loading="lazy" />
        {dish.promo && <span className="absolute right-3 top-3 rounded bg-white px-2 py-1 text-[11px] font-bold text-black">Promo</span>}
      </div>
      <div className="px-6 pb-5 pt-5">
        <h3 className="min-h-[3.25rem] pl-1 text-[17px] font-bold">{dish.name}</h3>
        <div className="mt-2 flex items-end justify-between gap-3 border-t border-line pt-4">
          <div>
            {dish.oldPrice ? (
              <p className="text-[11px] text-muted line-through">{fcfa(dish.oldPrice)}</p>
            ) : (
              <p className="text-[11px] text-muted">Prix</p>
            )}
            <p className="text-[18px] font-bold text-gold">{fcfa(dish.price)}</p>
          </div>
          {dish.available ? (
            <button className="btn-gold !px-4 !py-2" onClick={() => addToCart(dish)}>Ajouter</button>
          ) : (
            <button className="btn-dark !px-4 !py-2" disabled>Rupture</button>
          )}
        </div>
      </div>
    </article>
  )
}

function EventCard({ ev, onAction }) {
  const Icon = ev.icon === 'billard' ? BilliardIcon : Tv
  return (
    <article className="flex flex-col rounded-xl border border-line bg-[#111] p-6">
      {ev.poster ? (
        <img src={ev.poster} alt={ev.title} className="mb-4 h-24 w-full rounded-lg object-cover" />
      ) : (
        <Icon size={26} className="text-gold" />
      )}
      <span className="tag mt-5 w-fit !bg-[#1d1d1d] !text-[11px]">{ev.badge}</span>
      <h3 className="mt-4 text-xl font-bold">{ev.heading || ev.title}</h3>
      <p className="mt-3 flex-1 text-sm leading-relaxed text-muted">{ev.text}</p>
      <button className="btn-dark mt-6 w-full !bg-[#222]" onClick={() => onAction(ev)}>{ev.cta}</button>
    </article>
  )
}

export default function Home() {
  const { menu, events, user, toast } = useApp()
  const navigate = useNavigate()
  const code = user?.referralCode || 'ZEDUC-A98X2'

  const eventAction = (ev) => {
    if (!user) return navigate('/connexion')
    toast(`Demande enregistrée : ${ev.title}`)
  }
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(code)
      toast('Code copié !')
    } catch {
      toast(`Votre code : ${code}`)
    }
  }

  return (
    <div className="min-h-screen bg-black">
      <HomeNavbar />

      {/* HERO */}
      <section className="mx-auto grid min-h-[calc(100vh-78px)] max-w-[1440px] items-center gap-10 px-5 py-14 lg:grid-cols-2 lg:px-20">
        <div>
          <span className="inline-block rounded border border-line bg-[#111] px-3 py-1.5 text-xs font-medium">Résidence La Terrasse - Yansoki</span>
          <h1 className="mt-5 text-5xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">
            Prenez une pause.
            <br />
            Mangez bien.
          </h1>
          <p className="mt-6 max-w-xl text-lg leading-8 text-[#a3a3a3]">
            Bienvenue dans votre repaire gourmand. On vous prépare de bons plats pour décompresser après les cours. Profitez de la vue sur la Dibamba, on s'occupe du reste.
          </p>
          <div className="mt-9 flex flex-wrap gap-4">
            <button className="btn-gold !px-8 !py-3.5" onClick={() => scrollTo('menu')}>Voir le menu du jour</button>
            <button className="btn-dark !bg-[#111] !px-8 !py-3.5" onClick={() => scrollTo('evenements')}>Quoi de neuf ce soir ?</button>
          </div>
        </div>
        <div className="mx-auto w-full max-w-[490px] rounded-xl border border-line bg-[#111] p-0">
          <div className="flex aspect-[4/5] items-center justify-center overflow-hidden rounded-xl bg-black/60">
            <img src="/images/affiche.jpg" alt="Affiche : Zeduc BBQ, poulet et poisson braisé à 2000 XAF" className="w-full opacity-90" />
          </div>
        </div>
      </section>

      {/* MENU DU JOUR */}
      <section id="menu" className="scroll-mt-20 bg-[#0a0a0a] py-24">
        <div className="mx-auto max-w-[1360px] px-5 lg:px-16">
          <h2 className="text-3xl font-bold tracking-tight">Qu'est-ce qu'on mange aujourd'hui ?</h2>
          <p className="mt-3 text-muted">Préparé sur place, prêt à être dévoré.</p>
          <div className="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            {menu.map((d) => <DishCard key={d.id} dish={d} />)}
          </div>
        </div>
      </section>

      {/* ÉVÉNEMENTS */}
      <section id="evenements" className="scroll-mt-20 py-24">
        <div className="mx-auto max-w-[1360px] px-5 lg:px-16">
          <h2 className="text-3xl font-bold tracking-tight">On se détend un peu ?</h2>
          <p className="mt-3 text-muted">Ce qui se passe sur la terrasse cette semaine.</p>
          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {events.filter((e) => e.active).map((ev) => <EventCard key={ev.id} ev={ev} onAction={eventAction} />)}
            <article className="flex flex-col rounded-xl bg-gold p-6 text-black">
              <Gift size={24} />
              <span className="mt-5 w-fit rounded-md bg-black/10 px-2.5 py-1 text-[11px] font-semibold">Offre permanente</span>
              <h3 className="mt-4 text-xl font-bold">Invitez vos amis</h3>
              <p className="mt-3 flex-1 text-sm leading-relaxed text-black/70">
                Partagez votre code. Quand un ami commande, vous gagnez tous les deux des réductions.
              </p>
              <button className="mt-6 w-full rounded-lg bg-black px-5 py-3 text-sm font-medium text-gold transition hover:bg-[#161616]" onClick={() => scrollTo('fidelite')}>
                Récupérer mon code
              </button>
            </article>
          </div>
        </div>
      </section>

      {/* FIDÉLITÉ */}
      <section id="fidelite" className="scroll-mt-20 bg-[#0a0a0a] py-24">
        <div className="mx-auto grid max-w-[1360px] items-center gap-14 px-5 lg:grid-cols-2 lg:px-16">
          <div>
            <h2 className="text-4xl font-bold tracking-tight">Vos commandes vous rapportent.</h2>
            <p className="mt-6 max-w-xl text-[17px] leading-8 text-[#a3a3a3]">
              À chaque fois que vous mangez chez nous, vous cumulez des points. Pas besoin de carte physique, tout est dans votre espace étudiant.
            </p>
            <ol className="mt-9 space-y-6">
              {[
                ['1', 'Mangez', 'Passez commande sur l\'app. 1000 FCFA dépensés = 1 point dans votre cagnotte.'],
                ['2', 'Économisez', 'Dès 15 points, vous débloquez une réduction immédiate sur votre prochain repas.'],
              ].map(([n, t, d]) => (
                <li key={n} className="flex gap-4">
                  <span className="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-line bg-[#111] font-bold text-gold">{n}</span>
                  <div>
                    <p className="font-semibold">{t}</p>
                    <p className="mt-1 text-sm text-muted">{d}</p>
                  </div>
                </li>
              ))}
            </ol>
          </div>
          <div className="rounded-xl border border-line bg-[#111] p-8 text-center">
            <h3 className="text-xl font-bold">Votre code parrainage</h3>
            <p className="mt-3 text-sm text-muted">Donnez ce code à un ami pour qu'il le saisisse à l'inscription.</p>
            <div className="mt-7 flex items-center justify-between rounded-lg border border-line bg-black p-2 pl-6">
              <span className="font-bold tracking-wide text-gold">{code}</span>
              <button className="btn-dark !py-2.5" onClick={copy}>Copier</button>
            </div>
            <Link to={user ? '/mon-espace' : '/connexion'} className="mt-7 inline-block text-sm text-gold hover:underline">
              Voir mes points dans mon espace
            </Link>
          </div>
        </div>
      </section>

      {/* HABITUÉS */}
      <section id="habitues" className="scroll-mt-20 py-24">
        <div className="mx-auto max-w-[1360px] px-5 lg:px-16">
          <h2 className="text-3xl font-bold tracking-tight">Les habitués de la terrasse.</h2>
          <p className="mt-3 text-muted">Ceux qui nous font le plus confiance ce mois-ci.</p>
          <div className="mt-12 grid gap-8 lg:grid-cols-[1fr_360px]">
            <div className="overflow-hidden rounded-xl border border-line bg-[#0a0a0a]">
              <div className="border-b border-line px-6 py-5 font-semibold">Classement du mois</div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-line text-muted">
                      <th className="px-6 py-4 text-left font-normal">Rang</th>
                      <th className="px-3 py-4 text-left font-normal">Étudiant</th>
                      <th className="px-3 py-4 text-center font-normal">Commandes</th>
                      <th className="px-6 py-4 text-right font-normal">Points</th>
                    </tr>
                  </thead>
                  <tbody>
                    {LEADERBOARD.map((r, i) => (
                      <tr key={r.rank} className={`border-b border-line last:border-0 ${i === 0 ? 'bg-[#181818]' : 'bg-[#111]'}`}>
                        <td className={`px-6 py-4 ${i === 0 ? 'font-bold text-gold' : 'text-muted'}`}>{r.rank}</td>
                        <td className="px-3 py-4">{r.name}</td>
                        <td className="px-3 py-4 text-center text-muted">{r.orders}</td>
                        <td className={`px-6 py-4 text-right ${i === 0 ? 'font-bold text-gold' : ''}`}>{r.points}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
            <div className="flex flex-col items-center justify-center rounded-xl border border-line bg-[#111] p-8 text-center">
              <span className="grid h-20 w-20 place-items-center rounded-full bg-gold text-3xl font-bold text-black">D</span>
              <p className="mt-5 text-xl font-bold">Dan L</p>
              <p className="mt-1 text-sm text-gold">Le boss du mois</p>
              <p className="mt-6 text-sm italic text-muted">"Gagne un menu gratuit au prochain passage !"</p>
            </div>
          </div>
        </div>
      </section>

      <SiteFooter full />
      <div className="h-24" aria-hidden="true" />
    </div>
  )
}
