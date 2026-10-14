import { useState } from 'react'
import { Star } from 'lucide-react'
import GameShell from './GameShell.jsx'
import { Modal } from '../../components/ui.jsx'
import { useApp } from '../../context/AppContext.jsx'
import { WHEEL_PRIZES } from '../../data/mock.js'

const N = WHEEL_PRIZES.length
const SEG = 360 / N
const R = 125
const polar = (deg, r) => [R + r * Math.sin((deg * Math.PI) / 180), R - r * Math.cos((deg * Math.PI) / 180)]

function slicePath(i) {
  const [x1, y1] = polar(i * SEG, R - 3)
  const [x2, y2] = polar((i + 1) * SEG, R - 3)
  return `M${R},${R} L${x1},${y1} A${R - 3},${R - 3} 0 0 1 ${x2},${y2} Z`
}

export default function WheelGame() {
  const { user, updateUser, toast } = useApp()
  const key = `mmm_wheel_${user.id}`
  const today = new Date().toDateString()
  const [played, setPlayed] = useState(() => localStorage.getItem(key) === today)
  const [rotation, setRotation] = useState(0)
  const [spinning, setSpinning] = useState(false)
  const [prize, setPrize] = useState(null)

  const spin = () => {
    if (spinning || played) return
    const idx = Math.floor(Math.random() * N)
    // le segment idx doit finir sous le pointeur (en haut)
    const target = 360 * 6 + (360 - (idx * SEG + SEG / 2))
    setSpinning(true)
    setRotation((r) => r - (r % 360) + target)
    setTimeout(() => {
      setSpinning(false)
      setPlayed(true)
      try { localStorage.setItem(key, today) } catch { /* ignore */ }
      const p = WHEEL_PRIZES[idx]
      if (p === '+1 Point') updateUser({ points: (user.points || 0) + 1 })
      setPrize(p)
    }, 4200)
  }

  const msg = prize === 'Perdu' ? 'Pas de chance aujourd\'hui… reviens demain !' : prize === '+1 Point' ? 'Tu gagnes +1 point de fidélité !' : prize === 'Boisson' ? 'Une boisson offerte ! Présente-toi au comptoir.' : `Tu gagnes ${prize} de réduction !`

  return (
    <GameShell>
      <h1 className="mt-6 text-3xl font-bold">La Roue de la Chance</h1>
      <p className="mt-3 text-center text-sm text-muted">Tourne la roue et tente de gagner jusqu'à 500 FCFA de réduction ou des points de fidélité !</p>

      <div className="relative mt-8">
        <span className="absolute -top-5 left-1/2 z-10 h-0 w-0 -translate-x-1/2 border-x-[13px] border-t-[20px] border-x-transparent border-t-danger" aria-hidden="true" />
        <div className="rounded-full border-4 border-gold p-0 shadow-[0_0_40px_rgba(207,189,151,.2)]">
          <svg viewBox={`0 0 ${R * 2} ${R * 2}`} className="h-[260px] w-[260px] rounded-full sm:h-[300px] sm:w-[300px]" style={{ transform: `rotate(${rotation}deg)`, transition: spinning ? 'transform 4s cubic-bezier(.12,.6,.1,1)' : 'none' }} role="img" aria-label="Roue de la chance">
            {WHEEL_PRIZES.map((p, i) => {
              const mid = i * SEG + SEG / 2
              const [tx, ty] = polar(mid, R * 0.62)
              const dark = i % 2 === 1
              return (
                <g key={p}>
                  <path d={slicePath(i)} fill={dark ? '#121212' : '#cfbd97'} />
                  <text x={tx} y={ty} transform={`rotate(${mid - 90} ${tx} ${ty})`} textAnchor="middle" dominantBaseline="middle" fontSize="13" fontWeight="800" fill={dark ? '#fff' : '#000'}>{p}</text>
                </g>
              )
            })}
            <circle cx={R} cy={R} r="22" fill="#000" stroke="#cfbd97" strokeWidth="4" />
            <path d={`M${R},${R - 9} l2.6,5.4 5.9,.8 -4.3,4.1 1,5.8 -5.2,-2.8 -5.2,2.8 1,-5.8 -4.3,-4.1 5.9,-.8z`} fill="#cfbd97" />
          </svg>
        </div>
      </div>

      <button className="btn-gold mt-12 !px-12 !py-4 text-base font-extrabold uppercase" onClick={spin} disabled={spinning || played}>
        {played && !spinning ? 'Reviens demain' : 'Tourner la roue'}
      </button>
      <p className="mt-3 text-xs text-muted">1 essai par jour.</p>

      <Modal open={!!prize} onClose={() => { toast(msg); setPrize(null) }} size="sm">
        <div className="py-4 text-center">
          <Star size={34} className="mx-auto text-gold" fill="currentColor" />
          <h2 className="mt-4 text-2xl font-bold text-gold">{prize === 'Perdu' ? 'Dommage !' : 'Bravo !'}</h2>
          <p className="mt-3 text-muted">{msg}</p>
          <button className="btn-gold mt-6" onClick={() => setPrize(null)}>OK</button>
        </div>
      </Modal>
    </GameShell>
  )
}
