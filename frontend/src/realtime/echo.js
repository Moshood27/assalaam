import Echo from 'laravel-echo'
import { Reverb } from '@laravel/reverb'

let echoInstance = null

function resolveConfig() {
  // Prefer explicit Vite vars; otherwise infer from backend origin / window.location
  const backendOrigin = import.meta?.env?.VITE_BACKEND_ORIGIN || import.meta?.env?.VITE_API_URL || window.location.origin
  let url
  try { url = new URL(backendOrigin) } catch { url = new URL(window.location.origin) }

  const scheme = (import.meta?.env?.VITE_REVERB_SCHEME || url.protocol.replace(':', '') || 'http').toLowerCase()
  const isSecure = scheme === 'https' || scheme === 'wss'

  // Host/ports can be overridden via Vite vars
  const wsHost = import.meta?.env?.VITE_REVERB_HOST || url.hostname
  const wsPort = Number(import.meta?.env?.VITE_REVERB_PORT || (isSecure ? 443 : 8080))
  const wssPort = Number(import.meta?.env?.VITE_REVERB_WSS_PORT || 443)
  const key = import.meta?.env?.VITE_REVERB_APP_KEY || 'local-key'

  // Auth endpoint lives on the backend API. We rely on axios baseURL or absolute path.
  const authEndpoint = '/broadcasting/auth'

  return { key, wsHost, wsPort, wssPort, isSecure, authEndpoint }
}

export function getEcho() {
  if (echoInstance) return echoInstance

  const { key, wsHost, wsPort, wssPort, isSecure, authEndpoint } = resolveConfig()

  echoInstance = new Echo({
    broadcaster: Reverb,
    key,
    wsHost,
    wsPort,
    wssPort,
    forceTLS: isSecure,
    enabledTransports: isSecure ? ['wss'] : ['ws', 'wss'],
    authEndpoint,
    auth: {
      headers: {
        Authorization: (() => {
          const t = localStorage.getItem('token')
          return t ? `Bearer ${t}` : ''
        })(),
      },
    },
  })

  // Keep Authorization header up to date if token changes
  window.addEventListener('storage', (e) => {
    if (e.key === 'token' && echoInstance) {
      echoInstance.options.auth.headers.Authorization = e.newValue ? `Bearer ${e.newValue}` : ''
    }
  })

  return echoInstance
}
