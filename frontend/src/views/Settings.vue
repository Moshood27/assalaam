<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors" title="Back">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 class="text-lg font-bold text-slate-800">Support</h1>
        <div class="w-10"></div>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <SupportContacts />

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 text-center">
        <p class="text-xs text-slate-400 mb-4">Version 1.1.0</p>
        <button @click="logout" class="w-full py-3 rounded-2xl bg-rose-50 text-rose-600 font-bold hover:bg-rose-100 transition-colors">Sign Out</button>
      </div>
    </div>

    <nav class="bottom-nav" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/wallet')">
        <span class="text-xl">👛</span>
        <span class="text-[10px] font-bold">Wallet</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/passbook')">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/profile')">
        <span class="text-xl">👤</span>
        <span class="text-[10px] font-bold">Profile</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
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
