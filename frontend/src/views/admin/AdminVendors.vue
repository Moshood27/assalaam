<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between sticky top-0 z-10">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">Admin • Vendor Management</h1>
      <button class="text-sm font-bold text-slate-500" @click="$router.back()">Back</button>
    </header>

    <div class="p-4 space-y-4">
      <!-- Tabs -->
      <div class="flex border-b border-slate-200">
        <button 
          v-for="t in ['vendors', 'settlements']" :key="t"
          @click="tab = t; load(1)"
          class="px-4 py-2 text-sm font-bold capitalize transition-colors border-b-2"
          :class="tab === t ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
        >
          {{ t }}
        </button>
      </div>

      <!-- Vendors Tab -->
      <section v-if="tab === 'vendors'" class="space-y-4 animate-in fade-in duration-300">
        <div class="flex items-center gap-2">
          <input v-model="q" @keyup.enter="load(1)" type="search" placeholder="Search vendors…" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" />
          <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold shrink-0" @click="load(1)">Search</button>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm py-8 text-center">Loading vendors…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm py-12 text-center bg-white rounded-xl border border-dashed border-slate-300">No vendors found.</div>
          <div v-else class="space-y-3">
            <div v-for="v in items" :key="v.id" class="p-4 bg-white border rounded-xl shadow-sm space-y-3 hover:border-emerald-200 transition-colors">
              <div class="flex justify-between items-start">
                <div class="min-w-0 flex-1">
                  <h3 class="font-bold text-slate-800 truncate">{{ v.name }}</h3>
                  <p class="text-xs text-slate-500">Owner: {{ v.owner?.name || 'Unknown' }}</p>
                  <p v-if="v.owner?.email" class="text-[10px] text-slate-400">{{ v.owner.email }}</p>
                  <p v-if="v.phone" class="text-xs text-slate-500 mt-1">📞 {{ v.phone }}</p>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0 ml-2">
                  <span v-if="v.is_approved" class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-black uppercase tracking-widest border border-emerald-100">Approved</span>
                  <span v-else class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[9px] font-black uppercase tracking-widest border border-amber-100">Pending</span>
                  <span v-if="!v.is_active" class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 text-[9px] font-black uppercase tracking-widest border border-rose-100">Inactive</span>
                </div>
              </div>

              <div class="pt-3 border-t flex flex-wrap gap-2">
                <button v-if="!v.is_approved" @click="approveVendor(v)" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold shadow-sm hover:bg-emerald-700 transition-colors">Approve</button>
                <button v-if="v.is_approved" @click="rejectVendor(v)" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 hover:bg-amber-100 transition-colors">Mark Pending</button>
                <button @click="toggleActive(v)" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                  {{ v.is_active ? 'Deactivate' : 'Activate' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Settlements Tab -->
      <section v-if="tab === 'settlements'" class="space-y-4 animate-in fade-in duration-300">
        <div v-if="loading" class="text-slate-500 text-sm py-8 text-center">Loading settlements…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm py-12 text-center bg-white rounded-xl border border-dashed border-slate-300">No settlement requests found.</div>
          <div v-else class="space-y-3">
            <div v-for="s in items" :key="s.id" class="p-4 bg-white border rounded-xl shadow-sm space-y-3">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-bold text-slate-800 text-lg">₦{{ formatMoney(s.amount) }}</h3>
                  <p class="text-xs text-slate-500">Requested by {{ s.user?.name || 'Unknown' }}</p>
                  <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold">{{ s.reference }} • {{ formatDate(s.created_at) }}</p>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                  <span :class="statusClass(s.status)" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border">
                    {{ s.status }}
                  </span>
                </div>
              </div>

              <div class="bg-slate-50 p-3 rounded-xl text-[11px] text-slate-600 border border-slate-100 shadow-inner">
                <div class="font-black text-slate-400 uppercase text-[9px] mb-1 tracking-widest">Bank Payout Info</div>
                <p class="font-bold text-slate-700">{{ s.account_name }}</p>
                <p>{{ s.bank_name }} • {{ s.account_number }}</p>
                <div v-if="s.reason && s.status === 'declined'" class="mt-2 text-rose-700 bg-rose-50 p-2 rounded-lg border border-rose-100 text-[10px]">
                  <span class="font-black uppercase tracking-tighter mr-1">Reason:</span> {{ s.reason }}
                </div>
              </div>

              <div v-if="s.status === 'pending'" class="pt-3 border-t flex gap-2">
                <button @click="approveSettlement(s)" class="flex-1 px-3 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-md hover:bg-emerald-700 transition-all active:scale-95">Mark Paid</button>
                <button @click="declinePrompt(s)" class="flex-1 px-3 py-2 rounded-xl bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200 hover:bg-rose-100 transition-all active:scale-95">Decline</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Pagination -->
      <div v-if="lastPage > 1 && !loading" class="flex items-center justify-between mt-6 text-sm">
        <button class="px-4 py-2 rounded-xl border border-slate-200 bg-white disabled:opacity-50 text-slate-700 font-bold shadow-sm" :disabled="page <= 1" @click="load(page - 1)">Prev</button>
        <div class="text-slate-500 font-medium">Page {{ page }} / {{ lastPage }}</div>
        <button class="px-4 py-2 rounded-xl border border-slate-200 bg-white disabled:opacity-50 text-slate-700 font-bold shadow-sm" :disabled="page >= lastPage" @click="load(page + 1)">Next</button>
      </div>
    </div>

    <!-- Decline Modal -->
    <div v-if="prompting" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-[2rem] w-full max-w-sm shadow-2xl animate-in fade-in zoom-in duration-200 overflow-hidden border border-white/20">
        <div class="p-6">
          <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center text-xl mb-4">✕</div>
          <h3 class="text-lg font-black text-slate-800 mb-1">Decline Request</h3>
          <p class="text-sm text-slate-500 mb-4">Provide a reason for declining ₦{{ formatMoney(prompting.amount) }}.</p>
          <textarea v-model="declineReason" class="w-full border border-slate-200 rounded-2xl p-4 text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all" rows="3" placeholder="e.g. Incomplete verification, Name mismatch..."></textarea>
          
          <div class="flex gap-3 mt-6">
            <button @click="prompting = null" class="flex-1 px-4 py-3 rounded-2xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-colors">Cancel</button>
            <button @click="confirmDecline" :disabled="!declineReason.trim() || processing" class="flex-1 px-4 py-3 rounded-2xl bg-rose-600 text-white text-sm font-bold shadow-lg shadow-rose-200 hover:bg-rose-700 disabled:opacity-50 transition-all active:scale-95">
              {{ processing ? 'Wait...' : 'Confirm' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../../http'

const tab = ref('vendors')
const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const q = ref('')
const prompting = ref(null)
const declineReason = ref('')
const processing = ref(false)

const load = async (p = 1) => {
  loading.value = true
  error.value = ''
  try {
    page.value = p
    const endpoint = tab.value === 'vendors' ? '/api/admin/vendors' : '/api/admin/vendors/settlements'
    const params = { page: p }
    if (tab.value === 'vendors' && q.value) params.q = q.value

    const { data } = await axios.get(endpoint, { params })
    items.value = data?.data || []
    lastPage.value = Number(data?.last_page || 1)
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const approveVendor = async (v) => {
  if (!confirm(`Approve vendor '${v.name}'?`)) return
  try {
    await axios.post(`/api/admin/vendors/${v.id}/approve`)
    load(page.value)
  } catch (e) {
    alert(e?.response?.data?.message || 'Approval failed')
  }
}

const rejectVendor = async (v) => {
  if (!confirm(`Mark vendor '${v.name}' as pending?`)) return
  try {
    await axios.post(`/api/admin/vendors/${v.id}/reject`)
    load(page.value)
  } catch (e) {
    alert(e?.response?.data?.message || 'Action failed')
  }
}

const toggleActive = async (v) => {
  try {
    await axios.post(`/api/admin/vendors/${v.id}/toggle-active`)
    load(page.value)
  } catch (e) {
    alert(e?.response?.data?.message || 'Toggle failed')
  }
}

const approveSettlement = async (s) => {
  if (!confirm(`Confirm ₦${formatMoney(s.amount)} settlement marked as paid? This will deduct from member's wallet.`)) return
  try {
    await axios.post(`/api/admin/vendors/settlements/${s.id}/approve`)
    load(page.value)
  } catch (e) {
    alert(e?.response?.data?.message || 'Approval failed')
  }
}

const declinePrompt = (s) => {
  prompting.value = s
  declineReason.value = ''
}

const confirmDecline = async () => {
  if (!prompting.value || !declineReason.value.trim()) return
  processing.value = true
  try {
    await axios.post(`/api/admin/vendors/settlements/${prompting.value.id}/reject`, {
      reason: declineReason.value
    })
    prompting.value = null
    load(page.value)
  } catch (e) {
    alert(e?.response?.data?.message || 'Decline failed')
  } finally {
    processing.value = false
  }
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
const statusClass = (status) => {
  if (status === 'paid') return 'bg-emerald-50 text-emerald-700 border-emerald-100'
  if (status === 'declined') return 'bg-rose-50 text-rose-700 border-rose-100'
  return 'bg-amber-50 text-amber-700 border-amber-100'
}

onMounted(() => load())
</script>
