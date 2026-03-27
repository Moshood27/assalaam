<script setup>
import { onMounted } from 'vue'
import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import { SplashScreen } from '@capacitor/splash-screen'
import BaseModal from './components/BaseModal.vue'
import axios from 'axios'

const PENDING_PUSH_TOKEN_KEY = 'pending_push_token'
const wait = (ms) => new Promise((r) => setTimeout(r, ms))

async function saveTokenToBackend(token) {
  try {
    // Always persist locally first; backend route is protected and may not be available yet
    if (token) localStorage.setItem(PENDING_PUSH_TOKEN_KEY, token)

    // Only try sending if the user is authenticated
    const hasAuth = !!localStorage.getItem('token')
    if (!hasAuth) return false

    // Retry a few times with backoff to survive startup/network hiccups
    const attempts = 3
    for (let i = 0; i < attempts; i++) {
      try {
        await axios.post('/api/push/token', { token }, { timeout: Math.max(30000, Number(axios.defaults.timeout) || 0) })
        localStorage.removeItem(PENDING_PUSH_TOKEN_KEY)
        return true
      } catch (e) {
        if (i < attempts - 1) await wait(800 * (i + 1))
        else throw e
      }
    }
  } catch (e) {
    console.warn('Failed to save push token:', e?.message || e)
    return false
  }
}

async function flushPendingPushToken() {
  try {
    const cached = localStorage.getItem(PENDING_PUSH_TOKEN_KEY)
    if (!cached) return
    const hasAuth = !!localStorage.getItem('token')
    if (!hasAuth) return
    await saveTokenToBackend(cached)
  } catch (_) {}
}

onMounted(async () => {
  // 1. Wait for the app to be visually ready
  try {
    await SplashScreen.hide()
  } catch (_) {
    // ignore if plugin not available
  }
  
  // 2. Small delay to let the OS settle
  await new Promise(resolve => setTimeout(resolve, 1000))

  // 3. Setup Push Notifications only on native platforms
  const isNative = Capacitor.getPlatform() !== 'web'
  const hasPlugin = Capacitor.isPluginAvailable('PushNotifications')

  if (isNative && hasPlugin) {
    try {
      let permStatus = await PushNotifications.checkPermissions()
      if (permStatus.receive !== 'granted') {
        permStatus = await PushNotifications.requestPermissions()
      }

      if (permStatus.receive === 'granted') {
        // SET UP LISTENER FIRST
        PushNotifications.addListener('registration', (token) => {
          console.log('FCM Token received:', token.value)
          saveTokenToBackend(token.value)
        })

        // THEN REGISTER
        await PushNotifications.register()
      }
    } catch (e) {
      console.error('Push sequence failed', e)
    }
  } else {
    console.info('PushNotifications plugin is not available on this platform. Skipping push registration.')
  }

  // 4. If user is already authenticated and we have a cached push token, try to flush it now
  await flushPendingPushToken()
})
</script>

<template>
  <router-view />
  <BaseModal />
</template>

<style scoped>
</style>
