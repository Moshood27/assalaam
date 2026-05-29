<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 sm:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="text-xs font-semibold tracking-widest text-indigo-700 uppercase">Admin Portal</p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Airtime/Data (VTU) Monitor</h1>
          <p class="text-slate-600">Track member purchases, status, and profit in real-time.</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <router-link to="/admin/imports" class="btn-muted">Bulk Import</router-link>
          <router-link to="/admin/login" class="btn-muted">Admin Login</router-link>
        </div>
      </div>

      <!-- Filters -->
      <div class="card card-elevated p-4 mb-5">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
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
            <label class="lbl">Network</label>
            <select v-model="filters.network" class="inp">
              <option value="">All</option>
              <option value="mtn">MTN</option>
              <option value="airtel">Airtel</option>
              <option value="glo">Glo</option>
              <option value="9mobile">9mobile</option>
            </select>
          </div>
          <div>
            <label class="lbl">Search</label>
            <input v-model.trim="filters.q" placeholder="Phone or Ref" class="inp" />
          </div>
          <div>
            <label class="lbl">From</label>
            <input v-model="filters.date_from" type="date" class="inp" />
          </div>
          <div>
            <label class="lbl">To</label>
            <input v-model="filters.date_to" type="date" class="inp" />
          </div>
        </div>
        <div class="mt-3 flex gap-3">
          <button @click="reload" class="btn-primary">Apply</button>
          <button @click="resetFilters" class="btn-muted">Reset</button>
        </div>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
        <div class="card p-4 border-blue-200 bg-blue-50">
          <p class="text-xs uppercase text-blue-700 font-bold">Total Transactions</p>
          <p class="text-2xl font-extrabold text-blue-900">{{ summary.count || 0 }}</p>
        </div>
        <div class="card p-4 border-sky-200 bg-sky-50">
          <p class="text-xs uppercase text-sky-700 font-bold">Gross Amount</p>
          <p class="text-2xl font-extrabold text-sky-900">Ã¢â€šÂ¦ {{ money(summary.amount) }}</p>
        </div>
        <div class="card p-4 border-amber-200 bg-amber-50">
          <p class="text-xs uppercase text-amber-700 font-bold">Cost Price</p>
          <p class="text-2xl font-extrabold text-amber-900">Ã¢â€šÂ¦ {{ money(summary.cost_price) }}</p>
        </div>
        <div class="card p-4 border-indigo-200 bg-indigo-50">
          <p class="text-xs uppercase text-indigo-700 font-bold">Profit</p>
          <p class="text-2xl font-extrabold text-indigo-900">Ã¢â€šÂ¦ {{ money(summary.profit) }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="card card-elevated overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-100 text-slate-700">
            <tr>
              <th class="th">Date</th>
              <th class="th">Type</th>
              <th class="th">Network</th>
              <th class="th">Phone</th>
              <th class="th text-right">Amount</th>
              <th class="th text-right">Cost</th>
              <th class="th text-right">Profit</th>
              <th class="th">Status</th>
              <th class="th">Member</th>
              <th class="th">Ref</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in items" :key="tx.id" class="border-b last:border-b-0">
              <td class="td">{{ fmtDate(tx.created_at) }}</td>
              <td class="td capitalize">{{ tx.type }}</td>
              <td class="td uppercase">{{ tx.network }}</td>
              <td class="td">{{ tx.phone_number }}</td>
              <td class="td text-right">Ã¢â€šÂ¦ {{ money(tx.amount) }}</td>
              <td class="td text-right">Ã¢â€šÂ¦ {{ money(tx.cost_price) }}</td>
              <td class="td text-right font-bold" :class="tx.profit >= 0 ? 'text-blue-700' : 'text-rose-700'">Ã¢â€šÂ¦ {{ money(tx.profit) }}</td>
              <td class="td">
                <span :class="badgeClass(tx.status)" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">{{ tx.status }}</span>
              </td>
              <td class="td">
                <div class="leading-tight">
                  <p class="font-semibold text-slate-800">{{ tx.user?.name || 'Ã¢â‚¬â€' }}</p>
                  <p class="text-[10px] text-slate-500">{{ tx.user?.email }}</p>
                  <p class="text-[10px] text-slate-400">ID: {{ tx.user?.membership_number || 'Ã¢â‚¬â€' }}</p>
                </div>
              </td>
              <td class="td font-mono text-[11px] text-slate-600">{{ tx.reference }}</td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="10" class="td text-center text-slate-400 py-10">No records</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-between items-center mt-4">
        <button @click="prev" :disabled="!prevUrl" class="btn-muted disabled:opacity-50">Prev</button>
        <p class="text-sm text-slate-600">Page {{ page }}</p>
        <button @click="next" :disabled="!nextUrl" class="btn-muted disabled:opacity-50">Next</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axiosBase from '../../http.js'

const adminToken = ref(localStorage.getItem('admin_token') || '')
const items = ref([])
const page = ref(1)
const perPage = 20
const nextUrl = ref(null)
const prevUrl = ref(null)
const summary = reactive({ count: 0, amount: 0, cost_price: 0, profit: 0, by_status: {} })

const filters = reactive({ type: '', status: '', network: '', q: '', date_from: '', date_to: '' })

const client = axiosBase.create ? axiosBase.create() : axiosBase

const money = (v) => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const fmtDate = (d) => new Date(d).toLocaleString()
const badgeClass = (status) => status === 'success' ? 'bg-blue-100 text-blue-700' : (status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')

const buildParams = () => {
  const p = new URLSearchParams({ page: String(page.value), per_page: String(perPage), summary: '1' })
  if (filters.type) p.append('type', filters.type)
  if (filters.status) p.append('status', filters.status)
  if (filters.network) p.append('network', filters.network)
  if (filters.q) p.append('q', filters.q)
  if (filters.date_from) p.append('date_from', filters.date_from)
  if (filters.date_to) p.append('date_to', filters.date_to)
  return p
}

const load = async (url = null) => {
  if (!adminToken.value) {
    alert('Please login as admin')
    return
  }
  const headers = { Authorization: `Bearer ${adminToken.value}` }
  const finalUrl = url || `/api/admin/vtu/transactions?${buildParams().toString()}`
  const { data } = await client.get(finalUrl, { headers })
  items.value = data?.data || []
  summary.count = data?.summary?.count || 0
  summary.amount = data?.summary?.amount || 0
  summary.cost_price = data?.summary?.cost_price || 0
  summary.profit = data?.summary?.profit || 0
  summary.by_status = data?.summary?.by_status || {}
  nextUrl.value = data?.next_page_url || null
  prevUrl.value = data?.prev_page_url || null
}

const reload = async () => { page.value = 1; await load() }
const next = async () => { if (!nextUrl.value) return; page.value += 1; await load(nextUrl.value) }
const prev = async () => { if (!prevUrl.value) return; page.value = Math.max(1, page.value - 1); await load(prevUrl.value) }
const resetFilters = async () => { Object.assign(filters, { type: '', status: '', network: '', q: '', date_from: '', date_to: '' }); await reload() }

onMounted(load)
</script>

<style scoped>
@reference '../../style.css';
.lbl { @apply block text-[10px] font-bold text-gray-400 uppercase mb-1; }
.inp { @apply w-full bg-white p-2.5 rounded-xl border border-slate-200 text-sm outline-none; }
.th { @apply text-left px-4 py-2 font-semibold text-xs uppercase tracking-wide; }
.td { @apply px-4 py-2 align-top; }
.btn-primary { @apply bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow; }
.btn-muted { @apply bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-lg shadow-sm; }
.card { @apply bg-white rounded-2xl border border-slate-200; }
.card-elevated { @apply shadow-sm; }
</style>


