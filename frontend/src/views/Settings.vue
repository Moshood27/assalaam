<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-xl font-bold">Support</h1>
      <button @click="$router.back()" class="text-slate-600">Close</button>
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
        <button @click="openWebPortal" class="btn-primary w-full h-12">Open Web Passbook</button>
      </div>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1">
        <span class="text-xl">🛟</span>
        <span class="text-[10px] font-bold">Support</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/profile')">
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
