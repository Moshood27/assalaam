import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Reverb is Pusher-compatible, so we must assign Pusher to the window object
window.Pusher = Pusher

let echoInstance = null

function resolveConfig() {
    const backendOrigin = import.meta?.env?.VITE_BACKEND_ORIGIN || import.meta?.env?.VITE_API_URL || window.location.origin
    let url
    try { url = new URL(backendOrigin) } catch { url = new URL(window.location.origin) }

    const scheme = (import.meta?.env?.VITE_REVERB_SCHEME || url.protocol.replace(':', '') || 'https').toLowerCase()
    const isSecure = scheme === 'https' || scheme === 'wss'

    let wsHost = import.meta?.env?.VITE_REVERB_HOST || url.hostname
    const localHosts = ['localhost', '127.0.0.1', '::1']
    if (localHosts.includes(wsHost)) {
        // Fallback to backend hostname or brand domain to work in APK
        try {
            wsHost = new URL(import.meta?.env?.VITE_BACKEND_ORIGIN || import.meta?.env?.VITE_API_URL || 'https://attaqwacooposg.com').hostname
        } catch {
            wsHost = 'attaqwacooposg.com'
        }
    }
    const wsPort = Number(import.meta?.env?.VITE_REVERB_PORT || (isSecure ? 443 : 8080))
    const key = import.meta?.env?.VITE_REVERB_APP_KEY || 'local-key'

    // Auth endpoint for private channels (must be absolute for Capacitor/mobile)
    const base = backendOrigin && backendOrigin.endsWith('/') ? backendOrigin.slice(0, -1) : backendOrigin
    const authEndpoint = `${base}/broadcasting/auth`

    return { key, wsHost, wsPort, isSecure, authEndpoint }
}

export function getEcho() {
    if (echoInstance) return echoInstance

    const { key, wsHost, wsPort, isSecure, authEndpoint } = resolveConfig()

    echoInstance = new Echo({
        // Use 'reverb' broadcaster for Laravel Reverb (Pusher-compatible)
        broadcaster: 'reverb',
        key: key,
        wsHost: wsHost,
        wsPort: wsPort,
        wssPort: wsPort,
        forceTLS: isSecure,
        enabledTransports: ['ws', 'wss'],
        // Force the WebSocket path to root to avoid double "/app" when site is hosted under "/app"
        wsPath: '/reverb',
        disableStats: true,
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

    // Keep Authorization header up to date
    window.addEventListener('storage', (e) => {
        if (e.key === 'token' && echoInstance) {
            echoInstance.options.auth.headers.Authorization = e.newValue ? `Bearer ${e.newValue}` : ''
        }
    })

    return echoInstance
}