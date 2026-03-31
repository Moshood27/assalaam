<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-xl font-bold">Support</h1>
      <button @click="$router.back()" class="text-slate-600">Close</button>
    </header>

    <div class="p-4 space-y-4 max-w-3xl mx-auto">
      <!-- In-App Chat Widget -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-4">
        <ChatWidget />
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">WhatsApp Contacts</p>
        <div class="space-y-3">
          <a :href="wa('2348012345678')" target="_blank" class="flex items-center justify-between p-4 rounded-2xl border hover:bg-emerald-50">
            <div class="flex items-center gap-3">
              <span class="text-2xl">🟢</span>
              <div>
                <p class="font-bold text-slate-800">Support 1</p>
                <p class="text-xs text-slate-500">Tap to chat on WhatsApp</p>
              </div>
            </div>
            <span class="text-emerald-700 font-bold">Open</span>
          </a>
          <a :href="wa('2348098765432')" target="_blank" class="flex items-center justify-between p-4 rounded-2xl border hover:bg-emerald-50">
            <div class="flex items-center gap-3">
              <span class="text-2xl">🟢</span>
              <div>
                <p class="font-bold text-slate-800">Support 2</p>
                <p class="text-xs text-slate-500">Tap to chat on WhatsApp</p>
              </div>
            </div>
            <span class="text-emerald-700 font-bold">Open</span>
          </a>
          <a :href="wa('2348076543210')" target="_blank" class="flex items-center justify-between p-4 rounded-2xl border hover:bg-emerald-50">
            <div class="flex items-center gap-3">
              <span class="text-2xl">🟢</span>
              <div>
                <p class="font-bold text-slate-800">Support 3</p>
                <p class="text-xs text-slate-500">Tap to chat on WhatsApp</p>
              </div>
            </div>
            <span class="text-emerald-700 font-bold">Open</span>
          </a>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Email</p>
        <p class="text-slate-700">
          Prefer email? Reach us at
          <a href="mailto:support@example.com" class="text-emerald-700 font-semibold hover:underline">support@example.com</a>
        </p>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Helpful Links</p>
        <div class="flex gap-3 flex-wrap">
          <router-link to="/privacy" class="px-4 py-2 rounded-xl border text-emerald-700 font-semibold hover:bg-emerald-50">Privacy Policy</router-link>
          <router-link to="/policy" class="px-4 py-2 rounded-xl border text-emerald-700 font-semibold hover:bg-emerald-50">Terms / Policy</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import ChatWidget from '../components/ChatWidget.vue'
const wa = (phone) => `https://wa.me/${phone}`
</script>
