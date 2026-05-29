<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <AppHeader title="Passbook" :showBack="true">
      <template #right>
        <div class="flex items-center gap-1">
          <a :href="getDownloadUrl('pdf')" target="_blank" class="p-2 text-xs font-bold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">PDF</a>
          <a :href="getDownloadUrl('csv')" target="_blank" class="p-2 text-xs font-bold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">CSV</a>
        </div>
      </template>
    </AppHeader>

    <div class="p-4 space-y-6">
      <div v-if="loadError" class="card p-4 border border-rose-200 bg-rose-50 text-rose-700 text-sm">
        {{ loadError }}
      </div>
      <!-- Yearly summary -->
      <div v-if="!isLoading" class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-[2rem] p-6 text-white shadow-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-blue-100 text-[10px] font-bold uppercase tracking-widest">Yearly Cumulative (Ã¢â€šÂ¦)</p>
            <p class="text-3xl font-extrabold tracking-tight mt-1">{{ Number(grandTotal).toLocaleString() }}</p>
          </div>
          <div class="bg-white/10 rounded-xl px-3 py-2 text-xs">
            <span class="opacity-80 mr-1">Year</span>
            <select v-model.number="selectedYear" @change="fetchPassbook" class="bg-transparent outline-none font-bold">
              <option v-for="y in years" :key="y" :value="y" class="text-slate-900">{{ y }}</option>
            </select>
          </div>
        </div>
        <p v-if="dividendAmount !== null" class="mt-2 text-[11px] text-blue-100">
          Est. Dividend ({{ selectedYear }}):
          <span class="font-black text-white">Ã¢â€šÂ¦ {{ Number(dividendAmount).toLocaleString() }}</span>
        </p>
      </div>
      <div v-else class="rounded-[2rem] p-6 shadow-xl bg-slate-200/60 animate-pulse h-28"></div>

      <!-- Grid -->
      <div v-if="!isLoading" class="card card-elevated overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-800 text-white text-[10px] uppercase">
                <th class="p-3 sticky left-0 bg-slate-800 z-10 border-r border-slate-700">Scheme</th>
                <th class="p-3 text-center min-w-[64px] border-r border-slate-700 bg-slate-900/40">BF</th>
                <th v-for="(m, i) in months" :key="i" class="p-3 text-center min-w-[64px] border-r border-slate-700">{{ m }}</th>
                <th class="p-3 text-center bg-blue-700">Total</th>
              </tr>
            </thead>
            <tbody class="text-[11px]">
              <tr v-for="(row, idx) in matrix" :key="idx" class="border-b border-slate-100 hover:bg-slate-50">
                <td class="p-3 font-bold text-slate-700 sticky left-0 bg-white border-r border-slate-100 shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                  {{ row.scheme_name }}
                </td>
                <td class="p-3 text-center border-r border-slate-50 text-slate-700 font-semibold">
                  {{ Number(row.bf ?? row.brought_forward ?? 0) > 0 ? Number(row.bf ?? row.brought_forward ?? 0).toLocaleString() : '-' }}
                </td>
                <td v-for="mIdx in 12" :key="mIdx" class="p-3 text-center border-r border-slate-50 text-slate-600">
                  {{ Number(row.months[mIdx] || 0) > 0 ? Number(row.months[mIdx]).toLocaleString() : '-' }}
                </td>
                <td class="p-3 text-center font-black text-slate-900 bg-slate-50">
                  {{ Number(row.total).toLocaleString() }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="card card-elevated p-6 animate-pulse space-y-3">
        <div class="h-4 bg-slate-200 rounded"></div>
        <div class="h-4 bg-slate-200 rounded"></div>
        <div class="h-4 bg-slate-200 rounded"></div>
      </div>

      <p class="text-[10px] text-gray-400 mt-4 px-2 italic text-center">Swipe left/right to view all months</p>

      <div v-if="!isLoading && showAgm" class="card p-4 border-blue-200 bg-blue-50">
        <div class="flex items-center justify-between mb-2">
          <p class="text-[10px] text-blue-700 font-black uppercase tracking-widest">{{ selectedYear }} AGM Fee</p>
          <span :class="agmPaid ? 'bg-blue-200 text-blue-800' : 'bg-yellow-200 text-yellow-800'"
                class="px-2 py-1 rounded-full text-[10px] font-black uppercase">
            {{ agmPaid ? 'Paid' : 'Pending' }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <p class="text-slate-700 text-sm">Mandatory annual meeting fee</p>
          <p class="text-slate-900 font-black">Ã¢â€šÂ¦ {{ Number(agmAmount).toLocaleString() }}</p>
        </div>
      </div>
      <div v-else-if="!isLoading && !showAgm" class="hidden"></div>
      <div v-else class="card p-4 animate-pulse h-20"></div>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import { ref, onMounted, computed, onUnmounted } from 'vue'
import axios from '../http.js'
import { getEcho } from '../realtime/echo'

const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
const years = [new Date().getFullYear() - 1, new Date().getFullYear(), new Date().getFullYear() + 1]
const selectedYear = ref(new Date().getFullYear())
const matrix = ref([])
const grandTotal = ref(0)
const agmAmount = ref(0)
const agmPaid = ref(false)
const dividendAmount = ref(null)
const isLoading = ref(true)
const loadError = ref('')

// Only show the AGM card if backend provided data (amount > 0) or payment status is true
const showAgm = computed(() => Number(agmAmount.value) > 0 || Boolean(agmPaid.value))

const fetchPassbook = async () => {
  const token = localStorage.getItem('token')
  isLoading.value = true
  loadError.value = ''
  try {
    const { data } = await axios.get(`/api/passbook/${selectedYear.value}`, { headers: { Authorization: `Bearer ${token}` } })
    matrix.value = data.matrix
    grandTotal.value = data.grand_total

    // Optional fields for AGM fee tracking; dynamic per year with sensible fallbacks
    const amountKey = `agm_fee_${selectedYear.value}_amount`
    const paidKey = `agm_fee_${selectedYear.value}_paid`
    agmAmount.value = (data && (data[amountKey] ?? data.agm_fee_amount)) ?? 0
    agmPaid.value = Boolean(data && (data[paidKey] ?? data.agm_fee_paid ?? false))

    // Also fetch dividend for selected year; failure is non-fatal
    try {
      const { data: div } = await axios.get(`/api/reports/dividend/${selectedYear.value}`, { headers: { Authorization: `Bearer ${token}` } })
      dividendAmount.value = div?.dividend ?? 0
    } catch (_) {
      dividendAmount.value = null
    }
  } catch (e) {
    console.error('Failed to load passbook', e)
    loadError.value = e?.response?.data?.message || 'Failed to load passbook'
    // Provide safe defaults when API fails
    agmAmount.value = 0
    agmPaid.value = false
    dividendAmount.value = null
  } finally {
    isLoading.value = false
  }
}

const getDownloadUrl = (format) => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  const endpoint = format === 'csv' ? 'download-passbook-csv' : 'download-passbook'
  return `${baseUrl}/api/${endpoint}?year=${selectedYear.value}&token=${encodeURIComponent(token)}`
}
onMounted(async () => {
  await fetchPassbook()

  // Real-time listener
  try {
    const echo = getEcho()
    const token = localStorage.getItem('token')
    if (token) {
      // Get profile to know user ID
      const { data: userData } = await axios.get('/api/profile', { headers: { Authorization: `Bearer ${token}` } })
      const userId = userData.id

      if (userId) {
        echo.private(`user.${userId}`)
          .listen('UserAccountUpdated', (e) => {
            console.log('Real-time update received in Passbook:', e)
            fetchPassbook() // Just refresh everything to be sure
          })
      }
    }
  } catch (err) {
    console.error('Failed to initialize real-time listener in Passbook:', err)
  }
})

onUnmounted(() => {
  // Echo cleanup if needed
})
</script>

<style scoped>
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}
</style>


