<template>
  <div class="min-h-screen bg-slate-50 pb-72 font-sans">
    <AppHeader title="Make Payment" :showBack="true" />

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

        <!-- Gateway Selection (only if not paying from wallet) -->
        <div v-if="!payFromWallet" class="mb-4">
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-2 px-1">Payment Gateway</p>
          <div class="grid grid-cols-3 gap-2">
            <button 
              @click="selectedGateway = 'paystack'"
              type="button"
              :class="['p-2.5 rounded-xl border-2 transition-all text-center relative overflow-hidden', selectedGateway === 'paystack' ? 'border-emerald-600 bg-emerald-50' : 'border-slate-100 bg-white']"
            >
              <p class="font-bold text-[10px]" :class="selectedGateway === 'paystack' ? 'text-emerald-700' : 'text-slate-600'">Paystack</p>
              <div v-if="selectedGateway === 'paystack'" class="absolute top-0.5 right-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-emerald-600">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>
            <button 
              @click="selectedGateway = 'flutterwave'"
              type="button"
              :class="['p-2.5 rounded-xl border-2 transition-all text-center relative overflow-hidden', selectedGateway === 'flutterwave' ? 'border-emerald-600 bg-emerald-50' : 'border-slate-100 bg-white']"
            >
              <p class="font-bold text-[10px]" :class="selectedGateway === 'flutterwave' ? 'text-emerald-700' : 'text-slate-600'">Flutterwave</p>
              <div v-if="selectedGateway === 'flutterwave'" class="absolute top-0.5 right-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-emerald-600">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>
            <button 
              @click="selectedGateway = 'monnify'"
              type="button"
              :class="['p-2.5 rounded-xl border-2 transition-all text-center relative overflow-hidden', selectedGateway === 'monnify' ? 'border-sky-600 bg-sky-50' : 'border-slate-100 bg-white']"
            >
              <p class="font-bold text-[10px]" :class="selectedGateway === 'monnify' ? 'text-sky-700' : 'text-slate-600'">Monnify</p>
              <div v-if="selectedGateway === 'monnify'" class="absolute top-0.5 right-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-sky-600">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>
          </div>
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

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'
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
const selectedGateway = ref('paystack')
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
  schemes.value = data
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
    const callback_url = `${window.location.origin}${basePath}payment-callback?gateway=${selectedGateway.value}`
    const { data } = await axios.post('/api/initiate-payment', { 
      items: paymentList.value, 
      callback_url,
      gateway: selectedGateway.value 
    })
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
