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
        <!-- Project Stats Card -->
        <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-6 text-white shadow-xl">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-emerald-100 text-[10px] font-black uppercase tracking-widest">Management Fee</p>
              <p class="text-3xl font-extrabold mt-1">{{ Number(project?.management_fee_percent || 0).toLocaleString() }}%</p>
            </div>
            <div v-if="project?.is_unit_based" class="text-right">
              <p class="text-emerald-100 text-[10px] font-black uppercase tracking-widest">Unit Price</p>
              <p class="text-2xl font-extrabold mt-1 text-white">₦ {{ Number(project.unit_price).toLocaleString() }}</p>
            </div>
          </div>
          <div class="mt-4 grid grid-cols-2 gap-2">
            <div>
              <p v-if="project?.target_amount" class="text-emerald-100 text-[11px]">Target: ₦ {{ Number(project.target_amount).toLocaleString() }}</p>
              <p class="mt-1 text-emerald-50 text-[11px]">
                <span v-if="project?.started_at">Started: {{ formatDate(project.started_at) }}</span>
              </p>
            </div>
            <div v-if="project?.is_unit_based" class="text-right">
              <p class="text-emerald-100 text-[11px]">Available: {{ project.available_units }} / {{ project.total_units }} units</p>
            </div>
          </div>
        </div>

        <!-- Buy Units Button (Action) -->
        <div v-if="project?.is_unit_based && project?.active" class="px-2">
          <button @click="showBuyModal = true" class="btn-primary w-full py-4 shadow-lg flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
            Buy Units
          </button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="card card-elevated p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">My Total Invested</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">₦ {{ Number(totalInvested).toLocaleString() }}</p>
            <p v-if="project?.is_unit_based" class="text-[10px] text-slate-400 font-bold mt-1 uppercase">{{ totalUnits }} Units owned</p>
          </div>
          <div class="card card-elevated p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Profit Events</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ profits.length }}</p>
          </div>
        </div>

        <!-- Project Feed (Project Tracking) -->
        <div class="card card-elevated">
          <div class="p-4 border-b flex items-center justify-between bg-slate-50/50">
            <h3 class="font-bold text-slate-800">Project Feed</h3>
            <span class="badge bg-emerald-100 text-emerald-700 text-[9px] uppercase tracking-tighter px-2 py-0.5 rounded-full font-bold">Live Updates</span>
          </div>
          <div v-if="!project?.updates?.length" class="p-8 text-center">
            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <p class="text-slate-500 text-sm font-medium">No updates posted yet.</p>
            <p class="text-slate-400 text-[11px] mt-1">Check back later for progress reports.</p>
          </div>
          <div v-else class="p-4 space-y-6">
            <div v-for="update in project.updates" :key="update.id" class="relative pl-6 border-l-2 border-emerald-100 pb-2 last:pb-0">
              <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-4 border-emerald-500"></div>
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">{{ update.type }}</span>
                <span class="text-[10px] text-slate-400">{{ formatDateTime(update.created_at) }}</span>
              </div>
              <h4 class="font-bold text-slate-800 text-sm mb-1">{{ update.title }}</h4>
              <p class="text-slate-600 text-[13px] leading-relaxed">{{ update.content }}</p>

              <!-- Media Gallery -->
              <div v-if="update.media_urls?.length" class="mt-3 grid grid-cols-2 gap-2">
                <div v-for="(media, mIdx) in update.media_urls" :key="mIdx" class="aspect-video bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                  <img :src="media.url" class="w-full h-full object-cover" />
                </div>
              </div>
            </div>
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
                <p v-if="project?.is_unit_based" class="text-[11px] text-emerald-600 font-bold">{{ inv.units }} Units</p>
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

    <!-- Buy Units Modal -->
    <div v-if="showBuyModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl transition-all duration-300">
        <div class="p-6 border-b bg-slate-50/50 flex items-center justify-between">
          <h3 class="text-xl font-black text-slate-800 tracking-tight">Buy Units</h3>
          <button @click="showBuyModal = false" class="p-2 hover:bg-slate-200 rounded-full transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="p-6 space-y-6 text-slate-800">
          <div class="bg-emerald-50 rounded-3xl p-4 flex items-center justify-between border border-emerald-100">
             <div>
                <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Unit Price</p>
                <p class="text-2xl font-black text-emerald-900">₦ {{ Number(project.unit_price).toLocaleString() }}</p>
             </div>
             <div class="text-right">
                <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Available</p>
                <p class="text-xl font-bold text-emerald-900">{{ project.available_units }} units</p>
             </div>
          </div>

          <div>
            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2 ml-1">Number of Units</label>
            <div class="flex items-center gap-4">
               <button @click="unitsToBuy > 1 && unitsToBuy--" class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl font-bold text-slate-600">-</button>
               <input v-model.number="unitsToBuy" type="number" class="flex-1 h-12 rounded-2xl bg-slate-100 border-none text-center text-xl font-black focus:ring-2 focus:ring-emerald-500" />
               <button @click="unitsToBuy < project.available_units && unitsToBuy++" class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl font-bold text-slate-600">+</button>
            </div>
          </div>

          <div class="pt-4 border-t">
             <div class="flex justify-between items-center mb-4">
                <span class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">Total Investment</span>
                <span class="text-2xl font-black text-slate-900">₦ {{ Number(unitsToBuy * project.unit_price).toLocaleString() }}</span>
             </div>
             <button @click="initiateUnitPurchase" :disabled="buying || unitsToBuy <= 0 || unitsToBuy > project.available_units" class="btn-primary w-full py-4 text-lg flex items-center justify-center gap-2">
                <span v-if="buying" class="animate-spin w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></span>
                {{ buying ? 'Processing...' : 'Proceed to Payment' }}
             </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from '../http.js'

const route = useRoute()
const id = ref(Number(route.params.id))

const loading = ref(true)
const buying = ref(false)
const showBuyModal = ref(false)
const unitsToBuy = ref(1)
const project = ref(null)
const investments = ref([])
const profits = ref([])
const totalInvested = ref(0)
const totalUnits = ref(0)

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
    totalUnits.value = inv.data?.total_units || 0
    profits.value = prof.data?.profits || []
  } catch (e) {
    console.error('Failed to load project', e)
  } finally {
    loading.value = false
  }
}

const initiateUnitPurchase = async () => {
  if (unitsToBuy.value <= 0) return
  buying.value = true
  try {
    const { data: schemes } = await axios.get('/api/schemes')
    const projectScheme = schemes.find(s => s.name.toLowerCase().includes('project'))
    
    if (!projectScheme) {
      alert('Project investment scheme not found. Please contact support.')
      return
    }

    const payload = {
      items: [
        {
          scheme_id: projectScheme.id,
          project_id: project.value.id,
          units: unitsToBuy.value,
          amount: unitsToBuy.value * project.value.unit_price
        }
      ],
      callback_url: window.location.origin + '/payment-callback'
    }

    const { data } = await axios.post('/api/initiate-payment', payload)
    if (data.checkout_url) {
      window.location.href = data.checkout_url
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to initiate purchase')
  } finally {
    buying.value = false
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
