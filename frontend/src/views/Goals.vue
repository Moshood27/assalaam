<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 flex justify-between items-center bg-white border-b sticky top-0 z-10">
      <button @click="$router.back()" class="text-2xl">⬅️</button>
      <h1 class="text-lg font-bold text-slate-800">Hajj & Umrah Savings</h1>
      <button @click="openCreate" class="text-emerald-700 text-xs font-bold bg-emerald-50 px-3 py-1.5 rounded-full">New Goal</button>
    </header>

    <div class="p-4 max-w-2xl mx-auto space-y-4">
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-2xl p-6 text-white">
        <p class="text-emerald-100 text-sm">Available Wallet Balance</p>
        <h2 class="text-3xl font-bold">₦ {{ formatMoney(balance) }}</h2>
      </div>

      <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-2xl p-4">
        <p class="text-sm">
          Funds you deposit here are locked for Hajj & Umrah until booking. Standard partner commission:
          <span class="font-bold">{{ (commissionRate * 100).toFixed(2) }}%</span>.
        </p>
      </div>

      <div v-if="goals.length" class="space-y-3">
        <div v-for="g in goals" :key="g.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs text-slate-400 uppercase font-bold">Goal</p>
              <h3 class="text-base font-bold text-slate-800">{{ g.title }}</h3>
              <p class="text-xs text-slate-500">Target: ₦ {{ formatMoney(g.target_amount) }} • Saved: ₦ {{ formatMoney(g.saved_amount) }}</p>
            </div>
            <span :class="badgeClass(g.status)" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">{{ g.status }}</span>
          </div>
          <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
            <div class="h-2 rounded-full bg-emerald-600" :style="{ width: g.progress + '%' }"></div>
          </div>
          <div class="mt-3 grid grid-cols-3 gap-2">
            <button @click="openDeposit(g)" class="btn">Deposit</button>
            <button @click="viewGoal(g)" class="btn-muted">Details</button>
            <button @click="bookTravel(g)" :disabled="!g.is_complete || g.status==='booked'" class="btn-emerald" title="Book with partner and record commission">
              <span v-if="g.status==='booked'">Booked</span>
              <span v-else>Book Travel</span>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center text-slate-500 py-10">
        <p class="mb-2">No goals yet.</p>
        <button @click="openCreate" class="btn-emerald">Create your first goal</button>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="modal">
      <div class="card">
        <h3 class="title">Create Savings Goal</h3>
        <div class="space-y-3">
          <div>
            <label class="label">Title</label>
            <input v-model="form.title" placeholder="e.g., Umrah 2026" class="input" />
          </div>
          <div>
            <label class="label">Target Amount (₦)</label>
            <input v-model.number="form.target_amount" type="number" min="1" class="input" />
          </div>
          <div>
            <label class="label">Target Date</label>
            <input v-model="form.target_date" type="date" class="input" />
          </div>
          <div class="grid grid-cols-2 gap-2 mt-4">
            <button @click="createGoal" class="btn-emerald" :disabled="loading">{{ loading ? 'Creating...' : 'Create' }}</button>
            <button @click="showCreate=false" class="btn-muted">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Deposit Modal -->
    <div v-if="showDeposit" class="modal">
      <div class="card">
        <h3 class="title">Deposit to {{ depositGoal?.title }}</h3>
        <p class="text-xs text-slate-500 mb-2">Wallet: ₦ {{ formatMoney(balance) }}</p>
        <div class="space-y-3">
          <div>
            <label class="label">Amount (₦)</label>
            <input v-model.number="depositAmount" type="number" min="1" class="input" />
          </div>
          <div class="grid grid-cols-2 gap-2 mt-4">
            <button @click="confirmDeposit" class="btn-emerald" :disabled="loading || !canDeposit">{{ loading ? 'Processing...' : 'Deposit' }}</button>
            <button @click="closeDeposit" class="btn-muted">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
    case 'completed': return 'bg-emerald-100 text-emerald-700'
    case 'booked': return 'bg-indigo-100 text-indigo-700'
    case 'cancelled': return 'bg-rose-100 text-rose-700'
    default: return 'bg-slate-100 text-slate-700'
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
    const msg = `Title: ${details.title}\nTarget: ₦ ${formatMoney(details.target_amount)}\nSaved: ₦ ${formatMoney(details.saved_amount)}\nStatus: ${details.status}\nProgress: ${details.progress}%`
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
    alert(`Booking recorded with ${data?.booking?.partner_name}. Commission: ₦ ${formatMoney(data?.commission_amount)}`)
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
.btn { @apply bg-slate-800 text-white px-4 py-2 rounded-xl text-sm font-bold active:scale-95 transition; }
.btn-muted { @apply bg-slate-100 text-slate-700 px-4 py-2 rounded-xl text-sm font-bold active:scale-95 transition; }
.btn-emerald { @apply bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold active:scale-95 transition; }
.input { @apply w-full bg-slate-50 p-3 rounded-xl border border-slate-200 text-sm outline-none focus:border-emerald-500; }
.label { @apply block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1; }
.modal { @apply fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center p-4; }
.card { @apply w-full max-w-md bg-white rounded-2xl p-5 shadow-xl; }
.title { @apply text-base font-bold text-slate-800 mb-2; }
</style>
