<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 flex justify-between items-center bg-white border-b">
      <button @click="$router.back()" class="text-2xl">⬅️</button>
      <h1 class="text-xl font-bold">Wallet</h1>
      <div />
    </header>

    <div class="p-4 space-y-6">
      <!-- Balance Card -->
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-7 text-white shadow-xl">
        <p class="text-emerald-100 text-sm">Available Balance</p>
        <h2 class="text-4xl font-bold mt-1">₦ {{ formatMoney(wallet.balance) }}</h2>
        <div class="mt-5 flex gap-2">
          <button @click="goAllocate" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">Allocate to Schemes</button>
          <button @click="showFund = !showFund" class="bg-white text-emerald-800 px-4 py-2 rounded-xl text-xs font-bold">{{ showFund ? 'Hide' : 'Fund Wallet' }}</button>
        </div>
      </div>

      <!-- Virtual Account Info -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-3">
          <h3 class="font-bold text-slate-800">Virtual Account (Bank Transfer)</h3>
          <button v-if="!wallet.virtual_account?.account_number" @click="assignVirtualAccount" :disabled="assigning"
                  class="text-xs bg-emerald-700 text-white px-3 py-2 rounded-xl">
            {{ assigning ? 'Creating…' : 'Generate Account' }}
          </button>
        </div>
        <div v-if="wallet.virtual_account?.account_number" class="grid grid-cols-1 gap-3">
          <div class="flex justify-between">
            <span class="text-gray-500 text-xs">Bank</span>
            <span class="font-bold text-slate-800">{{ wallet.virtual_account.bank_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 text-xs">Account Name</span>
            <span class="font-bold text-slate-800">{{ wallet.virtual_account.account_name }}</span>
          </div>
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-xs">Account Number</p>
              <p class="font-bold text-slate-800">{{ wallet.virtual_account.account_number }}</p>
            </div>
            <button @click="copy(wallet.virtual_account.account_number)" class="text-emerald-700 text-sm font-bold">Copy</button>
          </div>
          <p class="text-xs text-slate-500">Transfer NGN to this account to top up your wallet automatically.</p>
        </div>
        <div v-else class="text-sm text-slate-500">No virtual account yet. Generate one to fund via bank transfer.</div>
      </div>

      <!-- Card Top-up Form -->
      <div v-if="showFund" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Fund Wallet (Card)</h3>
        <div class="flex gap-3 items-end">
          <div class="flex-1">
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount</label>
            <input v-model.number="topupAmount" type="number" min="1" placeholder="0.00" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" />
          </div>
          <button @click="initTopup" :disabled="loading || !topupAmount" class="bg-emerald-700 text-white px-5 py-3 rounded-xl font-bold">
            {{ loading ? 'Processing…' : 'Top up' }}
          </button>
        </div>
        <p class="mt-2 text-xs text-slate-500">You will be redirected to Paystack to complete payment.</p>
      </div>

      <!-- Recent Wallet Transactions -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-3 gap-2 flex-wrap">
          <h3 class="font-bold text-slate-800">Recent Wallet Activity</h3>
          <button @click="loadMore" class="text-emerald-700 text-xs font-bold px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 sm:ml-auto">Load more</button>
        </div>
        <div v-if="transactions.length" class="space-y-3">
          <div v-for="tx in transactions" :key="tx.id" class="border border-slate-100 rounded-xl p-4">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <div :class="tx.type === 'credit' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'" class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
                  {{ tx.type === 'credit' ? '+' : '−' }}
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-slate-800 truncate">{{ titleFor(tx) }}</p>
                </div>
              </div>
              <p class="font-bold shrink-0" :class="tx.type === 'credit' ? 'text-emerald-700' : 'text-rose-700'">₦ {{ formatMoney(tx.amount) }}</p>
            </div>
            <div class="flex items-center justify-between mt-1">
              <p class="text-[10px] uppercase text-slate-400 truncate">Ref: {{ tx.reference }}</p>
              <p class="text-[10px] text-slate-400 shrink-0">{{ new Date(tx.created_at).toLocaleString() }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500">No wallet activity yet.</div>
      </div>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1" @click="$router.push('/wallet')">
        <span class="text-xl">👛</span>
        <span class="text-[10px] font-bold">Wallet</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/passbook')">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const baseRaw = import.meta?.env?.BASE_URL || '/'
const basePath = (baseRaw && baseRaw.startsWith('./')) ? '/' : (baseRaw.endsWith('/') ? baseRaw : `${baseRaw}/`)
const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))

const wallet = ref({ balance: 0, virtual_account: {} })
const transactions = ref([])
const page = ref(1)
const perPage = 10
const topupAmount = ref('')
const loading = ref(false)
const assigning = ref(false)
const showFund = ref(true)

const formatMoney = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const titleFor = (tx) => {
  if (tx.source === 'wallet_allocation') return 'Allocation to Schemes'
  if (tx.source === 'paystack_dva') return 'Bank Transfer (DVA)'
  if (tx.source === 'vtu_airtime') return 'Airtime Purchase'
  if (tx.source === 'vtu_data') return 'Data Purchase'
  return 'Wallet Top-up'
}

const loadWallet = async () => {
  const { data } = await axios.get('/api/wallet')
  wallet.value = data
  // Prefer server-provided recent list
  transactions.value = data.recent_transactions || []
}

const loadMore = async () => {
  const { data } = await axios.get(`/api/wallet/transactions?page=${page.value + 1}&per_page=${perPage}`)
  if (data?.data?.length) {
    page.value += 1
    transactions.value = transactions.value.concat(data.data)
  }
}

const initTopup = async () => {
  try {
    loading.value = true
        // Build callback URL only for web; on native apps, omit to avoid invalid localhost redirects
    const cb = !isNative ? (new URL(router.resolve({ name: 'wallet.callback' }).href, window.location.origin).toString()) : null
    const payload = { amount: Number(topupAmount.value) }
    if (cb) payload.callback_url = cb
    const { data } = await axios.post('/api/wallet/topup/initiate', payload)
    window.location.href = data.checkout_url
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to start top-up')
  } finally {
    loading.value = false
  }
}

const assignVirtualAccount = async () => {
  try {
    assigning.value = true
    await axios.post('/api/virtual-account/assign', {})
    await loadWallet()
    alert('Virtual account generated!')
  } catch (e) {
    alert('Failed to generate virtual account')
  } finally {
    assigning.value = false
  }
}

const copy = async (text) => {
  try { await navigator.clipboard.writeText(String(text || '')); alert('Copied'); } catch (_) {}
}

const goAllocate = () => {
  // Send user to make payment page; they can toggle wallet allocation there
  router.push({ name: 'pay' })
}

onMounted(loadWallet)
</script>
