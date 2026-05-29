<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-6 font-sans">
    <div class="bg-white p-8 rounded-[2rem] shadow-xl text-center max-w-sm w-full border border-slate-100">
      <div class="flex justify-center mb-6">
        <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-10 h-10 text-blue-600 animate-bounce">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <h1 class="text-2xl font-black text-slate-800 mb-2 tracking-tight">Processing Payment</h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Reference: {{ reference || 'N/A' }}</p>
      
      <div class="flex items-center justify-center gap-3 bg-slate-50 p-4 rounded-2xl">
        <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-700 border-t-transparent"></div>
        <p class="text-xs font-medium text-slate-600">Verifying transaction...</p>
      </div>

      <p class="text-[10px] text-slate-400 mt-8 italic">You will be redirected to your dashboard shortly.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from '../http'

const router = useRouter()
const route = useRoute()

const reference = route.query.reference || route.query.trxref || route.query.tx_ref || ''
const gateway = route.query.gateway || 'paystack'
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
    const { data } = await axios.post('/api/verify-payment', { reference, gateway })
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


