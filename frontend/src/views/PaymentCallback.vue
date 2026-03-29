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
import axios from '../http'

const router = useRouter()
const route = useRoute()

const reference = route.query.reference || route.query.trxref || ''
const status = (route.query.status || '').toString().toLowerCase()

onMounted(async () => {
  try {
    if (!reference) {
      alert('Returning from payment.')
      return
    }

    if (status === 'cancelled' || status === 'failed') {
      alert('Payment was cancelled. You can try again.')
      return
    }

    // Server-side verification: prevents spoofing
    const { data } = await axios.post('/api/verify-payment', { reference })
    if (data?.status === 'success') {
      alert('Payment verified! Your contributions have been allocated.')
    } else if (data?.status === 'pending') {
      alert('Payment is pending confirmation. It will reflect shortly if successful.')
    } else {
      alert('Payment not successful yet. Please check your Passbook later or contact support with Ref: ' + reference)
    }
  } catch (e) {
    // Even if verify fails (e.g., network), webhook will finalize; avoid exposing details
    alert('We are verifying your payment in the background. If successful, it will reflect shortly. Ref: ' + reference)
  } finally {
    setTimeout(() => router.replace({ name: 'dashboard' }), 800)
  }
})
</script>
