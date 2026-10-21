import { Link } from 'react-router-dom'
import { ShipWheel, CircleQuestionMark } from 'lucide-react'
import { useEffect, useState } from 'react'
import { apiRequest } from '../../services/api.js'

export default function GamesHome() {
  const [games, setGames] = useState([])
  useEffect(() => {
    let actif = true
    apiRequest('/mini-jeux').then((rows) => { if (actif) setGames(rows || []) }).catch(() => {})
    return () => { actif = false }
  }, [])
  const wheel = games.find((game) => game.nom_mini_jeu === 'Roue de la chance')
  const guess = games.find((game) => game.nom_mini_jeu === 'Devine le plat')
  return (
    <div className="mx-auto max-w-[1100px] px-5 py-12">
      <h1 className="text-4xl font-bold">La Récré</h1>
      <p className="mb-10 mt-2 text-muted">Détends-toi entre deux cours et gagne des points pour tes prochaines commandes.</p>
      <div className="grid gap-6 md:grid-cols-2">
        {wheel && <article className="panel overflow-hidden">
          <div className="grid h-[162px] place-items-center bg-[radial-gradient(circle,#2e2a21_0%,#141414_55%)]"><ShipWheel size={64} className="text-gold" strokeWidth={1.5} /></div>
          <div className="p-7">
            <div className="flex items-start justify-between gap-3"><h2 className="text-xl font-bold">La Roue de la Chance</h2><span className="tag !text-[11px] !text-gold">1 partie / jour</span></div>
            <p className="mt-4 text-sm leading-relaxed text-muted">Tourne la roue et tente de gagner jusqu'à 500 FCFA de réduction, des boissons offertes ou des points de fidélité !</p>
            <Link to="/jeux/roue" className="btn-gold mt-6 w-full !py-3">Jouer maintenant</Link>
          </div>
        </article>}
        {guess && <article className="panel overflow-hidden">
          <div className="relative grid h-[162px] place-items-center bg-cover bg-center" style={{ backgroundImage: "linear-gradient(rgba(0,0,0,.35),rgba(0,0,0,.35)),url('/images/poulet.jpg')" }}>
            <CircleQuestionMark size={52} className="text-white" fill="white" stroke="#4a2a0a" />
          </div>
          <div className="p-7">
            <div className="flex items-start justify-between gap-3"><h2 className="text-xl font-bold">Devine le plat</h2><span className="tag !text-[11px]">Nouveau défi</span></div>
            <p className="mt-4 text-sm leading-relaxed text-muted">Un défi quotidien construit à partir du menu. Une bonne réponse rapporte jusqu'à {guess.points_maximum} points.</p>
            <Link to="/jeux/devine-le-plat" className="btn-dark mt-6 w-full !py-3 font-bold">Relever le défi</Link>
          </div>
        </article>}
        {!wheel && !guess && <p className="panel col-span-full p-8 text-center text-muted">Les jeux ne sont pas encore activés dans la base. Relancez les seeders de référence.</p>}
      </div>
    </div>
  )
}
