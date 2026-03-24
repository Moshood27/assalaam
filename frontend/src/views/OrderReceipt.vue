<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">Order Receipt</h1>
      <button class="text-sm font-bold text-emerald-700" @click="$router.push('/store')">Back to Store</button>
    </header>

    <div class="p-4">
      <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
      <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
      <section v-else class="card p-5 space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-xs text-slate-500">Reference</div>
            <div class="font-bold text-slate-800">{{ order.reference }}</div>
          </div>
          <div class="text-right">
            <div class="text-xs text-slate-500">Total Paid</div>
            <div class="text-lg font-extrabold text-emerald-700">₦ {{ money(order.total_amount) }}</div>
          </div>
        </div>

        <ul class="divide-y divide-slate-200 bg-white border rounded-xl">
          <li v-for="it in order.items || []" :key="it.id" class="p-3 flex items-center justify-between gap-3">
            <div>
              <div class="font-bold text-slate-800">{{ it.product_name }}</div>
              <div class="text-xs text-slate-500">₦ {{ money(it.unit_price) }} x {{ it.quantity }}</div>
            </div>
            <div class="text-sm font-bold">₦ {{ money(it.line_total) }}</div>
          </li>
        </ul>

        <div class="pt-2 text-xs text-slate-500">Status: <span class="font-bold text-slate-800 uppercase">{{ order.status }}</span></div>
        <div class="text-xs text-slate-500">Date: {{ new Date(order.created_at).toLocaleString() }}</div>

        <div class="flex items-center justify-end">
          <button class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm" @click="print()">Print</button>
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
import { useRoute } from 'vue-router'

const route = useRoute()
const id = Number(route.params.id)
const order = ref({})
const loading = ref(true)
const error = ref('')

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get(`/api/store/orders/${id}`)
    order.value = data || {}
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const print = () => {
  try { window.print() } catch (_) {}
}

onMounted(load)
</script>

<style scoped>
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; }
</style>
