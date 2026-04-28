<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <AppHeader title="Loan Analysis" :showBack="true">
      <template #right>
        <a :href="downloadUrl" target="_blank" class="p-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors flex items-center gap-1">
          <span>Report</span>
          <span class="text-[10px]">📥</span>
        </a>
      </template>
    </AppHeader>

    <div class="container-app py-4 space-y-6">
      <div v-if="loading" class="text-center text-slate-500 py-10">Loading analysis…</div>
      <div v-else-if="error" class="card p-4 text-rose-700 bg-rose-50 border-rose-200">{{ error }}</div>

      <div v-else class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-4">
          <div class="card p-4 bg-white shadow-sm">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Total Borrowed</p>
            <p class="text-lg font-black text-slate-800">₦ {{ n(analysis.summary.total_borrowed) }}</p>
          </div>
          <div class="card p-4 bg-white shadow-sm">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Total Repaid</p>
            <p class="text-lg font-black text-emerald-600">₦ {{ n(analysis.summary.total_paid) }}</p>
          </div>
          <div class="card p-4 bg-white shadow-sm border-l-4 border-rose-500">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Outstanding</p>
            <p class="text-lg font-black text-rose-600">₦ {{ n(analysis.summary.outstanding) }}</p>
          </div>
          <div class="card p-4 bg-white shadow-sm">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Active Loans</p>
            <p class="text-lg font-black text-slate-800">{{ analysis.summary.active_loans_count }}</p>
          </div>
        </div>

        <!-- Repayment Progress Chart -->
        <div class="card p-5 bg-white shadow-sm" v-if="analysis.summary.total_borrowed > 0">
          <h3 class="section-title mb-4">Overall Repayment Progress</h3>
          <div class="flex items-center justify-center">
            <apexchart type="radialBar" height="250" :options="progressChartOptions" :series="[progressPct]" />
          </div>
          <div class="text-center mt-2">
            <p class="text-sm text-slate-500">
              You have repaid <span class="font-bold text-emerald-600">{{ progressPct.toFixed(1) }}%</span> of your total borrowed principal.
            </p>
          </div>
        </div>

        <!-- Repayment Trend -->
        <div class="card p-5 bg-white shadow-sm">
          <h3 class="section-title mb-4">6-Month Repayment Trend</h3>
          <apexchart type="bar" height="250" :options="trendChartOptions" :series="trendSeries" />
        </div>

        <!-- Status Distribution -->
        <div class="card p-5 bg-white shadow-sm">
          <h3 class="section-title mb-4">Loan Status Distribution</h3>
          <div v-if="Object.keys(analysis.status_distribution).length">
             <apexchart type="donut" height="250" :options="statusChartOptions" :series="statusSeries" />
          </div>
          <div v-else class="text-center text-slate-400 text-sm py-4">No status data available</div>
        </div>

        <!-- Recent Activity -->
        <div class="card overflow-hidden">
          <div class="p-4 bg-slate-50 border-b border-slate-100">
            <h3 class="section-title">Recent Loans</h3>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="loan in analysis.recent_loans" :key="loan.id" class="p-4 flex justify-between items-center">
              <div>
                <p class="text-sm font-bold text-slate-800">{{ loan.qard_id_string }}</p>
                <p class="text-[10px] text-slate-400 uppercase font-black">{{ new Date(loan.created_at).toLocaleDateString() }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-black text-slate-700">₦ {{ n(loan.principal_amount) }}</p>
                <span :class="loan.status === 'active' ? 'text-emerald-600' : 'text-slate-400'" class="text-[10px] font-bold uppercase">{{ loan.status }}</span>
              </div>
            </div>
            <div v-if="!analysis.recent_loans.length" class="p-8 text-center text-slate-400 text-sm">
              No recent loans found.
            </div>
          </div>
        </div>
      </div>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'

const loading = ref(true)
const error = ref('')
const analysis = ref({
  summary: { total_borrowed: 0, total_paid: 0, outstanding: 0, loan_count: 0, active_loans_count: 0 },
  repayment_trend: {},
  status_distribution: {},
  recent_loans: []
})

const n = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const downloadUrl = computed(() => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-loan-analysis?token=${encodeURIComponent(token)}`
})

const progressPct = computed(() => {
  if (!analysis.value.summary.total_borrowed) return 0
  const pct = (analysis.value.summary.total_paid / analysis.value.summary.total_borrowed) * 100
  return Math.min(100, pct)
})

const fetchAnalysis = async () => {
  try {
    loading.value = true
    const res = await axios.get('/api/loans/analysis')
    analysis.value = res.data
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load loan analysis.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchAnalysis)

// Charts
const progressChartOptions = {
  chart: { type: 'radialBar' },
  plotOptions: {
    radialBar: {
      hollow: { size: '70%' },
      dataLabels: {
        name: { show: false },
        value: {
          offsetY: 10,
          fontSize: '22px',
          fontWeight: '900',
          formatter: (val) => val.toFixed(1) + '%'
        }
      }
    }
  },
  colors: ['#10b981'],
  labels: ['Progress']
}

const trendSeries = computed(() => [{
  name: 'Repayments',
  data: Object.values(analysis.value.repayment_trend)
}])

const trendChartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false } },
  colors: ['#10b981'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: Object.keys(analysis.value.repayment_trend),
    labels: { style: { fontSize: '10px', fontWeight: 'bold' } }
  },
  yaxis: {
    labels: {
      formatter: (val) => '₦' + Number(val).toLocaleString(),
      style: { fontSize: '10px' }
    }
  },
  tooltip: {
    y: { formatter: (val) => '₦' + n(val) }
  }
}))

const statusSeries = computed(() => Object.values(analysis.value.status_distribution))
const statusChartOptions = computed(() => ({
  chart: { type: 'donut' },
  labels: Object.keys(analysis.value.status_distribution).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
  colors: ['#10b981', '#f59e0b', '#ef4444', '#64748b', '#3b82f6'],
  legend: { position: 'bottom', fontSize: '12px' },
  dataLabels: { enabled: true, formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] },
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: true,
          total: { show: true, label: 'Total', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0) }
        }
      }
    }
  }
}))
</script>
