<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans text-slate-900">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold">Tahkim History</h1>
        <div class="w-6"></div>
      </div>
    </header>

    <div class="p-4 max-w-2xl mx-auto space-y-4">
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-600 mb-4"></div>
        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Loading Disputes...</p>
      </div>

      <div v-else-if="!disputes.length" class="text-center py-20 bg-white rounded-3xl border border-slate-100">
        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
        </div>
        <p class="text-slate-500 font-medium">No disputes found.</p>
        <p class="text-slate-400 text-xs mt-1">Raise a dispute from your Order Receipt if needed.</p>
        <router-link to="/store/orders" class="mt-6 inline-block bg-emerald-600 text-white px-6 py-2 rounded-full text-sm font-bold">Go to Orders</router-link>
      </div>

      <div v-else class="space-y-3">
        <div v-for="d in disputes" :key="d.id" class="card p-4 hover:border-emerald-200 transition-colors cursor-pointer" @click="selectDispute(d)">
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Order #{{ d.order?.reference || 'N/A' }}</p>
              <h3 class="font-bold text-slate-800">{{ d.reason }}</h3>
            </div>
            <span :class="statusClass(d.status)" class="text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-tighter">
              {{ d.status }}
            </span>
          </div>
          <p class="text-xs text-slate-500 line-clamp-1 mb-3">{{ d.description || 'No description provided.' }}</p>
          <div class="flex items-center justify-between pt-3 border-t border-slate-50">
            <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(d.created_at) }}</span>
            <span class="text-emerald-600 text-[10px] font-black uppercase tracking-widest flex items-center gap-1">
              View Details
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Dispute Detail Modal -->
    <div v-if="selectedDispute" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="selectedDispute = null"></div>
      <div class="relative bg-white w-full max-w-lg rounded-t-[32px] sm:rounded-[32px] shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-slide-up">
        <div class="p-6 overflow-y-auto">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-black text-slate-800">Tahkim Details</h2>
            <button @click="selectedDispute = null" class="p-2 bg-slate-50 rounded-full text-slate-400 hover:text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-slate-50 p-3 rounded-2xl">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</p>
                <span :class="statusClass(selectedDispute.status)" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-tighter">
                  {{ selectedDispute.status }}
                </span>
              </div>
              <div class="bg-slate-50 p-3 rounded-2xl">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Date Raised</p>
                <p class="text-xs font-bold text-slate-800">{{ formatDate(selectedDispute.created_at) }}</p>
              </div>
            </div>

            <div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Dispute Reason</p>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-sm font-bold text-slate-800 mb-1">{{ selectedDispute.reason }}</p>
                <p class="text-xs text-slate-600 leading-relaxed">{{ selectedDispute.description || 'No detailed description.' }}</p>
              </div>
            </div>

            <div v-if="selectedDispute.order">
               <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Associated Order</p>
               <div class="flex items-center justify-between p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                 <div>
                   <p class="text-xs font-bold text-emerald-900">Order #{{ selectedDispute.order.reference }}</p>
                   <p class="text-[10px] text-emerald-700">Total: ₦ {{ numberFormat(selectedDispute.order.total_amount) }}</p>
                 </div>
                 <router-link :to="'/store/orders/' + selectedDispute.order.id" class="text-[10px] font-black text-emerald-700 underline uppercase tracking-widest">View Receipt</router-link>
               </div>
            </div>

            <div v-if="selectedDispute.outcome_details">
              <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-2 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                Final Resolution
              </p>
              <div class="bg-emerald-50 p-4 rounded-2xl border border-emerald-100 text-sm text-emerald-900 leading-relaxed">
                {{ selectedDispute.outcome_details }}
              </div>
              <p v-if="selectedDispute.resolved_at" class="text-[9px] text-slate-400 mt-2 text-right">Resolved on {{ formatDate(selectedDispute.resolved_at) }}</p>
            </div>

            <div v-else-if="selectedDispute.status === 'mediation'">
               <div class="bg-amber-50 p-4 rounded-2xl border border-amber-100 flex gap-3">
                 <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 1.838a1.75 1.75 0 003.391.751A.75.75 0 0012 13h-.253a.25.25 0 01-.244-.304l.459-1.838a1.75 1.75 0 00-3.391-.751z" clip-rule="evenodd" />
                    </svg>
                 </div>
                 <div class="flex-1">
                   <p class="text-xs font-bold text-amber-900">Under Mediation</p>
                   <p class="text-[10px] text-amber-700 leading-relaxed">The Sharia Board is currently reviewing your case. You will be notified once a decision is reached.</p>
                 </div>
               </div>
            </div>
          </div>
        </div>
        <div class="p-6 bg-slate-50 border-t border-slate-100">
           <button @click="selectedDispute = null" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg shadow-slate-200 active:scale-95 transition-transform">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'
import { format } from 'date-fns'

const disputes = ref([])
const loading = ref(false)
const selectedDispute = ref(null)

const load = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/store/disputes')
    disputes.value = data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const selectDispute = (d) => {
  selectedDispute.value = d
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return format(new Date(date), 'MMM d, yyyy h:mm a')
}

const numberFormat = (v) => {
  return parseFloat(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const statusClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'pending': return 'bg-slate-100 text-slate-600'
    case 'mediation': return 'bg-amber-100 text-amber-700'
    case 'resolved': return 'bg-emerald-100 text-emerald-700'
    case 'rejected': return 'bg-rose-100 text-rose-700'
    default: return 'bg-slate-100 text-slate-600'
  }
}

onMounted(load)
</script>

<style scoped>
.animate-slide-up {
  animation: slide-up 0.3s ease-out;
}
@keyframes slide-up {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}
</style>
