<template>
  <div class="min-h-screen bg-slate-50/50 pb-24">
    <AppHeader title="Hajj & Umrah" :showBack="true">
      <template #right>
        <button @click="openCreate" class="text-blue-700 text-xs font-bold mr-2">New Goal</button>
      </template>
    </AppHeader>

    <div class="p-4 max-w-2xl mx-auto space-y-4">
      <div class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-[2rem] p-7 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full"></div>
        <p class="text-blue-100 text-sm font-medium mb-1 relative z-10">Available Wallet Balance</p>
        <h2 class="text-3xl font-black relative z-10">Ã¢â€šÂ¦ {{ formatMoney(balance) }}</h2>
      </div>

      <div class="bg-blue-50/50 border border-blue-100 text-blue-900 rounded-2xl p-4 flex gap-3">
        <div class="text-blue-500 mt-0.5">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        </div>
        <p class="text-[13px] leading-relaxed">
          Funds you deposit here are locked for Hajj & Umrah until booking. Standard partner commission:
          <span class="font-bold">{{ (commissionRate * 100).toFixed(2) }}%</span>.
        </p>
      </div>

      <div v-if="goals.length" class="space-y-4">
        <div v-for="g in goals" :key="g.id" class="card p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">Savings Goal</p>
              <h3 class="text-lg font-bold text-slate-800">{{ g.title }}</h3>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Target: Ã¢â€šÂ¦ {{ formatMoney(g.target_amount) }} Ã¢â‚¬Â¢ Saved: Ã¢â€šÂ¦ {{ formatMoney(g.saved_amount) }}</p>
            </div>
            <span :class="badgeClass(g.status)" class="badge">{{ g.status }}</span>
          </div>
          <div class="mt-4 w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-full rounded-full bg-blue-600 transition-all duration-1000" :style="{ width: g.progress + '%' }"></div>
          </div>
          <div class="mt-5 flex flex-wrap gap-2">
            <button @click="openDeposit(g)" class="btn-primary flex-1 py-2.5 text-xs">Deposit</button>
            <button @click="viewGoal(g)" class="btn-muted px-4 py-2.5 text-xs">Details</button>
            <button @click="bookTravel(g)" :disabled="!g.is_complete || g.status==='booked'" class="btn-primary bg-slate-800 hover:bg-slate-900 flex-1 py-2.5 text-xs" title="Book with partner and record commission">
              <span v-if="g.status==='booked'">Booked</span>
              <span v-else>Book Travel</span>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12 bg-white rounded-3xl border border-dashed border-slate-300">
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">Ã°Å¸Å½Â¯</div>
        <p class="text-slate-500 font-medium mb-4">No savings goals yet.</p>
        <button @click="openCreate" class="btn-primary">Create your first goal</button>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="modal">
      <div class="modal-card">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-800">Create Goal</h3>
          <button @click="showCreate=false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="lbl">Goal Title</label>
            <input v-model="form.title" placeholder="e.g., Umrah 2026" class="inp" />
          </div>
          <div>
            <label class="lbl">Target Amount (Ã¢â€šÂ¦)</label>
            <input v-model.number="form.target_amount" type="number" min="1" class="inp" placeholder="0.00" />
          </div>
          <div>
            <label class="lbl">Target Date</label>
            <input v-model="form.target_date" type="date" class="inp" />
          </div>
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button @click="showCreate=false" class="btn-muted">Cancel</button>
            <button @click="createGoal" class="btn-primary" :disabled="loading">{{ loading ? 'Creating...' : 'Create Goal' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Deposit Modal -->
    <div v-if="showDeposit" class="modal">
      <div class="modal-card">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xl font-bold text-slate-800">Deposit Funds</h3>
          <button @click="closeDeposit" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <p class="text-sm font-medium text-slate-500 mb-6">Deposit to <span class="text-slate-800 font-bold">{{ depositGoal?.title }}</span></p>
        
        <div class="bg-blue-50 p-4 rounded-2xl mb-6 flex justify-between items-center">
          <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">Wallet Balance</span>
          <span class="font-black text-blue-900">Ã¢â€šÂ¦ {{ formatMoney(balance) }}</span>
        </div>

        <div class="space-y-4">
          <div>
            <label class="lbl">Amount to Deposit (Ã¢â€šÂ¦)</label>
            <input v-model.number="depositAmount" type="number" min="1" class="inp" placeholder="0.00" />
          </div>
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button @click="closeDeposit" class="btn-muted">Cancel</button>
            <button @click="confirmDeposit" class="btn-primary" :disabled="loading || !canDeposit">{{ loading ? 'Processing...' : 'Confirm Deposit' }}</button>
          </div>
        </div>
      </div>
    </div>
    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'

const goals = ref([])
const balance = ref(0)
const commissionRate = ref(0)
const loading = ref(false)

const showCreate = ref(false)
const form = ref({ title: '', target_amount: '', target_date: '' })

const showDeposit = ref(false)
const depositGoal = ref(null)
const depositAmount = ref('')

const openCreate = () => { showCreate.value = true }

const openDeposit = (g) => {
  depositGoal.value = g
  depositAmount.value = ''
  showDeposit.value = true
}
const closeDeposit = () => { showDeposit.value = false; depositGoal.value = null }

const canDeposit = computed(() => {
  const a = Number(depositAmount.value || 0)
  return a > 0 && a <= Number(balance.value || 0)
})

function badgeClass(status) {
  switch ((status||'').toLowerCase()) {
    case 'completed': return 'badge-success'
    case 'booked': return 'bg-indigo-100 text-indigo-700'
    case 'cancelled': return 'bg-rose-100 text-rose-700'
    default: return 'badge-muted'
  }
}

function formatMoney(n) {
  const v = Number(n || 0)
  return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function load() {
  try {
    const { data } = await axios.get('/api/goals')
    balance.value = data.balance || 0
    commissionRate.value = Number(data.default_commission_rate || 0)
    goals.value = data.goals || []
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load goals')
  }
}

async function createGoal() {
  try {
    loading.value = true
    await axios.post('/api/goals', form.value)
    showCreate.value = false
    form.value = { title: '', target_amount: '', target_date: '' }
    await load()
    alert('Goal created successfully')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to create goal')
  } finally {
    loading.value = false
  }
}

async function confirmDeposit() {
  if (!depositGoal.value) return
  try {
    loading.value = true
    const { data } = await axios.post(`/api/goals/${depositGoal.value.id}/deposit`, { amount: Number(depositAmount.value) })
    alert('Deposit successful')
    showDeposit.value = false
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to deposit')
  } finally {
    loading.value = false
  }
}

async function viewGoal(g) {
  try {
    const { data } = await axios.get(`/api/goals/${g.id}`)
    const details = data.goal
    const msg = `Title: ${details.title}\nTarget: Ã¢â€šÂ¦ ${formatMoney(details.target_amount)}\nSaved: Ã¢â€šÂ¦ ${formatMoney(details.saved_amount)}\nStatus: ${details.status}\nProgress: ${details.progress}%`
    alert(msg)
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load goal')
  }
}

async function bookTravel(g) {
  if (!g.is_complete || g.status==='booked') return
  const partner = prompt('Enter travel partner name (required):', 'Trusted Travel Co.')
  if (!partner) return
  const pkg = prompt('Enter package name (optional):', 'Umrah Package')
  try {
    loading.value = true
    const { data } = await axios.post(`/api/goals/${g.id}/book`, {
      partner_name: partner,
      package: pkg || undefined,
    })
    alert(`Booking recorded with ${data?.booking?.partner_name}. Commission: Ã¢â€šÂ¦ ${formatMoney(data?.commission_amount)}`)
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to record booking')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
@reference "../style.css";
.modal { @apply fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4; }
.modal-card { @apply w-full max-w-md bg-white rounded-[2rem] p-8 shadow-2xl; }
</style>


