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
        <h2 class="text-4xl font-bold mt-1">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet.balance) }}</h2>
        <div class="mt-2 text-emerald-100 text-xs flex justify-between gap-2">
          <span>Available for Withdrawal</span>
          <span class="font-bold">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet.available_for_withdrawal || 0) }}</span>
        </div>
        <div class="mt-5 flex gap-2 flex-wrap">
          <button @click="goAllocate" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">Allocate to Schemes</button>
          <button @click="showFund = !showFund" class="bg-white text-emerald-800 px-4 py-2 rounded-xl text-xs font-bold">{{ showFund ? 'Hide' : 'Fund Wallet' }}</button>
          <button @click="showTransfer = !showTransfer" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">{{ showTransfer ? 'Hide' : 'Transfer' }}</button>
          <button @click="showWithdraw = !showWithdraw" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">{{ showWithdraw ? 'Hide' : 'Withdraw to Bank' }}</button>
        </div>
      </div>

      <!-- Merchant Pay (QR) quick access -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center">
          <h3 class="font-bold text-slate-800">Merchant Pay (QR)</h3>
          <div class="flex gap-2">
            <button @click="$router.push('/merchant/receive')" class="bg-emerald-700 text-white px-3 py-2 rounded-xl text-xs font-bold">Receive via QR</button>
            <button @click="$router.push('/merchant/pay')" class="bg-white text-emerald-700 border border-emerald-200 px-3 py-2 rounded-xl text-xs font-bold">Pay Merchant</button>
          </div>
        </div>
        <p class="text-xs text-slate-500 mt-2">Let local shops accept {{ brand.shortName }} Pay. Generate a QR to receive or pay a merchant by scanning their QR.</p>
      </div>

      <!-- Virtual Account Info -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-3">
          <h3 class="font-bold text-slate-800">Virtual Account (Bank Transfer)</h3>
          <button v-if="!wallet.virtual_account?.account_number" @click="assignVirtualAccount" :disabled="assigning || (!!bvn && !bvnValid)"
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
        <div v-else class="space-y-3">
          <p class="text-sm text-slate-500">No virtual account yet. Generate one to fund via bank transfer.</p>
          <div class="grid grid-cols-1 gap-2">
            <div>
              <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">BVN (optional)</label>
              <input v-model="bvn" type="tel" inputmode="numeric" maxlength="11" placeholder="11-digit BVN" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" />
              <p v-if="bvn && !bvnValid" class="text-rose-600 text-xs mt-1">Please enter a valid 11-digit BVN.</p>
              <p class="text-[10px] text-slate-400 mt-1">Providing your BVN helps us verify your dedicated account faster.</p>
            </div>
          </div>
        </div>
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

      <!-- P2P Transfer Form -->
      <div v-if="showTransfer" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Transfer to Member</h3>
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Send To</label>
            <select v-model="toType" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none">
              <option value="phone">Phone Number</option>
              <option value="membership">Member ID</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">{{ toType === 'phone' ? 'Phone' : 'Membership Number' }}</label>
            <div class="flex gap-2">
              <input v-model="toValue" type="text" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="e.g., 0803..., or MEM123" />
              <button @click="checkRecipient" type="button" class="shrink-0 bg-emerald-700 text-white px-3 py-2 rounded-xl text-xs font-bold">Verify</button>
            </div>
          </div>
        </div>
        <div v-if="toType === 'membership'" class="mt-3 space-y-2">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Branch ID (optional)</label>
            <input v-model.number="branchId" type="number" min="1" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="Branch ID (if known)" />
          </div>
          <!-- Recipient preview / disambiguation -->
          <div v-if="recipient" class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm">
            Recipient: <span class="font-bold">{{ recipient.name }}</span>
            <span v-if="recipient.membership_number" class="text-emerald-700">({{ recipient.membership_number }})</span>
            <span v-if="recipient.branch_name" class="ml-1">— {{ recipient.branch_name }}</span>
          </div>
          <div v-else-if="recipientError" class="p-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 text-sm space-y-2">
            <div>{{ recipientError }}</div>
            <div v-if="branchesOptions.length" class="flex flex-wrap gap-2">
              <button v-for="b in branchesOptions" :key="b.id" type="button" @click="chooseBranch(b)" class="px-3 py-1 rounded-lg bg-white border border-amber-300 text-amber-700 text-xs hover:bg-amber-100">
                {{ b.name }} (ID: {{ b.id }})
              </button>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount</label>
          <input v-model.number="transferAmount" type="number" min="1" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="0.00" />
        </div>
        <div class="mt-3">
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Note (optional)</label>
          <input v-model="note" type="text" maxlength="120" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="e.g., Lunch refund" />
        </div>
        <button @click="startTransfer" :disabled="loading || !canSend" class="bg-emerald-700 text-white px-5 py-3 rounded-xl font-bold mt-4">
          {{ loading ? 'Transferring…' : 'Send' }}
        </button>
        <p class="text-[10px] text-slate-500 mt-2">You will confirm with your Transaction PIN.</p>
      </div>

      <!-- Withdraw to Bank Form -->
      <div v-if="showWithdraw" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Withdraw to Bank</h3>
        <p class="text-xs text-slate-500 mb-3">Withdrawals are sent to your saved bank account (Profile › Bank Settings). You can withdraw up to your Available-for-Withdrawal amount.</p>
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount</label>
            <input v-model.number="withdrawAmount" type="number" min="1" :max="Number(wallet?.available_for_withdrawal || 0)" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="0.00" />
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Note (optional)</label>
            <input v-model="withdrawNote" type="text" maxlength="200" class="w-full bg-slate-50 p-3 rounded-xl border text-sm outline-none" placeholder="e.g., Personal cash-out" />
          </div>
        </div>
        <div class="mt-2 text-[10px] text-slate-500">Available for Withdrawal: ₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.available_for_withdrawal || 0) }}</div>
        <button @click="startWithdraw" :disabled="loading || !canWithdraw" class="bg-emerald-700 text-white px-5 py-3 rounded-xl font-bold mt-4">
          {{ loading ? 'Submitting…' : 'Request Withdrawal' }}
        </button>
        <p class="text-[10px] text-slate-500 mt-2">You will confirm with your Transaction PIN.</p>
      </div>

      <!-- Withdrawal Breakdown -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <h3 class="font-bold text-slate-800 mb-3">Withdrawal Breakdown</h3>
        <div class="grid grid-cols-1 gap-2 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">Credits (Withdrawable)</span>
            <span class="font-bold text-slate-800">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.credits_withdrawable || 0) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Credits (Restricted)</span>
            <span class="font-bold text-slate-800">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.credits_restricted || 0) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Total Debits</span>
            <span class="font-bold text-slate-800">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.total_debits || 0) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Remaining Withdrawable</span>
            <span class="font-bold text-slate-800">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.remaining_withdrawable || 0) }}</span>
          </div>
        </div>
        <p class="text-[10px] text-slate-500 mt-2">Note: Loan disbursements are marked as restricted by default and cannot be withdrawn to bank unless enabled by Admin. You can still spend restricted funds on Airtime/Data/Store inside the app.</p>
      </div>

      <!-- Your Withdrawal Requests -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-3 gap-2 flex-wrap">
          <h3 class="font-bold text-slate-800">Your Withdrawal Requests</h3>
          <button @click="loadMoreWithdrawals" class="text-emerald-700 text-xs font-bold px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 sm:ml-auto">Load more</button>
        </div>
        <div v-if="withdrawals.length" class="space-y-3">
          <div v-for="wr in withdrawals" :key="wr.id" class="border border-slate-100 rounded-xl p-4">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">₦ {{ formatMoney(wr.amount) }}</p>
                <p class="text-[10px] uppercase text-slate-400 truncate">Ref: {{ wr.reference }}</p>
              </div>
              <div class="shrink-0 flex items-center gap-2">
                <span :class="statusClass(wr.status)" class="text-xs font-bold px-2 py-1 rounded-full">{{ wr.status }}</span>
                <button v-if="wr.status === 'pending'" @click="cancelWithdrawal(wr)" class="text-rose-700 text-[10px] font-bold px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100">Cancel</button>
              </div>
            </div>
            <div class="flex items-center justify-between mt-1 gap-3 flex-wrap">
              <p class="text-[10px] text-slate-400 truncate">{{ new Date(wr.created_at).toLocaleString() }}</p>
              <p v-if="wr.bank?.account_number || wr.account_number" class="text-[10px] text-slate-400 truncate">Bank: {{ wr.bank?.bank_name || wr.bank_name }} • Acct: {{ (wr.bank?.account_number || wr.account_number || '').replace(/.(?=.{4})/g, '•') }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500">No withdrawal requests yet.</div>
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
            <div class="flex items-center justify-between mt-1 gap-3 flex-wrap">
              <p class="text-[10px] uppercase text-slate-400 truncate">Ref: {{ tx.reference }}</p>
              <div class="flex items-center gap-2 ml-auto">
                <button @click="downloadReceipt(tx)" class="text-emerald-700 text-[10px] font-bold px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100">Receipt</button>
                <p class="text-[10px] text-slate-400 shrink-0">{{ new Date(tx.created_at).toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500">No wallet activity yet.</div>
      </div>

      <!-- Reusable Notice Modal -->
      <CustomNotice
        v-model="notice.visible"
        :type="notice.type"
        :title="notice.title"
        :message="notice.message"
        @close="closeNotice"
      />

      <!-- PIN Prompt Modal for Transfers -->
      <CustomNotice
        v-model="pinPrompt.visible"
        :type="'info'"
        :title="pinPrompt.title || 'Confirm'"
        :message="pinPrompt.message || 'Enter your 4-digit Transaction PIN to proceed.'"
        :prompt="true"
        inputLabel="Transaction PIN (4 digits)"
        :confirmText="pinPrompt.confirmText || 'Confirm'"
        cancelText="Cancel"
        :busy="loading"
        @confirm="handlePinConfirm"
        @cancel="handlePinCancel"
      />
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
import { ref, onMounted, computed, watch } from 'vue'
import axios from '../http.js'
import { useRouter } from 'vue-router'
import { useBalanceVisibility } from '../composables/useBalanceVisibility'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'
import { verifyBiometricIdentity, isBiometricAvailable } from '../services/biometric'
import brand from '../brand.js'

const router = useRouter()
const baseRaw = import.meta?.env?.BASE_URL || '/'
const basePath = (baseRaw && baseRaw.startsWith('./')) ? '/' : (baseRaw.endsWith('/') ? baseRaw : `${baseRaw}/`)
const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))

// Notices
const { notice, showNotice, closeNotice } = useNotice()

// Balance visibility
const { hideBalances } = useBalanceVisibility()

const wallet = ref({ balance: 0, virtual_account: {} })
const transactions = ref([])
const page = ref(1)
const perPage = 10

// Withdrawal requests listing
const withdrawals = ref([])
const withdrawalsPage = ref(1)
const withdrawalsPerPage = 10
const withdrawalsLastPage = ref(1)
const topupAmount = ref('')
const loading = ref(false)
const assigning = ref(false)
const showFund = ref(true)
const showTransfer = ref(false)
const showWithdraw = ref(false)

// Withdraw to bank form state
const withdrawAmount = ref('')
const withdrawNote = ref('')

// P2P transfer form state
const toType = ref('phone') // 'phone' | 'membership'
const toValue = ref('')
const branchId = ref('')
const transferAmount = ref('')
const note = ref('')

// Recipient resolution state
const recipient = ref(null)
const recipientError = ref('')
const branchesOptions = ref([])

// Notice modal (shared)

// PIN prompt modal state
const pinPrompt = ref({ visible: false, mode: 'transfer', title: '', message: '', confirmText: '' })

// Optional BVN input before generating a virtual account
const bvn = ref('')
const bvnDigits = computed(() => String(bvn.value || '').replace(/\D/g, ''))
const bvnValid = computed(() => bvnDigits.value.length === 11)

const formatMoney = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const canSend = computed(() => {
  const amtOk = Number(transferAmount.value || 0) > 0
  const hasTo = String(toValue.value || '').trim().length > 0
  if (!amtOk || !hasTo) return false
  if (toType.value === 'membership') {
    if (branchesOptions.value.length) return false // force disambiguation
    if (recipient.value) return true
    if (Number(branchId.value)) return true
    // allow if backend won’t detect ambiguity; safer to require Verify
    return false
  }
  return true
})
const canWithdraw = computed(() => {
  const amt = Number(withdrawAmount.value || 0)
  const available = Number(wallet.value?.available_for_withdrawal || 0)
  return amt > 0 && amt <= available
})
const statusClass = (status) => {
  if (status === 'paid') return 'bg-emerald-100 text-emerald-700'
  if (status === 'declined') return 'bg-rose-100 text-rose-700'
  return 'bg-amber-100 text-amber-700'
}
const titleFor = (tx) => {
  const src = tx?.source
  if (src === 'wallet_allocation') return 'Allocation to Schemes'
  if (src === 'paystack_dva') return 'Bank Transfer (DVA)'
  if (src === 'vtu_airtime') return 'Airtime Purchase'
  if (src === 'vtu_data') return 'Data Purchase'
  if (src === 'p2p_transfer') {
    if (tx.type === 'debit') {
      const name = tx?.meta?.to_name || tx?.meta?.to_membership
      return name ? `Transfer to ${name}` : 'Transfer Sent'
    } else {
      const name = tx?.meta?.from_name || tx?.meta?.from_membership
      return name ? `Transfer from ${name}` : 'Transfer Received'
    }
  }
  return 'Wallet Top-up'
}

const loadWallet = async () => {
  const { data } = await axios.get('/api/wallet')
  wallet.value = data
  // Prefer server-provided recent list
  transactions.value = data.recent_transactions || []
}

const resetWithdrawals = async () => {
  withdrawalsPage.value = 1
  withdrawals.value = []
  await loadWithdrawals()
}

const loadWithdrawals = async () => {
  const { data } = await axios.get(`/api/wallet/withdrawals?page=${withdrawalsPage.value}&per_page=${withdrawalsPerPage}`)
  const items = Array.isArray(data?.data) ? data.data : []
  withdrawalsLastPage.value = Number(data?.last_page || 1)
  if (withdrawalsPage.value === 1) withdrawals.value = items
  else withdrawals.value = withdrawals.value.concat(items)
}

const loadMoreWithdrawals = async () => {
  if (withdrawalsPage.value < withdrawalsLastPage.value) {
    withdrawalsPage.value += 1
    await loadWithdrawals()
  }
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
    const payload = {}
    if (bvnDigits.value.length === 11) payload.bvn = bvnDigits.value
    const { data } = await axios.post('/api/virtual-account/assign', payload)
    const assigned = Boolean(data?.bvn_assigned ?? true)
    try { localStorage.setItem('bvn_assigned', JSON.stringify(assigned)) } catch (_) {}
    await loadWallet()
    bvn.value = ''
    alert('Virtual account generated!')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to generate virtual account')
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

// Start P2P transfer: biometric check then prompt for PIN
const startTransfer = async () => {
  if (!toValue.value || !transferAmount.value || Number(transferAmount.value) <= 0) {
    showNotice('Incomplete', 'Please enter a valid recipient and amount.', 'warning')
    return
  }
  if (toType.value === 'membership') {
    if (branchesOptions.value.length) {
      showNotice('Select Branch', 'Multiple members found. Please select the correct branch.', 'warning')
      return
    }
    if (!recipient.value && !Number(branchId.value)) {
      showNotice('Verify Recipient', 'Please tap Verify to confirm the recipient or provide a Branch ID.', 'warning')
      return
    }
  }
  try {
    const bioAvailable = await isBiometricAvailable()
    if (bioAvailable) {
      const ok = await verifyBiometricIdentity({
        reason: 'Authorize transfer',
        description: `Send ₦ ${Number(transferAmount.value).toLocaleString()} to ${toType.value === 'phone' ? 'phone' : 'member'} ${toValue.value}`,
      })
      if (!ok) {
        showNotice('Authentication required', 'Biometric verification was cancelled or failed. Unable to send transfer.', 'warning')
        return
      }
    }
  } catch (e) {
    // If biometric check throws, allow fallback to PIN only
  }
  pinPrompt.value.mode = 'transfer'
  pinPrompt.value.title = 'Confirm Transfer'
  pinPrompt.value.message = 'Enter your 4-digit Transaction PIN to authorize this transfer.'
  pinPrompt.value.confirmText = 'Send'
  pinPrompt.value.visible = true
}

// Start Withdraw: biometric check then prompt for PIN
const startWithdraw = async () => {
  const amt = Number(withdrawAmount.value || 0)
  const available = Number(wallet.value?.available_for_withdrawal || 0)
  if (!(amt > 0)) {
    showNotice('Enter amount', 'Please enter a valid withdrawal amount.', 'warning')
    return
  }
  if (amt > available) {
    showNotice('Too high', 'Amount exceeds your available-for-withdrawal balance.', 'error')
    return
  }
  try {
    const bioAvailable = await isBiometricAvailable()
    if (bioAvailable) {
      const ok = await verifyBiometricIdentity({
        reason: 'Authorize withdrawal',
        description: `Withdraw ₦ ${amt.toLocaleString()} to your saved bank account`,
      })
      if (!ok) {
        showNotice('Authentication required', 'Biometric verification was cancelled or failed. Unable to request withdrawal.', 'warning')
        return
      }
    }
  } catch (e) {
    // allow fallback to PIN
  }
  pinPrompt.value.mode = 'withdraw'
  pinPrompt.value.title = 'Confirm Withdrawal'
  pinPrompt.value.message = 'Enter your 4-digit Transaction PIN to request this withdrawal.'
  pinPrompt.value.confirmText = 'Request'
  pinPrompt.value.visible = true
}

const handlePinConfirm = async (val) => {
  const pin = String(val || '').trim()
  if (!/^\d{4}$/.test(pin)) {
    showNotice('Invalid PIN', 'Please enter a valid 4-digit Transaction PIN.', 'error')
    return
  }
  loading.value = true
  try {
    if (pinPrompt.value.mode === 'withdraw') {
      const payload = { amount: Number(withdrawAmount.value), pin }
      const n = String(withdrawNote.value || '').trim()
      if (n) payload.note = n
      const { data } = await axios.post('/api/wallet/withdraw', payload)
      pinPrompt.value.visible = false
      // Reset form
      withdrawAmount.value = ''
      withdrawNote.value = ''
      // Refresh wallet & withdrawals
      await loadWallet()
      await resetWithdrawals()
      showNotice('Success', 'Withdrawal request submitted.', 'success')
    } else {
      const payload = {
        to_type: toType.value,
        to: String(toValue.value || '').trim(),
        amount: Number(transferAmount.value),
        pin,
      }
      const n = String(note.value || '').trim()
      if (n) payload.note = n
      if (toType.value === 'membership' && Number(branchId.value)) payload.branch_id = Number(branchId.value)

      await axios.post('/api/wallet/transfer', payload)

      pinPrompt.value.visible = false
      // Reset form
      toValue.value = ''
      branchId.value = ''
      transferAmount.value = ''
      note.value = ''
      // Refresh wallet & transactions
      await loadWallet()
      showNotice('Success', 'Transfer sent successfully.', 'success')
    }
  } catch (e) {
    pinPrompt.value.visible = false
    const status = e?.response?.status
    const defaultMsg = pinPrompt.value.mode === 'withdraw' ? 'Withdrawal failed' : 'Transfer failed'
    const msg = e?.response?.data?.message || defaultMsg
    if (status === 409) {
      showNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
    } else if (status === 403) {
      showNotice('Invalid PIN', 'Your Transaction PIN is incorrect. Please try again.', 'error')
    } else if (status === 422 && pinPrompt.value.mode === 'withdraw' && (String(msg).toLowerCase().includes('bank details'))) {
      showNotice('Bank details required', 'Please add and verify your bank details in Profile > Bank Settings to withdraw to bank.', 'warning')
      try {
        if (window.confirm('Open Bank Settings now?')) router.push('/profile')
      } catch (_) {}
    } else if (status === 404 && pinPrompt.value.mode !== 'withdraw') {
      showNotice('Recipient not found', 'We could not find a member matching those details.', 'error')
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

// Resolve recipient preview
const checkRecipient = async () => {
  recipient.value = null
  recipientError.value = ''
  branchesOptions.value = []
  const v = String(toValue.value || '').trim()
  if (!v) return
  try {
    const params = { to_type: toType.value, to: v }
    if (toType.value === 'membership' && Number(branchId.value)) params.branch_id = Number(branchId.value)
    const { data } = await axios.get('/api/wallet/transfer/resolve', { params })
    recipient.value = data
  } catch (e) {
    const status = e?.response?.status
    if (status === 422 && e?.response?.data?.multiple) {
      recipientError.value = e?.response?.data?.message || 'Multiple members found. Please select a branch.'
      branchesOptions.value = Array.isArray(e?.response?.data?.branches) ? e.response.data.branches : []
    } else {
      recipientError.value = e?.response?.data?.message || 'Recipient not found'
    }
  }
}

const downloadReceipt = async (tx) => {
  try {
    const id = tx?.id ?? tx
    if (!id) {
      showNotice('Unavailable', 'Missing transaction ID for this receipt.', 'warning')
      return
    }
    const res = await axios.get(`/api/wallet/transactions/${id}/receipt`, { responseType: 'blob' })
    const contentType = res?.headers?.['content-type'] || 'application/pdf'
    const blob = new Blob([res.data], { type: contentType })
    let filename = `Wallet_Receipt_${tx?.reference || id}.pdf`
    const cd = res?.headers?.['content-disposition'] || res?.headers?.['Content-Disposition']
    if (cd) {
      const m = /filename\*?=(?:UTF-8''|\")?([^\";\n]+)\"?/i.exec(cd) || /filename="?([^\";\n]+)"?/i.exec(cd)
      if (m && m[1]) filename = m[1]
    }
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    const status = e?.response?.status
    if (status === 404) {
      showNotice('Not found', 'Receipt not found for this transaction.', 'error')
    } else {
      showNotice('Download failed', e?.response?.data?.message || 'Unable to download receipt. Please try again later.', 'error')
    }
  }
}

watch([toType, toValue, branchId], () => {
  recipient.value = null
  recipientError.value = ''
  branchesOptions.value = []
})

onMounted(async () => {
  await loadWallet()
  await resetWithdrawals()
})

const cancelWithdrawal = async (wr) => {
  if (!wr || !wr.id) return
  try {
    if (!window.confirm('Cancel this withdrawal request?')) return
    await axios.post(`/api/wallet/withdrawals/${wr.id}/cancel`)
    showNotice('Cancelled', 'Withdrawal request cancelled.', 'success')
    await resetWithdrawals()
  } catch (e) {
    const status = e?.response?.status
    if (status === 404) {
      showNotice('Not found', 'This withdrawal request could not be found.', 'error')
    } else if (status === 422) {
      showNotice('Unable to cancel', e?.response?.data?.message || 'Only pending requests can be cancelled.', 'warning')
    } else {
      showNotice('Failed', e?.response?.data?.message || 'Could not cancel request. Please try again later.', 'error')
    }
  }
}
</script>
