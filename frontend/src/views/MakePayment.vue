<template>
  <div class="min-h-screen bg-slate-50 pb-56 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Make Payment</h1>
        <span></span>
      </div>
    </header>

    <div class="p-4 space-y-6 max-w-md mx-auto">
      <!-- Wallet Balance -->
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-6 text-white shadow-xl">
        <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-widest">Wallet Balance</p>
        <p class="text-3xl font-extrabold tracking-tight mt-1">₦ {{ Number(walletBalance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
      </div>

      <!-- Add Payment Item -->
      <div class="card card-elevated p-6">
        <p class="text-[11px] text-rose-500 font-bold mb-4 uppercase">
          ⚠️ Click the "+" to split across multiple schemes
        </p>
        <div class="space-y-4">
          <div>
            <label class="lbl">Scheme</label>
            <select v-model="selectedSchemeId" class="inp">
              <option value="">Select Scheme</option>
              <option v-for="s in schemes" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="lbl">Project (optional)</label>
            <select v-model="selectedProjectId" class="inp">
              <option value="">No Project</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div>
            <label class="lbl">Amount</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₦</span>
              <input v-model.number="inputAmount" type="number" inputmode="decimal" placeholder="0.00" class="inp pl-8 text-xl font-black" />
            </div>
          </div>
          <button @click="addToList" class="btn-primary w-full py-4 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add to List
          </button>
        </div>
        <div class="mt-3 flex items-center gap-2">
          <input id="fine" type="checkbox" v-model="isFine" class="accent-emerald-700">
          <label for="fine" class="text-sm text-slate-700">Lateness/Apology Fine (Audit)</label>
        </div>
      </div>

      <!-- Payment Summary -->
      <h3 class="font-bold text-slate-800 mb-2 px-2">Payment Summary</h3>
      <div v-if="paymentList.length > 0" class="space-y-3">
        <div v-for="(item, index) in paymentList" :key="index" class="card p-4 flex items-center justify-between border-l-4 border-emerald-700">
          <div>
            <div class="flex items-center flex-wrap gap-2">
              <p class="font-bold text-slate-800 text-sm">{{ item.scheme_name }}</p>
              <span v-if="item.project_name" class="badge bg-emerald-100 text-emerald-700">Project: {{ item.project_name }}</span>
              <span v-if="item.category === 'fine'" class="badge badge-muted bg-rose-100 text-rose-700">Fine</span>
            </div>
            <p class="text-xs text-slate-500">Scheduled Payment</p>
          </div>
          <div class="flex items-center gap-4">
            <p class="font-bold text-slate-800">₦ {{ Number(item.amount).toLocaleString() }}</p>
            <button @click="removeFromList(index)" class="btn-muted text-rose-700 border-rose-200 hover:bg-rose-50 px-3 py-1 rounded-lg text-xs" aria-label="Remove from list">Remove</button>
          </div>
        </div>
        <div ref="summaryEnd"></div>
      </div>
      <div v-else class="card p-6 text-center empty-state">No schemes added yet.</div>
    </div>

    <div class="fixed left-0 right-0 bottom-16 p-4">
      <div class="card card-elevated p-4">
        <div class="flex justify-between items-center mb-2">
          <span class="text-gray-500 font-bold uppercase text-[10px] tracking-widest">Total to Transfer</span>
          <span class="text-2xl font-black text-slate-900">₦ {{ Number(totalAmount).toLocaleString() }}</span>
        </div>
        <div class="flex items-center justify-between mb-3">
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" v-model="payFromWallet" class="accent-emerald-700" />
            Pay from wallet
          </label>
          <span class="text-xs text-slate-500">Balance: ₦ {{ Number(walletBalance).toLocaleString() }}</span>
        </div>
        <button @click="initiatePayment" :disabled="paymentList.length === 0 || loading" class="btn-primary w-full py-4 text-lg">
          {{ loading ? 'Processing…' : (payFromWallet ? 'Allocate from Wallet' : 'Make Payment') }}
        </button>
      </div>
    </div>

    <!-- Custom Notice Modal -->
    <CustomNotice
      v-model="notice.visible"
      :type="notice.type"
      :title="notice.title"
      :message="notice.message"
      @close="closeNotice"
    />

    <!-- PIN Prompt Modal -->
    <CustomNotice
      v-model="pinPrompt.visible"
      :type="'info'"
      :title="'Confirm Transfer'"
      :message="'Enter your 4-digit Transaction PIN to confirm transfer.'"
      :prompt="true"
      inputLabel="Transaction PIN (4 digits)"
      confirmText="Confirm"
      cancelText="Cancel"
      :busy="loading"
      @confirm="handlePinConfirm"
      @cancel="handlePinCancel"
    />

    <div class="bottom-nav">
      <div class="bottom-nav-inner">
        <button class="nav-item group" @click="$router.push('/dashboard')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
          </svg>
          <span>Home</span>
        </button>
        <button class="nav-item group" @click="$router.push('/passbook')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
          </svg>
          <span>Passbook</span>
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
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'

const router = useRouter()
const baseRaw = import.meta?.env?.BASE_URL || '/'
const basePath = (baseRaw && baseRaw.startsWith('./')) ? '/' : (baseRaw.endsWith('/') ? baseRaw : `${baseRaw}/`)
const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))

const schemes = ref([])
const projects = ref([])
const paymentList = ref([])
const selectedSchemeId = ref('')
const selectedProjectId = ref('')
const inputAmount = ref('')
const loading = ref(false)
const isFine = ref(false)
const payFromWallet = ref(false)
const walletBalance = ref(0)
const summaryEnd = ref(null)

const totalAmount = computed(() => paymentList.value.reduce((sum, i) => sum + Number(i.amount || 0), 0))

// Custom notice (shared)
const { notice, showNotice, closeNotice } = useNotice()

// PIN prompt modal state
const pinPrompt = ref({ visible: false })

const addToList = () => {
  if (!selectedSchemeId.value || !inputAmount.value || Number(inputAmount.value) <= 0) return
  // robust id compare (string/number)
  const s = schemes.value.find(x => String(x.id) == String(selectedSchemeId.value))
  if (!s) return
  const pid = selectedProjectId.value ? String(selectedProjectId.value) : ''
  const p = pid ? projects.value.find(x => String(x.id) == pid) : null
  const item = { scheme_id: s.id, scheme_name: s.name, amount: Number(inputAmount.value), category: isFine.value ? 'fine' : 'deposit' }
  if (p) {
    item.project_id = p.id
    item.project_name = p.name
  }
  paymentList.value.push(item)
  // Smooth scroll to the end of the payment summary after DOM updates
  nextTick(() => {
    try {
      summaryEnd.value?.scrollIntoView({ behavior: 'smooth', block: 'end' })
    } catch (_) {}
  })
  selectedSchemeId.value = ''
  selectedProjectId.value = ''
  inputAmount.value = ''
  isFine.value = false
}

const removeFromList = (idx) => paymentList.value.splice(idx, 1)

const loadSchemes = async () => {
  const { data } = await axios.get('/api/schemes')
  // Combine Savings and Shares into "Passbook" for the member-facing page
  const savings = data.find(s => s.name === 'Savings')
  const shares = data.find(s => s.name === 'Shares')
  const passbook = data.find(s => s.name === 'Passbook')

  if (savings && shares && passbook) {
    // Show Passbook instead of Savings/Shares
    schemes.value = data.filter(s => s.name !== 'Savings' && s.name !== 'Shares')
  } else {
    schemes.value = data
  }
}

const loadProjects = async () => {
  try {
    const { data } = await axios.get('/api/projects')
    projects.value = Array.isArray(data) ? data : []
  } catch (_) {}
}

const loadWallet = async () => {
  try {
    const { data } = await axios.get('/api/wallet')
    walletBalance.value = data.balance || 0
  } catch (_) {}
}

const initiatePayment = async () => {
  // If paying from wallet, show custom PIN prompt modal
  if (payFromWallet.value) {
    pinPrompt.value.visible = true
    return
  }

  // Otherwise, go through Paystack checkout
  try {
    loading.value = true
    const callback_url = `${window.location.origin}${basePath}payment-callback`
    const { data } = await axios.post('/api/initiate-payment', { items: paymentList.value, callback_url })
    window.location.href = data.checkout_url
  } catch (e) {
    const status = e?.response?.status
    const msg = e?.response?.data?.message || 'Payment failed'
    if (status === 409) {
      alert('You need to set your Transaction PIN first. Go to Profile > Transaction PIN.')
    } else if (status === 403) {
      alert('Invalid Transaction PIN. Please try again.')
    } else {
      alert(msg)
    }
  } finally {
    loading.value = false
  }
}

const handlePinConfirm = async (val) => {
  const pin = String(val || '').trim()
  if (!/^\d{4}$/.test(pin)) {
    showNotice('Invalid PIN', 'Please enter a valid 4-digit PIN.', 'error')
    return
  }
  loading.value = true
  try {
    await axios.post('/api/wallet/allocate', { items: paymentList.value, pin })
    pinPrompt.value.visible = false
    // Navigate on success (same behavior as before)
    router.replace({ name: 'dashboard' })
  } catch (e) {
    pinPrompt.value.visible = false
    const status = e?.response?.status
    const msg = e?.response?.data?.message || 'Payment failed'
    if (status === 409) {
      showNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
    } else if (status === 403) {
      showNotice('Invalid PIN', 'Invalid Transaction PIN. Please try again.', 'error')
    } else {
      showNotice('Failed', msg, 'error')
    }
  } finally {
    loading.value = false
  }
}

const handlePinCancel = () => {
  pinPrompt.value.visible = false
}

onMounted(async () => {
  await Promise.all([loadSchemes(), loadProjects(), loadWallet()])
})
</script>
