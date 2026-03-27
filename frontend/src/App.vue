<script setup>
import { onMounted } from 'vue'
import { PushNotifications } from '@capacitor/push-notifications'
import { SplashScreen } from '@capacitor/splash-screen'
import BaseModal from './components/BaseModal.vue'
import axios from 'axios'

async function saveTokenToBackend(token) {
  try {
    await axios.post('/api/push/token', { token })
  } catch (e) {
    console.warn('Failed to save push token:', e?.message || e)
  }
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

  // 3. Setup Push Notifications
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
})
</script>

<template>
  <router-view />
  <BaseModal />
</template>

<style scoped>
</style>
