<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">Coop Store</h1>
      <button class="text-sm font-bold text-emerald-700" @click="$router.back()">Back</button>
    </header>

    <div class="p-4 space-y-4">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Available Products</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Read-only</span>
        </div>
        <div class="flex items-center gap-2 mb-4">
          <input v-model="q" @keyup.enter="load(1)" type="search" placeholder="Search products…" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" />
          <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold" @click="load(1)">Search</button>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm">No products found.</div>
          <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <li v-for="p in items" :key="p.id" class="p-3 bg-white border rounded-xl shadow-sm flex gap-3">
              <img v-if="p.image_url" :src="p.image_url" alt="image" class="w-16 h-16 rounded object-cover" />
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3">
                  <div class="font-bold text-slate-800 truncate">{{ p.name }}</div>
                  <div class="text-emerald-700 font-black text-sm whitespace-nowrap">₦ {{ money(p.selling_price) }}</div>
                </div>
                <p class="text-[12px] text-slate-600 line-clamp-2">{{ p.description || '—' }}</p>
              </div>
            </li>
          </ul>

          <div class="flex items-center justify-between mt-4 text-sm">
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page <= 1 || loading" @click="load(page - 1)">Prev</button>
            <div class="text-slate-500">Page {{ page }} / {{ lastPage }}</div>
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page >= lastPage || loading" @click="load(page + 1)">Next</button>
          </div>
        </div>
      </section>

      <section class="text-[12px] text-slate-500 px-1">
        Note: Store is informational for now. Purchasing will be enabled later.
      </section>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1">
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
const q = ref('')

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const load = async (p = 1) => {
  loading.value = true
  error.value = ''
  try {
    page.value = p
    const { data } = await axios.get('/api/products', {
      params: { page: p, q: q.value || '' }
    })
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
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
