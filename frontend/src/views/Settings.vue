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
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Help</p>
        <button v-if="false" @click="openWebPortal" class="btn-primary w-full h-12">Open Web Passbook</button>
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
const wa = (phone) => `https://wa.me/${phone}`
const openWebPortal = () => window.open('https://portal.example.com', '_blank')
</script>
