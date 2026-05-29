<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-6 font-sans">
    <div class="bg-white p-8 rounded-[2rem] shadow-xl text-center max-w-sm w-full border border-slate-100">
      <div class="flex justify-center mb-6">
        <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-10 h-10 text-blue-600 animate-pulse">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
          </svg>
        </div>
      </div>
      <h1 class="text-2xl font-black text-slate-800 mb-2 tracking-tight">Updating Wallet</h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Reference: {{ reference || 'N/A' }}</p>
      
      <div class="flex items-center justify-center gap-3 bg-slate-50 p-4 rounded-2xl">
        <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-700 border-t-transparent"></div>
        <p class="text-xs font-medium text-slate-600">Finalizing top-upÃ¢â‚¬Â¦</p>
      </div>

      <p class="text-[10px] text-slate-400 mt-8 italic">You will be redirected to your wallet shortly.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const reference = route.query.reference || route.query.trxref || ''
const status = (route.query.status || '').toString().toLowerCase()

onMounted(() => {
  // Basic UX notification
  if (status === 'cancelled' || status === 'failed') {
    alert('Payment was cancelled. You can try again.')
  } else if (reference) {
    alert('Payment successful! Your wallet will update shortly.')
  } else {
    alert('Returning from payment.')
  }
  setTimeout(() => router.replace({ name: 'wallet' }), 500)
})
</script>


