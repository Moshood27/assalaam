<template>
  <div class="space-y-4">
    <!-- WhatsApp Contacts -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
      <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">WhatsApp Contacts</p>
      <div class="space-y-3">
        <a v-for="(p, i) in phones" :key="i" :href="wa(p.number)" target="_blank" class="flex items-center justify-between p-4 rounded-2xl border hover:bg-emerald-50 transition-colors group">
          <div class="flex items-center gap-3">
            <span class="text-2xl group-hover:scale-110 transition-transform">🟢</span>
            <div>
              <p class="font-bold text-slate-800">{{ p.label }}</p>
              <p class="text-xs text-slate-500">Tap to chat on WhatsApp</p>
            </div>
          </div>
          <span class="text-emerald-700 font-bold">Open</span>
        </a>
      </div>
    </div>

    <!-- Email Contact -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
      <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Email Support</p>
      <div class="flex items-center justify-between">
        <p class="text-slate-700 text-sm">
          Reach us at
          <a :href="`mailto:${email}`" class="text-emerald-700 font-bold hover:underline ml-1">{{ email }}</a>
        </p>
        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const phones = [
  { label: 'Support 1', number: '2348012345678' },
  { label: 'Support 2', number: '2348098765432' },
  { label: 'Support 3', number: '2348076543210' },
]
const email = 'support@example.com'
const wa = (phone) => `https://wa.me/${phone}`
</script>
