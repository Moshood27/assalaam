<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <div class="flex items-center gap-3">
          <button @click="$router.push('/profile')" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-lg font-bold text-slate-800">Vendor Portal</h1>
        </div>
        <div class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">
          {{ vendor.is_approved ? 'Approved' : 'Pending' }}
        </div>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <!-- Business Header -->
      <div v-if="vendor.id" class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 opacity-40" />
        <div class="relative z-10">
          <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest mb-1">Business Name</p>
          <h2 class="text-2xl font-black text-slate-800 uppercase leading-tight mb-4">{{ vendor.name }}</h2>
          
          <div class="grid grid-cols-2 gap-4 mt-6">
            <div @click="$router.push('/vendor/settlements')" class="bg-emerald-50 p-4 rounded-2xl border border-emerald-100 cursor-pointer active:scale-95 transition-all">
              <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Available Balance</p>
              <p class="text-xl font-black text-emerald-700">₦{{ formatMoney(stats.available_balance) }}</p>
            </div>
            <div class="bg-slate-50 p-4 rounded-2xl">
              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Earned</p>
              <p class="text-xl font-black text-slate-800">₦{{ formatMoney(stats.total_earned) }}</p>
            </div>
          </div>
          <div class="grid grid-cols-4 gap-2 mt-4">
            <div class="bg-slate-50 p-2 rounded-2xl">
              <p class="text-[7px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Approved</p>
              <p class="text-sm font-black text-slate-800 text-center">{{ stats.approved_products_count || 0 }}</p>
            </div>
            <div class="bg-slate-50 p-2 rounded-2xl">
              <p class="text-[7px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Pending</p>
              <p class="text-sm font-black text-amber-600 text-center">{{ stats.pending_products_count || 0 }}</p>
            </div>
            <div class="bg-slate-50 p-2 rounded-2xl">
              <p class="text-[7px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Active Orders</p>
              <p class="text-sm font-black text-blue-600 text-center">{{ stats.pending_orders_count || 0 }}</p>
            </div>
            <div class="bg-slate-50 p-2 rounded-2xl">
              <p class="text-[7px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Completed</p>
              <p class="text-sm font-black text-emerald-600 text-center">{{ stats.completed_orders_count || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock Warning -->
      <div v-if="stats.low_stock_products_count > 0" @click="$router.push('/vendor/products')" class="bg-rose-50 border border-rose-100 p-4 rounded-3xl flex items-center gap-4 animate-pulse cursor-pointer">
        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl">⚠️</div>
        <div class="flex-1">
          <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest">Low Stock Alert</p>
          <p class="text-xs font-bold text-rose-800">{{ stats.low_stock_products_count }} products are running low on stock.</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-3 gap-3">
        <button @click="$router.push('/vendor/products')" class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:scale-95 transition-all">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">📦</div>
          <span class="text-[9px] font-black text-slate-800 uppercase tracking-wider">Products</span>
        </button>
        <button @click="$router.push('/vendor/orders')" class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:scale-95 transition-all">
          <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl">📋</div>
          <span class="text-[9px] font-black text-slate-800 uppercase tracking-wider">Orders</span>
        </button>
        <button @click="$router.push('/vendor/settlements')" class="bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:scale-95 transition-all">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">💰</div>
          <span class="text-[9px] font-black text-slate-800 uppercase tracking-wider">Payout</span>
        </button>
      </div>

      <!-- Recent Payouts -->
      <div class="space-y-4">
        <div class="flex items-center justify-between px-2">
          <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recent Activity</h3>
          <button class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">View All</button>
        </div>
        
        <div v-if="activities.length === 0" class="bg-white rounded-3xl p-8 text-center border border-dashed border-slate-200">
          <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">No recent activity</p>
        </div>
        
        <div v-else class="space-y-3">
          <div v-for="act in activities" :key="act.id" class="bg-white p-4 rounded-2xl border border-slate-100 flex items-center gap-4">
            <div :class="act.type === 'payout' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'" class="w-10 h-10 rounded-xl flex items-center justify-center font-bold">
              {{ act.type === 'payout' ? '₦' : '📦' }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-slate-800 truncate">{{ act.title }}</p>
              <p class="text-[10px] text-slate-500 font-medium">{{ act.date }}</p>
            </div>
            <div class="text-right">
              <p :class="act.amount > 0 ? 'text-emerald-700' : 'text-slate-800'" class="text-sm font-black">
                {{ act.amount > 0 ? '+' : '' }}₦{{ formatMoney(Math.abs(act.amount)) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'

const vendor = ref({})
const stats = ref({
  total_earned: 0,
  products_count: 0,
  pending_orders_count: 0,
  completed_orders_count: 0
})
const activities = ref([])

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 })
}

onMounted(async () => {
  try {
    const [profRes, statsRes] = await Promise.all([
      axios.get('/api/vendor/profile'),
      axios.get('/api/vendor/stats')
    ])
    vendor.value = profRes.data
    stats.value = statsRes.data
    activities.value = statsRes.data.activities || []
  } catch (err) {
    console.error('Failed to load vendor data', err)
  }
})
</script>
