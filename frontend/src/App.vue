<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import BaseModal from './components/BaseModal.vue'
import MobileSplash from './components/MobileSplash.vue'

const showSplash = ref(true)

function onAppReady() {
  // Small delay to allow first paint to settle
  setTimeout(() => (showSplash.value = false), 100)
}

onMounted(() => {
  window.addEventListener('app:ready', onAppReady)
  // Fallback: auto-hide after 5s in case ready event never fires
  const fallback = setTimeout(() => (showSplash.value = false), 5000)
  onBeforeUnmount(() => clearTimeout(fallback))
})

// Request push notification permission and register as early as possible
onMounted(async () => {
  try {
    const platform = Capacitor?.getPlatform?.() || 'web'
    if (platform === 'web') return

    // 1. Check status immediately on startup
    let permStatus = await PushNotifications.checkPermissions()
    console.log('Initial Push Status:', permStatus.receive)

    // 2. If it's the first time (prompt) or denied, ask again
    if (permStatus.receive !== 'granted') {
      permStatus = await PushNotifications.requestPermissions()
    }

    // 3. Register if granted (This is what gets the token)
    if (permStatus.receive === 'granted') {
      await PushNotifications.register()
    }
  } catch (e) {
    console.warn('Push permission flow error:', e?.message || e)
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('app:ready', onAppReady)
})
</script>

<template>
  <MobileSplash :visible="showSplash" />
  <router-view />
  <BaseModal />
</template>

<style scoped>
</style>
