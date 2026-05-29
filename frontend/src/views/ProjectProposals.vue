<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <AppHeader title="Project Proposals" :showBack="true" />

    <div class="p-4 space-y-4 max-w-md mx-auto">
      <!-- Action: Submit New Proposal -->
      <section v-if="!showForm" class="card card-elevated p-5 bg-blue-600 text-white border-0 shadow-lg shadow-blue-200">
        <h2 class="font-black tracking-tight text-lg mb-1">Submit Investment Idea</h2>
        <p class="text-xs text-blue-100 mb-4 leading-relaxed">Have a profitable idea for the cooperative? Submit a proposal for Sharia review and member voting.</p>
        <button @click="showForm = true" class="w-full py-3 bg-white text-blue-700 rounded-2xl font-black uppercase tracking-widest text-[11px] shadow-sm hover:bg-blue-50 transition-colors">
          Submit New Proposal
        </button>
      </section>

      <!-- Proposal Form -->
      <section v-if="showForm" class="card card-elevated p-5 space-y-4">
        <div class="flex items-center justify-between mb-2">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">New Proposal</h2>
          <button @click="showForm = false" class="text-[10px] font-bold uppercase text-slate-400">Cancel</button>
        </div>
        <div class="space-y-3">
          <div>
            <label class="block text-[10px] font-black uppercase text-slate-500 mb-1 ml-1">Title</label>
            <input v-model="form.title" type="text" placeholder="e.g. Fish Farm Investment" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all" />
          </div>
          <div>
            <label class="block text-[10px] font-black uppercase text-slate-500 mb-1 ml-1">Description</label>
            <textarea v-model="form.description" rows="4" placeholder="Detail your idea, expected costs, and returns..." class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all"></textarea>
          </div>
          <div>
            <label class="block text-[10px] font-black uppercase text-slate-500 mb-1 ml-1">Target Amount (Optional)</label>
            <input v-model="form.target_amount" type="number" placeholder="0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all" />
          </div>
        </div>
        <button @click="submit" :disabled="submitting" class="w-full py-3 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest text-[11px] shadow-md shadow-blue-100 active:scale-95 transition-all disabled:opacity-50">
          {{ submitting ? 'Submitting...' : 'Submit Proposal' }}
        </button>
      </section>

      <!-- Proposals List -->
      <section class="card card-elevated p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-black text-slate-800 tracking-tight text-lg">Current Proposals</h2>
          <button @click="load" class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-2 py-1 rounded">Refresh</button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-700"></div>
        </div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-100 p-4 rounded-2xl text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!proposals.length" class="text-slate-400 text-sm text-center py-12 italic">No proposals found.</div>
          <ul class="space-y-4">
            <li v-for="p in proposals" :key="p.id" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex flex-col gap-3 hover:border-blue-200 transition-colors cursor-pointer" @click="$router.push({ name: 'agm.proposal_detail', params: { id: p.id } })">
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                  <div class="font-bold text-slate-800 leading-tight mb-1">{{ p.title }}</div>
                  <div class="text-[11px] text-slate-500 line-clamp-2">{{ p.description }}</div>
                </div>
                <div class="flex flex-col items-end gap-1">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter"
                        :class="statusClass(p.status)">
                    {{ p.status }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter"
                        :class="shariaClass(p.sharia_status)">
                    {{ p.sharia_status }}
                  </span>
                </div>
              </div>
              <div class="flex items-center justify-between mt-1 pt-3 border-t border-slate-100">
                <div class="text-[9px] text-slate-400 font-bold uppercase">By {{ p.user?.name || 'Unknown' }}</div>
                <div v-if="p.target_amount" class="text-[10px] font-black text-blue-700">Ã¢â€šÂ¦ {{ Number(p.target_amount).toLocaleString() }}</div>
              </div>
            </li>
          </ul>
        </div>
      </section>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'

const loading = ref(false)
const error = ref('')
const proposals = ref([])
const showForm = ref(false)
const submitting = ref(false)

const form = ref({
  title: '',
  description: '',
  target_amount: null
})

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

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get('/api/project-proposals')
    proposals.value = data.data || []
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  if (!form.value.title || !form.value.description) {
    alert('Please fill in both title and description.')
    return
  }
  submitting.value = true
  try {
    await axios.post('/api/project-proposals', form.value)
    alert('Proposal submitted successfully! It will undergo Sharia review.')
    showForm.value = false
    form.value = { title: '', description: '', target_amount: null }
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to submit proposal')
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>


