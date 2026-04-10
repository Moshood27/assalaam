<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <div class="flex items-center gap-3">
          <button @click="$router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-lg font-bold text-slate-800">Sales Orders</h1>
        </div>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <div v-if="loading" class="text-center py-12">
        <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Loading orders...</p>
      </div>
      
      <div v-else-if="orders.length === 0" class="bg-white rounded-[2rem] p-12 text-center border border-dashed border-slate-200">
        <div class="text-4xl mb-4">📋</div>
        <h3 class="text-sm font-bold text-slate-800 mb-1">No orders yet</h3>
        <p class="text-xs text-slate-500">When members buy your products, they will appear here.</p>
      </div>

      <div v-else class="space-y-4">
        <div v-for="order in orders" :key="order.id" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="p-5 border-b border-slate-50 flex justify-between items-start">
            <div>
              <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">{{ order.reference }}</p>
              <h3 class="text-sm font-bold text-slate-800">{{ order.user?.full_name || order.user?.name || 'Member' }}</h3>
              <p class="text-[10px] text-slate-500">{{ formatDate(order.created_at) }}</p>
            </div>
            <div :class="getStatusClass(order.status)" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase">
              {{ formatStatus(order.status) }}
            </div>
          </div>
          
          <div class="p-5 space-y-3 bg-slate-50/50">
            <div v-for="item in order.items" :key="item.id" class="flex justify-between items-center text-xs">
              <div class="flex-1 min-w-0 pr-4">
                <p class="font-bold text-slate-700 truncate">{{ item.product_name }}</p>
                <p class="text-[10px] text-slate-500">Qty: {{ item.quantity }} × ₦{{ formatMoney(item.unit_price) }}</p>
              </div>
              <div class="text-right">
                <p class="font-black text-slate-800">₦{{ formatMoney(item.line_total) }}</p>
                <p v-if="item.vendor_amount" class="text-[9px] text-emerald-600 font-bold uppercase">Payout: ₦{{ formatMoney(item.vendor_amount) }}</p>
              </div>
            </div>
          </div>
          
          <div class="p-4 flex items-center justify-between">
            <a :href="'tel:' + order.user?.phone" class="flex items-center gap-2 text-[10px] font-black text-emerald-700 uppercase tracking-widest">
              <span class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-xs">📞</span>
              Contact Member
            </a>
            <button @click="openStatusModal(order)" class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Update Status</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="selectedOrder" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl animate-slide-up">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Update Order Status</h2>
          <button @click="selectedOrder = null" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">✕</button>
        </div>
        
        <p class="text-xs text-slate-500 mb-6 font-medium">Updating status for order <span class="font-bold text-slate-800">{{ selectedOrder.reference }}</span></p>
        
        <div class="space-y-3">
          <button v-for="status in availableStatuses" :key="status.id" 
            @click="updateStatus(status.id)"
            :disabled="updating"
            class="w-full p-4 rounded-2xl border border-slate-100 flex items-center gap-4 active:bg-slate-50 transition-all text-left"
            :class="selectedOrder.status === status.id ? 'bg-emerald-50 border-emerald-200' : 'bg-white'">
            <div :class="status.class" class="w-10 h-10 rounded-xl flex items-center justify-center text-lg">
              {{ status.icon }}
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-800">{{ status.label }}</p>
              <p class="text-[10px] text-slate-500">{{ status.description }}</p>
            </div>
            <div v-if="selectedOrder.status === status.id" class="text-emerald-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </div>
          </button>
        </div>
        
        <div v-if="updating" class="mt-6 text-center">
          <p class="text-[10px] font-black text-emerald-700 uppercase animate-pulse">Updating status...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'

const orders = ref([])
const loading = ref(true)
const selectedOrder = ref(null)
const updating = ref(false)

const availableStatuses = [
  { id: 'processing', label: 'Processing', description: 'Currently preparing the order', icon: '⚙️', class: 'bg-blue-50 text-blue-600' },
  { id: 'shipped', label: 'Shipped', description: 'Item has been handed to courier', icon: '🚚', class: 'bg-amber-50 text-amber-600' },
  { id: 'delivered', label: 'Delivered', description: 'Item reached the customer', icon: '🏠', class: 'bg-emerald-50 text-emerald-600' },
  { id: 'completed', label: 'Completed', description: 'Finalized and payout triggered', icon: '✅', class: 'bg-emerald-100 text-emerald-700' },
  { id: 'cancelled', label: 'Cancelled', description: 'Order will not be fulfilled', icon: '✕', class: 'bg-rose-50 text-rose-600' },
]

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 })
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatStatus = (s) => {
  return String(s || '').replace(/_/g, ' ')
}

const getStatusClass = (s) => {
  switch (s) {
    case 'completed':
    case 'paid':
      return 'bg-emerald-100 text-emerald-700'
    case 'pending':
    case 'murabaha_pending':
      return 'bg-amber-100 text-amber-700'
    case 'cancelled':
      return 'bg-rose-100 text-rose-700'
    case 'shipped':
      return 'bg-blue-100 text-blue-700'
    case 'processing':
      return 'bg-indigo-100 text-indigo-700'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

const loadOrders = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/vendor/orders')
    orders.value = data.data // Paginated response
  } catch (err) {
    console.error('Failed to load vendor orders', err)
  } finally {
    loading.value = false
  }
}

const openStatusModal = (order) => {
  selectedOrder.value = { ...order }
}

const updateStatus = async (newStatus) => {
  if (newStatus === selectedOrder.value.status) return
  updating.value = true
  try {
    await axios.post(`/api/vendor/orders/${selectedOrder.value.id}/status`, { status: newStatus })
    selectedOrder.value = null
    await loadOrders()
  } catch (err) {
    console.error('Failed to update order status', err)
    alert('Error updating status: ' + (err.response?.data?.message || 'Unknown error'))
  } finally {
    updating.value = false
  }
}

const viewDetails = (order) => {
  // Can be used for detailed view later if needed
  openStatusModal(order)
}

onMounted(loadOrders)
</script>
