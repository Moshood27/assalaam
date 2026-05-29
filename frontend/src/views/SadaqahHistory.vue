<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <AppHeader title="My Contributions" :showBack="true" />

    <div class="p-4">
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
        <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-medium">Loading history...</p>
      </div>

      <div v-else-if="contributions.length === 0" class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-200">
        <div class="text-4xl mb-4">Ã°Å¸Å½Â</div>
        <p class="text-slate-500 text-sm">You haven't made any contributions yet.</p>
        <button @click="$router.push('/sadaqah')" class="mt-4 text-blue-600 font-bold text-sm">Browse Projects</button>
      </div>

      <div v-else class="space-y-3">
        <div v-for="c in contributions" :key="c.id" class="bg-white p-4 rounded-2xl flex items-center gap-4 border border-slate-50 shadow-sm">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-slate-800 text-sm truncate">{{ c.project?.name || 'Project Deleted' }}</h3>
            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">{{ formatDate(c.created_at) }} Ã¢â‚¬Â¢ {{ c.reference }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-black text-blue-600">Ã¢â€šÂ¦ {{ formatMoney(c.amount) }}</p>
            <p v-if="c.is_anonymous" class="text-[9px] font-bold text-slate-300 uppercase italic">Anonymous</p>
          </div>
        </div>
      </div>
    </div>
    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http.js'

const contributions = ref([])
const loading = ref(false)

const fetchHistory = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/sadaqah/my-contributions')
    contributions.value = data.data || []
  } catch (e) {
    console.error('Failed to load contribution history', e)
  } finally {
    loading.value = false
  }
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchHistory)
</script>


