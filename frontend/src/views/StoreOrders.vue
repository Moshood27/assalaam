<template>
  <div class="min-h-screen bg-slate-50/50 pb-24">
    <AppHeader title="My Orders" :showBack="true">
      <template #right>
        <button class="p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="$router.push('/store')" title="Store">
          <span class="i-mdi-store-outline text-2xl text-emerald-700"></span>
        </button>
      </template>
    </AppHeader>

    <div class="max-w-3xl mx-auto p-4 space-y-6">
      <section class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Purchase History</h2>
            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Track your coop store orders</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
            <span class="i-mdi-package-variant-closed"></span>
          </div>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-12 gap-3">
          <div class="w-10 h-10 border-4 border-emerald-100 border-t-emerald-600 rounded-full animate-spin"></div>
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Loading history...</p>
        </div>
        <div v-else-if="error" class="p-6 bg-rose-50 border border-rose-100 rounded-2xl text-rose-600 text-sm font-bold text-center">
          {{ error }}
        </div>
        <div v-else>
          <div v-if="!items.length" class="py-20 text-center border-2 border-dashed border-slate-100 rounded-[2rem]">
            <div class="text-5xl mb-4">🛒</div>
            <p class="text-slate-400 text-sm font-medium">You haven't placed any orders yet.</p>
            <button @click="$router.push('/store')" class="mt-6 px-6 py-3 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-900/10">Start Shopping</button>
          </div>
          
          <div v-else class="space-y-4">
            <div v-for="o in items" :key="o.id" 
              class="group relative bg-slate-50 hover:bg-white p-5 rounded-[2rem] border border-transparent hover:border-slate-100 hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-300 cursor-pointer overflow-hidden" 
              @click="$router.push(`/store/orders/${o.id}`)">
              
              <div class="flex items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-slate-400 shadow-sm border border-slate-100">
                    <span class="i-mdi-barcode-scan"></span>
                  </div>
                  <div>
                    <div class="font-black text-slate-800 uppercase tracking-tight text-sm">{{ o.reference }}</div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ new Date(o.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}</div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-lg font-black text-slate-900">₦ {{ money(o.total_amount) }}</div>
                  <div class="flex items-center justify-end gap-2 mt-1">
                     <span v-if="o.dispute" class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[8px] font-black uppercase tracking-widest border border-rose-200">Tahkim / Dispute</span>
                     <div :class="statusClass(o.status)" class="px-2 py-0.5 rounded-md bg-white border border-slate-100 shadow-sm text-[8px] font-black uppercase tracking-widest">
                       {{ o.status }}
                     </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between pt-4 border-t border-slate-200/50">
                <div class="flex -space-x-2">
                   <div v-for="i in (o.items || []).slice(0, 3)" :key="i.id" class="w-8 h-8 rounded-full border-2 border-white bg-slate-200 flex items-center justify-center text-[8px] font-bold overflow-hidden shadow-sm">
                      <img v-if="i.product_image_url" :src="getImageUrl(i.product_image_url)" class="w-full h-full object-cover" />
                      <span v-else>{{ i.product_name?.charAt(0) }}</span>
                   </div>
                   <div v-if="(o.items || []).length > 3" class="w-8 h-8 rounded-full border-2 border-white bg-emerald-600 text-white flex items-center justify-center text-[8px] font-black shadow-sm">+{{ o.items.length - 3 }}</div>
                </div>
                <div class="flex items-center gap-2">
                   <button v-if="!['failed', 'cancelled'].includes(o.status?.toLowerCase()) && !o.dispute" @click.stop="$router.push(`/store/orders/${o.id}?dispute=1`)" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-rose-600 transition-colors">Dispute</button>
                   <span class="i-mdi-chevron-right text-xl text-slate-300 group-hover:text-emerald-500 transition-colors"></span>
                </div>
              </div>
            </div>
          </div>

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
import getImageUrl from '../utils/image'

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
