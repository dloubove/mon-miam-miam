import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import GameShell from './GameShell.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { apiRequest } from '../../services/api.js'

export default function GuessGame() {
  const { refreshProfile } = useApp()
  const [gameId, setGameId] = useState(null)
  const [sessionId, setSessionId] = useState(null)
  const [question, setQuestion] = useState('Chargement du défi…')
  const [options, setOptions] = useState([])
  const [picked, setPicked] = useState(null)
  const [result, setResult] = useState(null)
  const [done, setDone] = useState(false)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    let actif = true
    const start = async () => {
      try {
        const games = await apiRequest('/mini-jeux')
        const game = (games || []).find((item) => item.nom_mini_jeu === 'Devine le plat')
        if (!game) throw new Error('Le jeu « Devine le plat » n’est pas activé dans la base.')
        if (!actif) return
        setGameId(game.id_mini_jeu)
        const status = await apiRequest(`/mini-jeux/${game.id_mini_jeu}/statut`)
        if (status.joue_aujourdhui) { setDone(true); setQuestion('Tu as déjà relevé le défi aujourd’hui.'); return }
        const challenge = await apiRequest(`/mini-jeux/${game.id_mini_jeu}/question`, { method: 'POST' })
        if (actif) { setSessionId(challenge.session_id); setQuestion(challenge.question); setOptions(challenge.options || []) }
      } catch (problem) { if (actif) setError(problem.message) }
    }
    start()
    return () => { actif = false }
  }, [])

  const pick = async (answer) => {
    if (!sessionId || busy || done) return
    setBusy(true)
    setPicked(answer)
    try {
      const outcome = await apiRequest(`/mini-jeux/${gameId}/parties`, { method: 'POST', body: { session_id: sessionId, reponse: answer } })
      setResult(outcome)
      setDone(true)
      await refreshProfile()
    } catch (problem) { setError(problem.message); setPicked(null) }
    finally { setBusy(false) }
  }
  const letters = ['A', 'B', 'C', 'D']

  return (
    <GameShell>
      <h1 className="mt-2 text-3xl font-bold">Devine le Plat</h1>
      <p className="mt-3 text-center text-sm text-muted">Une question quotidienne sur la carte de ZEDUCSP@CE. Une seule tentative, les points sont enregistrés dans ton compte.</p>
      <div className="panel mt-8 grid min-h-40 w-full place-items-center p-6 text-center">
        <p className="text-xl font-semibold text-gold">{question}</p>
      </div>
      {error && <p role="alert" className="mt-5 text-center text-sm text-danger">{error}</p>}
      <h2 className="mb-4 mt-9 font-bold">Choisis ta réponse</h2>
      <div className="grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
        {options.map((option, index) => {
          const good = result && result.correct && picked === option
          const bad = result && !result.correct && picked === option
          return <button key={option} onClick={() => pick(option)} disabled={!sessionId || busy || done}
            className={`rounded-lg border px-4 py-4 text-sm transition ${good ? 'border-ok bg-ok/10 text-ok' : bad ? 'border-danger bg-danger/10 text-danger' : 'border-line bg-surface hover:border-gold/60 disabled:opacity-50'}`}>
            {letters[index]}. {option}
          </button>
        })}
      </div>
      {(result || done) && <div className="mt-8 text-center">
        {result && <p className={`font-semibold ${result.correct ? 'text-ok' : 'text-danger'}`}>{result.correct ? `Bien joué ! +${result.points_gagnes} points enregistrés.` : 'Raté ! Ta tentative du jour est utilisée.'}</p>}
        {done && !result && <p className="text-muted">Reviens demain pour un nouveau défi.</p>}
        <Link to="/jeux" className="btn-gold mt-5">Retour aux jeux</Link>
      </div>}
    </GameShell>
  )
}
