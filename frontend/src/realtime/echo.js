import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from '../http.js'

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
        wsPath: '',
        disableStats: true,
        // Use a custom authorizer to ensure we use the latest token from our axios instance
        authorizer: (channel, options) => {
            return {
                authorize: (socketId, callback) => {
                    axios.post(authEndpoint, {
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                    .then(response => {
                        callback(false, response.data)
                    })
                    .catch(error => {
                        callback(true, error)
                    })
                }
            }
        },
    })

    return echoInstance
}