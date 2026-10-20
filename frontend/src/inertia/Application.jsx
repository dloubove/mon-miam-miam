import { BrowserRouter } from 'react-router-dom'
import App from '../App.jsx'
import { AppProvider } from '../context/AppContext.jsx'

/** L'application existante est servie par Laravel/Inertia et conserve ses routes React actuelles. */
export default function Application() {
  return (
    <BrowserRouter>
      <AppProvider>
        <App />
      </AppProvider>
    </BrowserRouter>
  )
}
