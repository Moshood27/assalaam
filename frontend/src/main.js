import { createApp } from 'vue'
import './style.css'
import axios from './http.js'
import App from './App.vue'
import router from './router/index.js'
import VueApexCharts from 'vue3-apexcharts'

// Simple global idle timer: logs out after 2 minutes of no activity
function setupIdleLogout(router, timeoutMs = 120000) {
  let timerId = null
  const events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'visibilitychange']

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
      window.location.href = `${basePath}login`
    }
  }

  const reset = () => {
    if (timerId) clearTimeout(timerId)
    // Only arm timer if authenticated (member or admin)
    if (localStorage.getItem('token') || localStorage.getItem('admin_token')) {
      timerId = setTimeout(clearTokensAndRedirect, timeoutMs)
    }
  }

  // Hook into common user activity to reset timer
  const onActivity = () => reset()
  events.forEach(evt => window.addEventListener(evt, onActivity, { passive: true }))

  // Reset on route navigation as well
  router.afterEach(() => reset())

  // Initialize
  reset()

  // Return a disposer if needed later
  return () => {
    if (timerId) clearTimeout(timerId)
    events.forEach(evt => window.removeEventListener(evt, onActivity))
  }
}

import { useModal } from './composables/useModal'

const app = createApp(App)
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
  setupIdleLogout(router, 120000) // 2 minutes

  // Push notification startup is handled sequentially in App.vue to avoid overlapping system dialogs and race conditions.
})

app.mount('#app')

/**
 * Optional: Silence noisy MetaMask extension errors in environments
 * where browser extensions are not available (e.g., Capacitor WebView).
 * Disable by setting VITE_SILENCE_METAMASK_ERRORS=false
 */
try {
  const shouldSilence = String(import.meta?.env?.VITE_SILENCE_METAMASK_ERRORS ?? 'true') === 'true'
  if (shouldSilence && typeof window !== 'undefined') {
    const isNoisy = (msg) => {
      const s = String(msg || '')
      return s.includes('MetaMask extension not found')
        || s.includes('Failed to connect to MetaMask')
        || s.includes('inpage.js')
        || s.includes('Could not establish connection. Receiving end does not exist')
        || s.includes('runtime.lastError')
    }
    window.addEventListener('unhandledrejection', (e) => {
      const r = e?.reason
      const m = (r && (r.message || r.toString?.())) || ''
      if (isNoisy(m)) {
        e.preventDefault?.()
        console?.debug?.('[silenced] unhandledrejection:', m)
      }
    })
    window.addEventListener('error', (e) => {
      const m = e?.message || ''
      if (isNoisy(m)) {
        e.preventDefault?.()
        e.stopImmediatePropagation?.()
        console?.debug?.('[silenced] error:', m)
        return false
      }
    }, true)
  }
} catch (_) {}

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
