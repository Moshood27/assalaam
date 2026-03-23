<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-6">
    <div class="bg-white p-8 rounded-2xl shadow-sm text-center max-w-sm w-full">
      <div class="text-5xl mb-4">✅</div>
      <h1 class="text-xl font-bold text-slate-800 mb-2">Processing Payment</h1>
      <p class="text-sm text-slate-500">Reference: {{ reference || 'N/A' }}</p>
      <p class="text-xs text-slate-400 mt-2">You will be redirected to your dashboard shortly…</p>
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
  if (status === 'cancelled' || status === 'failed') {
    alert('Payment was cancelled. You can try again.')
  } else if (reference) {
    alert('Payment successful! Your contributions will reflect shortly.')
  } else {
    alert('Returning from payment.')
  }
  setTimeout(() => router.replace({ name: 'dashboard' }), 600)
})
</script>
