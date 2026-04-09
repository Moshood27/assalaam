<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="p-2 -ml-2 hover:bg-slate-100 rounded-full transition-colors" aria-label="Go back">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Projects</h1>
        <div class="w-10" />
      </div>
    </header>

    <div class="p-4 space-y-3">
      <div v-if="loading" class="text-center text-slate-500 py-10">Loading projects...</div>
      <div v-else-if="projects.length === 0" class="text-center text-slate-500 py-10">No active projects at the moment.</div>

      <button
        v-for="p in projects"
        :key="p.id"
        class="w-full text-left bg-white p-4 rounded-2xl border border-slate-100 shadow-sm hover:shadow transition flex items-center justify-between gap-3"
        @click="$router.push(`/projects/${p.id}`)"
      >
        <div>
          <p class="font-bold text-slate-800">{{ p.name }}</p>
          <p class="text-[11px] text-slate-500 mt-1">
            Mgmt fee: <span class="font-semibold text-slate-700">{{ Number(p.management_fee_percent || 0).toLocaleString() }}%</span>
            <span v-if="p.target_amount" class="ml-2">Target: ₦{{ Number(p.target_amount).toLocaleString() }}</span>
          </p>
          <p class="text-[11px] text-slate-500 mt-1">
            <span v-if="p.started_at">Started: {{ formatDate(p.started_at) }}</span>
            <span v-if="p.closed_at" class="ml-2">Closed: {{ formatDate(p.closed_at) }}</span>
          </p>
        </div>
        <span :class="p.active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'" class="px-2 py-1 rounded-full text-[10px] font-black uppercase">{{ p.active ? 'Active' : 'Closed' }}</span>
      </button>
    </div>

    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active" @click="$router.push('/projects')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2h8l4 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8l4-6Z"/><path d="M12 2v6"/></svg>
        <span class="text-[10px] font-bold">Projects</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/pay')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8h10M12 12h10M12 16h10"/><path d="M5.88 5.88 9 9m6 6 3.12 3.12M5.88 18.12 9 15m6-6 3.12-3.12"/></svg>
        <span class="text-[10px] font-bold">Pay</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http.js'

const projects = ref([])
const loading = ref(false)

const fetchProjects = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/projects')
    projects.value = data || []
  } catch (e) {
    console.error('Failed to load projects', e)
    projects.value = []
  } finally {
    loading.value = false
  }
}

const formatDate = (d) => {
  if (!d) return ''
  try { return new Date(d).toLocaleDateString() } catch (_) { return d }
}

onMounted(fetchProjects)
</script>
