<template>
  <div class="min-h-screen pb-28 overflow-x-hidden">
    <header class="p-4 flex justify-between items-center bg-white/80 backdrop-blur border-b">
      <button class="flex items-center gap-2 min-w-0" @click="$router.push('/profile')">
        <div class="w-10 h-10 rounded-full overflow-hidden bg-emerald-700 flex items-center justify-center text-white font-bold text-xl">
          <img v-if="dashboardData.passport_url" :src="dashboardData.passport_url" alt="Profile photo" class="w-10 h-10 object-cover" />
          <span v-else>{{ (dashboardData.full_name || 'M')[0] }}</span>
        </div>
        <div class="text-left min-w-0">
          <p class="text-xs text-gray-500 font-medium">Welcome back,</p>
          <h2 class="text-sm font-bold text-slate-800 uppercase truncate">{{ dashboardData.full_name }}</h2>
        </div>
      </button>
      <button class="bg-slate-100 p-2 rounded-full text-xl" title="Support" @click="$router.push('/settings')">⚙️</button>
    </header>

    <div class="p-4">
      <div v-if="!bannerDismissed" class="mb-3 bg-emerald-100 text-emerald-900 p-3 rounded-xl border border-emerald-200 flex items-start justify-between gap-3 flex-wrap relative">
        <div class="text-sm">
          <p class="font-medium">Please copy your email address and use the same password to login when accessing your web passbook.</p>
          <button @click="openWebPortal" class="mt-2 text-emerald-800 font-bold underline">Login to Web Passbook</button>
        </div>
        <button @click="dismissBanner" class="text-emerald-900/70 absolute right-3 top-3 sm:static sm:mt-0">✕</button>
      </div>
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-7 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full" />
        <div class="flex items-center gap-2 mb-2 relative z-10">
          <p class="text-emerald-100 text-sm font-medium">Available Balance</p>
          <button @click="hideBalance = !hideBalance" class="text-lg opacity-80" title="Toggle visibility">
            <span v-if="hideBalance">👁️</span>
            <span v-else>🙈</span>
          </button>
        </div>
        <h1 class="text-3xl sm:text-4xl leading-tight font-bold relative z-10 tracking-tight">
          ₦ {{ hideBalance ? '***,***.**' : formatMoney(dashboardData.balance) }}
        </h1>
        <div class="mt-8 flex items-center justify-between flex-wrap gap-2 relative z-10">
          <div class="flex items-center gap-2">
            <p class="text-xs text-emerald-100 font-mono tracking-widest">ID: {{ dashboardData.membership_id }}</p>
            <button @click="copy(dashboardData.membership_id)" class="text-xs text-white/80 underline">Copy</button>
          </div>
          <button @click="$router.push('/wallet')" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">
            + Add Money
          </button>
        </div>
      </div>

      <!-- KPI row -->
      <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-2">
        <StatPill label="Contributions" :value="currency + ' ' + formatMoney(kpis.contributions)" hint="Total" intent="success" icon="💰" />
        <StatPill label="Loans" :value="currency + ' ' + formatMoney(kpis.loans)" hint="Outstanding" intent="warning" icon="📊" />
        <StatPill label="Utilities" :value="currency + ' ' + formatMoney(kpis.utilities)" hint="Spent" intent="default" icon="📶" />
      </div>

      <!-- Trend chart -->
      <FinCard class="mt-4" :padded="true" :elevated="true">
        <template #title>
          Activity Trend
        </template>
        <TrendChart :series="chart.series" :categories="chart.categories" :currency="currency" />
      </FinCard>
    </div>

    <div class="px-4 grid grid-cols-2 gap-4 mt-2">
      <button @click="$router.push('/pay')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">💳</div>
        <span class="text-sm font-bold text-slate-700">Make Payment</span>
      </button>
      <button @click="$router.push('/vtu')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">📶</div>
        <span class="text-sm font-bold text-slate-700">Airtime/Data</span>
      </button>
      <button @click="$router.push('/loans')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-2xl">📊</div>
        <span class="text-sm font-bold text-slate-700">Loan Records</span>
      </button>
      <button @click="$router.push('/reports')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">📈</div>
        <span class="text-sm font-bold text-slate-700">Reports</span>
      </button>
      <button @click="$router.push('/store')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center text-2xl">🛒</div>
        <span class="text-sm font-bold text-slate-700">Store</span>
      </button>
      <button @click="$router.push('/agm')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-fuchsia-50 rounded-2xl flex items-center justify-center text-2xl">🗳️</div>
        <span class="text-sm font-bold text-slate-700">AGM & Voting</span>
      </button>
      <button @click="checkZakat" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-2xl">🕌</div>
        <span class="text-sm font-bold text-slate-700">Zakat</span>
      </button>
      <button @click="$router.push('/goals')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">🕋</div>
        <span class="text-sm font-bold text-slate-700">Hajj & Umrah</span>
      </button>
    </div>

    <div class="px-4 mt-8">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-slate-800 text-lg">Recent Transactions</h3>
        <button class="text-emerald-700 text-sm font-bold" @click="$router.push('/passbook')">Passbook</button>
      </div>

      <div v-if="dashboardData.transactions?.length" class="space-y-3">
        <div v-for="tx in dashboardData.transactions" :key="tx.id"
             class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 overflow-hidden border border-slate-100 shadow-sm">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div :class="tx.status === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-yellow-100 text-yellow-600'"
                 class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
              {{ tx.status === 'success' ? '✓' : '⌛' }}
            </div>
            <div class="min-w-0 overflow-hidden">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-bold text-slate-800 text-sm truncate max-w-[160px] sm:max-w-none">{{ tx.scheme?.name || (isFine(tx) ? 'Lateness/Apology Fine' : 'Contribution') }}</p>
                <span v-if="isFine(tx)" class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black uppercase">Fine</span>
                <span v-if="tx.status === 'success'" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase">Approved</span>
              </div>
              <p class="text-[10px] text-gray-500 uppercase font-medium">{{ formatDate(tx.created_at) }}</p>
              <p class="text-[10px] text-slate-400 font-mono truncate">{{ txPrefix(tx) }}</p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-slate-800">₦ {{ formatMoney(tx.amount) }}</p>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-10 text-gray-400">
        <p>No transactions yet.</p>
      </div>

      <!-- Recent Utility Transactions -->
      <div class="flex justify-between items-center mb-4 mt-10">
        <h3 class="font-bold text-slate-800 text-lg">Recent Airtime/Data</h3>
        <button class="text-emerald-700 text-sm font-bold" @click="$router.push('/vtu/history')">See all</button>
      </div>
      <div v-if="dashboardData.utility_transactions?.length" class="space-y-3">
        <div v-for="ux in dashboardData.utility_transactions" :key="ux.id"
             class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 overflow-hidden border border-slate-100 shadow-sm">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div :class="ux.status === 'success' ? 'bg-emerald-100 text-emerald-600' : (ux.status === 'failed' ? 'bg-rose-100 text-rose-600' : 'bg-yellow-100 text-yellow-600')"
                 class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
              {{ ux.status === 'success' ? '✓' : (ux.status === 'failed' ? '✕' : '⌛') }}
            </div>
            <div class="min-w-0 overflow-hidden">
              <p class="font-bold text-slate-800 text-sm capitalize truncate max-w-[180px] sm:max-w-none">{{ utilLabel(ux) }}</p>
              <p class="text-[10px] text-gray-500 uppercase font-medium">{{ formatDate(ux.created_at) }}</p>
              <p class="text-[10px] text-slate-400 font-mono truncate">{{ ux.reference }}</p>
            </div>
          </div>
          <div class="text-right shrink-0">
            <p class="font-bold text-slate-800">₦ {{ formatMoney(ux.amount) }}</p>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-6 text-gray-400">
        <p>No VTU activity yet.</p>
      </div>
    </div>

    <!-- Reusable Custom Notice Modal for Zakat/info alerts -->
    <CustomNotice
      v-model="notice.visible"
      :type="notice.type"
      :title="notice.title"
      :message="notice.message"
      @close="closeNotice"
    />

    <nav class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur border-t p-3 flex justify-around items-center" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
      <button class="text-emerald-700 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/passbook')">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="logout">
        <span class="text-xl">🚪</span>
        <span class="text-[10px] font-bold">Logout</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useModal } from '../composables/useModal'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'
import FinCard from '../components/FinCard.vue'
import StatPill from '../components/StatPill.vue'
import TrendChart from '../components/TrendChart.vue'

const modal = useModal()
const { notice, showNotice, closeNotice } = useNotice()

const currency = '₦'
const dashboardData = ref({})
const hideBalance = ref(false)
const bannerDismissed = ref(JSON.parse(localStorage.getItem('web_portal_banner_dismissed') || 'false'))

const formatMoney = (val) => Number(val ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
const copy = async (text) => {
  try {
    await navigator.clipboard.writeText(String(text || ''))
    await modal.alert('Copied to clipboard')
  } catch (_) {}
}
const openWebPortal = () => window.open('https://portal.example.com', '_blank')
const dismissBanner = () => { bannerDismissed.value = true; localStorage.setItem('web_portal_banner_dismissed', 'true') }

const kpis = computed(() => {
  const d = dashboardData.value || {}
  const txs = Array.isArray(d.transactions) ? d.transactions : []
  const utils = Array.isArray(d.utility_transactions) ? d.utility_transactions : []
  const totalContrib = txs.reduce((sum, t) => sum + Number(t.amount || 0), 0)
  const outstandingLoans = txs.filter(t => (t.type === 'loan' || String(t.scheme?.name || '').toLowerCase().includes('loan')))
    .reduce((sum, t) => sum + Number(t.balance || 0), 0)
  const utilSpent = utils.reduce((sum, u) => sum + Number(u.amount || 0), 0)
  return { contributions: totalContrib, loans: outstandingLoans, utilities: utilSpent }
})

const chart = computed(() => {
  const d = dashboardData.value || {}
  const txs = Array.isArray(d.transactions) ? d.transactions.slice().sort((a,b) => new Date(a.created_at) - new Date(b.created_at)) : []
  // build simple last-10 points
  const points = txs.slice(-10)
  const categories = points.map(p => new Date(p.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }))
  const series = [{ name: 'Balance', data: points.map(p => Number(p.balance_after || p.running_balance || 0)) }]
  return { categories, series }
})

const txPrefix = (tx) => {
  const ref = tx.reference || tx.tx_ref || ''
  if (ref) return ref
  const schemeName = (tx.scheme?.name || '').toLowerCase()
  if (tx.type === 'loan' || schemeName.includes('loan')) return `lnref_${tx.id}`
  if (tx.type === 'fine' || schemeName.includes('fine') || schemeName.includes('lateness') || schemeName.includes('apology')) return `fine_${tx.id}`
  return `dpref_${tx.id}`
}
const isFine = (tx) => {
  const schemeName = (tx.scheme?.name || '').toLowerCase()
  return tx.type === 'fine' || schemeName.includes('fine') || schemeName.includes('lateness') || schemeName.includes('apology')
}

const utilLabel = (ux) => {
  const type = (ux.type || '').toLowerCase()
  const net = (ux.network || '').toUpperCase()
  const phone = ux.phone_number || ''
  if (type === 'airtime') return `Airtime — ${net} (${phone})`
  if (type === 'data') return `Data — ${net} (${phone})`
  return `${type || 'utility'} — ${net} (${phone})`
}

const load = async () => {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('/api/dashboard', { headers: { Authorization: `Bearer ${token}` } })
  dashboardData.value = data
}

const logout = () => {
  localStorage.removeItem('token')
  const base = import.meta?.env?.BASE_URL || '/'
  const basePath = (base && base.endsWith('/')) ? base : `${base}/`
  window.location.assign(`${basePath}login`)
}

const checkZakat = async () => {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/zakat/estimate', { headers: { Authorization: `Bearer ${token}` } })

    if (!data || !data.base) {
      showNotice('Zakat', 'Could not compute your Zakat at this time. Please try again later.', 'error')
      return
    }

    if (!data.eligible) {
      const msg = data.base < data.nisab
        ? `You are currently below the Nisab (${currency} ${formatMoney(data.nisab)}).`
        : `You will be eligible on ${new Date(data.eligible_on).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}.`
      showNotice('Zakat', `Zakat not yet due.\n${msg}`, 'info')
      return
    }

    const due = formatMoney(data.zakat_due)
    const ok = await modal.confirm(`Your Zakat for this year is ${currency} ${due}. Would you like to pay now?`, { confirmText: 'Pay Now' })
    if (!ok) return

    const payResp = await axios.post('/api/zakat/pay', {}, { headers: { Authorization: `Bearer ${token}` } })
    const url = payResp.data?.checkout_url || payResp.data?.authorization_url
    if (url) {
      window.location.assign(url)
    } else {
      showNotice('Zakat', 'Failed to start payment. Please try again.', 'error')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'An error occurred while checking Zakat.'
    showNotice('Zakat', msg, 'error')
  }
}

onMounted(load)
</script>
