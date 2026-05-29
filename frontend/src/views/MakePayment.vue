<template>
  <div class="min-h-screen bg-slate-50 pb-60 font-sans">
    <AppHeader title="Allocate Fund" :showBack="true" />

    <div class="p-4 space-y-6 max-w-md mx-auto">
      <!-- Wallet Balance -->
      <div class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-[2rem] p-6 text-white shadow-xl">
        <p class="text-blue-100 text-[10px] font-bold uppercase tracking-widest">Wallet Balance</p>
        <p class="text-3xl font-extrabold tracking-tight mt-1">₦ {{ Number(walletBalance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
      </div>

      <div class="space-y-4">
        <h3 class="flex items-center gap-2 font-black text-slate-800 px-2 uppercase tracking-wider text-sm">
          <span class="w-6 h-6 rounded-full bg-blue-700 text-white flex items-center justify-center text-[10px]">1</span>
          Select Schemes
        </h3>
        <!-- Add Payment Item -->
        <div class="card card-elevated p-5">
        <p class="text-[11px] text-rose-500 font-bold mb-4 uppercase">
          ⚠️ Click the "+" to split across multiple schemes
        </p>
        <div class="space-y-4">
          <div>
            <label class="lbl">Scheme</label>
            <select v-model="selectedSchemeId" class="inp">
              <option value="">Select Scheme</option>
              <option value="combined">Shares & Savings (50/50 Split)</option>
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
          <div v-if="outstandingLoan" class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
            <p class="text-[10px] text-amber-700 font-bold uppercase tracking-wider">Outstanding Loan</p>
            <p class="text-sm font-bold text-amber-900">₦ {{ Number(outstandingLoan.remaining_principal).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
            <p class="text-[9px] text-amber-600 mt-1">Payments will be applied to: {{ outstandingLoan.qard_id_string }}</p>
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
          <input id="fine" type="checkbox" v-model="isFine" class="accent-blue-700">
          <label for="fine" class="text-sm text-slate-700">Lateness/Apology Fine (Audit)</label>
        </div>
      </div>
    </div>

    <!-- Payment Summary -->
    <div v-if="paymentList.length > 0" class="space-y-4">
      <h3 class="font-bold text-slate-800 px-2 text-sm uppercase tracking-wider">Payment Summary</h3>
      <div class="space-y-3">
        <div v-for="(item, index) in paymentList" :key="index" class="card p-4 flex items-center justify-between border-l-4 border-blue-700">
          <div>
            <div class="flex items-center flex-wrap gap-2">
              <p class="font-bold text-slate-800 text-sm">{{ item.scheme_name }}</p>
              <span v-if="item.project_name" class="badge bg-blue-100 text-blue-700">Project: {{ item.project_name }}</span>
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

      <!-- Step 2: Payment Method (Moved from fixed bar) -->
      <h3 class="flex items-center gap-2 font-black text-slate-800 px-2 uppercase tracking-wider text-sm mt-8">
        <span class="w-6 h-6 rounded-full bg-blue-700 text-white flex items-center justify-center text-[10px]">2</span>
        Payment Method
      </h3>
      
      <div class="card card-elevated p-4">
        <div class="space-y-2">
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest px-1">Choose Source</p>
          
          <!-- Online Gateway -->
          <div @click="source = 'gateway'" :class="['p-3 rounded-xl border-2 flex items-center justify-between cursor-pointer transition-all', source === 'gateway' ? 'border-blue-600 bg-blue-50' : 'border-slate-100 bg-white']">
            <div class="flex items-center gap-3">
              <div :class="['w-4 h-4 rounded-full border-2 flex items-center justify-center', source === 'gateway' ? 'border-blue-600' : 'border-slate-300']">
                <div v-if="source === 'gateway'" class="w-2 h-2 rounded-full bg-blue-600"></div>
              </div>
              <span class="text-xs font-bold text-slate-700 uppercase">Online Payment</span>
            </div>
          </div>

          <!-- Wallet -->
          <div @click="source = 'wallet'" :class="['p-3 rounded-xl border-2 flex items-center justify-between cursor-pointer transition-all', source === 'wallet' ? 'border-blue-600 bg-blue-50' : 'border-slate-100 bg-white']">
            <div class="flex items-center gap-3">
              <div :class="['w-4 h-4 rounded-full border-2 flex items-center justify-center', source === 'wallet' ? 'border-blue-600' : 'border-slate-300']">
                <div v-if="source === 'wallet'" class="w-2 h-2 rounded-full bg-blue-600"></div>
              </div>
              <span class="text-xs font-bold text-slate-700 uppercase">Wallet</span>
            </div>
            <span class="text-[10px] font-bold text-slate-500">₦ {{ Number(walletBalance).toLocaleString() }}</span>
          </div>

          <!-- Special Savings -->
          <div v-if="specialSavingsBalance > 0" @click="source = 'special_savings'" :class="['p-3 rounded-xl border-2 flex items-center justify-between cursor-pointer transition-all', source === 'special_savings' ? 'border-blue-600 bg-blue-50' : 'border-slate-100 bg-white']">
            <div class="flex items-center gap-3">
              <div :class="['w-4 h-4 rounded-full border-2 flex items-center justify-center', source === 'special_savings' ? 'border-blue-600' : 'border-slate-300']">
                <div v-if="source === 'special_savings'" class="w-2 h-2 rounded-full bg-blue-600"></div>
              </div>
              <span class="text-xs font-bold text-blue-700 uppercase">Special Savings</span>
            </div>
            <span class="text-[10px] font-bold text-blue-600">₦ {{ Number(specialSavingsBalance).toLocaleString() }}</span>
          </div>
        </div>

        <!-- Gateway Selection -->
        <div v-if="source === 'gateway' && enabledGateways.length" class="mt-4 pt-4 border-t border-slate-100">
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-2 px-1">Select Gateway</p>
          <div class="grid grid-cols-2 gap-2">
            <button 
              v-for="gw in enabledGateways" :key="gw"
              @click="selectedGateway = gw"
              type="button"
              :class="['p-2.5 rounded-xl border-2 transition-all text-center relative overflow-hidden', selectedGateway === gw ? 'border-blue-600 bg-blue-50' : 'border-slate-100 bg-white']"
            >
              <p class="font-bold text-[10px] uppercase" :class="selectedGateway === gw ? 'text-blue-700' : 'text-slate-600'">{{ gw }}</p>
              <div v-if="selectedGateway === gw" class="absolute top-0.5 right-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-blue-600">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="card p-6 text-center empty-state text-slate-400 text-sm">
      No schemes added yet.
    </div>
  </div>

    <div class="fixed left-0 right-0 bottom-16 p-4">
      <div class="card card-elevated p-4">
        <div class="flex justify-between items-center mb-4">
          <span class="text-gray-500 font-bold uppercase text-[10px] tracking-widest">Total Amount</span>
          <span class="text-2xl font-black text-slate-900">₦ {{ Number(totalAmount).toLocaleString() }}</span>
        </div>

        <button @click="initiatePayment" :disabled="paymentList.length === 0 || loading" class="btn-primary w-full py-4 text-lg">
          {{ loading ? 'Processing...' : (source !== 'gateway' ? 'Allocate Fund' : 'Make Payment') }}
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
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'
import { useRouter } from 'vue-router'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'

import { useAppStatusStore } from '../stores/appStatus'

const router = useRouter()
const appStatusStore = useAppStatusStore()

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
const source = ref('gateway')
const outstandingLoan = ref(null)
const specialSavingsBalance = ref(0)
const enabledGateways = computed(() => {
  const gws = appStatusStore.paymentGateways || {}
  return Object.keys(gws).filter(k => k !== 'primary' && gws[k])
})
const selectedGateway = ref(appStatusStore.paymentGateways?.primary || 'paystack')
watch(() => appStatusStore.paymentGateways?.primary, (newVal) => {
  if (newVal) selectedGateway.value = newVal
})
const walletBalance = ref(0)
const summaryEnd = ref(null)

const totalAmount = computed(() => paymentList.value.reduce((sum, i) => sum + Number(i.amount || 0), 0))

// Custom notice (shared)
const { notice, showNotice, closeNotice } = useNotice()

// PIN prompt modal state
const pinPrompt = ref({ visible: false })

watch(selectedSchemeId, async (newVal) => {
  if (!newVal || newVal === 'combined') {
    outstandingLoan.value = null
    return
  }
  const s = schemes.value.find(x => String(x.id) == String(newVal))
  if (s && s.name.toLowerCase().includes('loan')) {
    try {
      loading.value = true
      const { data } = await axios.get('/api/loans/outstanding')
      if (!data || !data.id) {
        showNotice('No Active Loan', 'You do not have any outstanding loan to repay.', 'warning')
        selectedSchemeId.value = ''
        outstandingLoan.value = null
      } else {
        outstandingLoan.value = data
      }
    } catch (e) {
      console.error(e)
      outstandingLoan.value = null
    } finally {
      loading.value = false
    }
  } else {
    outstandingLoan.value = null
  }
})

const addToList = () => {
  if (selectedSchemeId.value !== 'combined') {
    if (!selectedSchemeId.value || !inputAmount.value || Number(inputAmount.value) <= 0) return
    // robust id compare (string/number)
    const s = schemes.value.find(x => String(x.id) == String(selectedSchemeId.value))
    if (!s) return
    const pid = selectedProjectId.value ? String(selectedProjectId.value) : ''
    const p = pid ? projects.value.find(x => String(x.id) == pid) : null
    
    let category = isFine.value ? 'fine' : 'deposit'
    if (s.name.toLowerCase().includes('loan')) {
      category = 'loan_repayment'
    }

    const item = { scheme_id: s.id, scheme_name: s.name, amount: Number(inputAmount.value), category }
    if (p) {
      item.project_id = p.id
      item.project_name = p.name
    }
    paymentList.value.push(item)
  } else {
    if (!inputAmount.value || Number(inputAmount.value) <= 0) return
    // Find Shares and Savings schemes
    const sharesScheme = schemes.value.find(s => s.name === 'Shares') || schemes.value.find(s => s.name.toLowerCase().includes('share'))
    const savingsScheme = schemes.value.find(s => s.name === 'Savings') || schemes.value.find(s => s.name.toLowerCase().includes('saving'))

    if (!sharesScheme || !savingsScheme) {
      showNotice('Scheme Not Found', 'Could not find standard "Shares" or "Savings" schemes.', 'error')
      return
    }

    const half = Number(inputAmount.value) / 2
    const pid = selectedProjectId.value ? String(selectedProjectId.value) : ''
    const p = pid ? projects.value.find(x => String(x.id) == pid) : null

    const itemsToAdd = [
      { scheme_id: sharesScheme.id, scheme_name: sharesScheme.name, amount: half, category: isFine.value ? 'fine' : 'deposit' },
      { scheme_id: savingsScheme.id, scheme_name: savingsScheme.name, amount: half, category: isFine.value ? 'fine' : 'deposit' }
    ]

    itemsToAdd.forEach(item => {
      if (p) {
        item.project_id = p.id
        item.project_name = p.name
      }
      paymentList.value.push(item)
    })
  }

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
    specialSavingsBalance.value = data.special_savings_balance || 0
  } catch (_) {}
}

const initiatePayment = async () => {
  // If paying from wallet or special savings, show custom PIN prompt modal
  if (source.value === 'wallet' || source.value === 'special_savings') {
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
    const endpoint = source.value === 'special_savings' ? '/api/wallet/allocate-special' : '/api/wallet/allocate'
    await axios.post(endpoint, { items: paymentList.value, pin })
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


