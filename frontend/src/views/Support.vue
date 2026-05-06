<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between sticky top-0 z-10">
      <h1 class="text-xl font-bold">Support Center</h1>
      <button @click="$router.back()" class="text-slate-600 font-medium hover:text-slate-900 transition-colors">Close</button>
    </header>

    <div class="p-4 space-y-4 max-w-3xl mx-auto">
      <div v-if="!isLoggedIn" class="bg-emerald-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-500/20 mb-6">
        <h2 class="text-xl font-black mb-2 leading-tight">Need assistance with your account?</h2>
        <p class="text-emerald-50/80 text-sm leading-relaxed mb-4">
          If you're having trouble signing in or want to learn more about our Cooperative, reach out to our team via WhatsApp or Email below.
        </p>
        <div class="flex gap-2">
          <div class="px-3 py-1 bg-white/20 rounded-full text-[10px] font-bold uppercase tracking-wider">Fast Response</div>
          <div class="px-3 py-1 bg-white/20 rounded-full text-[10px] font-bold uppercase tracking-wider">9AM - 5PM</div>
        </div>
      </div>

      <!-- Shared Contact Links -->
      <SupportContacts />

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Legal & Policy</p>
        <div class="flex gap-3 flex-wrap">
          <router-link to="/privacy" class="px-4 py-2 rounded-xl border text-emerald-700 font-semibold hover:bg-emerald-50 transition-colors text-sm">Privacy Policy</router-link>
          <router-link to="/policy" class="px-4 py-2 rounded-xl border text-emerald-700 font-semibold hover:bg-emerald-50 transition-colors text-sm">Terms of Use</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SupportContacts from '../components/SupportContacts.vue'

const isLoggedIn = computed(() => !!localStorage.getItem('token'))
</script>
