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
        <div class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase">
          {{ vendor.is_approved ? 'Approved' : 'Pending' }}
        </div>
      </div>
    </header>

    <div v-if="vendor.id" class="p-4 space-y-6">
      <!-- Pending Approval Banner -->
      <div v-if="!vendor.is_approved" class="bg-amber-50 border border-amber-100 p-6 rounded-[2rem] text-center space-y-4">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-3xl flex items-center justify-center text-3xl mx-auto">â³</div>
        <h2 class="text-xl font-black text-slate-800 uppercase">Approval Pending</h2>
        <p class="text-sm text-slate-600 leading-relaxed">
          Your vendor profile is currently being reviewed by our administrative team. 
          You will gain full access to the vendor portal and be able to list products once your application is approved.
        </p>
        <button @click="$router.push('/profile')" class="px-6 py-3 bg-slate-800 text-white rounded-2xl font-bold text-xs uppercase tracking-widest">Back to Profile</button>
      </div>

      <template v-else>
        <!-- Sophisticated Business Header with Chart -->
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
          <div class="p-8 pb-4 flex items-start justify-between">
            <div class="space-y-1">
              <p class="text-[10px] text-blue-600 font-black uppercase tracking-widest">Business Dashboard</p>
              <h2 class="text-3xl font-black text-slate-800 leading-tight uppercase">{{ vendor.name }}</h2>
            </div>
            <div class="text-right">
              <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Available to Payout</p>
              <p class="text-2xl font-black text-blue-700">â‚¦{{ formatMoney(stats.available_balance) }}</p>
            </div>
          </div>

          <!-- Trend Chart Integration -->
          <div class="px-4 -mb-4">
             <TrendChart :series="chartSeries" :categories="chartCategories" />
          </div>

          <div class="p-8 pt-0 grid grid-cols-2 gap-4">
             <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100/50">
               <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Lifetime Earnings</p>
               <p class="text-xl font-black text-slate-800">â‚¦{{ formatMoney(stats.total_earned) }}</p>
             </div>
             <div class="bg-blue-600 p-4 rounded-3xl flex flex-col justify-center items-center text-center cursor-pointer hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 active:scale-95" @click="$router.push('/vendor/settlements')">
               <span class="text-[9px] font-black text-white uppercase tracking-widest">Request Settlement</span>
             </div>
          </div>
        </div>

        <!-- Dynamic Status Grid -->
        <div class="grid grid-cols-4 gap-3">
          <div class="bg-white p-3 rounded-3xl border border-slate-100 text-center shadow-sm">
             <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Approved</p>
             <p class="text-lg font-black text-slate-800">{{ stats.approved_products_count || 0 }}</p>
          </div>
          <div class="bg-white p-3 rounded-3xl border border-slate-100 text-center shadow-sm">
             <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Pending</p>
             <p class="text-lg font-black text-amber-500">{{ stats.pending_products_count || 0 }}</p>
          </div>
          <div class="bg-white p-3 rounded-3xl border border-slate-100 text-center shadow-sm">
             <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Orders</p>
             <p class="text-lg font-black text-blue-500">{{ stats.pending_orders_count || 0 }}</p>
          </div>
          <div class="bg-white p-3 rounded-3xl border border-slate-100 text-center shadow-sm">
             <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Success</p>
             <p class="text-lg font-black text-blue-600">{{ stats.completed_orders_count || 0 }}</p>
          </div>
        </div>

        <!-- Enhanced Quick Actions -->
        <div class="grid grid-cols-3 gap-4">
          <button @click="$router.push('/vendor/products')" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center gap-3 active:scale-95 transition-all hover:border-blue-500/20 hover:shadow-lg hover:shadow-blue-900/5">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">ðŸ“¦</div>
            <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Inventory</span>
          </button>
          <button @click="$router.push('/vendor/orders')" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center gap-3 active:scale-95 transition-all hover:border-orange-500/20 hover:shadow-lg hover:shadow-orange-900/5">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">ðŸ“‹</div>
            <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Orders</span>
          </button>
          <button @click="$router.push('/vendor/settlements')" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center gap-3 active:scale-95 transition-all hover:border-blue-500/20 hover:shadow-lg hover:shadow-blue-900/5">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">ðŸ’°</div>
            <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Payouts</span>
          </button>
        </div>

        <!-- Recent Payouts -->
        <div class="space-y-4">
          <div class="flex items-center justify-between px-2">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recent Activity</h3>
            <button class="text-[10px] font-black text-blue-700 uppercase tracking-widest">View All</button>
          </div>
          
          <div v-if="activities.length === 0" class="bg-white rounded-3xl p-8 text-center border border-dashed border-slate-200">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">No recent activity</p>
          </div>
          
          <div v-else class="space-y-3">
            <div v-for="act in activities" :key="act.id" class="bg-white p-4 rounded-2xl border border-slate-100 flex items-center gap-4">
              <div :class="act.type === 'payout' ? 'bg-blue-50 text-blue-600' : 'bg-blue-50 text-blue-600'" class="w-10 h-10 rounded-xl flex items-center justify-center font-bold">
                {{ act.type === 'payout' ? 'â‚¦' : 'ðŸ“¦' }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ act.title }}</p>
                <p class="text-[10px] text-slate-500 font-medium">{{ act.date }}</p>
              </div>
              <div class="text-right">
                <p :class="act.amount > 0 ? 'text-blue-700' : 'text-slate-800'" class="text-sm font-black">
                  {{ act.amount > 0 ? '+' : '' }}â‚¦{{ formatMoney(Math.abs(act.amount)) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../http'
import TrendChart from '../components/TrendChart.vue'

const vendor = ref({})
const stats = ref({
  total_earned: 0,
  products_count: 0,
  pending_orders_count: 0,
  completed_orders_count: 0,
  trend: []
})
const activities = ref([])

const chartSeries = computed(() => {
  return [{
    name: 'Earnings',
    data: (stats.value.trend || []).map(t => t.value)
  }]
})

const chartCategories = computed(() => {
  return (stats.value.trend || []).map(t => t.label)
})

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 })
}

onMounted(async () => {
  try {
    const profRes = await axios.get('/api/vendor/profile')
    vendor.value = profRes.data

    if (vendor.value.is_approved) {
      const statsRes = await axios.get('/api/vendor/stats')
      stats.value = statsRes.data
      activities.value = statsRes.data.activities || []
    }
  } catch (err) {
    console.error('Failed to load vendor data', err)
  }
})
</script>


