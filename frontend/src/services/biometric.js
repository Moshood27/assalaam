// Biometric service for Capacitor mobile apps
// Uses capacitor-native-biometric to securely store and retrieve member credentials
// Falls back gracefully on web or when biometrics are unavailable.

import { Capacitor } from '@capacitor/core'
import axios from 'axios'

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

  try {
    // Tell Vite to ignore resolving this import in web context and avoid static analysis by using a non-literal id
    const id = 'capacitor-native-biometric'
    const mod = await import(/* @vite-ignore */ id)
    NativeBiometric = mod?.NativeBiometric || mod?.default?.NativeBiometric || mod?.default || mod
  } catch (e) {
    // Plugin not available (web or not installed)
    NativeBiometric = null
  }
  return NativeBiometric
}

const SERVICE = 'assalam-cooperative-app'

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
