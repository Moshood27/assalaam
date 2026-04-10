<template>
  <div class="min-h-screen bg-slate-50/50 pb-24">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="p-2 -ml-2 hover:bg-slate-100 rounded-full transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg font-bold text-slate-800">Junior Cooperative</h1>
        <div class="flex gap-1">
          <button @click="openCreate" class="btn-ghost text-xs py-1.5 px-3">New Account</button>
        </div>
      </div>
    </header>

    <div class="p-4 max-w-2xl mx-auto space-y-4">
      <div class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-[2rem] p-7 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full" />
        <p class="text-blue-100 text-sm font-medium mb-1 relative z-10">Total Junior Savings</p>
        <h2 class="text-3xl font-black relative z-10">₦ {{ formatMoney(totalBalance) }}</h2>
      </div>

      <div class="bg-blue-50/50 border border-blue-100 text-blue-900 rounded-2xl p-4 flex gap-3">
        <div class="text-blue-500 mt-0.5">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        </div>
        <p class="text-[13px] leading-relaxed">
          Junior Cooperative accounts are locked Sharia-compliant accounts designed for your children's future education and needs.
        </p>
      </div>

      <div v-if="accounts.length" class="space-y-4">
        <div v-for="acc in accounts" :key="acc.id" class="card p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest mb-1">Junior Account</p>
              <h3 class="text-lg font-bold text-slate-800">{{ acc.child_name }}</h3>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Purpose: {{ acc.purpose }}</p>
            </div>
            <div class="flex flex-col items-end">
              <button @click="openEdit(acc)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-blue-600 mb-1" title="Edit Account">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
              </button>
              <div class="text-right">
                <p class="text-lg font-black text-slate-900">₦ {{ formatMoney(acc.balance) }}</p>
                <p v-if="acc.locked_until" class="text-[10px] text-slate-400 font-bold uppercase" :class="isLocked(acc) ? 'text-red-500' : 'text-green-500'">
                  {{ isLocked(acc) ? 'Locked until ' + formatDate(acc.locked_until) : 'Unlocked (' + formatDate(acc.locked_until) + ')' }}
                </p>
              </div>
            </div>
          </div>
          <div class="mt-5 flex gap-2">
            <button @click="openDeposit(acc)" class="btn-primary flex-1 py-2.5 text-xs">Deposit</button>
            <button @click="openWithdraw(acc)" class="btn-muted flex-1 py-2.5 text-xs" :disabled="isLocked(acc)">Withdraw</button>
            <button @click="viewDetails(acc)" class="btn-ghost px-4 py-2.5 text-xs">History</button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12 bg-white rounded-3xl border border-dashed border-slate-300">
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">👶</div>
        <p class="text-slate-500 font-medium mb-4">No junior accounts yet.</p>
        <button @click="openCreate" class="btn-primary">Open an account for your child</button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreate" class="modal">
      <div class="modal-card">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-800">{{ editingId ? 'Edit' : 'New' }} Junior Account</h3>
          <button @click="showCreate=false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="lbl">Child's Full Name</label>
            <input v-model="form.child_name" placeholder="e.g. Ahmed Musa" class="inp" />
          </div>
          <div>
            <label class="lbl">Date of Birth</label>
            <input v-model="form.child_dob" type="date" class="inp" />
          </div>
          <div>
            <label class="lbl">Savings Purpose</label>
            <input v-model="form.purpose" placeholder="e.g. University Education" class="inp" />
          </div>
          <div>
            <label class="lbl">Lock Until (Optional)</label>
            <input v-model="form.locked_until" type="date" class="inp" />
            <p class="text-[10px] text-slate-400 mt-1 italic">Withdrawals are restricted until this date.</p>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button @click="showCreate=false" class="btn-muted">Cancel</button>
            <button @click="saveAccount" class="btn-primary" :disabled="loading || !form.child_name || !form.child_dob">{{ loading ? 'Saving...' : (editingId ? 'Update Account' : 'Create Account') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Deposit Modal -->
    <div v-if="showDeposit" class="modal">
      <div class="modal-card">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xl font-bold text-slate-800">Deposit Savings</h3>
          <button @click="showDeposit=false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <p class="text-sm font-medium text-slate-500 mb-6">Adding to <span class="text-slate-800 font-bold">{{ activeAccount?.child_name }}'s</span> account</p>
        
        <div class="bg-blue-50 p-4 rounded-2xl mb-6 flex justify-between items-center">
          <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">Your Wallet Balance</span>
          <span class="font-black text-blue-900">₦ {{ formatMoney(walletBalance) }}</span>
        </div>

        <div class="space-y-4">
          <div>
            <label class="lbl">Amount to Save (₦)</label>
            <input v-model.number="depositAmount" type="number" min="1" class="inp" placeholder="0.00" />
          </div>
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button @click="showDeposit=false" class="btn-muted">Cancel</button>
            <button @click="confirmDeposit" class="btn-primary" :disabled="loading || !canDeposit">{{ loading ? 'Processing...' : 'Confirm Deposit' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Withdraw Modal -->
    <div v-if="showWithdraw" class="modal">
      <div class="modal-card">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xl font-bold text-slate-800">Withdraw Funds</h3>
          <button @click="showWithdraw=false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <p class="text-sm font-medium text-slate-500 mb-6">Withdrawing from <span class="text-slate-800 font-bold">{{ activeAccount?.child_name }}'s</span> account</p>
        
        <div class="bg-slate-50 p-4 rounded-2xl mb-6 flex justify-between items-center">
          <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Junior Balance</span>
          <span class="font-black text-slate-900">₦ {{ formatMoney(activeAccount?.balance) }}</span>
        </div>

        <div class="space-y-4">
          <div>
            <label class="lbl">Amount to Withdraw (₦)</label>
            <input v-model.number="withdrawAmount" type="number" min="1" class="inp" placeholder="0.00" />
          </div>
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button @click="showWithdraw=false" class="btn-muted">Cancel</button>
            <button @click="confirmWithdraw" class="btn-primary" :disabled="loading || !canWithdraw">{{ loading ? 'Processing...' : 'Confirm Withdrawal' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistory" class="modal">
      <div class="modal-card max-w-lg">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-800">{{ activeAccount?.child_name }}'s History</h3>
          <button @click="showHistory=false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div v-if="historyLoading" class="py-12 text-center text-slate-400">Loading history...</div>
        <div v-else-if="!historyTransactions.length" class="py-12 text-center text-slate-400">No transactions yet.</div>
        <div v-else class="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
          <div v-for="tx in historyTransactions" :key="tx.id" class="flex items-center justify-between p-3 rounded-2xl bg-slate-50">
            <div>
              <p class="text-sm font-bold text-slate-800">{{ tx.type === 'debit' ? 'Deposit' : 'Withdrawal' }}</p>
              <p class="text-[10px] text-slate-500 font-medium">{{ formatDate(tx.created_at) }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-black" :class="tx.type === 'debit' ? 'text-green-600' : 'text-blue-600'">
                {{ tx.type === 'debit' ? '+' : '-' }} ₦ {{ formatMoney(tx.amount) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '../http'

const accounts = ref([])
const walletBalance = ref(0)
const loading = ref(false)

const totalBalance = computed(() => {
  return accounts.value.reduce((sum, acc) => sum + Number(acc.balance), 0)
})

const showCreate = ref(false)
const editingId = ref(null)
const form = ref({ child_name: '', child_dob: '', purpose: 'Education', locked_until: '' })

const showDeposit = ref(false)
const showWithdraw = ref(false)
const showHistory = ref(false)
const activeAccount = ref(null)
const depositAmount = ref('')
const withdrawAmount = ref('')
const historyTransactions = ref([])
const historyLoading = ref(false)

const openCreate = () => {
  editingId.value = null
  form.value = { child_name: '', child_dob: '', purpose: 'Education', locked_until: '' }
  showCreate.value = true
}
const openEdit = (acc) => {
  editingId.value = acc.id
  form.value = {
    child_name: acc.child_name,
    child_dob: acc.child_dob ? acc.child_dob.split('T')[0] : '',
    purpose: acc.purpose,
    locked_until: acc.locked_until ? acc.locked_until.split('T')[0] : ''
  }
  showCreate.value = true
}
const openDeposit = (acc) => {
  activeAccount.value = acc
  depositAmount.value = ''
  showDeposit.value = true
}
const openWithdraw = (acc) => {
  if (isLocked(acc)) return
  activeAccount.value = acc
  withdrawAmount.value = ''
  showWithdraw.value = true
}

const canDeposit = computed(() => {
  const a = Number(depositAmount.value || 0)
  return a > 0 && a <= Number(walletBalance.value || 0)
})

const canWithdraw = computed(() => {
  const a = Number(withdrawAmount.value || 0)
  return a > 0 && a <= Number(activeAccount.value?.balance || 0)
})

function formatMoney(n) {
  return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString()
}

function isLocked(acc) {
  if (!acc.locked_until) return false
  return new Date(acc.locked_until) > new Date()
}

async function load() {
  try {
    const { data } = await axios.get('/api/junior-cooperative')
    accounts.value = data.accounts || []
    walletBalance.value = data.balance || 0
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load junior accounts')
  }
}

async function saveAccount() {
  try {
    loading.value = true
    if (editingId.value) {
      await axios.patch(`/api/junior-cooperative/${editingId.value}`, form.value)
    } else {
      await axios.post('/api/junior-cooperative', form.value)
    }
    showCreate.value = false
    form.value = { child_name: '', child_dob: '', purpose: 'Education', locked_until: '' }
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to save account')
  } finally {
    loading.value = false
  }
}

async function confirmDeposit() {
  if (!activeAccount.value) return
  try {
    loading.value = true
    await axios.post(`/api/junior-cooperative/${activeAccount.value.id}/deposit`, { amount: Number(depositAmount.value) })
    showDeposit.value = false
    await load()
    alert('Deposit successful')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to deposit')
  } finally {
    loading.value = false
  }
}

async function confirmWithdraw() {
  if (!activeAccount.value) return
  try {
    loading.value = true
    await axios.post(`/api/junior-cooperative/${activeAccount.value.id}/withdraw`, { amount: Number(withdrawAmount.value) })
    showWithdraw.value = false
    await load()
    alert('Withdrawal successful')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to withdraw')
  } finally {
    loading.value = false
  }
}

async function viewDetails(acc) {
  activeAccount.value = acc
  showHistory.value = true
  historyLoading.value = true
  try {
    const { data } = await axios.get(`/api/junior-cooperative/${acc.id}/history`)
    historyTransactions.value = data.transactions || []
  } catch (e) {
    alert('Failed to load history')
  } finally {
    historyLoading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
@reference "../style.css";
.modal { @apply fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4; }
.modal-card { @apply w-full max-w-md bg-white rounded-[2rem] p-8 shadow-2xl; }
</style>
