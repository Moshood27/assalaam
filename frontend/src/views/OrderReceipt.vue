<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">Order Receipt</h1>
      <button class="text-sm font-bold text-emerald-700" @click="$router.push('/store')">Back to Store</button>
    </header>

    <div class="p-4">
      <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
      <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
      <section v-else class="card p-5 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <div class="text-xs text-slate-500">Reference</div>
            <div class="font-bold text-slate-800 break-all">{{ order.reference }}</div>
          </div>
          <div class="text-left sm:text-right">
            <div class="text-xs text-slate-500">Total Amount</div>
            <div class="text-lg font-extrabold text-emerald-700">₦ {{ money(order.total_amount) }}</div>
          </div>
        </div>

        <ul class="divide-y divide-slate-200 bg-white border rounded-xl">
          <li v-for="it in order.items || []" :key="it.id" class="p-3 flex items-center justify-between gap-3">
            <div>
              <div class="font-bold text-slate-800">{{ it.product_name }}</div>
              <div class="text-xs text-slate-500">₦ {{ money(it.unit_price) }} x {{ it.quantity }}</div>
            </div>
            <div class="text-sm font-bold">₦ {{ money(it.line_total) }}</div>
          </li>
        </ul>

        <div v-if="order.meta && order.meta.note" class="pt-2 text-xs text-slate-500">
          <span class="uppercase font-bold mr-1">Note:</span>
          <span class="text-slate-700">{{ order.meta.note }}</span>
        </div>

        <div class="pt-2 text-xs text-slate-500">Status: <span class="font-bold text-slate-800 uppercase">{{ order.status }}</span></div>
        <div class="text-xs text-slate-500">Date: {{ new Date(order.created_at).toLocaleString() }}</div>

        <div v-if="financing" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded text-xs text-slate-700">
          <div class="font-black uppercase tracking-widest text-amber-700 mb-2">Murabaha Financing</div>
          <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div>Tenor: <span class="font-bold">{{ financing.months }} months</span></div>
            <div>Profit Rate: <span class="font-bold">{{ financingRate }}</span></div>
            <div v-if="monthlyDue !== null">Monthly (min): <span class="font-bold">₦ {{ money(monthlyDue) }}</span></div>
            <div v-if="financingNextDue">Next Due: <span class="font-bold">{{ financingNextDue }}</span></div>
            <div>Total Paid: <span class="font-bold text-emerald-700">₦ {{ money(totalPaid) }}</span></div>
            <div>Remaining: <span class="font-bold text-rose-700">₦ {{ money(remaining) }}</span></div>
          </div>

          <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
            <label class="text-[11px] text-slate-600 font-bold">Enter Amount to Pay</label>
            <div class="flex items-center gap-2">
              <input v-model.number="payAmount" :min="monthlyDue || 0" :max="remaining || undefined" step="0.01" type="number" inputmode="decimal" class="input !py-1 !px-2 w-40" placeholder="e.g. 10000"/>
              <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold disabled:opacity-50" :disabled="paying || !canPayInstallment || !validPayAmount" @click="openPin()">
                <span v-if="paying" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a 8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Processing…</span>
                <span v-else>Pay Installment</span>
              </button>
            </div>
            <div class="text-[11px] text-slate-500">Min: ₦ {{ money(monthlyDue || 0) }} • Max: ₦ {{ money(remaining || 0) }}</div>
          </div>

          <div v-if="payError" class="mt-2 text-rose-700 bg-rose-50 border border-rose-200 p-2 rounded">{{ payError }}</div>
          <div v-if="paySuccess" class="mt-2 text-emerald-700 bg-emerald-50 border border-emerald-200 p-2 rounded">{{ paySuccess }}</div>
        </div>

        <div class="flex items-center justify-end">
          <a :href="getDownloadUrl()" target="_blank" class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm">Print / Download PDF</a>
        </div>
      </section>
    </div>

    <!-- PIN Prompt Modal for Installment Payment -->
    <CustomNotice
      v-model="pinPrompt.visible"
      :type="'info'"
      :title="'Pay Installment'"
      :message="'Enter your 4-digit Transaction PIN to confirm payment.'"
      :prompt="true"
      inputLabel="Transaction PIN (4 digits)"
      confirmText="Pay Now"
      cancelText="Cancel"
      :busy="paying"
      @confirm="handlePinConfirm"
      @cancel="handlePinCancel"
    />

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1" @click="$router.push('/store')">
        <span class="text-xl">🛒</span>
        <span class="text-[10px] font-bold">Store</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/reports')">
        <span class="text-xl">📈</span>
        <span class="text-[10px] font-bold">Reports</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from '../http'
import { useRoute } from 'vue-router'
import CustomNotice from '../components/CustomNotice.vue'

const route = useRoute()
const id = Number(route.params.id)
const order = ref({})
const loading = ref(true)
const error = ref('')

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const financing = computed(() => (order.value?.meta && order.value.meta.financing) ? order.value.meta.financing : null)
const financingRate = computed(() => {
  const r = Number(financing.value?.profit_rate || 0)
  return `${Math.round(r * 100)}%`
})

// Determine next due installment and amounts
const schedule = computed(() => Array.isArray(financing.value?.schedule) ? financing.value.schedule : [])
const nextInstallment = computed(() => schedule.value.find(x => ['pending','partial'].includes(String(x.status||'').toLowerCase())) || schedule.value[0] || null)
const monthlyDue = computed(() => {
  const it = nextInstallment.value
  if (!it) return null
  const amt = Number(it.amount || 0)
  const paid = Number(it.paid_amount || 0)
  return Math.max(0, Number((amt - paid).toFixed(2)))
})
const financingNextDue = computed(() => {
  const it = nextInstallment.value
  if (!it?.due_date) return null
  try { return new Date(it.due_date).toLocaleDateString() } catch (_) { return it.due_date }
})

const totalPaid = computed(() => {
  if (!financing.value) return 0
  if (typeof financing.value.total_paid === 'number') return Number(financing.value.total_paid || 0)
  let sum = 0
  for (const it of schedule.value) {
    const amt = Number(it.amount || 0)
    const pd = Number(it.paid_amount || 0)
    sum += Math.min(amt, pd)
  }
  return Number(sum.toFixed(2))
})
const remaining = computed(() => {
  if (!financing.value) return 0
  if (typeof financing.value.remaining === 'number') return Number(financing.value.remaining || 0)
  const tot = Number(order.value?.total_amount || 0)
  return Math.max(0, Number((tot - totalPaid.value).toFixed(2)))
})

// Installment payment state & actions
const paying = ref(false)
const payError = ref('')
const paySuccess = ref('')
const pinPrompt = ref({ visible: false })
const payAmount = ref(null)

watch(monthlyDue, (v) => {
  // default pay to monthly due, but not beyond remaining
  const minPay = Math.min(Number(v || 0), Number(remaining.value || 0))
  payAmount.value = minPay > 0 ? Number(minPay.toFixed(2)) : null
})

const validPayAmount = computed(() => {
  const amt = Number(payAmount.value || 0)
  const min = Math.min(Number(monthlyDue.value || 0), Number(remaining.value || 0))
  const max = Number(remaining.value || 0)
  return amt > 0 && amt >= min && amt <= max
})

const canPayInstallment = computed(() => !!financing.value && Number(remaining.value) > 0 && String(order.value?.status||'').toLowerCase().startsWith('murabaha'))

const openPin = () => {
  payError.value = ''
  paySuccess.value = ''
  if (!payAmount.value && monthlyDue.value) {
    const minPay = Math.min(Number(monthlyDue.value || 0), Number(remaining.value || 0))
    payAmount.value = minPay > 0 ? Number(minPay.toFixed(2)) : null
  }
  pinPrompt.value.visible = true
}

const handlePinConfirm = async (val) => {
  let pin = String(val || '').trim()
  if (!/^\d{4}$/.test(pin)) {
    alert('Please enter a valid 4-digit PIN')
    return
  }
  paying.value = true
  try {
    const payload = { pin }
    if (validPayAmount.value) payload.amount = Number(payAmount.value)
    const { data } = await axios.post(`/api/store/orders/${id}/installments/pay`, payload)
    order.value = data?.order || order.value
    paySuccess.value = data?.message || 'Installment paid successfully'
    // refresh to get updated schedule/status
    try {
      const { data: fresh } = await axios.get(`/api/store/orders/${id}`)
      order.value = fresh || order.value
    } catch(_) {}
    pinPrompt.value.visible = false
  } catch (e) {
    pinPrompt.value.visible = false
    const status = e?.response?.status
    const msg = e?.response?.data?.message || e.message
    if (status === 409) {
      payError.value = 'You need to set your Transaction PIN before making payments. Go to Profile > Transaction PIN.'
    } else if (status === 403) {
      payError.value = 'Invalid Transaction PIN. Please try again.'
    } else {
      payError.value = msg
    }
  } finally {
    paying.value = false
  }
}

const handlePinCancel = () => { pinPrompt.value.visible = false }

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get(`/api/store/orders/${id}`)
    order.value = data || {}
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const getDownloadUrl = () => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-order-receipt/${id}?token=${encodeURIComponent(token)}`
}

onMounted(load)
</script>

<style scoped>
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; }
</style>
