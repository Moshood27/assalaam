<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 p-4 sm:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="text-xs font-semibold tracking-widest text-blue-700 uppercase">Admin Portal</p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Takaful (Member Welfare Pool)</h1>
          <p class="text-slate-600">Monitor pool ledger, filter by member, and trigger manual batch charges.</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <router-link to="/admin/vtu" class="btn-muted">VTU Monitor</router-link>
          <router-link to="/admin/products" class="btn-muted">Products</router-link>
        </div>
      </div>

      <!-- Summary and Exports -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="card p-4 lg:col-span-2">
          <div class="flex flex-wrap gap-3 items-end">
            <div>
              <label class="lbl">Period</label>
              <input v-model="summaryFilters.period" type="month" class="inp"/>
            </div>
            <button @click="loadSummary" class="btn-primary">Load Summary</button>
            <span class="text-xs text-slate-500" v-if="summary.period">Showing: {{ summary.period }}</span>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
            <div class="card p-3 border-blue-200 bg-blue-50">
              <p class="text-xs uppercase text-blue-700 font-bold">Pool Balance</p>
              <p class="text-2xl font-extrabold text-blue-900">Ã¢â€šÂ¦ {{ money(summary.pool_balance) }}</p>
            </div>
            <div class="card p-3 border-sky-200 bg-sky-50">
              <p class="text-xs uppercase text-sky-700 font-bold">Contrib Count</p>
              <p class="text-2xl font-extrabold text-sky-900">{{ summary.contributions?.count || 0 }}</p>
            </div>
            <div class="card p-3 border-amber-200 bg-amber-50">
              <p class="text-xs uppercase text-amber-700 font-bold">Contrib Total</p>
              <p class="text-2xl font-extrabold text-amber-900">Ã¢â€šÂ¦ {{ money(summary.contributions?.sum || 0) }}</p>
            </div>
            <div class="card p-3 border-indigo-200 bg-indigo-50">
              <p class="text-xs uppercase text-indigo-700 font-bold">Status</p>
              <p class="text-sm text-indigo-900">Ã¢Å“â€¦ {{ summary.contributions?.by_status?.success || 0 }} Ã¢â‚¬Â¢ Ã¢Å’â€º {{ summary.contributions?.by_status?.pending || 0 }} Ã¢â‚¬Â¢ Ã¢Å“â€¢ {{ summary.contributions?.by_status?.failed || 0 }}</p>
            </div>
          </div>
          <div class="mt-4 flex flex-wrap gap-2">
            <a class="btn-muted" :href="getExportSummaryUrl('csv')" target="_blank">Export Summary CSV</a>
            <a class="btn-muted" :href="getExportSummaryUrl('pdf')" target="_blank">Export Summary PDF</a>
          </div>
        </div>

        <!-- Manual Batch Charge -->
        <div class="card p-4">
          <h3 class="text-sm font-bold text-slate-800 mb-3">Manual Batch Charge</h3>
          <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
              <label class="lbl">Period</label>
              <input v-model="chargeForm.period" type="month" class="inp"/>
            </div>
            <div>
              <label class="lbl">Amount (optional)</label>
              <input v-model.number="chargeForm.amount" type="number" min="1" step="0.01" class="inp" placeholder="Default config"/>
            </div>
            <div>
              <label class="lbl">User ID (optional)</label>
              <input v-model.number="chargeForm.user_id" type="number" min="1" class="inp" placeholder="All members"/>
            </div>
            <div class="col-span-2 flex items-center gap-2">
              <input id="dryrun" v-model="chargeForm.dry_run" type="checkbox" class="w-4 h-4"/>
              <label for="dryrun" class="text-sm text-slate-700">Dry run (no wallet debit)</label>
            </div>
          </div>
          <div class="mt-3 flex gap-2">
            <button class="btn-primary" @click="triggerCharge" :disabled="loading.charge">{{ loading.charge ? 'ProcessingÃ¢â‚¬Â¦' : 'Run' }}</button>
            <button class="btn-muted" @click="resetCharge">Reset</button>
          </div>
          <div v-if="chargeResult" class="mt-3 text-xs bg-slate-50 border border-slate-200 rounded-xl p-3 whitespace-pre-wrap font-mono">
            {{ JSON.stringify(chargeResult, null, 2) }}
          </div>
        </div>
      </div>

      <!-- Ledger Filters -->
      <div class="card card-elevated p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
          <div>
            <label class="lbl">Direction</label>
            <select v-model="filters.direction" class="inp">
              <option value="">All</option>
              <option value="credit">Credit</option>
              <option value="debit">Debit</option>
            </select>
          </div>
          <div>
            <label class="lbl">Member ID</label>
            <input v-model.number="filters.user_id" type="number" class="inp" placeholder="Optional"/>
          </div>
          <div>
            <label class="lbl">From</label>
            <input v-model="filters.date_from" type="date" class="inp"/>
          </div>
          <div>
            <label class="lbl">To</label>
            <input v-model="filters.date_to" type="date" class="inp"/>
          </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <button @click="reloadLedger" class="btn-primary">Apply</button>
          <button @click="resetFilters" class="btn-muted">Reset</button>
          <a class="btn-muted" :href="getExportLedgerUrl('csv')" target="_blank">Export Ledger CSV</a>
          <a class="btn-muted" :href="getExportLedgerUrl('pdf')" target="_blank">Export Ledger PDF</a>
        </div>
      </div>

      <!-- Ledger Table -->
      <div class="card card-elevated overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-100 text-slate-700">
            <tr>
              <th class="th">Date</th>
              <th class="th">Direction</th>
              <th class="th text-right">Amount</th>
              <th class="th">Reference</th>
              <th class="th">User</th>
              <th class="th">Period</th>
              <th class="th">Qard Code</th>
              <th class="th">Reason</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in ledgerRows" :key="row.id" class="border-b last:border-b-0">
              <td class="td">{{ fmtDate(row.created_at) }}</td>
              <td class="td capitalize">{{ row.direction }}</td>
              <td class="td text-right">Ã¢â€šÂ¦ {{ money(row.amount) }}</td>
              <td class="td font-mono text-[11px] text-slate-600">{{ row.reference }}</td>
              <td class="td">{{ row.meta?.user_id || '' }}</td>
              <td class="td">{{ row.meta?.period || '' }}</td>
              <td class="td">{{ row.meta?.qard_code || '' }}</td>
              <td class="td">{{ row.meta?.reason || '' }}</td>
            </tr>
            <tr v-if="!ledgerRows.length">
              <td colspan="8" class="td text-center text-slate-400 py-10">No records</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination and Summary -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mt-4">
        <div class="flex items-center gap-2">
          <button @click="prev" :disabled="!prevUrl" class="btn-muted disabled:opacity-50">Prev</button>
          <button @click="next" :disabled="!nextUrl" class="btn-muted disabled:opacity-50">Next</button>
          <span class="text-sm text-slate-600">Page {{ page }}</span>
        </div>
        <div class="text-sm text-slate-600">
          <span>Credits: Ã¢â€šÂ¦ {{ money(ledgerSummary.credits) }}</span>
          <span class="mx-2">Ã¢â‚¬Â¢</span>
          <span>Debits: Ã¢â€šÂ¦ {{ money(ledgerSummary.debits) }}</span>
          <span class="mx-2">Ã¢â‚¬Â¢</span>
          <span>Net: Ã¢â€šÂ¦ {{ money(ledgerSummary.net) }}</span>
          <span class="mx-2">Ã¢â‚¬Â¢</span>
          <span>Pool Balance: Ã¢â€šÂ¦ {{ money(ledgerSummary.pool_balance) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axiosBase from '../../http.js'

const adminToken = ref(localStorage.getItem('admin_token') || '')
const client = axiosBase.create ? axiosBase.create() : axiosBase

// Summary state
const summary = reactive({ period: '', pool_balance: 0, contributions: { count: 0, sum: 0, by_status: {} } })
const summaryFilters = reactive({ period: new Date().toISOString().slice(0,7) })

// Ledger state
const ledgerRows = ref([])
const ledgerSummary = reactive({ credits: 0, debits: 0, net: 0, pool_balance: 0 })
const page = ref(1)
const perPage = 20
const nextUrl = ref(null)
const prevUrl = ref(null)
const filters = reactive({ direction: '', user_id: '', date_from: '', date_to: '' })

// Charge form
const chargeForm = reactive({ period: new Date().toISOString().slice(0,7), amount: '', user_id: '', dry_run: true })
const chargeResult = ref(null)
const loading = reactive({ charge: false })

const authHeaders = () => ({ Authorization: `Bearer ${adminToken.value}` })
const money = (v) => Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const fmtDate = (d) => { try { return new Date(d).toLocaleString() } catch { return d } }

const loadSummary = async () => {
  if (!adminToken.value) { alert('Please login as admin'); return }
  const { data } = await client.get(`/api/admin/takaful/summary`, { headers: authHeaders(), params: { period: summaryFilters.period } })
  Object.assign(summary, data || {})
}

const buildLedgerParams = () => {
  const p = new URLSearchParams({ page: String(page.value), per_page: String(perPage) })
  if (filters.direction) p.append('direction', filters.direction)
  if (filters.user_id) p.append('user_id', String(filters.user_id))
  if (filters.date_from) p.append('date_from', filters.date_from)
  if (filters.date_to) p.append('date_to', filters.date_to)
  return p
}

const loadLedger = async (url = null) => {
  if (!adminToken.value) { alert('Please login as admin'); return }
  const finalUrl = url || `/api/admin/takaful/ledger?${buildLedgerParams().toString()}`
  const { data } = await client.get(finalUrl, { headers: authHeaders() })
  ledgerRows.value = data?.data || []
  Object.assign(ledgerSummary, data?.summary || {})
  nextUrl.value = data?.next_page_url || null
  prevUrl.value = data?.prev_page_url || null
}

const reloadLedger = async () => { page.value = 1; await loadLedger() }
const resetFilters = async () => { Object.assign(filters, { direction: '', user_id: '', date_from: '', date_to: '' }); await reloadLedger() }
const next = async () => { if (!nextUrl.value) return; page.value += 1; await loadLedger(nextUrl.value) }
const prev = async () => { if (!prevUrl.value) return; page.value = Math.max(1, page.value - 1); await loadLedger(prevUrl.value) }

const triggerCharge = async () => {
  if (!adminToken.value) { alert('Please login as admin'); return }
  if (loading.charge) return
  loading.charge = true
  chargeResult.value = null
  try {
    const payload = {
      period: chargeForm.period || undefined,
      amount: chargeForm.amount || undefined,
      user_id: chargeForm.user_id || undefined,
      dry_run: !!chargeForm.dry_run,
    }
    const { data } = await client.post('/api/admin/takaful/charge', payload, { headers: authHeaders() })
    chargeResult.value = data
    alert(`Charge ${data.dry_run ? 'dry-run ' : ''}completed. Processed: ${data.processed}, Created: ${data.created}, Charged: Ã¢â€šÂ¦ ${money(data.charged)}`)
    await loadSummary(); await reloadLedger()
  } catch (e) {
    const msg = e?.response?.data?.message || 'Failed to run charge'
    alert(msg)
  } finally {
    loading.charge = false
  }
}
const resetCharge = () => { Object.assign(chargeForm, { period: new Date().toISOString().slice(0,7), amount: '', user_id: '', dry_run: true }); chargeResult.value = null }

const getExportSummaryUrl = (type) => {
  const path = type === 'pdf' ? '/api/admin/takaful/export/summary.pdf' : '/api/admin/takaful/export/summary.csv'
  const baseUrl = axiosBase.defaults.baseURL || ''
  const params = new URLSearchParams({ period: summaryFilters.period, token: adminToken.value })
  return `${baseUrl}${path}?${params.toString()}`
}

const getExportLedgerUrl = (type) => {
  const path = type === 'pdf' ? '/api/admin/takaful/export/ledger.pdf' : '/api/admin/takaful/export/ledger.csv'
  const baseUrl = axiosBase.defaults.baseURL || ''
  const params = buildLedgerParams()
  params.append('token', adminToken.value)
  return `${baseUrl}${path}?${params.toString()}`
}

onMounted(async () => { await loadSummary(); await loadLedger() })
</script>

<style scoped>
@reference '../../style.css';
.lbl { @apply block text-[10px] font-bold text-gray-400 uppercase mb-1; }
.inp { @apply w-full bg-white p-2.5 rounded-xl border border-slate-200 text-sm outline-none; }
.th { @apply text-left px-4 py-2 font-semibold text-xs uppercase tracking-wide; }
.td { @apply px-4 py-2 align-top; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow; }
.btn-muted { @apply bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-lg shadow-sm; }
.card { @apply bg-white rounded-2xl border border-slate-200; }
.card-elevated { @apply shadow-sm; }
</style>


