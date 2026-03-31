<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">My Orders</h1>
      <div class="flex items-center gap-2">
        <button class="text-sm font-bold text-emerald-700" @click="$router.push('/store')">Back to Store</button>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Recent Orders</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-emerald-700">History</span>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm">You have no orders yet.</div>
          <ul v-else class="divide-y divide-slate-200 bg-white border rounded-xl">
            <li v-for="o in items" :key="o.id" class="p-3 flex items-center justify-between gap-3 hover:bg-slate-50 cursor-pointer" @click="$router.push(`/store/orders/${o.id}`)">
              <div class="min-w-0">
                <div class="font-bold text-slate-800 truncate">{{ o.reference }}</div>
                <div class="text-[12px] text-slate-500">{{ new Date(o.created_at).toLocaleString() }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm font-extrabold text-slate-800">₦ {{ money(o.total_amount) }}</div>
                <div class="text-[10px] font-bold uppercase" :class="statusClass(o.status)">{{ o.status }}</div>
              </div>
            </li>
          </ul>

          <div class="flex items-center justify-between mt-4 text-sm" v-if="lastPage > 1">
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page <= 1 || loading" @click="load(page - 1)">Prev</button>
            <div class="text-slate-500">Page {{ page }} / {{ lastPage }}</div>
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page >= lastPage || loading" @click="load(page + 1)">Next</button>
          </div>
        </div>
      </section>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1" @click="$router.push('/store')">
        <span class="text-xl">🛒</span>
        <span class="text-[10px] font-bold">Store</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/reports')">
        <span class="text-xl">📈</span>
        <span class="text-[10px] font-bold">Reports</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'

const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const statusClass = (status) => {
  const s = String(status || '').toLowerCase()
  if (s === 'paid' || s === 'completed' || s === 'success') return 'text-emerald-700'
  if (s === 'pending' || s === 'processing' || s.includes('murabaha')) return 'text-amber-600'
  if (s === 'failed' || s === 'cancelled') return 'text-rose-700'
  return 'text-slate-500'
}

const load = async (p = 1) => {
  loading.value = true
  error.value = ''
  try {
    page.value = p
    const { data } = await axios.get('/api/store/orders', { params: { page: p } })
    const list = Array.isArray(data) ? data : (data?.data || [])
    items.value = list
    lastPage.value = Number(data?.last_page || 1)
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

onMounted(() => load(1))
</script>

<style scoped>
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; }
.section-title { font-weight: 800; color: #0f172a; }
</style>
