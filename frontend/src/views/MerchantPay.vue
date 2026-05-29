<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Pay Merchant</h1>
        <div class="w-6"></div>
      </div>
    </header>

    <div class="p-4 space-y-6 max-w-md mx-auto">
      <div class="card card-elevated p-6">
        <h3 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
          </svg>
          Scan or Paste QR
        </h3>
        <p class="text-xs text-slate-500 mb-3">Paste the QR payload text here. Camera scanning will be added later.</p>
        <textarea v-model.trim="qr" rows="3" class="inp text-sm mb-4" placeholder="assalaam:pay?to_type=membership&to=...&amount=...&note=..."></textarea>
        <div class="flex flex-wrap gap-2">
          <button @click="paste" class="btn-muted px-4 py-2">Paste</button>
          <button v-if="canScan" @click="scan" class="bg-white border border-blue-200 text-blue-700 px-4 py-2 rounded-xl font-bold hover:bg-blue-50 transition">Scan QR</button>
          <button @click="resolve" :disabled="!qr || loading" class="btn-primary flex-1 py-2">{{ loading ? 'ResolvingÃ¢â‚¬Â¦' : 'Resolve' }}</button>
        </div>
        <p v-if="scanError" class="mt-3 p-3 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-sm">{{ scanError }}</p>
        <p v-if="error" class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-sm">{{ error }}</p>
      </div>

      <div v-if="multiple" class="card card-elevated p-6">
        <h3 class="font-bold text-slate-800 mb-2">Select Merchant Branch</h3>
        <p class="text-xs text-slate-500 mb-3">This Member ID exists in multiple branches. Please select one.</p>
        <select v-model.number="branchId" class="inp mb-4">
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <div class="flex gap-2">
          <button @click="previewRecipient" :disabled="!branchId || loading" class="btn-muted flex-1 py-2">Preview</button>
          <button @click="proceedAfterBranch" :disabled="!branchId" class="btn-primary flex-1 py-2">Continue</button>
        </div>
      </div>

      <div v-if="recipient" class="card card-elevated p-6">
        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Confirm Details
        </h3>
        <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100 text-blue-900 text-sm mb-6">
          <div class="flex flex-col">
            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-0.5">Pay to</span>
            <span class="font-black text-base">{{ recipient.name }}</span>
            <div class="flex items-center gap-2 mt-1">
              <span v-if="recipient.membership_number" class="text-blue-700 font-bold">{{ recipient.membership_number }}</span>
              <span v-if="recipient.branch_name" class="text-blue-500 text-xs font-medium">| {{ recipient.branch_name }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label class="lbl">Amount</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Ã¢â€šÂ¦</span>
              <input v-model.number="amount" type="number" min="1" placeholder="0.00" class="inp pl-8 font-bold text-lg" />
            </div>
          </div>
          <div>
            <label class="lbl">Note</label>
            <input v-model.trim="note" type="text" maxlength="120" placeholder="What's this for?" class="inp" />
          </div>
          <div>
            <label class="lbl">Transaction PIN</label>
            <input v-model="pin" type="password" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="4-digit PIN" class="inp tracking-[1em] text-center font-black" />
          </div>
          <div class="flex gap-3 pt-2">
            <button @click="pay" :disabled="loading || !amount || !pin || pin.length !== 4" class="btn-primary flex-1 py-4 text-lg">
              {{ loading ? 'PayingÃ¢â‚¬Â¦' : 'Pay Now' }}
            </button>
            <button @click="reset" class="btn-muted px-6 py-4">Clear</button>
          </div>
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
          <button class="nav-item group" @click="$router.push('/wallet')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
            </svg>
            <span>Wallet</span>
          </button>
          <button class="nav-item group active" @click="$router.push('/pay')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <span>Pay</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from '../http.js'
import { BarcodeScanner } from '@capacitor-mlkit/barcode-scanning'

const canScan = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))

const qr = ref('')
const loading = ref(false)
const error = ref('')
const scanError = ref('')
const scanning = ref(false)

const multiple = ref(false)
const branches = ref([])
const branchId = ref()

const toType = ref('')
const toVal = ref('')
const recipient = ref(null)
const amount = ref()
const note = ref('')
const pin = ref('')

function reset() {
  qr.value = ''
  error.value = ''
  multiple.value = false
  branches.value = []
  branchId.value = undefined
  toType.value = ''
  toVal.value = ''
  recipient.value = null
  amount.value = undefined
  note.value = ''
  pin.value = ''
}

async function paste() {
  try { qr.value = await navigator.clipboard.readText() } catch { alert('Clipboard read failed') }
}

async function scan() {
  scanError.value = ''
  if (!canScan) { scanError.value = 'Scanning is only available on the mobile app.'; return }
  try {
    const perm = await BarcodeScanner.checkPermissions()
    if (perm.camera !== 'granted') {
      const req = await BarcodeScanner.requestPermissions()
      if (req.camera !== 'granted') {
        scanError.value = 'Camera permission denied'
        return
      }
    }
    const { barcodes } = await BarcodeScanner.scan({ formats: ['qrCode'], lensFacing: 'back' })
    const code = Array.isArray(barcodes) && barcodes[0]
      ? (barcodes[0].rawValue || barcodes[0].displayValue || barcodes[0].content || '')
      : ''
    if (code) {
      qr.value = String(code)
      await new Promise(r => setTimeout(r, 10))
      await resolve()
    } else {
      scanError.value = 'No QR code detected'
    }
  } catch (e) {
    scanError.value = e?.message || 'Failed to scan QR'
  }
}

async function stopScan() {
  // No persistent preview to stop when using single-shot scan()
}

async function resolve() {
  error.value = ''
  multiple.value = false
  recipient.value = null
  loading.value = true
  try {
    const { data } = await axios.post('/api/merchant/pay/resolve', { qr: qr.value })
    toType.value = data.to_type
    toVal.value = data.to
    branchId.value = data.branch_id || data.recipient?.branch_id
    recipient.value = data.recipient
    amount.value = data.amount || amount.value
    note.value = data.note || note.value
  } catch (e) {
    const res = e?.response
    if (res?.status === 422 && res?.data?.multiple) {
      const d = res.data
      multiple.value = true
      branches.value = d.branches || []
      toType.value = d.to_type
      toVal.value = d.to
      amount.value = d.amount || amount.value
      note.value = d.note || note.value
      error.value = 'Multiple members found. Please select a branch.'
    } else {
      error.value = res?.data?.message || 'Failed to resolve QR'
    }
  } finally {
    loading.value = false
  }
}

async function previewRecipient() {
  try {
    const params = { to_type: toType.value || 'membership', to: toVal.value, branch_id: branchId.value }
    const { data } = await axios.get('/api/wallet/transfer/resolve', { params })
    recipient.value = data
    error.value = ''
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to preview recipient'
  }
}

function proceedAfterBranch() {
  // We may not have the name yet; allow paying after selecting branch
  if (!recipient.value) {
    // try to fetch preview silently
    previewRecipient()
  }
}

async function pay() {
  loading.value = true
  error.value = ''
  try {
    const body = { qr: qr.value, pin: pin.value }
    if (amount.value) body.amount = Number(amount.value)
    if (note.value) body.note = note.value
    if (branchId.value) body.branch_id = Number(branchId.value)

    await axios.post('/api/merchant/pay', body)
    alert('Payment successful')
    // Go to wallet to see updated balance
    try { await refreshWalletCache() } catch {}
    setTimeout(() => { window?.history?.length ? history.back() : (location.href = '/wallet') }, 300)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Payment failed'
  } finally {
    loading.value = false
  }
}

async function refreshWalletCache() {
  try { await axios.get('/api/wallet', { params: { t: Date.now() } }) } catch {}
}
</script>

<style scoped>
</style>


