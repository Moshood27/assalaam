<template>
  <div class="min-h-screen pb-24">
    <header class="p-4 flex justify-between items-center bg-white/80 backdrop-blur border-b">
      <h1 class="font-bold text-slate-800 text-lg">Member Welfare Pool (Takaful)</h1>
      <button class="text-emerald-700 font-bold" @click="$router.back()">Back</button>
    </header>

    <div class="p-4 space-y-4">
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-slate-500">Current Period</p>
            <p class="text-sm font-bold text-slate-800">{{ summary.period }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-500">Monthly Amount</p>
            <p class="text-sm font-bold text-slate-800">₦ {{ formatMoney(summary.monthly_amount) }}</p>
          </div>
        </div>
        <div class="mt-3 flex items-center gap-2 flex-wrap">
          <span v-if="summary.paid_this_period" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-1 rounded-full">
            ✅ Paid this month
          </span>
          <template v-else>
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-1 rounded-full">
              ⏳ Pending this month
            </span>
            <button @click="payNow" :disabled="payLoading" class="text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 px-3 py-1 rounded-full">
              <span v-if="!payLoading">Pay now</span>
              <span v-else>Processing…</span>
            </button>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
          <p class="text-xs text-slate-500">My Total Contributions</p>
          <p class="text-xl font-extrabold text-slate-800">₦ {{ formatMoney(summary.my_total_contributions) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
          <p class="text-xs text-slate-500">Pool Balance</p>
          <p class="text-xl font-extrabold text-slate-800">₦ {{ formatMoney(summary.pool_balance) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
        <div class="p-4 border-b flex items-center justify-between">
          <h2 class="text-sm font-bold text-slate-800">My Contribution History</h2>
          <div class="text-xs text-slate-500">Page {{ page + 1 }}</div>
        </div>
        <div class="divide-y">
          <div v-for="row in rows" :key="row.id" class="p-4 flex items-center justify-between">
            <div>
              <p class="text-sm font-bold text-slate-800">{{ row.period }}</p>
              <p class="text-xs text-slate-500">{{ formatDate(row.created_at) }} • Ref: {{ row.reference || '—' }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold" :class="row.status==='success' ? 'text-emerald-700' : row.status==='pending' ? 'text-amber-700' : 'text-rose-700'">
                ₦ {{ formatMoney(row.amount) }}
              </p>
              <p class="text-[11px] uppercase tracking-wide" :class="row.status==='success' ? 'text-emerald-600' : row.status==='pending' ? 'text-amber-600' : 'text-rose-600'">{{ row.status }}</p>
            </div>
          </div>
          <div v-if="!loading && rows.length === 0" class="p-8 text-center text-slate-500 text-sm">No contributions yet.</div>
          <div v-if="loading" class="p-8 text-center text-slate-500 text-sm">Loading…</div>
        </div>
        <div class="p-4 flex items-center justify-between">
          <button class="px-3 py-2 rounded-lg bg-slate-100 disabled:opacity-50" :disabled="page<=0" @click="prev">Previous</button>
          <div class="text-xs text-slate-500">{{ total }} total</div>
          <button class="px-3 py-2 rounded-lg bg-slate-100 disabled:opacity-50" :disabled="!hasMore" @click="next">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'

const summary = ref({ period: '', monthly_amount: 0, paid_this_period: false, my_total_contributions: 0, pool_balance: 0 })
const rows = ref([])
const page = ref(0)
const perPage = ref(10)
const total = ref(0)
const hasMore = ref(false)
const loading = ref(false)
const payLoading = ref(false)

const loadSummary = async () => {
  try {
    const { data } = await axios.get('/api/takaful/summary')
    summary.value = data
  } catch (e) {
    console.error('Failed to load takaful summary', e)
  }
}

const loadRows = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/takaful/contributions', { params: { page: page.value + 1, per_page: perPage.value } })
    rows.value = data.data || []
    total.value = data.total || rows.value.length
    const from = data.from || 0
    const to = data.to || 0
    hasMore.value = (to < total.value)
  } catch (e) {
    console.error('Failed to load contributions', e)
  } finally {
    loading.value = false
  }
}

const payNow = async () => {
  if (payLoading.value) return
  payLoading.value = true
  try {
    const { data } = await axios.post('/api/takaful/pay-now', { period: summary.value?.period })
    alert('Takaful contribution paid successfully. Ref: ' + (data.reference || ''))
    await loadSummary()
    await loadRows()
  } catch (e) {
    const status = e?.response?.status
    const msg = e?.response?.data?.message || 'Failed to pay now'
    if (status === 422) {
      alert('Insufficient wallet balance. Please top up your wallet.')
    } else if (status === 409) {
      alert('Already paid for this period.')
      await loadSummary()
      await loadRows()
    } else {
      alert(msg)
    }
  } finally {
    payLoading.value = false
  }
}

const next = () => { if (hasMore.value) { page.value++; loadRows() } }
const prev = () => { if (page.value > 0) { page.value--; loadRows() } }

onMounted(() => {
  loadSummary()
  loadRows()
})

const formatMoney = (n) => {
  const num = Number(n || 0)
  return num.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
const formatDate = (s) => {
  if (!s) return ''
  try {
    const d = new Date(s)
    return d.toLocaleString()
  } catch (_) {
    return s
  }
}
</script>

<style scoped>
</style>
