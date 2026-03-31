<script setup>
import { onMounted, ref, computed } from 'vue'
import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import { SplashScreen } from '@capacitor/splash-screen'
import BaseModal from './components/BaseModal.vue'
import InboxDrawer from './components/InboxDrawer.vue'
import router from './router/index.js'
import axios from './http.js'

const PENDING_PUSH_TOKEN_KEY = 'pending_push_token'
const wait = (ms) => new Promise((r) => setTimeout(r, ms))

const showInbox = ref(false)
const unreadCount = ref(0)
const isLoggedIn = computed(() => !!localStorage.getItem('token'))
let unreadTimer = null

async function refreshUnreadCount() {
  try {
    if (!isLoggedIn.value) return
    const { data } = await axios.get('/api/notifications', { params: { per_page: 1 } })
    unreadCount.value = Number(data?.unread_count || 0)
  } catch (_) {}
}

async function saveTokenToBackend(token) {
  try {
    // Always persist locally first; backend route is protected and may not be available yet
    if (token) localStorage.setItem(PENDING_PUSH_TOKEN_KEY, token)

    // Only try sending if the user is authenticated
    const hasAuth = !!localStorage.getItem('token')
    if (!hasAuth) return false

    const platform = (Capacitor?.getPlatform?.() || 'web').toString()

    // Retry a few times with backoff to survive startup/network hiccups
    const attempts = 3
    for (let i = 0; i < attempts; i++) {
      try {
        await axios.post('/api/push/token', { token, platform }, { timeout: Math.max(30000, Number(axios.defaults.timeout) || 0) })
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
        // SET UP LISTENERS FIRST
        PushNotifications.addListener('registration', (token) => {
          try {
            console.log('FCM Token received:', token.value)
            saveTokenToBackend(token.value)
          } catch (_) {}
        })

        // Foreground receive handler (optional UI hook)
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
          try {
            const data = notification?.data || {}
            const title = notification?.title || notification?.notification?.title || 'Notification'
            const body = notification?.body || notification?.notification?.body || ''
            console.log('[push] received (fg):', { title, body, data })
          } catch (e) {
            console.warn('Error handling received notification', e)
          }
        })

        // Tap action handler to route user
        PushNotifications.addListener('pushNotificationActionPerformed', (event) => {
          try {
            const data = event?.notification?.data || {}
            const route = (data?.route || data?.screen || '').toString()
            if (route) {
              router.push(route)
              return
            }
            // Fallbacks for known types
            const type = (data?.type || '').toString()
            if (type === 'voting_open' && data?.session_id) {
              const sid = String(data.session_id)
              router.push(`/agm/sessions/${sid}`)
              return
            }
            if (type === 'wallet_topup') {
              router.push('/wallet')
              return
            }
            if (type === 'scheme_payment') {
              router.push('/passbook')
              return
            }
            router.push('/dashboard')
          } catch (e) {
            console.warn('Error handling notification action', e)
          }
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

  // 5. Start polling unread count while logged in
  if (isLoggedIn.value) {
    await refreshUnreadCount()
    if (unreadTimer) clearInterval(unreadTimer)
    unreadTimer = setInterval(refreshUnreadCount, 30000)
  }
})
</script>

<template>
  <div>
    <router-view />

    <!-- Floating Bell Icon (visible when logged in) -->
    <button v-if="isLoggedIn" @click="showInbox = true" class="fixed bottom-6 right-6 z-40 bg-white border shadow-lg rounded-full w-12 h-12 flex items-center justify-center">
      <span class="i-mdi-bell-outline text-2xl"></span>
      <span v-if="unreadCount>0" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1.5 py-0.5">{{ unreadCount }}</span>
    </button>

    <InboxDrawer v-model="showInbox" @unread="(n)=> unreadCount = n" />

    <BaseModal />
  </div>
</template>

<style scoped>
</style>
