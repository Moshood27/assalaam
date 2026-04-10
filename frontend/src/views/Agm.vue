<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.push('/dashboard')" class="text-2xl hover:opacity-70 transition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">AGM & Voting</h1>
        <div class="w-6"></div>
      </div>
    </header>

    <div class="p-4 space-y-4 max-w-md mx-auto">
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Active Sessions</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Live</span>
        </div>
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-700"></div>
        </div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!sessions.length" class="text-slate-400 text-sm text-center py-8 italic">No active sessions at the moment.</div>
          <ul class="space-y-4">
            <li v-for="s in sessions" :key="s.id" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-start justify-between gap-3 hover:border-emerald-200 transition-colors">
              <div class="flex-1">
                <div class="font-bold text-slate-800 leading-tight mb-1">{{ s.name || s.title || ('AGM #' + s.id) }}</div>
                <div class="text-[11px] text-slate-500 line-clamp-2 mb-2">{{ s.description || 'No description available' }}</div>
                <div class="flex flex-wrap gap-2 items-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-tighter"
                        :class="s.status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ s.status }}
                  </span>
                  <span v-if="s.start_at" class="text-[9px] text-slate-400 font-bold uppercase">{{ formatDate(s.start_at) }}</span>
                </div>
              </div>
              <button class="btn-primary px-4 py-2 text-xs"
                      @click="$router.push({ name: 'agm.session', params: { id: s.id } })">
                Enter
              </button>
            </li>
          </ul>
        </div>
      </section>

      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Project Proposals</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Shura</span>
        </div>
        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
          Submit investment ideas or vote on community-proposed projects.
        </p>
        <button class="w-full py-3 bg-slate-800 text-white rounded-2xl font-black uppercase tracking-widest text-[11px] shadow-sm active:scale-95 transition-all"
                @click="$router.push({ name: 'agm.proposals' })">
          View Proposals
        </button>
      </section>

      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Sharia Board</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Gov</span>
        </div>
        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
          Meet the scholars ensuring our operations remain interest-free and ethical.
        </p>
        <button class="w-full py-3 border-2 border-slate-200 text-slate-700 rounded-2xl font-black uppercase tracking-widest text-[11px] shadow-sm active:scale-95 transition-all"
                @click="$router.push({ name: 'sharia.board' })">
          Meet the Scholars
        </button>
      </section>

      <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-600 flex-shrink-0">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <p class="text-[11px] text-emerald-800 font-medium leading-relaxed">
          You can vote once per position. Your selections are final and cannot be reversed after submission.
        </p>
      </div>
    </div>

    <div class="bottom-nav">
      <div class="bottom-nav-inner">
        <button class="nav-item group" @click="$router.push('/dashboard')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
          </svg>
          <span>Home</span>
        </button>
        <button class="nav-item group active">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25v3.375c0 .621.504 1.125 1.125 1.125h3.375M9 15l2.25 2.25L15 12" />
          </svg>
          <span>AGM</span>
        </button>
        <button class="nav-item group" @click="$router.push('/reports')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0017.25 3.75H6.75A2.25 2.25 0 004.5 6v12A2.25 2.25 0 006.75 20.25z" />
          </svg>
          <span>Reports</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(false)
const error = ref('')
const sessions = ref([])

const formatDate = (val) => {
  try { return new Date(val).toLocaleString() } catch (_) { return String(val || '') }
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/agm/sessions', { headers: { Authorization: `Bearer ${token}` } })
    sessions.value = Array.isArray(data) ? data : []
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
</style>
