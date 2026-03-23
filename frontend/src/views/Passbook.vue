<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">⬅️</button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Passbook</h1>
        <button @click="downloadPdf" class="btn-ghost text-xs">PDF</button>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <!-- Yearly summary -->
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-6 text-white shadow-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-widest">Yearly Cumulative (₦)</p>
            <p class="text-3xl font-extrabold tracking-tight mt-1">{{ Number(grandTotal).toLocaleString() }}</p>
          </div>
          <div class="bg-white/10 rounded-xl px-3 py-2 text-xs">
            <span class="opacity-80 mr-1">Year</span>
            <select v-model.number="selectedYear" @change="fetchPassbook" class="bg-transparent outline-none font-bold">
              <option v-for="y in years" :key="y" :value="y" class="text-slate-900">{{ y }}</option>
            </select>
          </div>
        </div>
        <p v-if="dividendAmount !== null" class="mt-2 text-[11px] text-emerald-100">
          Est. Dividend ({{ selectedYear }}):
          <span class="font-black text-white">₦ {{ Number(dividendAmount).toLocaleString() }}</span>
        </p>
      </div>

      <!-- Grid -->
      <div class="card card-elevated overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-800 text-white text-[10px] uppercase">
                <th class="p-3 sticky left-0 bg-slate-800 z-10 border-r border-slate-700">Scheme</th>
                <th class="p-3 text-center min-w-[64px] border-r border-slate-700 bg-slate-900/40">BF</th>
                <th v-for="(m, i) in months" :key="i" class="p-3 text-center min-w-[64px] border-r border-slate-700">{{ m }}</th>
                <th class="p-3 text-center bg-emerald-700">Total</th>
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
      <p class="text-[10px] text-gray-400 mt-4 px-2 italic text-center">Swipe left/right to view all months</p>

      <div v-if="showAgm" class="card p-4 border-emerald-200 bg-emerald-50">
        <div class="flex items-center justify-between mb-2">
          <p class="text-[10px] text-emerald-700 font-black uppercase tracking-widest">{{ selectedYear }} AGM Fee</p>
          <span :class="agmPaid ? 'bg-emerald-200 text-emerald-800' : 'bg-yellow-200 text-yellow-800'"
                class="px-2 py-1 rounded-full text-[10px] font-black uppercase">
            {{ agmPaid ? 'Paid' : 'Pending' }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <p class="text-slate-700 text-sm">Mandatory annual meeting fee</p>
          <p class="text-slate-900 font-black">₦ {{ Number(agmAmount).toLocaleString() }}</p>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active" @click="$router.push('/passbook')">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/pay')">
        <span class="text-xl">💳</span>
        <span class="text-[10px] font-bold">Pay</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
const years = [new Date().getFullYear() - 1, new Date().getFullYear(), new Date().getFullYear() + 1]
const selectedYear = ref(new Date().getFullYear())
const matrix = ref([])
const grandTotal = ref(0)
const agmAmount = ref(0)
const agmPaid = ref(false)
const dividendAmount = ref(null)

// Only show the AGM card if backend provided data (amount > 0) or payment status is true
const showAgm = computed(() => Number(agmAmount.value) > 0 || Boolean(agmPaid.value))

const fetchPassbook = async () => {
  const token = localStorage.getItem('token')
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
    // Provide safe defaults when API fails
    agmAmount.value = 0
    agmPaid.value = false
    dividendAmount.value = null
  }
}

const downloadPdf = async () => {
  const token = localStorage.getItem('token')
  try {
    const res = await axios.get('/api/download-passbook', {
      headers: { Authorization: `Bearer ${token}` },
      responseType: 'blob'
    })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Coop_Statement_${new Date().getFullYear()}.pdf`
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download PDF', e)
    alert(e?.response?.data?.message || e.message || 'Failed to download')
  }
}

onMounted(fetchPassbook)
</script>

<style scoped>
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}
</style>
