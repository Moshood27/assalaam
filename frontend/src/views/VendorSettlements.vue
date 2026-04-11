<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <div class="flex items-center gap-3">
          <button @click="$router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-lg font-bold text-slate-800">Settlements</h1>
        </div>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <!-- Bank Info Missing Warning -->
      <div v-if="!vendor.settlement_account_number || !vendor.settlement_bank_code || !vendor.settlement_account_name" class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex items-center gap-3">
        <div class="text-xl">⚠️</div>
        <div class="flex-1">
          <p class="text-[10px] font-black text-amber-800 uppercase tracking-widest mb-0.5">Bank Details Missing</p>
          <p class="text-[11px] text-amber-700 font-medium">Please update your bank details in your profile to request payouts.</p>
        </div>
        <button @click="$router.push('/vendor/apply')" class="text-[10px] font-black text-amber-800 uppercase bg-amber-200 px-3 py-1.5 rounded-lg active:scale-95 transition-all">Update</button>
      </div>

      <!-- Balance Card -->
      <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 opacity-40" />
        <div class="relative z-10">
          <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Withdrawable Balance</p>
          <h2 class="text-3xl font-black text-slate-800 uppercase leading-tight">₦{{ formatMoney(availableBalance) }}</h2>
          
          <div class="mt-6">
            <button 
              @click="showRequestModal = true"
              :disabled="availableBalance < 100 || !vendor.settlement_account_number || !vendor.settlement_bank_code || !vendor.settlement_account_name"
              class="w-full h-14 rounded-2xl bg-emerald-700 text-white font-black uppercase tracking-wider shadow-lg shadow-emerald-700/30 disabled:bg-slate-300 disabled:shadow-none transition-all active:scale-95"
            >
              Request Payout
            </button>
            <p v-if="availableBalance < 100" class="text-[10px] text-center text-slate-400 mt-2 font-bold uppercase">Minimum settlement: ₦100.00</p>
          </div>
        </div>
      </div>

      <!-- History -->
      <div class="space-y-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-2">Settlement History</h3>
        
        <div v-if="loading" class="text-center py-12">
          <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Loading history...</p>
        </div>

        <div v-else-if="settlements.length === 0" class="bg-white rounded-3xl p-12 text-center border border-dashed border-slate-200">
          <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">No settlement requests yet</p>
        </div>
        
        <div v-else class="space-y-3">
          <div v-for="s in settlements" :key="s.id" class="bg-white p-4 rounded-2xl border border-slate-100 flex items-center gap-4">
            <div :class="getStatusClass(s.status)" class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold">
              {{ getStatusIcon(s.status) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-slate-800 truncate">₦{{ formatMoney(s.amount) }}</p>
              <p class="text-[10px] text-slate-500 font-medium">{{ formatDate(s.created_at) }}</p>
            </div>
            <div class="text-right">
              <span :class="getStatusBadgeClass(s.status)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
                {{ s.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Modal -->
    <div v-if="showRequestModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl animate-slide-up">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Request Payout</h2>
          <button @click="showRequestModal = false" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">✕</button>
        </div>
        
        <div class="bg-emerald-50 p-4 rounded-2xl mb-6">
          <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">To Bank Account</p>
          <p class="text-sm font-bold text-slate-800">{{ vendor.settlement_bank_name }}</p>
          <p class="text-xs text-slate-500">{{ vendor.settlement_account_number }} • {{ vendor.settlement_account_name }}</p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Amount to Withdraw (₦)</label>
            <input v-model.number="form.amount" type="number" step="0.01" :max="availableBalance" class="w-full mt-1 px-4 py-4 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-black text-xl text-slate-800" placeholder="0.00" />
            <div class="flex justify-between mt-1 px-1">
               <span class="text-[9px] text-slate-400 font-bold uppercase">Max: ₦{{ formatMoney(availableBalance) }}</span>
               <button @click="form.amount = availableBalance" class="text-[9px] text-emerald-700 font-black uppercase">Withdraw All</button>
            </div>
          </div>

          <button 
            @click="submitRequest"
            :disabled="submitting || !form.amount || form.amount > availableBalance || form.amount < 100"
            class="w-full h-16 rounded-3xl bg-emerald-700 text-white font-black uppercase tracking-wider shadow-lg shadow-emerald-700/30 disabled:bg-slate-300 disabled:shadow-none transition-all active:scale-95"
          >
            {{ submitting ? 'Processing...' : 'Confirm Request' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'

const vendor = ref({})
const settlements = ref([])
const availableBalance = ref(0)
const loading = ref(true)
const submitting = ref(false)
const showRequestModal = ref(false)

const form = ref({
  amount: null
})

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 })
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

const getStatusClass = (s) => {
  switch (s) {
    case 'processed':
    case 'completed': return 'bg-emerald-50 text-emerald-600'
    case 'pending': return 'bg-amber-50 text-amber-600'
    case 'failed': return 'bg-rose-50 text-rose-600'
    default: return 'bg-slate-50 text-slate-600'
  }
}

const getStatusIcon = (s) => {
  switch (s) {
    case 'processed':
    case 'completed': return '✓'
    case 'pending': return '⌛'
    case 'failed': return '✕'
    default: return '?'
  }
}

const getStatusBadgeClass = (s) => {
  switch (s) {
    case 'processed':
    case 'completed': return 'bg-emerald-100 text-emerald-700'
    case 'pending': return 'bg-amber-100 text-amber-700'
    case 'failed': return 'bg-rose-100 text-rose-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}

const loadData = async () => {
  loading.value = true
  try {
    const [profRes, statsRes, settleRes] = await Promise.all([
      axios.get('/api/vendor/profile'),
      axios.get('/api/vendor/stats'),
      axios.get('/api/vendor/settlements')
    ])
    vendor.value = profRes.data
    availableBalance.value = statsRes.data.available_balance || 0
    settlements.value = settleRes.data.data || []
  } catch (err) {
    console.error('Failed to load data', err)
  } finally {
    loading.value = false
  }
}

const submitRequest = async () => {
  if (submitting.value) return
  submitting.value = true
  try {
    await axios.post('/api/vendor/settlements', form.value)
    showRequestModal.value = false
    form.value.amount = null
    await loadData()
    alert('Settlement request submitted successfully.')
  } catch (err) {
    console.error('Failed to submit request', err)
    
    // Check for validation errors or specific messages
    const errorData = err.response?.data
    let errorMsg = 'Error submitting request'
    
    if (errorData?.errors) {
      // If validation failed, get the first error
      const firstError = Object.values(errorData.errors)[0][0]
      errorMsg = firstError
    } else if (errorData?.message) {
      errorMsg = errorData.message
    }
    
    alert(errorMsg)
  } finally {
    submitting.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
@keyframes slide-up {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}
.animate-slide-up {
  animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
