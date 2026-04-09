<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="p-2 -ml-2 hover:bg-slate-100 rounded-full transition-colors" aria-label="Go back">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Reports</h1>
        <div class="w-10"></div>
      </div>
    </header>

    <div class="p-4 space-y-6 max-w-2xl mx-auto">
      <!-- Contribution Mix -->
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Contribution Mix</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Breakdown</span>
        </div>
        <div v-if="mixLoading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="mixError" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ mixError }}</div>
        <div v-else>
          <div v-if="!mix.breakdown?.length" class="text-slate-500 text-sm">No data</div>
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
            <div>
              <apexchart type="pie" height="300" :options="mixChartOptions" :series="mixSeries" />
            </div>
            <ul class="space-y-3">
              <li v-for="row in mix.breakdown" :key="row.scheme_id">
                <div class="flex items-center justify-between mb-1">
                  <div class="font-bold text-slate-700">{{ row.scheme_name }}</div>
                  <div class="text-xs text-slate-500">{{ row.percentage.toFixed(2) }}%</div>
                </div>
                <div class="h-2 bg-slate-200 rounded overflow-hidden">
                  <div class="h-2 bg-emerald-500" :style="{ width: Math.min(100, Math.max(0, row.percentage)).toFixed(2) + '%' }"></div>
                </div>
                <div class="text-right text-[11px] text-slate-500 mt-1">₦ {{ money(row.amount) }}</div>
              </li>
            </ul>
          </div>
          <div class="mt-4 text-right text-xs text-slate-600">
            Total: <span class="font-black text-slate-900">₦ {{ money(mix.total) }}</span>
          </div>
        </div>
      </section>

      <!-- Annual Dividend -->
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Annual Dividend Statement</h2>
          <div class="flex items-center gap-2">
            <select v-model.number="divYear" @change="loadDividend" class="inp text-xs font-bold">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <a :href="getDividendUrl()" target="_blank" class="btn-ghost text-xs">
              Download PDF
            </a>
          </div>
        </div>
        <div v-if="divLoading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="divError" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ divError }}</div>
        <div v-else class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500 uppercase font-black tracking-widest">Total Savings ({{ divYear }})</span>
            <span class="font-black text-slate-900">₦ {{ money(divData.total_savings) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500 uppercase font-black tracking-widest">Rate</span>
            <span class="font-black text-slate-900">{{ (Number(divData.rate || 0) * 100).toFixed(2) }}%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500 uppercase font-black tracking-widest">Estimated Dividend</span>
            <span class="text-emerald-700 font-black text-lg">₦ {{ money(divData.dividend) }}</span>
          </div>
        </div>
      </section>

      <!-- Financial Statements Downloads -->
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Financial Statements</h2>
          <div class="flex items-center gap-2">
            <select v-model.number="divYear" class="inp text-xs font-bold">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <a :href="getAppropriationUrl()" target="_blank" class="btn-ghost text-xs">
              Appropriation Account
            </a>
            <a :href="getFinancialsUrl()" target="_blank" class="btn-ghost text-xs">
              Financial Statements
            </a>
          </div>
        </div>
        <p class="text-[12px] text-slate-500">Download your cooperative's Appropriation Account and full Financial Statements for the selected year.</p>
      </section>
    </div>

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/passbook')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span class="text-[10px] font-bold">Reports</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../http.js'
import { openBlob } from '../utils/download'

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// Contribution mix state
const mix = ref({ total: 0, breakdown: [] })
const mixLoading = ref(false)
const mixError = ref('')

// Pie chart computed data
const mixSeries = computed(() => (mix.value.breakdown || []).map(r => Number(r.amount || 0)))
const mixLabels = computed(() => (mix.value.breakdown || []).map(r => r.scheme_name || 'Unknown'))
const mixChartOptions = computed(() => ({
  chart: { id: 'mix-pie' },
  labels: mixLabels.value,
  legend: { position: 'bottom' },
  dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(1)}%` },
}))

// Dividend state
const now = new Date().getFullYear()
const years = [now - 1, now, now + 1]
const divYear = ref(now)
const divData = ref({ total_savings: 0, rate: 0, dividend: 0 })
const divLoading = ref(false)
const divError = ref('')

const loadMix = async () => {
  mixLoading.value = true
  mixError.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/reports/contribution-mix', { headers: { Authorization: `Bearer ${token}` } })
    mix.value = data
  } catch (e) {
    mixError.value = e?.response?.data?.message || e.message
  } finally {
    mixLoading.value = false
  }
}

const loadDividend = async () => {
  divLoading.value = true
  divError.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get(`/api/reports/dividend/${divYear.value}`, { headers: { Authorization: `Bearer ${token}` } })
    divData.value = data
  } catch (e) {
    divError.value = e?.response?.data?.message || e.message
  } finally {
    divLoading.value = false
  }
}

// Robust blob opener that works in web and most mobile webviews
// (Moved to utils/download.js)

const getDividendUrl = () => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-dividend/${divYear.value}?token=${encodeURIComponent(token)}`
}

const getAppropriationUrl = () => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-appropriation/${divYear.value}?token=${encodeURIComponent(token)}`
}

const getFinancialsUrl = () => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-financials/${divYear.value}?token=${encodeURIComponent(token)}`
}

onMounted(() => { loadMix(); loadDividend() })
</script>

