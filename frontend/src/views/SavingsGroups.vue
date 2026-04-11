<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.push('/dashboard')" class="p-2 -ml-2 hover:bg-slate-100 rounded-full transition-colors" aria-label="Go back">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800 uppercase tracking-tight">Group Savings</h1>
        <button @click="$router.push('/savings-groups/create')" class="p-2 -mr-2 text-emerald-600 hover:bg-emerald-50 rounded-full transition-colors" title="Create Group">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        </button>
      </div>
    </header>

    <div class="p-4">
      <div class="bg-gradient-to-br from-indigo-600 to-blue-700 p-6 rounded-[2rem] text-white shadow-lg mb-6 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full blur-xl" />
        <h2 class="text-xl font-bold mb-1">Jama'ah Savings</h2>
        <p class="text-indigo-50 text-xs opacity-90 leading-relaxed mb-4">
          Ajo/Esusu/Pardna - Form small groups with friends or family, save together monthly, and invest in Halal cooperative projects.
        </p>
        <div class="flex gap-2">
           <span class="px-2 py-1 bg-white/20 rounded-lg text-[10px] font-bold uppercase tracking-widest">Family Hajj Group</span>
           <span class="px-2 py-1 bg-white/20 rounded-lg text-[10px] font-bold uppercase tracking-widest">Poultry Farm</span>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex bg-slate-200/50 p-1 rounded-2xl mb-6">
        <button 
          @click="activeTab = 'my'" 
          :class="activeTab === 'my' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'"
          class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
        >
          My Groups
        </button>
        <button 
          @click="activeTab = 'invitations'" 
          :class="activeTab === 'invitations' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'"
          class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all relative"
        >
          Invitations
          <span v-if="invitations.length > 0" class="absolute -top-1 -right-1 w-2 h-2 bg-rose-500 rounded-full"></span>
        </button>
        <button 
          @click="activeTab = 'discover'" 
          :class="activeTab === 'discover' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'"
          class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
        >
          Discover
        </button>
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
        <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-medium">Loading groups...</p>
      </div>

      <div v-else>
        <div v-if="activeTab === 'my'">
          <div v-if="myGroups.length === 0" class="text-center py-16 bg-white rounded-[2.5rem] border border-dashed border-slate-200">
            <div class="text-5xl mb-4">🤝</div>
            <p class="text-slate-500 text-sm mb-6">You haven't joined any group yet.</p>
            <button @click="activeTab = 'discover'" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-100">Browse Groups</button>
          </div>
          <div v-else class="space-y-4">
            <GroupCard 
              v-for="group in myGroups" 
              :key="group.id" 
              :group="group" 
              @click="$router.push(`/savings-groups/${group.id}`)"
            />
          </div>
        </div>

        <div v-if="activeTab === 'invitations'">
          <div v-if="invitations.length === 0" class="text-center py-16 bg-white rounded-[2.5rem] border border-dashed border-slate-200">
            <div class="text-5xl mb-4">📧</div>
            <p class="text-slate-500 text-sm">No pending invitations.</p>
          </div>
          <div v-else class="space-y-4">
            <GroupCard 
              v-for="group in invitations" 
              :key="group.id" 
              :group="group" 
              is-invitation
              @click="$router.push(`/savings-groups/${group.id}`)"
            />
          </div>
        </div>

        <div v-if="activeTab === 'discover'">
          <div v-if="discoverGroups.length === 0" class="text-center py-16 bg-white rounded-[2.5rem] border border-dashed border-slate-200">
            <div class="text-5xl mb-4">🌍</div>
            <p class="text-slate-500 text-sm">No public groups available right now.</p>
          </div>
          <div v-else class="space-y-4">
            <GroupCard 
              v-for="group in discoverGroups" 
              :key="group.id" 
              :group="group" 
              @click="$router.push(`/savings-groups/${group.id}`)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '../http.js'
import GroupCard from '../components/GroupCard.vue'

const activeTab = ref('my')
const myGroups = ref([])
const discoverGroups = ref([])
const invitations = ref([])
const loading = ref(false)

const fetchInvitations = async () => {
  try {
    const { data } = await axios.get('/api/savings-groups/invitations')
    invitations.value = data || []
  } catch (e) {
    console.error('Failed to load invitations', e)
  }
}

const fetchMyGroups = async () => {
  loading.value = true
  fetchInvitations() // silent fetch
  try {
    const { data } = await axios.get('/api/savings-groups')
    myGroups.value = data || []
  } catch (e) {
    console.error('Failed to load my groups', e)
  } finally {
    loading.value = false
  }
}

const fetchDiscoverGroups = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/savings-groups/discover')
    discoverGroups.value = data || []
  } catch (e) {
    console.error('Failed to load discoverable groups', e)
  } finally {
    loading.value = false
  }
}

watch(activeTab, (newTab) => {
  if (newTab === 'my') fetchMyGroups()
  else if (newTab === 'invitations') fetchInvitations()
  else fetchDiscoverGroups()
})

onMounted(() => {
  fetchMyGroups()
})
</script>
