// Biometric service for Capacitor mobile apps
// Uses capacitor-native-biometric to securely store and retrieve member credentials
// Falls back gracefully on web or when biometrics are unavailable.

import { Capacitor } from '@capacitor/core'
import axios from 'axios'
import brand from '../brand'

let NativeBiometric

// Lazy-load the plugin to avoid issues on web
async function loadPlugin() {
  if (NativeBiometric) return NativeBiometric
  // Avoid resolving native plugin on web — Vite can't bundle it and it's not needed
  try {
    const platform = Capacitor?.getPlatform?.() || 'web'
    if (platform === 'web') {
      NativeBiometric = null
      return NativeBiometric
    }
  } catch (_) {}

  // Prefer the runtime-registered Capacitor plugin first
  try {
    const cap = (typeof window !== 'undefined' ? window.Capacitor : null) || Capacitor
    const viaPlugins = cap?.Plugins?.NativeBiometric
    if (viaPlugins && typeof viaPlugins === 'object') {
      NativeBiometric = viaPlugins
      return NativeBiometric
    }
  } catch (_) {}

  try {
    // Try a direct dynamic import of the Capgo plugin
    const mod = await import('@capgo/capacitor-native-biometric')
    NativeBiometric = mod?.NativeBiometric || mod?.default?.NativeBiometric || mod?.default || mod
  } catch (e) {
    // Plugin not available (web or not installed)
    NativeBiometric = null
  }
  return NativeBiometric
}

const SERVICE = `${(brand?.slug || 'assalam')}-cooperative-app`

export async function isNative() {
  try {
    return Capacitor.getPlatform() !== 'web'
  } catch (_) {
    return false
  }
}

export async function isBiometricAvailable() {
  if (!(await isNative())) return false
  const plugin = await loadPlugin()
  if (!plugin?.isAvailable) return false
  try {
    const { isAvailable } = await plugin.isAvailable()
    return !!isAvailable
  } catch (_) {
    return false
  }
}

// Return detailed availability info from the native plugin
// Useful to diagnose why Samsung devices may report unavailable
export async function getBiometricAvailabilityDetails() {
  if (!(await isNative())) {
    return { isAvailable: false, errorCode: -1, platform: 'web' }
  }
  const plugin = await loadPlugin()
  if (!plugin?.isAvailable) {
    return { isAvailable: false, errorCode: -2, reason: 'plugin_missing' }
  }
  try {
    const result = await plugin.isAvailable()
    // Ensure we always have a boolean isAvailable and optional errorCode
    if (typeof result === 'boolean') {
      return { isAvailable: !!result }
    }
    return result || { isAvailable: false }
  } catch (e) {
    return { isAvailable: false, errorCode: e?.code ?? -3, reason: e?.message || 'unknown' }
  }
}

export async function canQuickLogin() {
  if (!(await isBiometricAvailable())) return false
  const plugin = await loadPlugin()
  try {
    // Try to peek credentials existence without prompting identity yet
    // Some platforms may still prompt; we swallow errors
    const creds = await plugin.getCredentials({ server: SERVICE })
    const hasUsername = !!creds?.username
    const hasPassword = !!creds?.password
    return hasUsername && hasPassword
  } catch (_) {
    return false
  }
}

export async function storeBiometricCredentials({ membership_number, branch_id, password }) {
  if (!(await isBiometricAvailable())) return false
  const plugin = await loadPlugin()
  if (!plugin?.setCredentials) return false
  try {
    const username = JSON.stringify({ membership_number, branch_id })
    await plugin.setCredentials({ server: SERVICE, username, password })
    return true
  } catch (_) {
    return false
  }
}

export async function clearBiometricCredentials() {
  const plugin = await loadPlugin()
  if (!plugin?.deleteCredentials) return false
  try {
    await plugin.deleteCredentials({ server: SERVICE })
    return true
  } catch (_) {
    return false
  }
}

export async function quickLoginViaBiometric() {
  // Returns { ok: boolean, error?: string }
  if (!(await isBiometricAvailable())) {
    return { ok: false, error: 'Biometric authentication not available on this device.' }
  }
  const plugin = await loadPlugin()
  try {
    // Ask user to authenticate
    if (plugin.verifyIdentity) {
      await plugin.verifyIdentity({
        reason: 'Quick Login',
        title: 'Authenticate',
        subtitle: 'Login with biometrics',
        description: 'Use your fingerprint or face to sign in',
      })
    }

    const { username, password } = await plugin.getCredentials({ server: SERVICE })
    if (!username || !password) return { ok: false, error: 'No saved credentials found.' }

    let creds
    try {
      creds = JSON.parse(username)
    } catch (_) {
      // Fallback if username was saved plainly as membership number
      creds = { membership_number: username, branch_id: localStorage.getItem('biometric_branch_id') || '' }
    }

    if (!creds.branch_id) {
      return { ok: false, error: 'Saved credentials are incomplete. Please login once with your password.' }
    }

    const payload = {
      branch_id: creds.branch_id,
      membership_number: creds.membership_number,
      password: password,
    }

    const { data } = await axios.post('/api/login', payload)
    localStorage.setItem('token', data.token)
    // also remember branch id for potential fallback flows
    localStorage.setItem('biometric_branch_id', String(creds.branch_id))

    return { ok: true }
  } catch (e) {
    // Map common errors to messages
    const msg = e?.message || 'Biometric login failed.'
    return { ok: false, error: msg }
  }
}

// Prompt biometric identity verification without logging in
// Returns true if user successfully verified biometrics, else false.
export async function verifyBiometricIdentity(options = {}) {
  const {
    reason = 'Approve request',
    title = 'Authenticate',
    subtitle = 'Confirm identity',
    description = 'Use your fingerprint or face to continue',
  } = options || {}

  if (!(await isBiometricAvailable())) return false
  const plugin = await loadPlugin()
  if (!plugin?.verifyIdentity) return false
  try {
    await plugin.verifyIdentity({ reason, title, subtitle, description })
    return true
  } catch (_) {
    return false
  }
}
