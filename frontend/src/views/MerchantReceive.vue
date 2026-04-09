<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Receive via QR</h1>
        <div class="w-6"></div>
      </div>
    </header>

    <div class="p-4 space-y-6 max-w-md mx-auto">
      <div class="card card-elevated p-6">
        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
          </svg>
          Generate QR
        </h3>
        <div class="space-y-4">
          <div>
            <label class="lbl">Amount (optional)</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₦</span>
              <input v-model.number="amount" type="number" min="1" placeholder="0.00" class="inp pl-8" />
            </div>
          </div>
          <div>
            <label class="lbl">Note (optional)</label>
            <input v-model.trim="note" type="text" maxlength="120" placeholder="e.g., Groceries" class="inp" />
          </div>
          <div class="flex gap-2 pt-2">
            <button @click="generate" :disabled="loading" class="btn-primary flex-1 py-4">
              {{ loading ? 'Generating…' : 'Generate QR' }}
            </button>
            <button v-if="payload" @click="reset" class="btn-muted px-6 py-4">Clear</button>
          </div>
        </div>
        <p class="text-[10px] text-slate-400 mt-4 text-center italic">You can leave amount empty to make a reusable QR.</p>
      </div>

      <div v-if="payload" class="card card-elevated p-6 border-t-4 border-emerald-600">
        <div class="flex items-start justify-between gap-4 mb-6">
          <div>
            <h3 class="font-bold text-slate-800">Your QR Code</h3>
            <p class="text-xs text-slate-500">Ask the payer to scan this with the Attaqwa app.</p>
          </div>
          <button @click="copy(payload)" class="text-emerald-700 text-xs font-black uppercase tracking-wider hover:opacity-70">Copy Payload</button>
        </div>
        
        <div class="flex flex-col items-center gap-6">
          <div class="relative p-4 bg-white rounded-3xl shadow-inner border border-slate-100">
            <img :src="qrUrl" alt="QR" class="w-56 h-56 rounded-xl" @error="imgError=true" v-if="!imgError"/>
            <div v-else class="w-56 h-56 flex flex-col items-center justify-center p-4 bg-amber-50 border border-amber-100 rounded-xl text-amber-800 text-center text-xs">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2 opacity-50">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
              Could not load QR image.
            </div>
          </div>
          
          <div class="flex gap-3 w-full">
            <button @click="share" class="btn-muted flex-1 py-3 flex items-center justify-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0-10.628a2.25 2.25 0 110-4.5 2.25 2.25 0 010 4.5zm0 10.628a2.25 2.25 0 110 4.5 2.25 2.25 0 010-4.5z" />
              </svg>
              Share
            </button>
            <button @click="copy(payload)" class="btn-primary flex-1 py-3 flex items-center justify-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
              </svg>
              Copy
            </button>
          </div>
        </div>

        <div class="mt-8 space-y-3 pt-6 border-t border-slate-50">
          <div class="flex justify-between items-center"><span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Merchant</span><span class="font-bold text-slate-800 text-sm text-right">{{ display.merchant?.name }}</span></div>
          <div class="flex justify-between items-center" v-if="display.merchant?.membership_number"><span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Member ID</span><span class="font-bold text-slate-700 text-sm">{{ display.merchant.membership_number }}</span></div>
          <div class="flex justify-between items-center" v-if="display.suggested_amount"><span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Suggested Amount</span><span class="font-black text-emerald-700 text-sm">₦ {{ formatMoney(display.suggested_amount) }}</span></div>
          <div class="flex justify-between items-center" v-if="display.note"><span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Note</span><span class="font-medium text-slate-600 text-sm text-right italic">"{{ display.note }}"</span></div>
        </div>
      </div>

      <div class="bottom-nav">
        <div class="bottom-nav-inner">
          <button class="nav-item group" @click="$router.push('/dashboard')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span>Home</span>
          </button>
          <button class="nav-item group active" @click="$router.push('/wallet')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
            </svg>
            <span>Wallet</span>
          </button>
          <button class="nav-item group" @click="$router.push('/pay')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <span>Pay</span>
          </button>
        </div>
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
