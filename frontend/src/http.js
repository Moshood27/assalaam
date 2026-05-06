import axios from 'axios'

// Configure a sane default baseURL for all axios requests
// - In web dev (Vite), keep baseURL empty so '/api' proxies to backend via Vite proxy
// - In mobile (Capacitor) or production preview, set VITE_API_URL to your backend origin,
//   e.g. http://localhost or http://10.0.2.2 (Android emulator), without trailing slash.
const origin = import.meta?.env?.VITE_API_URL || ''
axios.defaults.baseURL = origin
const base = import.meta?.env?.BASE_URL || '/'

// Apply a reasonable default timeout; can be overridden via VITE_HTTP_TIMEOUT (ms)
const timeout = Number(import.meta?.env?.VITE_HTTP_TIMEOUT || 30000)
axios.defaults.timeout = isNaN(timeout) ? 30000 : timeout

// Attach token automatically if present
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('token') || localStorage.getItem('admin_token')
  if (token) {
    config.headers = config.headers || {}
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Global response interceptor to auto-logout on auth/permission errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    // 401 = Unauthorized (expired/invalid token)
    // 423 = Locked (account locked)
    // Note: 403 is also used by the API for business logic errors (e.g., invalid PIN).
    // Do NOT auto-logout on 403 to avoid redirecting members during normal error flows.
    if (status === 401 || status === 423) {
      // Clear both member and admin tokens to be safe
      const hadMember = !!localStorage.getItem('token')
      const hadAdmin = !!localStorage.getItem('admin_token')
      localStorage.removeItem('token')
      localStorage.removeItem('admin_token')
      localStorage.removeItem('is_admin')

      // Try to redirect to the appropriate login screen
      try {
        const current = window?.location?.pathname || '/'
        const basePath = (base && base.endsWith('/')) ? base : `${base}/`
        if (hadAdmin && current.startsWith(`${basePath}admin`)) {
          window.location.href = `${basePath}admin/login`
        } else {
          // Fallback to /app/login if basePath is just / (SPA is served under /app/)
          const target = (basePath === '/' && !current.startsWith('/app/')) ? '/app/login' : `${basePath}login`
          window.location.href = target
        }
      } catch (_) {}
    }
    return Promise.reject(error)
  }
)

export default axios
