<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <AppHeader title="Transparency Dashboard" :showBack="true" />

    <div class="p-4 max-w-3xl mx-auto space-y-6">
      <section class="card card-elevated p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Total Assets</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-50 px-2 py-0.5 rounded" v-if="data.as_of">As of {{ formatDateTime(data.as_of) }}</span>
        </div>
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-700"></div>
        </div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ error }}</div>
        <div v-else class="space-y-3">
          <div class="text-4xl font-black text-slate-900 tracking-tighter">₦ {{ money(data.total_assets) }}</div>
          <div class="flex gap-4">
            <div class="flex flex-col">
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Projects</span>
              <span class="font-bold text-blue-700 text-sm">₦ {{ money(data.projects_total) }}</span>
            </div>
            <div class="flex flex-col">
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Cash</span>
              <span class="font-bold text-slate-700 text-sm">₦ {{ money(data.cash_total) }}</span>
            </div>
          </div>
        </div>
      </section>

      <section class="card card-elevated p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Breakdown</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Portfolio</span>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-700"></div>
        </div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ error }}</div>
        <div v-else>
          <ul class="space-y-4">
            <li v-for="row in breakdownWithCash" :key="row.key" class="bg-slate-50 border border-slate-100 rounded-3xl p-5 hover:border-blue-200 transition-colors">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-xl shadow-sm">
                    <span v-if="row.type==='project'">🏗️</span>
                    <span v-else>💵</span>
                  </div>
                  <div>
                    <div class="font-black text-slate-800 text-sm tracking-tight">{{ row.name }}</div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ row.status }} • ₦ {{ money(row.amount) }}</div>
                  </div>
                </div>
                <div class="text-xs font-black text-blue-700 bg-white px-2 py-1 rounded-lg border border-slate-100">{{ row.percent.toFixed(2) }}%</div>
              </div>
              <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-blue-500 rounded-full" :style="{ width: Math.min(100, Math.max(0, row.percent)).toFixed(2) + '%' }"></div>
              </div>

              <!-- Attachments for project rows -->
              <div v-if="row.type==='project'" class="mt-4 flex flex-col gap-3">
                <div v-if="row.attachments?.report_url" class="text-xs">
                  <a class="inline-flex items-center gap-1 text-blue-700 font-bold hover:underline" :href="row.attachments.report_url" target="_blank" rel="noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    View PDF Report
                  </a>
                </div>
                <div v-if="(row.attachments?.media_urls || []).length" class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                  <a v-for="(u, i) in row.attachments.media_urls" :key="i" :href="u" target="_blank" rel="noopener" class="block flex-shrink-0 w-24 h-16 rounded-2xl overflow-hidden border border-slate-200 bg-white p-0.5">
                    <img :src="u" class="w-full h-full object-cover rounded-xl" loading="lazy" @error="onImgError" />
                  </a>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </section>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http.js'

const data = ref({ total_assets: 0, projects_total: 0, cash_total: 0, breakdown: [], cash: null })
const loading = ref(false)
const error = ref('')

const fetchData = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data: res } = await axios.get('/api/transparency')
    data.value = res || {}
  } catch (e) {
    console.error(e)
    error.value = 'Failed to load transparency data.'
  } finally {
    loading.value = false
  }
}

const breakdownWithCash = computed(() => {
  const list = Array.isArray(data.value.breakdown) ? [...data.value.breakdown] : []
  if (data.value.cash) {
    list.push({ ...data.value.cash, key: 'cash' })
  }
  return list.map((r, i) => ({ key: r.key || (r.type + ':' + (r.project_id || i)), ...r }))
})

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDateTime = (d) => { try { return new Date(d).toLocaleString() } catch { return d } }
const onImgError = (ev) => { ev.target.style.display = 'none' }

onMounted(fetchData)
</script>

<style scoped>
</style>


