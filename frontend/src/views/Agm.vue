<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">AGM & Voting</h1>
      <button class="text-sm font-bold text-emerald-700" @click="$router.push('/dashboard')">Back</button>
    </header>

    <div class="p-4 space-y-4">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Active Sessions</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Latest</span>
        </div>
        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!sessions.length" class="text-slate-500 text-sm">No active sessions at the moment.</div>
          <ul class="space-y-3">
            <li v-for="s in sessions" :key="s.id" class="p-4 bg-white border rounded-xl shadow-sm flex items-start justify-between gap-3">
              <div>
                <div class="font-bold text-slate-800">{{ s.name || s.title || ('AGM #' + s.id) }}</div>
                <div class="text-[11px] text-slate-500">{{ s.description || '—' }}</div>
                <div class="text-[11px] text-slate-500 mt-1">
                  <span v-if="s.start_at">Starts: {{ formatDate(s.start_at) }}</span>
                  <span v-if="s.end_at"> · Ends: {{ formatDate(s.end_at) }}</span>
                </div>
                <div class="mt-1">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                        :class="s.status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ s.status }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold"
                        @click="$router.push({ name: 'agm.session', params: { id: s.id } })">
                  Enter
                </button>
              </div>
            </li>
          </ul>
        </div>
      </section>

      <section class="text-[12px] text-slate-500 px-1">
        Note: You can vote once per position. Your selections are final.
      </section>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1">
        <span class="text-xl">🗳️</span>
        <span class="text-[10px] font-bold">AGM</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/reports')">
        <span class="text-xl">📈</span>
        <span class="text-[10px] font-bold">Reports</span>
      </button>
    </nav>
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
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; }
.section-title { font-weight: 800; color: #0f172a; }
</style>
