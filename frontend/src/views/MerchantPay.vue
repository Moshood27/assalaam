<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 flex justify-between items-center bg-white border-b">
      <button @click="$router.back()" class="text-2xl">⬅️</button>
      <h1 class="text-xl font-bold">Pay Merchant</h1>
      <div />
    </header>

    <div class="p-4 space-y-6">
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Scan or Paste QR</h3>
        <p class="text-xs text-slate-500 mb-2">Paste the QR payload text here. Camera scanning will be added later.</p>
        <textarea v-model.trim="qr" rows="3" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="attaqwa:pay?to_type=membership&to=...&amount=...&note=..."></textarea>
        <div class="mt-2 flex flex-wrap gap-2">
          <button @click="paste" class="bg-slate-100 px-4 py-2 rounded-xl font-bold text-slate-800">Paste</button>
          <button v-if="canScan" @click="scan" class="bg-white border border-emerald-200 text-emerald-700 px-4 py-2 rounded-xl font-bold">Scan QR</button>
          <button @click="resolve" :disabled="!qr || loading" class="bg-emerald-700 text-white px-5 py-2 rounded-xl font-bold">{{ loading ? 'Resolving…' : 'Resolve' }}</button>
        </div>
        <p v-if="scanError" class="mt-2 p-3 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-sm">{{ scanError }}</p>
        <p v-if="error" class="mt-2 p-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-sm">{{ error }}</p>
      </div>


      <div v-if="multiple" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Select Merchant Branch</h3>
        <p class="text-xs text-slate-500">This Member ID exists in multiple branches. Please select one.</p>
        <select v-model.number="branchId" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none mt-2">
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <div class="mt-3 flex gap-2">
          <button @click="previewRecipient" :disabled="!branchId || loading" class="bg-slate-100 px-4 py-2 rounded-xl font-bold text-slate-800">Preview</button>
          <button @click="proceedAfterBranch" :disabled="!branchId" class="bg-emerald-700 text-white px-5 py-2 rounded-xl font-bold">Continue</button>
        </div>
      </div>

      <div v-if="recipient" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Confirm Details</h3>
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm">
          Pay to: <span class="font-bold">{{ recipient.name }}</span>
          <span v-if="recipient.membership_number" class="text-emerald-700">({{ recipient.membership_number }})</span>
          <span v-if="recipient.branch_name" class="ml-1">— {{ recipient.branch_name }}</span>
        </div>
        <div class="grid sm:grid-cols-2 gap-3 mt-4 items-end">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount</label>
            <input v-model.number="amount" type="number" min="1" placeholder="0.00" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Note</label>
            <input v-model.trim="note" type="text" maxlength="120" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Transaction PIN</label>
            <input v-model="pin" type="password" inputmode="numeric" pattern="\\d{4}" maxlength="4" placeholder="4-digit PIN" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none tracking-widest" />
          </div>
          <div class="sm:col-span-2 flex gap-2">
            <button @click="pay" :disabled="loading || !amount || !pin || pin.length !== 4" class="bg-emerald-700 text-white px-5 py-3 rounded-xl font-bold">{{ loading ? 'Paying…' : 'Pay Now' }}</button>
            <button @click="reset" class="bg-slate-100 px-4 py-3 rounded-xl font-bold text-slate-700">Clear</button>
          </div>
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
import { ref } from 'vue'
import axios from '../http.js'
import { BarcodeScanner, BarcodeFormat, LensFacing } from '@capacitor-mlkit/barcode-scanning'
import { Capacitor } from '@capacitor/core'

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
    const { barcodes } = await BarcodeScanner.scan({ formats: [BarcodeFormat.QR_CODE], lensFacing: LensFacing.BACK })
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

    const { data } = await axios.post('/api/merchant/pay', body)
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
