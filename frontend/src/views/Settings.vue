<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <AppHeader title="Settings" :showBack="true" />

    <div class="p-4 space-y-4">
      <SupportContacts />

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 text-center">
        <p class="text-xs text-slate-400 mb-4">Version 1.1.0</p>
        <button @click="logout" class="w-full py-3 rounded-2xl bg-rose-50 text-rose-600 font-bold hover:bg-rose-100 transition-colors">Sign Out</button>
      </div>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import SupportContacts from '../components/SupportContacts.vue'

const router = useRouter()
const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (_) {}
  localStorage.removeItem('token')
  localStorage.removeItem('admin_token')
  router.push('/login')
}
</script>


