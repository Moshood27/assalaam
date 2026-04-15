<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <AppHeader title="Sadaqah Jariyah" :showBack="true">
      <template #right>
        <button @click="$router.push('/sadaqah/history')" class="p-2 -mr-2 hover:bg-slate-100 rounded-full transition-colors text-slate-500" aria-label="History">
          <span class="i-mdi-history text-2xl text-slate-600"></span>
        </button>
      </template>
    </AppHeader>

    <div class="p-4 space-y-4">
      <div class="bg-gradient-to-br from-emerald-600 to-teal-700 p-6 rounded-[2rem] text-white shadow-lg mb-6">
        <h2 class="text-xl font-bold mb-2">Crowdfunding</h2>
        <p class="text-emerald-50 text-xs opacity-90 leading-relaxed">
          Contribute small amounts to community projects like building wells, mosque repairs, or helping members with medical bills. Earning continuous rewards.
        </p>
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
        <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-medium">Loading projects...</p>
      </div>
      
      <div v-else-if="projects.length === 0" class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-200">
        <div class="text-4xl mb-4">🌟</div>
        <p class="text-slate-500 text-sm">No active projects at the moment.</p>
      </div>

      <div v-else class="grid gap-4">
        <div 
          v-for="p in projects" 
          :key="p.id"
          class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
          @click="$router.push(`/sadaqah/${p.id}`)"
        >
          <div class="h-40 bg-slate-200 relative">
            <img 
              v-if="p.media_urls && p.media_urls.length" 
              :src="getImageUrl(p.media_urls[0])" 
              class="w-full h-full object-cover"
              alt="Project image"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100">
               <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
            </div>
            <div class="absolute top-3 right-3">
              <span class="px-2 py-1 bg-white/90 backdrop-blur rounded-full text-[10px] font-bold text-emerald-700 shadow-sm uppercase tracking-wider">
                {{ p.type }}
              </span>
            </div>
          </div>
          
          <div class="p-5">
            <h3 class="font-bold text-slate-800 text-lg mb-1">{{ p.name }}</h3>
            <p class="text-slate-500 text-xs line-clamp-2 mb-4">{{ p.description }}</p>
            
            <div class="space-y-2">
              <div class="flex justify-between text-[11px] font-bold">
                <span class="text-emerald-600">₦ {{ formatMoney(p.raised_amount) }} raised</span>
                <span class="text-slate-400">Target: ₦ {{ formatMoney(p.target_amount) }}</span>
              </div>
              <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-emerald-500 transition-all duration-500" 
                  :style="{ width: getProgress(p) + '%' }"
                ></div>
              </div>
              <div class="text-right">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ getProgress(p) }}% Complete</span>
              </div>
            </div>
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

const projects = ref([])
const loading = ref(false)

const fetchProjects = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/sadaqah/projects')
    projects.value = data || []
  } catch (e) {
    console.error('Failed to load sadaqah projects', e)
  } finally {
    loading.value = false
  }
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const getProgress = (p) => {
  if (!p.target_amount || p.target_amount <= 0) return 0
  const pct = (Number(p.raised_amount) / Number(p.target_amount)) * 100
  return Math.min(100, Math.round(pct))
}

const getImageUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http')) return url
  return `${axios.defaults.baseURL}/storage/${url}`
}

onMounted(fetchProjects)
</script>
