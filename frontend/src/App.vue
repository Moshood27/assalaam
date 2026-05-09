<script setup>
import { onMounted, ref, computed, onBeforeUnmount, watch } from 'vue'
import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import { Geolocation } from '@capacitor/geolocation'
import { SplashScreen } from '@capacitor/splash-screen'
import BaseModal from './components/BaseModal.vue'
import InboxDrawer from './components/InboxDrawer.vue'
import IslamicChat from './components/IslamicChat.vue'
import router from './router/index.js'
import axios from './http.js'
import { getEcho } from './realtime/echo.js'

const PENDING_PUSH_TOKEN_KEY = 'pending_push_token'
const wait = (ms) => new Promise((r) => setTimeout(r, ms))

const showInbox = ref(false)
const showChat = ref(false)
const supportRoomId = ref(null)
const loadingSupport = ref(false)
const unreadCount = ref(0)
const isInputFocused = ref(false)
const authToken = ref(localStorage.getItem('token'))
const isLoggedIn = computed(() => !!authToken.value)

async function toggleSupportChat() {
  if (!showChat.value) {
    try {
      loadingSupport.value = true
      const { data } = await axios.get('/api/chat/support-room')
      supportRoomId.value = data.room.id
      showChat.value = true
    } catch (e) {
      console.error('Failed to load support room', e)
    } finally {
      loadingSupport.value = false
    }
  } else {
    showChat.value = false
  }
}

window.addEventListener('storage', (e) => {
  if (e.key === 'token') {
    authToken.value = e.newValue
  }
})
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

const isNative = Capacitor.getPlatform() !== 'web'
const isMobile = computed(() => isNative || window.innerWidth < 768)

watch(isLoggedIn, (val) => {
  if (val) {
    try {
      const echo = getEcho()
      const userId = localStorage.getItem('user_id')
      const channel = echo.join('online-members')
      
      if (userId) {
        channel.whisper('activity', {
          id: userId,
          activity: router.currentRoute.value.meta?.title || router.currentRoute.value.name || 'Browsing'
        })
      }
    } catch (_) {}
  } else {
    try { getEcho().leave('online-members') } catch (_) {}
  }
}, { immediate: true })

router.afterEach((to) => {
  // Sync token state on route change (covers same-tab login/logout)
  const currentToken = localStorage.getItem('token')
  if (authToken.value !== currentToken) {
    authToken.value = currentToken
  }

  if (isLoggedIn.value) {
    const userId = localStorage.getItem('user_id')
    try {
      const echo = getEcho()
      const channel = echo.join('online-members')
      if (userId) {
        channel.whisper('activity', {
          id: userId,
          activity: to.meta?.title || to.name || 'Browsing'
        })
      }
    } catch (_) {}
  }
})

function handleFocusIn(e) {
  if (['INPUT', 'TEXTAREA'].includes(e.target?.tagName) || e.target?.isContentEditable) {
    isInputFocused.value = true
  }
}

function handleFocusOut() {
  setTimeout(() => {
    const activeEl = document.activeElement
    if (!activeEl || !(['INPUT', 'TEXTAREA'].includes(activeEl.tagName) || activeEl.isContentEditable)) {
      isInputFocused.value = false
    }
  }, 100)
}

onMounted(async () => {
  // 0. Ensure user_id is in localStorage if logged in (for real-time tracking)
  if (isLoggedIn.value && !localStorage.getItem('user_id')) {
    try {
      const { data } = await axios.get('/api/profile')
      if (data?.id) {
        localStorage.setItem('user_id', data.id)
        localStorage.setItem('is_admin', data.is_admin ? 'true' : 'false')
      }
    } catch (_) {}
  }

  window.addEventListener('focusin', handleFocusIn)
  window.addEventListener('focusout', handleFocusOut)

  // 1. Wait for the app to be visually ready
  try {
    await SplashScreen.hide()
  } catch (_) {
    // ignore if plugin not available
  }
  
  // 2. Small delay to let the OS settle
  await new Promise(resolve => setTimeout(resolve, 1000))

  // 3. Setup Push Notifications only on native platforms
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

  // 6. Setup Geolocation permissions on native platforms
  if (isNative && Capacitor.isPluginAvailable('Geolocation')) {
    try {
      const geoPermStatus = await Geolocation.checkPermissions()
      if (geoPermStatus.location !== 'granted') {
        await Geolocation.requestPermissions()
      }
    } catch (e) {
      console.warn('Geolocation permission sequence failed', e)
    }
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('focusin', handleFocusIn)
  window.removeEventListener('focusout', handleFocusOut)
  if (unreadTimer) clearInterval(unreadTimer)
})
</script>

<template>
  <div>
    <router-view />

    <!-- Floating Chat Launcher (visible when logged in) -->
    <button
      v-if="isLoggedIn && !(isMobile && isInputFocused)"
      @click="toggleSupportChat"
      aria-label="Open Support Chat"
      class="fixed bottom-32 right-6 z-50 bg-emerald-600 text-white shadow-xl shadow-emerald-200 rounded-full w-14 h-14 flex items-center justify-center hover:bg-emerald-700 active:scale-95 transition-all mb-[env(safe-area-inset-bottom)]"
    >
      <svg v-if="!showChat" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-7 h-7">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 .621-.504 1.125-1.125 1.125h-1.5a1.125 1.125 0 0 1-1.125-1.125v-4.25c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125Zm-16.5 0v4.25c0 .621.504 1.125 1.125 1.125h1.5a1.125 1.125 0 0 0 1.125-1.125v-4.25c0-.621-.504-1.125-1.125-1.125h-1.5a1.125 1.125 0 0 0-1.125 1.125ZM12 3c4.97 0 9 4.03 9 9.375v.125c0 .414-.336.75-.75.75h-1.5a.75.75 0 0 1-.75-.75V12c0-4.142-3.358-7.5-7.5-7.5S4.5 7.858 4.5 12v.5c0 .414-.336.75-.75.75h-1.5a.75.75 0 0 1-.75-.75v-.125C1.5 7.03 5.53 3 12 3Z" />
      </svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-7 h-7">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- Floating Chat Widget -->
    <div 
      v-if="isLoggedIn && showChat && supportRoomId" 
      class="fixed inset-0 sm:inset-auto sm:bottom-4 sm:right-4 md:right-6 z-[60] w-full h-[100dvh] sm:h-[600px] sm:w-[450px] mb-[env(safe-area-inset-bottom)] animate-in fade-in slide-in-from-bottom-4 duration-300"
    >
      <div class="h-full bg-white rounded-none sm:rounded-3xl overflow-hidden flex flex-col shadow-2xl relative border border-slate-200 dark:border-gray-700">
        <IslamicChat :room-id="supportRoomId" :show-back="false" @back="showChat = false" class="flex-1" />
        <button @click="showChat = false" class="absolute top-4 right-4 z-[70] p-1 text-slate-400 hover:text-slate-600 bg-white/80 dark:bg-gray-800/80 backdrop-blur rounded-full transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
      </div>
    </div>

    <!-- Floating Inbox Widget (visible when logged in) -->
    <button
      v-if="isLoggedIn && !(isMobile && isInputFocused)"
      @click="showInbox = true"
      aria-label="Open Inbox"
      class="fixed bottom-20 right-6 z-40 bg-white border border-slate-200 shadow-xl shadow-slate-200/50 rounded-full w-12 h-12 md:w-14 md:h-14 flex items-center justify-center hover:bg-slate-50 active:scale-95 transition-all mb-[env(safe-area-inset-bottom)]"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 md:w-7 md:h-7 text-slate-600">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.32a2.25 2.25 0 0 0-2.159-1.57H6.92a2.25 2.25 0 0 0-2.159 1.57L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />
      </svg>
      <span v-if="unreadCount>0" class="absolute -top-1 -right-1 md:-top-0 md:-right-0 bg-red-600 text-white text-[10px] md:text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 border-2 border-white">{{ unreadCount }}</span>
    </button>

    <InboxDrawer v-model="showInbox" @unread="(n)=> unreadCount = n" />

    <BaseModal />
  </div>
</template>

<style scoped>
</style>
