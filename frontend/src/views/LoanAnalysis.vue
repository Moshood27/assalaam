<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <AppHeader title="Loan Analysis" :showBack="true">
      <template #right>
        <a :href="downloadUrl" target="_blank" class="p-2 text-xs font-bold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center gap-1">
          <span>Report</span>
          <span class="text-[10px]">Ã°Å¸â€œÂ¥</span>
        </a>
      </template>
    </AppHeader>

    <div class="container-app py-4 space-y-6">
      <div v-if="loading" class="text-center text-slate-500 py-10">Loading analysis...</div>
      <div v-else-if="error" class="card p-4 text-rose-700 bg-rose-50 border-rose-200">{{ error }}</div>

      <div v-else class="space-y-6">
        <!-- Detailed Loan List -->
        <div class="card overflow-hidden">
          <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="section-title">Loan Details</h3>
            <span class="text-[10px] font-bold text-slate-400 uppercase">{{ analysis.loans.length }} total</span>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="loan in analysis.loans" :key="loan.id" class="p-4 space-y-3">
              <div class="flex justify-between items-start">
                <div>
                  <p class="text-sm font-black text-slate-800">{{ loan.qard_id_string }}</p>
                  <p class="text-[10px] text-slate-400 uppercase font-black">{{ new Date(loan.created_at).toLocaleDateString() }}</p>
                </div>
                <div class="text-right">
                   <span :class="getStatusClass(loan.status)" class="text-[10px] font-bold uppercase px-2 py-1 rounded-md">{{ loan.status }}</span>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                   <p class="text-[9px] text-slate-400 font-bold uppercase">Principal</p>
                   <p class="text-sm font-bold text-slate-700">₦ {{ n(loan.principal_amount) }}</p>
                </div>
                <div>
                   <p class="text-[9px] text-slate-400 font-bold uppercase">Paid</p>
                   <p class="text-sm font-bold text-blue-600">₦ {{ n(loan.paid_amount) }}</p>
                </div>
                <div>
                   <p class="text-[9px] text-slate-400 font-bold uppercase">Remaining</p>
                   <p class="text-sm font-bold text-rose-600">₦ {{ n(loan.remaining_principal) }}</p>
                </div>
                <div v-if="loan.next_due_at">
                   <p class="text-[9px] text-slate-400 font-bold uppercase">Next Due</p>
                   <p class="text-sm font-bold text-slate-700">{{ new Date(loan.next_due_at).toLocaleDateString() }}</p>
                </div>
                <div v-if="loan.next_installment_amount && loan.status === 'active'">
                   <p class="text-[9px] text-slate-400 font-bold uppercase">Next Amount</p>
                   <p class="text-sm font-bold text-amber-600">₦ {{ n(loan.next_installment_amount) }}</p>
                </div>
                <div>
                   <p class="text-[9px] text-slate-400 font-bold uppercase">Interval</p>
                   <p class="text-sm font-bold text-slate-700 capitalize">{{ loan.interval }} ({{ loan.total_installments }}x)</p>
                </div>
              </div>

              <div v-if="loan.guarantors && loan.guarantors.length" class="pt-2 border-t border-slate-50">
                 <p class="text-[9px] text-slate-400 font-bold uppercase mb-1">Guarantors</p>
                 <div class="flex flex-wrap gap-1">
                   <span v-for="g in loan.guarantors" :key="g.id" class="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-full border border-slate-200">
                     {{ g.name }} <span v-if="g.branch" class="text-[8px] opacity-75">• {{ g.branch.name }}</span>
                   </span>
                 </div>
              </div>

              <div class="pt-1">
                <div class="flex justify-between text-[9px] font-bold text-slate-400 mb-1 uppercase">
                  <span>Progress</span>
                  <span>{{ (loan.progress_pct || 0).toFixed(1) }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: (loan.progress_pct || 0) + '%' }"></div>
                </div>
              </div>
            </div>
            <div v-if="!analysis.loans.length" class="p-8 text-center text-slate-400 text-sm">
              No loans found.
            </div>
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

        <!-- Repayment Progress Chart -->
        <div class="card p-5 bg-white shadow-sm" v-if="analysis.summary.total_borrowed > 0">
          <h3 class="section-title mb-4">Overall Repayment Progress</h3>
          <div class="flex items-center justify-center">
            <apexchart type="radialBar" height="250" :options="progressChartOptions" :series="[progressPct]" />
          </div>
          <div class="text-center mt-2">
            <p class="text-sm text-slate-500">
              You have repaid <span class="font-bold text-blue-600">{{ progressPct.toFixed(1) }}%</span> of your total borrowed principal.
            </p>
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
  loans: [],
  recent_loans: []
})

const n = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const getStatusClass = (status) => {
  switch (status) {
    case 'active': return 'bg-blue-50 text-blue-700 border border-blue-100'
    case 'completed': return 'bg-blue-50 text-blue-700 border border-blue-100'
    case 'pending': return 'bg-amber-50 text-amber-700 border border-amber-100'
    case 'defaulted': return 'bg-rose-50 text-rose-700 border border-rose-100'
    default: return 'bg-slate-50 text-slate-700 border border-slate-100'
  }
}

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


