<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans text-slate-900">
    <AppHeader title="Sharia Board" :showBack="true" />

    <div class="p-4 max-w-2xl mx-auto space-y-6">
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-600 mb-4"></div>
        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Loading Board...</p>
      </div>

      <div v-else-if="error" class="card p-6 border-rose-100 bg-rose-50">
        <p class="text-rose-700 text-sm font-medium">{{ error }}</p>
        <button @click="load" class="mt-4 text-xs font-black uppercase text-rose-700 underline">Try Again</button>
      </div>

      <div v-else class="space-y-4">
        <div class="card p-6 bg-emerald-700 text-white overflow-hidden relative">
          <div class="relative z-10">
            <h2 class="text-2xl font-black mb-2">Sharia Governance</h2>
            <p class="text-emerald-50 text-xs leading-relaxed opacity-90 mb-4">
              Our cooperative operates under the guidance of our esteemed Sharia Supervisory Board to ensure all transactions and project investments are 100% Sharia compliant.
            </p>
            <router-link to="/sharia-board/history" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 transition-colors px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-sm border border-white/10">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              My Tahkim (Disputes)
            </router-link>
          </div>
          <div class="absolute -right-10 -bottom-10 opacity-10">
             <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-40 h-40">
               <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71L12 2z" />
             </svg>
          </div>
        </div>

        <div v-if="!members.length" class="text-center py-20">
           <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
             </svg>
           </div>
           <p class="text-slate-400 text-sm">Board members directory is being updated.</p>
        </div>

        <div v-for="m in members" :key="m.id" class="card p-5 flex flex-col sm:flex-row gap-5 hover:border-emerald-200 transition-colors">
          <div class="w-20 h-20 bg-slate-100 rounded-2xl flex-shrink-0 overflow-hidden border border-slate-100 shadow-sm self-center sm:self-start">
             <img v-if="m.photo_url" :src="getImageUrl(m.photo_url)" class="w-full h-full object-cover" />
             <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12">
                  <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                </svg>
             </div>
          </div>
          <div class="flex-1 text-center sm:text-left">
            <h3 class="font-black text-slate-800 text-lg leading-tight">{{ m.name }}</h3>
            <p class="text-emerald-600 text-xs font-black uppercase tracking-wider mb-3">{{ m.title || 'Member' }}</p>
            <div v-if="m.bio" class="text-sm text-slate-500 leading-relaxed" v-html="m.bio"></div>
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
import axios from '../http'
import getImageUrl from '../utils/image'

const members = ref([])
const loading = ref(false)
const error = ref('')

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get('/api/sharia-board')
    members.value = data
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
