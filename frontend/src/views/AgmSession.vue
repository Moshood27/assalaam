<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <AppHeader title="AGM Session" :showBack="true" />

    <div class="p-4 space-y-6 max-w-2xl mx-auto">
      <!-- Feature Disabled Alert -->
      <div v-if="appStatusStore.features['shura-voting-active'] === false" class="card card-elevated p-8 rounded-[2rem] text-center space-y-4 shadow-sm">
        <div class="w-20 h-20 bg-indigo-100 rounded-[2.5rem] flex items-center justify-center mx-auto text-4xl shadow-inner">
          🔒
        </div>
        <div>
          <h3 class="text-xl font-black text-slate-800">Voting Restricted</h3>
          <p class="text-sm text-slate-500 mt-2 leading-relaxed px-4">
            Shura Council voting is currently restricted for your account or the session is inactive. 
            Ensure your account is verified and you meet the minimum Attaqwa Score requirements.
          </p>
        </div>
        <button @click="$router.back()" class="w-full bg-slate-800 text-white p-4 rounded-2xl font-bold active:scale-95 transition-all">
          Go Back
        </button>
      </div>

      <template v-else>
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-6">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Positions & Candidates</h2>
          <button class="text-xs font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl hover:bg-emerald-100 transition-colors" @click="load" :disabled="loading">
            {{ loading ? '...' : 'Refresh' }}
          </button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-700"></div>
        </div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!positions.length" class="text-slate-400 text-sm text-center py-12 italic">No candidates available at this time.</div>
          <div v-else class="space-y-8">
            <div v-for="pos in positions" :key="pos.position" class="bg-slate-50 border border-slate-100 rounded-[2rem] overflow-hidden">
              <div class="p-5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div>
                  <div class="font-black text-slate-800 tracking-tight">{{ pos.position }}</div>
                  <div class="text-[11px] text-emerald-600 font-bold mt-0.5" v-if="pos.voted_candidate_id">
                    You voted: <span class="uppercase tracking-tighter">{{ votedName(pos) }}</span>
                  </div>
                </div>
                <div>
                  <span v-if="pos.voted_candidate_id" class="px-3 py-1 rounded-full bg-emerald-500 text-white text-[9px] font-black uppercase tracking-widest shadow-sm">Voted</span>
                </div>
              </div>
              <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="c in pos.candidates" :key="c.id" class="p-4 bg-white border border-slate-100 rounded-2xl flex gap-4 items-start shadow-sm hover:border-emerald-200 transition-colors">
                  <div class="relative">
                    <img v-if="c.photo_url" :src="getImageUrl(c.photo_url)" alt="photo" class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-50 shadow-sm" />
                    <div v-else class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 border-2 border-slate-50">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                      </svg>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-black text-slate-800 truncate mb-1">{{ c.name }}</div>
                    <p class="text-[11px] text-slate-500 line-clamp-3 leading-relaxed mb-3">{{ c.manifesto || 'No manifesto available' }}</p>
                    <button
                      class="w-full py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                      :class="canVote(pos, c) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-100 active:scale-95' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                      :disabled="!canVote(pos, c) || voting"
                      @click="cast(c)">
                      Vote
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-6">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Live Results</h2>
          <div v-if="participation" class="text-right flex flex-col items-end">
             <div class="text-[9px] font-black uppercase text-slate-400">Participation</div>
             <div class="text-[11px] font-black" :class="participation.quorum_met ? 'text-emerald-600' : 'text-amber-600'">
               {{ participation.total_cast }} / {{ participation.total_eligible }}
               <span v-if="participation.minimum_quorum" class="opacity-60 text-[9px]"> (Q: {{ participation.minimum_quorum }})</span>
             </div>
          </div>
          <button class="text-xs font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl hover:bg-emerald-100 transition-colors" @click="loadResults" :disabled="resLoading">
            {{ resLoading ? '...' : 'Refresh' }}
          </button>
        </div>

        <div v-if="resLoading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-700"></div>
        </div>
        <div v-else-if="resError" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ resError }}</div>
        <div v-else>
          <div v-if="!Object.keys(results).length" class="text-slate-400 text-sm text-center py-12 italic">No results recorded yet.</div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="(list, pos) in results" :key="pos" class="bg-slate-50 border border-slate-100 rounded-3xl overflow-hidden">
              <div class="p-4 bg-white border-b border-slate-100 font-black text-slate-800 text-sm uppercase tracking-tight">{{ pos }}</div>
              <ul class="divide-y divide-slate-100">
                <li v-for="row in list" :key="row.candidate_id" class="p-4 flex items-center justify-between hover:bg-white transition-colors">
                  <div class="flex items-center gap-2 min-w-0">
                    <div class="font-bold text-slate-700 text-sm truncate">{{ row.candidate_name }}</div>
                    <span v-if="row.is_tied" class="px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-700 text-[8px] font-black uppercase tracking-tighter shadow-sm">Tie</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-emerald-700 font-black text-[10px] shadow-sm">{{ row.votes }}</span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">votes</span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
      </template>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'
import { useAppStatusStore } from '../stores/appStatus'
import getImageUrl from '../utils/image'

const appStatusStore = useAppStatusStore()
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
const participation = ref(null)

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
    if (data.features) {
      appStatusStore.setFeatures(data.features)
    }
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
    participation.value = data?.participation || null
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
</style>
