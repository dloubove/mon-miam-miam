import { useState } from 'react'
import { Link } from 'react-router-dom'
import GameShell from './GameShell.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { GUESS_QUESTION } from '../../data/mock.js'

export default function GuessGame() {
  const { user, updateUser } = useApp()
  const key = `mmm_guess_${user.id}`
  const today = new Date().toDateString()
  const [done] = useState(() => localStorage.getItem(key) === today)
  const [picked, setPicked] = useState(null)
  const answered = picked !== null

  const pick = (i) => {
    if (answered || done) return
    setPicked(i)
    try { localStorage.setItem(key, today) } catch { /* ignore */ }
    if (i === GUESS_QUESTION.answer) updateUser({ points: (user.points || 0) + 2 })
  }
  const letters = ['A', 'B', 'C', 'D']

  return (
    <GameShell>
      <h1 className="mt-2 text-3xl font-bold">Devine le Plat</h1>
      <p className="mt-3 text-center text-sm text-muted">Une image de notre menu a été floutée. Sauras-tu deviner de quoi il s'agit ?</p>
      <div className="panel mt-8 w-full p-4">
        <div className="relative h-[280px] overflow-hidden rounded-lg">
          <img src={GUESS_QUESTION.image} alt="Plat flouté à deviner" className={`h-full w-full object-cover transition-all duration-700 ${answered ? 'blur-0' : 'scale-110 blur-2xl'}`} />
          {!answered && <span className="absolute inset-0 grid place-items-center text-6xl font-extrabold drop-shadow">?</span>}
        </div>
      </div>
      <h2 className="mb-4 mt-9 font-bold">Quel est ce plat ?</h2>
      <div className="grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
        {GUESS_QUESTION.options.map((o, i) => {
          const good = answered && i === GUESS_QUESTION.answer
          const bad = answered && picked === i && i !== GUESS_QUESTION.answer
          return (
            <button key={o} onClick={() => pick(i)} disabled={answered || done}
              className={`rounded-lg border px-4 py-4 text-sm transition ${good ? 'border-ok bg-ok/10 text-ok' : bad ? 'border-danger bg-danger/10 text-danger' : 'border-line bg-surface hover:border-gold/60 disabled:opacity-50'}`}>
              {letters[i]}. {o}
            </button>
          )
        })}
      </div>
      {(answered || done) && (
        <div className="mt-8 text-center">
          {answered && <p className={`font-semibold ${picked === GUESS_QUESTION.answer ? 'text-ok' : 'text-danger'}`}>{picked === GUESS_QUESTION.answer ? 'Bien joué ! +2 points ajoutés à ta cagnotte.' : 'Raté ! C\'était : ' + GUESS_QUESTION.options[GUESS_QUESTION.answer]}</p>}
          {!answered && done && <p className="text-muted">Tu as déjà relevé le défi aujourd'hui. Reviens demain !</p>}
          <Link to="/jeux" className="btn-gold mt-5">Retour aux jeux</Link>
        </div>
      )}
    </GameShell>
  )
}
