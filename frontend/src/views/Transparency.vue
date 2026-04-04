<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">⬅️</button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Transparency Dashboard</h1>
        <div />
      </div>
    </header>

    <div class="p-4 max-w-3xl mx-auto space-y-6">
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-2">
          <h2 class="section-title">Total Assets</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400" v-if="data.as_of">As of {{ formatDateTime(data.as_of) }}</span>
        </div>
        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else class="space-y-2">
          <div class="text-3xl font-black text-slate-900">₦ {{ money(data.total_assets) }}</div>
          <div class="text-xs text-slate-500">Projects: ₦ {{ money(data.projects_total) }} • Cash: ₦ {{ money(data.cash_total) }}</div>
        </div>
      </section>

      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-2">
          <h2 class="section-title">Breakdown</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Portfolio</span>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <ul class="space-y-4">
            <li v-for="row in breakdownWithCash" :key="row.key" class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                  <span v-if="row.type==='project'" class="text-xl">🏗️</span>
                  <span v-else class="text-xl">💵</span>
                  <div>
                    <div class="font-bold text-slate-800">{{ row.name }}</div>
                    <div class="text-[11px] text-slate-500">{{ row.status }} • ₦ {{ money(row.amount) }}</div>
                  </div>
                </div>
                <div class="text-xs text-slate-500">{{ row.percent.toFixed(2) }}%</div>
              </div>
              <div class="h-2 bg-slate-200 rounded overflow-hidden">
                <div class="h-2 bg-emerald-500" :style="{ width: Math.min(100, Math.max(0, row.percent)).toFixed(2) + '%' }"></div>
              </div>

              <!-- Attachments for project rows -->
              <div v-if="row.type==='project'" class="mt-3 flex flex-col gap-2">
                <div v-if="row.attachments?.report_url" class="text-sm">
                  <a class="text-emerald-700 font-semibold underline" :href="row.attachments.report_url" target="_blank" rel="noopener">View PDF Report</a>
                </div>
                <div v-if="(row.attachments?.media_urls || []).length" class="flex items-center gap-2 overflow-x-auto">
                  <a v-for="(u, i) in row.attachments.media_urls" :key="i" :href="u" target="_blank" rel="noopener" class="block w-20 h-14 rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                    <img :src="u" class="w-full h-full object-cover" loading="lazy" @error="onImgError" />
                  </a>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </section>
    </div>

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active">
        <span class="text-xl">🧾</span>
        <span class="text-[10px] font-bold">Transparency</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/projects')">
        <span class="text-xl">📦</span>
        <span class="text-[10px] font-bold">Projects</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
.header-fintech { position: sticky; top: 0; z-index: 40; background: rgba(255,255,255,0.8); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(15, 23, 42, 0.08); }
.navbar-inner { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; }
.card { background: white; border-radius: 16px; border: 1px solid rgba(15, 23, 42, 0.06); }
.card-elevated { box-shadow: 0 10px 16px -12px rgba(15, 23, 42, 0.3); }
.section-title { font-weight: 800; color: #0f172a; }
.bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; border-top: 1px solid rgba(15, 23, 42, 0.08); display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; padding: 8px 12px; }
.bottom-nav-btn { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px; border-radius: 12px; color: #334155; }
.bottom-nav-btn-active { background: #ecfeff; color: #0369a1; }
.inp { border: 1px solid rgba(15,23,42,0.12); border-radius: 8px; padding: 6px 8px; }
.btn-ghost { color: #047857; }
</style>
