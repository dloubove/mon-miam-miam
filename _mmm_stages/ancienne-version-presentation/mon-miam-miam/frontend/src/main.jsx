import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import './index.css'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./inertia/**/*.jsx', { eager: true })
    const page = pages[`./inertia/${name}.jsx`]
    if (!page) throw new Error(`Page Inertia introuvable : ${name}`)
    return page
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
