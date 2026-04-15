<template>
  <div class="min-h-screen bg-slate-50/50 pb-24">
    <AppHeader title="My Orders" :showBack="true">
      <template #right>
        <button class="p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="$router.push('/store')" title="Store">
          <span class="i-mdi-store-outline text-2xl text-emerald-700"></span>
        </button>
      </template>
    </AppHeader>

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
              <div class="text-right flex flex-col items-end">
                <div class="text-sm font-extrabold text-slate-800">₦ {{ money(o.total_amount) }}</div>
                <div class="flex items-center gap-2">
                  <span v-if="o.dispute" class="text-[9px] font-black bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded border border-rose-100 uppercase">Tahkim</span>
                  <button v-else-if="!['failed', 'cancelled'].includes(o.status?.toLowerCase())" @click.stop="$router.push(`/store/orders/${o.id}?dispute=1`)" class="text-[9px] font-bold bg-slate-50 text-slate-500 px-1.5 py-0.5 rounded border border-slate-100 uppercase hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-colors">Dispute</button>
                  <div class="text-[10px] font-bold uppercase" :class="statusClass(o.status)">{{ o.status }}</div>
                </div>
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

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'

const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const statusClass = (status) => {
  const s = String(status || '').toLowerCase()
  if (s === 'paid' || s === 'completed' || s === 'success' || s === 'delivered') return 'text-emerald-700'
  if (s === 'pending' || s === 'processing' || s === 'shipped' || s.includes('murabaha')) return 'text-amber-600'
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
