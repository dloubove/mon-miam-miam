import { cp, mkdir } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const here = path.dirname(fileURLToPath(import.meta.url))
const frontendRoot = path.resolve(here, '..')
const backendPublic = path.resolve(frontendRoot, '../backend/public')
const sourceImages = path.join(frontendRoot, 'public', 'images')
const targetImages = path.join(backendPublic, 'images')
await mkdir(targetImages, { recursive: true })
await cp(sourceImages, targetImages, { recursive: true, force: true })
const favicon = path.join(frontendRoot, 'public', 'favicon.svg')
try { await cp(favicon, path.join(backendPublic, 'favicon.svg'), { force: true }) } catch {}
console.log('Images statiques copiées vers backend/public/images')
