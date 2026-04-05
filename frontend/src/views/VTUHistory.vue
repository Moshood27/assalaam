<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">⬅️</button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">VTU History</h1>
        <router-link to="/vtu" class="btn-ghost text-xs">Buy</router-link>
      </div>
    </header>

    <div class="p-4 space-y-4 max-w-md mx-auto">
      <div class="card card-elevated p-4 grid grid-cols-3 gap-3 items-end">
        <div>
          <label class="lbl">Type</label>
          <select v-model="filters.type" class="inp">
            <option value="">All</option>
            <option value="airtime">Airtime</option>
            <option value="data">Data</option>
            <option value="electricity">Electricity</option>
            <option value="cable">Cable TV</option>
          </select>
        </div>
        <div>
          <label class="lbl">Status</label>
          <select v-model="filters.status" class="inp">
            <option value="">All</option>
            <option value="success">Success</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
        </div>
        <div>
          <button @click="reload" class="btn-primary w-full">Apply</button>
        </div>
      </div>

      <div class="space-y-3">
        <div v-if="!items.length" class="card p-6 text-center text-slate-500">No transactions yet.</div>
        <div v-for="tx in items" :key="tx.id" class="card p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div :class="badgeClass(tx.status)" class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold">
              {{ statusIcon(tx.status) }}
            </div>
            <div>
              <p class="text-sm font-bold text-slate-800 capitalize">{{ tx.type }} — {{ tx.network }}</p>
              <p class="text-[10px] text-slate-500">{{ tx.phone_number }}</p>
              <p class="text-[10px] text-slate-400">Ref: {{ tx.reference }}</p>
              <p v-if="tx.type === 'electricity' && getToken(tx)" class="mt-1 p-2 bg-emerald-50 text-emerald-800 rounded-lg text-xs font-mono font-bold border border-emerald-100">
                TOKEN: {{ getToken(tx) }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold">₦ {{ formatMoney(tx.amount) }}</p>
            <p class="text-[10px] text-slate-400">{{ new Date(tx.created_at).toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-between items-center">
        <button @click="prev" :disabled="page<=1" class="btn-secondary">Prev</button>
        <p class="text-sm text-slate-500">Page {{ page }}</p>
        <button @click="next" :disabled="!hasMore" class="btn-secondary">Next</button>
      </div>
    </div>

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active" @click="$router.push('/vtu/history')">
        <span class="text-xl">🧾</span>
        <span class="text-[10px] font-bold">VTU History</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/vtu')">
        <span class="text-xl">📶</span>
        <span class="text-[10px] font-bold">Buy</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const items = ref([])
const page = ref(1)
const perPage = 10
const hasMore = ref(false)
const filters = ref({ type: '', status: '' })

const formatMoney = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const badgeClass = (status) => status === 'success' ? 'bg-emerald-100 text-emerald-700' : (status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-yellow-100 text-yellow-700')
const statusIcon = (status) => status === 'success' ? '✓' : (status === 'failed' ? '✕' : '⌛')

const getToken = (tx) => {
  if (tx.type !== 'electricity' || !tx.provider_response) return null
  const body = tx.provider_response
  return body.mainToken || body.token || body.purchased_code || (body.data && body.data.token) || null
}

const load = async () => {
  const params = new URLSearchParams({ page: String(page.value), per_page: String(perPage) })
  if (filters.value.type) params.append('type', filters.value.type)
  if (filters.value.status) params.append('status', filters.value.status)
  const { data } = await axios.get(`/api/vtu/transactions?${params.toString()}`)
  items.value = data?.data || []
  const nextUrl = data?.next_page_url
  hasMore.value = !!nextUrl
}

const reload = async () => { page.value = 1; await load() }
const next = async () => { if (!hasMore.value) return; page.value += 1; await load() }
const prev = async () => { if (page.value <= 1) return; page.value -= 1; await load() }

onMounted(load)
</script>
