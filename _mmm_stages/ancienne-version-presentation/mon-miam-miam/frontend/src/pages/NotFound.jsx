import { Link } from 'react-router-dom'

export default function NotFound() {
  return (
    <main className="grid min-h-screen place-items-center bg-black px-6 text-center">
      <div>
        <p className="text-6xl font-extrabold text-gold">404</p>
        <h1 className="mt-4 text-2xl font-bold">Cette page est introuvable</h1>
        <p className="mt-2 text-muted">Le plat que vous cherchez n'est pas au menu.</p>
        <Link to="/" className="btn-gold mt-8">Retour à l'accueil</Link>
      </div>
    </main>
  )
}
