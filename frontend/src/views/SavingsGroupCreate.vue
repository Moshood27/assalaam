<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <AppHeader title="New Savings Group" :showBack="true" />

    <div class="p-4">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100">
        <form @submit.prevent="createGroup" class="space-y-6">
          <div class="space-y-1">
            <label class="text-xs font-black text-slate-400 uppercase tracking-widest px-1">Group Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="e.g. Family Hajj 2026" 
              class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all"
              required
            />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-black text-slate-400 uppercase tracking-widest px-1">Purpose (Optional)</label>
            <textarea 
              v-model="form.purpose" 
              rows="3" 
              placeholder="What are you saving for?" 
              class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all"
            ></textarea>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-black text-slate-400 uppercase tracking-widest px-1">Monthly Contribution (₦)</label>
            <input 
              v-model.number="form.monthly_contribution_amount" 
              type="number" 
              step="100"
              class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all"
              required
            />
            <p class="text-[10px] text-slate-400 px-1 mt-1 font-medium italic">Members will be charged this amount automatically every month.</p>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-black text-slate-400 uppercase tracking-widest px-1">Invest in Project (Halal)</label>
            <div v-if="loadingProjects" class="p-4 text-center text-xs text-slate-400 italic">Loading projects...</div>
            <select 
              v-else
              v-model="form.project_id" 
              class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all appearance-none"
            >
              <option :value="null">General Cooperative Fund</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <p class="text-[10px] text-slate-400 px-1 mt-1 font-medium italic">Total group savings will be invested in the selected project.</p>
          </div>

          <div class="pt-4">
            <button 
              type="submit" 
              :disabled="submitting"
              class="w-full bg-indigo-600 text-white p-5 rounded-3xl font-black uppercase tracking-widest text-xs shadow-xl shadow-indigo-100 active:scale-95 transition-all disabled:opacity-50"
            >
              <span v-if="submitting">Creating...</span>
              <span v-else>Create Group</span>
            </button>
          </div>
        </form>
      </div>
    </div>
    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import { useRouter } from 'vue-router'
import axios from '../http.js'

const router = useRouter()
const projects = ref([])
const loadingProjects = ref(false)
const submitting = ref(false)

const form = ref({
  name: '',
  purpose: '',
  monthly_contribution_amount: 10000,
  project_id: null
})

const fetchProjects = async () => {
  loadingProjects.value = true
  try {
    const { data } = await axios.get('/api/savings-groups/projects')
    projects.value = data || []
  } catch (e) {
    console.error('Failed to load projects', e)
  } finally {
    loadingProjects.value = false
  }
}

const createGroup = async () => {
  submitting.value = true
  try {
    const { data } = await axios.post('/api/savings-groups', form.value)
    router.push(`/savings-groups/${data.group.id}`)
  } catch (e) {
    console.error('Failed to create group', e)
    alert(e.response?.data?.message || 'Failed to create group. Please try again.')
  } finally {
    submitting.value = false
  }
}

onMounted(fetchProjects)
</script>


