<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="p-2 -ml-2 hover:bg-slate-100 rounded-full transition-colors" aria-label="Go back">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800 truncate">{{ project?.name || 'Project' }}</h1>
        <div class="w-10" />
      </div>
    </header>

    <div class="p-4 space-y-4">
      <div v-if="loading" class="text-center text-slate-500 py-10">Loading...</div>

      <div v-else>
        <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-6 text-white shadow-xl">
          <p class="text-emerald-100 text-[10px] font-black uppercase tracking-widest">Management Fee</p>
          <p class="text-3xl font-extrabold mt-1">{{ Number(project?.management_fee_percent || 0).toLocaleString() }}%</p>
          <p v-if="project?.target_amount" class="mt-2 text-emerald-100 text-[11px]">Target: ₦ {{ Number(project.target_amount).toLocaleString() }}</p>
          <p class="mt-2 text-emerald-50 text-[11px]">
            <span v-if="project?.started_at">Started: {{ formatDate(project.started_at) }}</span>
            <span v-if="project?.closed_at" class="ml-2">Closed: {{ formatDate(project.closed_at) }}</span>
          </p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="card card-elevated p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">My Total Invested</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">₦ {{ Number(totalInvested).toLocaleString() }}</p>
          </div>
          <div class="card card-elevated p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Profit Events</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ profits.length }}</p>
          </div>
        </div>

        <div class="card card-elevated">
          <div class="p-4 border-b flex items-center justify-between">
            <h3 class="font-bold text-slate-800">My Investments</h3>
            <span class="text-[11px] text-slate-500">Total: ₦ {{ Number(totalInvested).toLocaleString() }}</span>
          </div>
          <div v-if="investments.length === 0" class="p-6 text-center text-slate-500 text-sm">No investments yet.</div>
          <div v-else class="divide-y">
            <div v-for="inv in investments" :key="inv.id" class="p-4 flex items-center justify-between">
              <div>
                <p class="font-semibold text-slate-700">₦ {{ Number(inv.amount).toLocaleString() }}</p>
                <p class="text-[11px] text-slate-500">{{ formatDateTime(inv.created_at) }}</p>
              </div>
              <span class="text-[11px] text-slate-400 font-mono">{{ inv.reference }}</span>
            </div>
          </div>
        </div>

        <div class="card card-elevated">
          <div class="p-4 border-b">
            <h3 class="font-bold text-slate-800">Profit Distributions</h3>
          </div>
          <div v-if="profits.length === 0" class="p-6 text-center text-slate-500 text-sm">No profit records yet.</div>
          <div v-else class="divide-y">
            <div v-for="p in profits" :key="p.id" class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-semibold text-slate-800">Net: ₦ {{ Number(p.net_distributable).toLocaleString() }}</p>
                  <p class="text-[11px] text-slate-500">Gross ₦ {{ Number(p.gross_profit).toLocaleString() }} • Mgmt {{ Number(p.management_fee_percent).toLocaleString() }}% (₦ {{ Number(p.management_fee_amount).toLocaleString() }})</p>
                </div>
                <p class="text-[11px] text-slate-500">{{ formatDateTime(p.created_at) }}</p>
              </div>
              <div class="mt-2 text-[12px]">
                <div class="flex items-center justify-between">
                  <span class="text-slate-600">My expected share</span>
                  <span class="font-bold text-slate-800">₦ {{ Number(p.my_expected_share || 0).toLocaleString() }}</span>
                </div>
                <div class="flex items-center justify-between mt-1">
                  <span class="text-slate-600">My payout</span>
                  <span v-if="p.my_payout" class="font-bold text-emerald-700">₦ {{ Number(p.my_payout.amount).toLocaleString() }}</span>
                  <span v-else class="text-slate-400">—</span>
                </div>
                <p v-if="p.note" class="mt-2 text-[11px] text-slate-500">Note: {{ p.note }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/projects')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2h8l4 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8l4-6Z"/><path d="M12 2v6"/></svg>
        <span class="text-[10px] font-bold">Projects</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active" @click="$router.push($route.fullPath)">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span class="text-[10px] font-bold">Detail</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/pay')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8h10M12 12h10M12 16h10"/><path d="M5.88 5.88 9 9m6 6 3.12 3.12M5.88 18.12 9 15m6-6 3.12-3.12"/></svg>
        <span class="text-[10px] font-bold">Pay</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '../http.js'

const route = useRoute()
const id = ref(Number(route.params.id))

const loading = ref(true)
const project = ref(null)
const investments = ref([])
const profits = ref([])
const totalInvested = ref(0)

const fetchAll = async () => {
  loading.value = true
  try {
    const [p, inv, prof] = await Promise.all([
      axios.get(`/api/projects/${id.value}`),
      axios.get(`/api/projects/${id.value}/investments`),
      axios.get(`/api/projects/${id.value}/profits`),
    ])
    project.value = p.data
    investments.value = inv.data?.investments || []
    totalInvested.value = inv.data?.total_invested || 0
    profits.value = prof.data?.profits || []
  } catch (e) {
    console.error('Failed to load project', e)
  } finally {
    loading.value = false
  }
}

const formatDate = (d) => {
  if (!d) return ''
  try { return new Date(d).toLocaleDateString() } catch (_) { return d }
}
const formatDateTime = (d) => {
  if (!d) return ''
  try { return new Date(d).toLocaleString() } catch (_) { return d }
}

onMounted(fetchAll)
watch(() => route.params.id, (v) => { id.value = Number(v); fetchAll() })
</script>
