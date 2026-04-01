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

    const wsHost = import.meta?.env?.VITE_REVERB_HOST || url.hostname
    const wsPort = Number(import.meta?.env?.VITE_REVERB_PORT || (isSecure ? 443 : 8080))
    const key = import.meta?.env?.VITE_REVERB_APP_KEY || 'local-key'

    // Auth endpoint for private channels
    const authEndpoint = '/api/broadcasting/auth'

    return { key, wsHost, wsPort, isSecure, authEndpoint }
}

export function getEcho() {
    if (echoInstance) return echoInstance

    const { key, wsHost, wsPort, isSecure, authEndpoint } = resolveConfig()

    echoInstance = new Echo({
        broadcaster: 'reverb', // Set this as a string
        key: key,
        wsHost: wsHost,
        wsPort: wsPort,
        wssPort: wsPort,
        forceTLS: isSecure,
        enabledTransports: ['ws', 'wss'],
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