<template>
  <transition name="modal-fade">
    <div v-if="isOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>

      <!-- Modal Content -->
      <div class="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300">
        <!-- Header -->
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-black text-slate-800 leading-tight">Repayment Schedule</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1" v-if="loan">{{ loan.qard_id_string }}</p>
          </div>
          <button @click="close" class="w-10 h-10 bg-white rounded-xl shadow-sm border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 transition-colors">
            ✕
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
          <div v-if="loading" class="py-20 text-center space-y-4">
            <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p class="text-xs text-slate-500 font-black uppercase tracking-widest">Calculating Schedule...</p>
          </div>

          <div v-else-if="error" class="p-8 text-center bg-rose-50 rounded-3xl border border-rose-100">
            <span class="text-3xl mb-3 block">⚠️</span>
            <p class="text-sm font-bold text-rose-800">{{ error }}</p>
            <button @click="fetchSchedule" class="mt-4 text-xs font-black text-rose-700 underline">Try Again</button>
          </div>

          <template v-else-if="scheduleData">
            <!-- Loan Summary in Modal -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Principal</p>
                <p class="text-sm font-black text-slate-800">₦ {{ n(scheduleData.loan.principal_amount) }}</p>
              </div>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Paid</p>
                <p class="text-sm font-black text-emerald-600">₦ {{ n(scheduleData.paid_total) }}</p>
              </div>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Expected to Pay</p>
                <p class="text-sm font-black text-rose-600">₦ {{ n(scheduleData.loan.overdue_amount) }}</p>
              </div>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Balance</p>
                <p class="text-sm font-black text-slate-700">₦ {{ n(scheduleData.remaining_principal) }}</p>
              </div>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Progress</p>
                <p class="text-sm font-black text-indigo-600">{{ Math.round((scheduleData.paid_total / scheduleData.loan.principal_amount) * 100) }}%</p>
              </div>
            </div>

            <!-- Schedule Table -->
            <div class="overflow-hidden rounded-3xl border border-slate-100 shadow-sm">
              <table class="w-full text-left">
                <thead>
                  <tr class="bg-slate-900 text-white">
                    <th class="p-4 text-[10px] font-black uppercase tracking-widest">#</th>
                    <th class="p-4 text-[10px] font-black uppercase tracking-widest">Due Date</th>
                    <th class="p-4 text-[10px] font-black uppercase tracking-widest text-right">Amount</th>
                    <th class="p-4 text-[10px] font-black uppercase tracking-widest text-center">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="item in scheduleData.schedule" :key="item.sequence" class="hover:bg-slate-50 transition-colors">
                    <td class="p-4 text-xs font-bold text-slate-500">{{ item.sequence }}</td>
                    <td class="p-4 text-xs font-black text-slate-800">{{ formatDate(item.due_date) }}</td>
                    <td class="p-4 text-xs font-black text-slate-800 text-right">₦ {{ n(item.installment_amount) }}</td>
                    <td class="p-4 text-center">
                      <span :class="getStatusBadgeClass(item.status)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider">
                        {{ item.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="scheduleData.next_due" class="p-4 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-between">
               <div class="flex items-center gap-3">
                 <span class="text-xl">🔔</span>
                 <div>
                   <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Next Due Installment</p>
                   <p class="text-sm font-black text-indigo-900">{{ formatDate(scheduleData.next_due.due_date) }} • ₦ {{ n(scheduleData.next_due.amount_due) }}</p>
                   <p v-if="scheduleData.loan.overdue_amount > 0" class="text-[10px] text-rose-600 font-bold uppercase mt-1">Expected Amount to Pay: ₦ {{ n(scheduleData.loan.overdue_amount) }}</p>
                 </div>
               </div>
               <span class="text-[10px] font-black text-indigo-600 bg-white px-3 py-1 rounded-full border border-indigo-200 uppercase">Item #{{ scheduleData.next_due.sequence }}</span>
            </div>
          </template>
        </div>

        <!-- Footer -->
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-4">
          <button @click="close" class="flex-1 h-12 rounded-2xl font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-colors text-xs">
            Close
          </button>
          <a v-if="scheduleData" :href="downloadUrl" target="_blank" class="flex-1 h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-emerald-100 transition-all flex items-center justify-center gap-2 text-xs">
            <span>📥</span> Download PDF
          </a>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import axios from '../http'

const props = defineProps({
  isOpen: Boolean,
  loan: Object
})

const emit = defineEmits(['close'])

const loading = ref(false)
const error = ref('')
const scheduleData = ref(null)

const n = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'paid': return 'bg-emerald-100 text-emerald-700'
    case 'partial': return 'bg-amber-100 text-amber-700'
    case 'overdue': return 'bg-rose-100 text-rose-700'
    default: return 'bg-slate-100 text-slate-500'
  }
}

const downloadUrl = computed(() => {
  if (!props.loan) return '#'
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-loan-schedule/${props.loan.id}?token=${encodeURIComponent(token)}`
})

const fetchSchedule = async () => {
  if (!props.loan?.id) return
  loading.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get(`/api/reports/loans/${props.loan.id}/schedule`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    scheduleData.value = data
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load schedule'
  } finally {
    loading.value = false
  }
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    fetchSchedule()
  } else {
    scheduleData.value = null
  }
})

const close = () => {
  emit('close')
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
