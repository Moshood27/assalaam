<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 flex justify-between items-center bg-white border-b">
      <button @click="$router.back()" class="text-2xl">⬅️</button>
      <h1 class="text-xl font-bold">Receive via QR</h1>
      <div />
    </header>

    <div class="p-4 space-y-6">
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Generate QR</h3>
        <div class="grid sm:grid-cols-2 gap-3 items-end">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount (optional)</label>
            <input v-model.number="amount" type="number" min="1" placeholder="0.00" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none"/>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Note (optional)</label>
            <input v-model.trim="note" type="text" maxlength="120" placeholder="e.g., Groceries" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none"/>
          </div>
          <div class="sm:col-span-2 flex gap-2">
            <button @click="generate" :disabled="loading" class="bg-emerald-700 text-white px-5 py-3 rounded-xl font-bold">
              {{ loading ? 'Generating…' : 'Generate QR' }}
            </button>
            <button v-if="payload" @click="reset" class="bg-slate-100 px-4 py-3 rounded-xl font-bold text-slate-700">Clear</button>
          </div>
        </div>
        <p class="text-xs text-slate-500 mt-2">You can leave amount empty to make a reusable QR.</p>
      </div>

      <div v-if="payload" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="font-bold text-slate-800">Your QR Code</h3>
            <p class="text-xs text-slate-500">Ask the payer to scan this with the Attaqwa app.</p>
          </div>
          <button @click="copy(payload)" class="text-emerald-700 text-sm font-bold">Copy Payload</button>
        </div>
        <div class="mt-5 flex flex-col items-center gap-3">
          <img :src="qrUrl" alt="QR" class="w-64 h-64 rounded-xl border" @error="imgError=true" v-if="!imgError"/>
          <div v-else class="p-4 bg-amber-50 border border-amber-100 rounded-xl text-amber-800 text-sm">
            Could not load QR image. Share this text instead:
            <div class="mt-2 p-2 bg-slate-50 border rounded text-xs break-all">{{ payload }}</div>
          </div>
          <div class="flex gap-2">
            <button @click="share" class="px-4 py-2 rounded-xl bg-slate-100 font-bold text-slate-800">Share</button>
            <button @click="copy(payload)" class="px-4 py-2 rounded-xl bg-emerald-700 text-white font-bold">Copy</button>
          </div>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500 text-xs">Merchant</span><span class="font-bold text-slate-800">{{ display.merchant?.name }}</span></div>
          <div class="flex justify-between" v-if="display.merchant?.membership_number"><span class="text-gray-500 text-xs">Member ID</span><span class="font-bold text-slate-800">{{ display.merchant.membership_number }}</span></div>
          <div class="flex justify-between" v-if="display.suggested_amount"><span class="text-gray-500 text-xs">Suggested Amount</span><span class="font-bold text-slate-800">₦ {{ formatMoney(display.suggested_amount) }}</span></div>
          <div class="flex justify-between" v-if="display.note"><span class="text-gray-500 text-xs">Note</span><span class="font-bold text-slate-800">{{ display.note }}</span></div>
        </div>
      </div>

      <div class="fixed bottom-0 left-0 right-0 bg-white border-t p-3">
        <div class="grid grid-cols-3 text-center">
          <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
            <span class="text-lg">🏠</span>
            <span class="text-[10px]">Home</span>
          </button>
          <button class="text-emerald-700 flex flex-col items-center gap-1" @click="$router.push('/wallet')">
            <span class="text-lg">👛</span>
            <span class="text-[10px] font-bold">Wallet</span>
          </button>
          <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/pay')">
            <span class="text-lg">💳</span>
            <span class="text-[10px]">Pay</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from '../http.js'

const amount = ref()
const note = ref('')
const payload = ref('')
const display = ref({})
const loading = ref(false)
const imgError = ref(false)

const qrUrl = computed(() => payload.value ? `https://api.qrserver.com/v1/create-qr-code/?size=512x512&data=${encodeURIComponent(payload.value)}` : '')

function formatMoney(n) {
  try { return Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } catch { return n }
}

async function generate() {
  loading.value = true
  imgError.value = false
  try {
    const params = {}
    if (amount.value) params.amount = Number(amount.value)
    if (note.value) params.note = note.value
    const { data } = await axios.get('/api/merchant/pay/qr', { params })
    payload.value = data?.payload || ''
    display.value = data?.display || {}
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to generate QR')
  } finally {
    loading.value = false
  }
}

function reset() {
  payload.value = ''
  display.value = {}
  imgError.value = false
}

async function copy(text) {
  try { await navigator.clipboard.writeText(text); alert('Copied'); } catch { alert('Copy failed') }
}

async function share() {
  const text = `Pay with Attaqwa\n${payload.value}`
  try {
    if (navigator.share) {
      await navigator.share({ title: 'Pay with Attaqwa', text })
    } else {
      await navigator.clipboard.writeText(text)
      alert('Copied to clipboard')
    }
  } catch (_) {}
}
</script>

<style scoped>
</style>
