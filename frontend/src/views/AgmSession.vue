<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <button class="text-sm font-bold text-emerald-700" @click="$router.back()">Back</button>
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">AGM Session</h1>
      <div />
    </header>

    <div class="p-4 space-y-6">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Positions & Candidates</h2>
          <button class="text-xs font-bold text-emerald-700" @click="load" :disabled="loading">Refresh</button>
        </div>
        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!positions.length" class="text-slate-500 text-sm">No candidates available.</div>
          <div v-else class="space-y-6">
            <div v-for="pos in positions" :key="pos.position" class="bg-white border rounded-xl shadow-sm">
              <div class="p-4 border-b flex items-center justify-between">
                <div>
                  <div class="font-black text-slate-800">{{ pos.position }}</div>
                  <div class="text-[11px] text-slate-500" v-if="pos.voted_candidate_id">
                    You voted: <strong>{{ votedName(pos) }}</strong>
                  </div>
                </div>
                <div>
                  <span v-if="pos.voted_candidate_id" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase">Voted</span>
                </div>
              </div>
              <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="c in pos.candidates" :key="c.id" class="p-3 border rounded-lg flex gap-3 items-start">
                  <img v-if="c.photo_url" :src="getImageUrl(c.photo_url)" alt="photo" class="w-12 h-12 rounded object-cover" />
                  <div class="flex-1">
                    <div class="font-bold text-slate-800">{{ c.name }}</div>
                    <p class="text-[12px] text-slate-600 whitespace-pre-line">{{ c.manifesto || '—' }}</p>
                    <div class="mt-2">
                      <button
                        class="px-3 py-2 rounded-lg text-xs font-bold"
                        :class="canVote(pos, c) ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                        :disabled="!canVote(pos, c) || voting"
                        @click="cast(c)">
                        Vote {{ c.name }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Live Results</h2>
          <div class="flex items-center gap-2">
            <button class="bg-white hover:bg-slate-50 px-3 py-2 rounded-lg text-xs font-bold border border-slate-200 shadow-sm" @click="loadResults" :disabled="resLoading">
              Refresh Results
            </button>
          </div>
        </div>
        <div v-if="resLoading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="resError" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ resError }}</div>
        <div v-else>
          <div v-if="!Object.keys(results).length" class="text-slate-500 text-sm">No results yet.</div>
          <div v-else class="space-y-4">
            <div v-for="(list, pos) in results" :key="pos" class="bg-white border rounded-xl">
              <div class="p-3 font-black text-slate-800 border-b">{{ pos }}</div>
              <ul class="divide-y">
                <li v-for="row in list" :key="row.candidate_id" class="p-3 flex items-center justify-between">
                  <div class="font-medium text-slate-700">{{ row.candidate_name }}</div>
                  <div class="text-xs">
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-black">{{ row.votes }} vote{{ row.votes === 1 ? '' : 's' }}</span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1" @click="$router.push('/agm')">
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
import { useRoute } from 'vue-router'
import axios from '../http'
import getImageUrl from '../utils/image'

const route = useRoute()
const id = Number(route.params.id)

const loading = ref(false)
const error = ref('')
const positions = ref([])
const session = ref(null)

const voting = ref(false)

const resLoading = ref(false)
const resError = ref('')
const results = ref({})

const votedName = (pos) => {
  const cid = pos?.voted_candidate_id
  if (!cid) return ''
  const c = (pos?.candidates || []).find(x => x.id === cid)
  return c?.name || ''
}

const canVote = (pos, cand) => !pos?.voted_candidate_id && !!cand?.id

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get(`/api/agm/sessions/${id}/candidates`, { headers: { Authorization: `Bearer ${token}` } })
    session.value = data?.session || null
    positions.value = Array.isArray(data?.positions) ? data.positions : []
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const cast = async (cand) => {
  if (!cand?.id) return
  if (!confirm(`Are you sure you want to vote for ${cand.name}? This cannot be changed.`)) return
  voting.value = true
  try {
    const token = localStorage.getItem('token')
    await axios.post(`/api/agm/sessions/${id}/vote`, { candidate_id: cand.id }, { headers: { Authorization: `Bearer ${token}` } })
    alert('Vote recorded!')
    await load()
    await loadResults()
  } catch (e) {
    alert(e?.response?.data?.message || e.message || 'Failed to cast vote')
  } finally {
    voting.value = false
  }
}

const loadResults = async () => {
  resLoading.value = true
  resError.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get(`/api/agm/sessions/${id}/results`, { headers: { Authorization: `Bearer ${token}` } })
    results.value = data?.results || {}
  } catch (e) {
    resError.value = e?.response?.data?.message || e.message
  } finally {
    resLoading.value = false
  }
}

onMounted(async () => {
  await load()
  await loadResults()
})
</script>

<style scoped>
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; }
.section-title { font-weight: 800; color: #0f172a; }
</style>
