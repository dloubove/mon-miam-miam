import { Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'

export default function GameShell({ children }) {
  return (
    <div className="min-h-screen bg-black">
      <header className="border-b border-line">
        <div className="mx-auto flex h-[68px] max-w-[1100px] items-center px-5">
          <Link to="/jeux" className="flex items-center gap-2.5 text-sm text-[#b5b5b5] hover:text-white"><ArrowLeft size={16} /> Retour aux jeux</Link>
        </div>
      </header>
      <main className="mx-auto flex max-w-[720px] flex-col items-center px-5 py-14">{children}</main>
    </div>
  )
}
