<template>
  <div class="min-h-screen bg-slate-50 pb-40 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">⬅️</button>
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
        <div class="flex gap-2">
          <div class="flex-grow">
            <label class="lbl">Scheme</label>
            <select v-model="selectedSchemeId" class="inp">
              <option value="">Select Scheme</option>
              <option v-for="s in schemes" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="w-1/3">
            <label class="lbl">Amount</label>
            <input v-model.number="inputAmount" type="number" placeholder="0.00" class="inp" />
          </div>
          <button @click="addToList" class="btn-primary mt-6 w-12 h-12 rounded-xl text-2xl font-bold flex items-center justify-center">+</button>
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
            <div class="flex items-center gap-2">
              <p class="font-bold text-slate-800 text-sm">{{ item.scheme_name }}</p>
              <span v-if="item.category === 'fine'" class="badge badge-muted bg-rose-100 text-rose-700">Fine</span>
            </div>
            <p class="text-xs text-slate-500">Scheduled Payment</p>
          </div>
          <div class="flex items-center gap-4">
            <p class="font-bold text-slate-800">₦ {{ Number(item.amount).toLocaleString() }}</p>
            <button @click="removeFromList(index)" class="text-rose-400 text-sm">✕</button>
          </div>
        </div>
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

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/passbook')">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active" @click="$router.push('/pay')">
        <span class="text-xl">💳</span>
        <span class="text-[10px] font-bold">Pay</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const schemes = ref([])
const paymentList = ref([])
const selectedSchemeId = ref('')
const inputAmount = ref('')
const loading = ref(false)
const isFine = ref(false)
const payFromWallet = ref(false)
const walletBalance = ref(0)

const totalAmount = computed(() => paymentList.value.reduce((sum, i) => sum + Number(i.amount || 0), 0))

const addToList = () => {
  if (!selectedSchemeId.value || !inputAmount.value || Number(inputAmount.value) <= 0) return
  // robust id compare (string/number)
  const s = schemes.value.find(x => String(x.id) == String(selectedSchemeId.value))
  if (!s) return
  paymentList.value.push({ scheme_id: s.id, scheme_name: s.name, amount: Number(inputAmount.value), category: isFine.value ? 'fine' : 'deposit' })
  selectedSchemeId.value = ''
  inputAmount.value = ''
  isFine.value = false
}

const removeFromList = (idx) => paymentList.value.splice(idx, 1)

const loadSchemes = async () => {
  const { data } = await axios.get('/api/schemes')
  schemes.value = data
}

const loadWallet = async () => {
  try {
    const { data } = await axios.get('/api/wallet')
    walletBalance.value = data.balance || 0
  } catch (_) {}
}

const initiatePayment = async () => {
  try {
    loading.value = true
    if (payFromWallet.value) {
      // Allocate from wallet
      await axios.post('/api/wallet/allocate', { items: paymentList.value })
      alert('Allocation successful!')
      window.location.assign('/dashboard')
      return
    }
    // Otherwise, go through Paystack checkout
    const callback_url = `${window.location.origin}/payment-callback`
    const { data } = await axios.post('/api/initiate-payment', { items: paymentList.value, callback_url })
    window.location.href = data.checkout_url
  } catch (e) {
    alert(e?.response?.data?.message || 'Payment failed')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadSchemes(), loadWallet()])
})
</script>
