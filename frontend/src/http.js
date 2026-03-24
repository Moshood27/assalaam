import axios from 'axios'

// Configure a sane default baseURL for all axios requests
// - In web dev (Vite), keep baseURL empty so '/api' proxies to backend via Vite proxy
// - In mobile (Capacitor) or production preview, set VITE_API_URL to your backend origin,
//   e.g. http://localhost or http://10.0.2.2 (Android emulator), without trailing slash.
const origin = import.meta?.env?.VITE_API_URL || ''
axios.defaults.baseURL = origin
const base = import.meta?.env?.BASE_URL || '/'

// Apply a reasonable default timeout; can be overridden via VITE_HTTP_TIMEOUT (ms)
const timeout = Number(import.meta?.env?.VITE_HTTP_TIMEOUT || 15000)
axios.defaults.timeout = isNaN(timeout) ? 15000 : timeout

// Attach token automatically if present
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers = config.headers || {}
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Global response interceptor to auto-logout on 401 (expired/invalid token)
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    if (status === 401) {
      // Clear both member and admin tokens to be safe
      const hadMember = !!localStorage.getItem('token')
      const hadAdmin = !!localStorage.getItem('admin_token')
      localStorage.removeItem('token')
      localStorage.removeItem('admin_token')

      // Try to redirect if router is available (SPA context)
      try {
        const current = window?.location?.pathname || '/'
        const basePath = (base && base.endsWith('/')) ? base : `${base}/`
        if (hadAdmin && current.startsWith(`${basePath}admin`)) {
          window.location.href = `${basePath}admin/login`
        } else {
          window.location.href = `${basePath}login`
        }
      } catch (_) {}
    }
    return Promise.reject(error)
  }
)

export default axios
