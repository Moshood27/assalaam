<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.push({ name: 'agm.proposals' })" class="text-2xl hover:opacity-70 transition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Proposal Details</h1>
        <div class="w-6"></div>
      </div>
    </header>

    <div class="p-4 space-y-6 max-w-md mx-auto">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-700"></div>
      </div>
      <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ error }}</div>
      <div v-else-if="proposal">
        <!-- Main Content -->
        <section class="card card-elevated p-6 space-y-4">
          <div class="flex justify-between items-start">
            <h2 class="text-xl font-black text-slate-800 leading-tight">{{ proposal.title }}</h2>
            <div class="flex flex-col items-end gap-1">
              <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter" :class="statusClass(proposal.status)">{{ proposal.status }}</span>
              <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter" :class="shariaClass(proposal.sharia_status)">{{ proposal.sharia_status }}</span>
            </div>
          </div>
          <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{{ proposal.description }}</p>
          
          <div v-if="proposal.fatwa_summary" class="p-3 bg-blue-50 border border-blue-100 rounded-2xl">
            <div class="text-[9px] font-black uppercase text-blue-700 mb-1">Fatwa Summary</div>
            <p class="text-[11px] text-blue-900 leading-relaxed italic">"{{ proposal.fatwa_summary }}"</p>
            <a v-if="proposal.sharia_certificate_path" :href="'/storage/'+proposal.sharia_certificate_path" target="_blank" class="mt-2 inline-flex items-center gap-1 text-[10px] font-black text-blue-700 underline uppercase tracking-widest">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
              Download Fatwa (PDF)
            </a>
          </div>

          <div v-if="proposal.target_amount" class="pt-4 border-t border-slate-100">
            <div class="text-[10px] font-black uppercase text-slate-400">Target Investment Amount</div>
            <div class="text-2xl font-black text-blue-700">Ã¢â€šÂ¦ {{ Number(proposal.target_amount).toLocaleString() }}</div>
          </div>
          <div class="text-[10px] text-slate-400 font-bold uppercase">Proposed by {{ proposal.user?.name }} Ã¢â‚¬Â¢ {{ formatDate(proposal.created_at) }}</div>
        </section>

        <!-- Voting Section -->
        <section v-if="isVotingOpen || myVote || (results.yes + results.no > 0)" class="card card-elevated p-6 space-y-6">
          <div class="flex justify-between items-center">
            <h3 class="font-black text-slate-800 tracking-tight text-lg">Member Voting</h3>
            <div v-if="participation" class="text-right">
               <div class="text-[9px] font-black uppercase text-slate-400">Participation</div>
               <div class="text-[11px] font-black" :class="participation.quorum_met ? 'text-blue-600' : 'text-amber-600'">
                 {{ participation.total_cast }} / {{ participation.total_eligible }}
                 <span v-if="participation.minimum_quorum"> (Quorum: {{ participation.minimum_quorum }})</span>
               </div>
            </div>
          </div>
          
          <!-- Results Visualization -->
          <div class="space-y-4">
            <div v-if="isTie" class="p-2 bg-amber-50 border border-amber-100 rounded-xl text-center text-[10px] font-black uppercase text-amber-700">
              Current Result: Tie (Further Shura required)
            </div>
            <div class="space-y-1">
              <div class="flex justify-between text-[11px] font-black uppercase tracking-wider">
                <span class="text-blue-700">Yes ({{ results.yes.toLocaleString() }})</span>
                <span class="text-slate-400">{{ resultsPercent('yes') }}%</span>
              </div>
              <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-blue-500 transition-all duration-1000" :style="{ width: resultsPercent('yes') + '%' }"></div>
              </div>
            </div>
            <div class="space-y-1">
              <div class="flex justify-between text-[11px] font-black uppercase tracking-wider">
                <span class="text-rose-700">No ({{ results.no.toLocaleString() }})</span>
                <span class="text-slate-400">{{ resultsPercent('no') }}%</span>
              </div>
              <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-rose-500 transition-all duration-1000" :style="{ width: resultsPercent('no') + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- Cast Vote -->
          <div v-if="isVotingOpen && !myVote" class="pt-4 border-t border-slate-100 space-y-3">
            <p class="text-[11px] text-slate-500 text-center font-bold">Cast your vote below. Your vote is weighted by your {{ proposal.voting_type === 'share_percentage' ? 'share percentage' : 'membership' }}.</p>
            <div class="grid grid-cols-2 gap-4">
              <button @click="cast('yes')" :disabled="voting" class="py-4 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-md shadow-blue-100 active:scale-95 transition-all">Yes</button>
              <button @click="cast('no')" :disabled="voting" class="py-4 bg-rose-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-md shadow-rose-100 active:scale-95 transition-all">No</button>
            </div>
          </div>
          <div v-else-if="myVote" class="pt-4 border-t border-slate-100 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-xs font-black uppercase tracking-widest">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
              </svg>
              You voted {{ myVote }}
            </div>
          </div>
        </section>

        <!-- Consultation (Comments) -->
        <section class="card card-elevated p-6 space-y-4">
          <h3 class="font-black text-slate-800 tracking-tight text-lg">Consultation (Shura)</h3>
          
          <div class="space-y-4">
            <div v-for="c in proposal.comments" :key="c.id" class="space-y-1">
              <div class="flex justify-between items-baseline">
                <span class="text-[11px] font-black text-slate-800 uppercase tracking-tight">{{ c.user?.name }}</span>
                <span class="text-[9px] text-slate-400">{{ formatDate(c.created_at) }}</span>
              </div>
              <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-2xl border border-slate-100">{{ c.comment }}</p>
            </div>
            <div v-if="!proposal.comments?.length" class="text-center py-4 text-slate-400 text-xs italic">No comments yet. Start the consultation.</div>
          </div>

          <div class="pt-4 border-t border-slate-100 space-y-2">
            <textarea v-model="comment" rows="2" placeholder="Share your thoughts..." class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all"></textarea>
            <button @click="postComment" :disabled="commenting || !comment.trim()" class="w-full py-2 bg-slate-800 text-white rounded-xl font-black uppercase tracking-widest text-[10px] active:scale-95 transition-all disabled:opacity-50">Post Comment</button>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '../http'

const route = useRoute()
const id = route.params.id
const loading = ref(false)
const error = ref('')
const proposal = ref(null)
const results = ref({ yes: 0, no: 0 })
const isTie = ref(false)
const participation = ref(null)
const myVote = ref(null)
const isVotingOpen = ref(false)
const voting = ref(false)
const comment = ref('')
const commenting = ref(false)

const statusClass = (s) => {
  switch (s) {
    case 'approved': return 'bg-blue-100 text-blue-700'
    case 'voting': return 'bg-blue-100 text-blue-700'
    case 'rejected': return 'bg-rose-100 text-rose-700'
    case 'closed': return 'bg-slate-100 text-slate-700'
    default: return 'bg-amber-100 text-amber-700'
  }
}

const shariaClass = (s) => {
  switch (s) {
    case 'compliant': return 'bg-blue-100 text-blue-700'
    case 'non_compliant': return 'bg-rose-100 text-rose-700'
    default: return 'bg-amber-100 text-amber-700'
  }
}

const resultsPercent = (choice) => {
  const total = results.value.yes + results.value.no
  if (total === 0) return 0
  return Math.round((results.value[choice] / total) * 100)
}

const formatDate = (val) => {
  try { return new Date(val).toLocaleDateString() } catch (_) { return String(val || '') }
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get(`/api/project-proposals/${id}`)
    proposal.value = data.proposal
    results.value = data.results
    isTie.value = data.is_tie
    participation.value = data.participation
    myVote.value = data.my_vote
    isVotingOpen.value = data.is_voting_open
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const cast = async (choice) => {
  if (!confirm(`Are you sure you want to vote ${choice}? This cannot be changed.`)) return
  voting.value = true
  try {
    await axios.post(`/api/project-proposals/${id}/vote`, { choice })
    alert('Vote recorded!')
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to cast vote')
  } finally {
    voting.value = false
  }
}

const postComment = async () => {
  if (!comment.value.trim()) return
  commenting.value = true
  try {
    await axios.post(`/api/project-proposals/${id}/comments`, { comment: comment.value })
    comment.value = ''
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to post comment')
  } finally {
    commenting.value = false
  }
}

onMounted(load)
</script>


