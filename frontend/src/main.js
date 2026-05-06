import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'

/**
 * Silence noisy browser extension errors (e.g. MetaMask, Grammarly)
 * that clutter the console but don't affect app functionality.
 */
try {
  const shouldSilence = String(import.meta?.env?.VITE_SILENCE_METAMASK_ERRORS ?? 'true') === 'true'
  if (shouldSilence && typeof window !== 'undefined') {
    const noisyStrings = [
      'MetaMask',
      'Grammarly',
      'inpage.js',
      'Could not establish connection. Receiving end does not exist',
      'runtime.lastError',
      'ExtensionContext',
      'extension'
    ]
    const isNoisy = (arg) => {
      const s = String(arg || (arg?.message || arg?.toString?.() || ''))
      return noisyStrings.some(str => s.includes(str))
    }

    // 1. Capture global error events
    const handleError = (e) => {
      const m = e?.message || (e?.reason?.message || e?.reason?.toString?.() || '')
      if (isNoisy(m)) {
        try {
          e.preventDefault?.()
          e.stopImmediatePropagation?.()
        } catch (_) {}
        return true
      }
      return false
    }
    window.addEventListener('unhandledrejection', handleError, true)
    window.addEventListener('error', handleError, true)

    // 2. Patch ALL console methods to hide browser-internal extension logs
    const methods = ['log', 'info', 'warn', 'error', 'debug', 'trace']
    methods.forEach(method => {
      const native = console[method]
      if (typeof native !== 'function') return
      console[method] = (...args) => {
        try {
          for (let i = 0; i < args.length; i++) {
            if (isNoisy(args[i])) return
          }
        } catch (_) {}
        return native.apply(console, args)
      }
    })
  }
} catch (_) {}

import App from './App.vue'
import router from './router/index.js'
import VueApexCharts from 'vue3-apexcharts'

// Simple global idle timer: logs out after X ms of no activity (configurable via VITE_IDLE_TIMEOUT_MS)
function setupIdleLogout(router, timeoutMs = 600000) {
  let timerId = null
  const events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll']
  const LAST_ACTIVITY_KEY = 'last_activity_ts'

  const clearTokensAndRedirect = () => {
    const hadAdmin = !!localStorage.getItem('admin_token')
    const hadMember = !!localStorage.getItem('token')
    if (!hadAdmin && !hadMember) return // nothing to do

    localStorage.removeItem('token')
    localStorage.removeItem('admin_token')

    // Redirect based on current path; use window.location for reliability in Capacitor WebView
    const base = import.meta?.env?.BASE_URL || '/'
    const basePath = (base && base.endsWith('/')) ? base : `${base}/`
    const current = window?.location?.pathname || '/'
    if (hadAdmin && current.startsWith(`${basePath}admin`)) {
      window.location.href = `${basePath}admin/login`
    } else {
      // Fallback to /app/login if basePath is just / (SPA is served under /app/)
      const target = (basePath === '/' && !current.startsWith('/app/')) ? '/app/login' : `${basePath}login`
      window.location.href = target
    }
  }

  const isExpired = () => {
    const ts = Number(localStorage.getItem(LAST_ACTIVITY_KEY) || 0)
    return ts > 0 && (Date.now() - ts >= timeoutMs)
  }

  const arm = () => {
    if (timerId) clearTimeout(timerId)
    if (localStorage.getItem('token') || localStorage.getItem('admin_token')) {
      timerId = setTimeout(clearTokensAndRedirect, timeoutMs)
    }
  }

  const reset = () => {
    // Update last activity timestamp and arm timer
    localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()))
    arm()
  }

  // Hook into common user activity to reset timer
  const onActivity = () => reset()
  events.forEach(evt => window.addEventListener(evt, onActivity, { passive: true }))

  // Handle tab/app visibility and focus to avoid premature resets
  const onVisibility = () => {
    try {
      if (document.visibilityState === 'visible') {
        if (isExpired()) return clearTokensAndRedirect()
        reset()
      }
    } catch (_) {}
  }
  const onFocusLike = () => {
    if (isExpired()) return clearTokensAndRedirect()
    reset()
  }
  document.addEventListener('visibilitychange', onVisibility)
  window.addEventListener('focus', onFocusLike)
  window.addEventListener('pageshow', onFocusLike)

  // Reset on route navigation as well
  router.afterEach(() => reset())

  // Integrate with Capacitor App lifecycle to make this truly global on mobile
  try {
    // Lazy import to avoid errors on web
    import('@capacitor/core').then(({ Capacitor }) => {
      const hasApp = Capacitor?.isPluginAvailable?.('App')
      if (!hasApp) return
      import('@capacitor/app').then(({ App }) => {
        App.addListener('appStateChange', ({ isActive }) => {
          // When app becomes active, if we've exceeded the timeout, logout immediately
          if (isActive) {
            if (isExpired()) return clearTokensAndRedirect()
            // If not expired, refresh timer and timestamp
            reset()
          }
        })
      }).catch(() => {})
    }).catch(() => {})
  } catch (_) {}

  // Initialize: only set timestamp if authenticated
  if (localStorage.getItem('token') || localStorage.getItem('admin_token')) {
    if (!localStorage.getItem(LAST_ACTIVITY_KEY)) {
      localStorage.setItem(LAST_ACTIVITY_KEY, String(Date.now()))
    }
  }
  arm()

  // Return a disposer if needed later
  return () => {
    if (timerId) clearTimeout(timerId)
    events.forEach(evt => window.removeEventListener(evt, onActivity))
    document.removeEventListener('visibilitychange', onVisibility)
    window.removeEventListener('focus', onFocusLike)
    window.removeEventListener('pageshow', onFocusLike)
    try {
      import('@capacitor/app').then(({ App }) => {
        // Capacitor doesn't yet expose removeAllListeners per event in all versions; best-effort cleanup.
        App.removeAllListeners?.()
      }).catch(() => {})
    } catch (_) {}
  }
}

import { useModal } from './composables/useModal'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(VueApexCharts)

// Override native alert with app modal for consistent UX
try {
  const modal = useModal()
  const nativeAlert = window.alert ? window.alert.bind(window) : (m) => {}
  window.alert = (message) => {
    try {
      return modal.alert(String(message ?? ''))
    } catch (_) {
      try { return nativeAlert(String(message ?? '')) } catch (_) {}
    }
  }
} catch (_) {
  // If composable not available for any reason, keep native alert
}

// Start idle logout after router is ready
router.isReady().then(async () => {
  const envMs = Number(import.meta?.env?.VITE_IDLE_TIMEOUT_MS ?? 600000)
  const idleMs = isNaN(envMs) ? 600000 : envMs
  setupIdleLogout(router, idleMs)

  // Push notification startup is handled sequentially in App.vue to avoid overlapping system dialogs and race conditions.
})

app.mount('#app')

// Signal app ready and hide native splash (Capacitor) once mounted
setTimeout(async () => {
  try {
    window.dispatchEvent(new Event('app:ready'))
    let hide
    try {
      const mod = await import('@capacitor/splash-screen')
      hide = mod?.SplashScreen?.hide
    } catch (_) {}
    if (!hide) {
      const cap = typeof window !== 'undefined' ? window.Capacitor : undefined
      hide = cap?.Plugins?.SplashScreen?.hide
    }
    if (typeof hide === 'function') {
      await hide({ fadeOutDuration: 200 })
    }
  } catch (_) {
    // ignore if web/not available
  }
}, 0)
