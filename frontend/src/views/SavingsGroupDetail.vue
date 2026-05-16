<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <AppHeader :title="group.name || 'Group Detail'" :showBack="true" />

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
      <p class="text-sm font-medium">Loading group details...</p>
    </div>

    <div v-else class="p-4 space-y-6">
      <!-- Group Header Card -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100 relative overflow-hidden text-center">
        <div class="w-20 h-20 bg-indigo-50 rounded-[2rem] flex items-center justify-center text-4xl mx-auto mb-4">
          {{ group.icon || '🤝' }}
        </div>
        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ group.name }}</h2>
        <p class="text-slate-500 text-xs mt-1 font-medium">{{ group.purpose || 'Community savings & investment group' }}</p>
        
        <div class="mt-6 flex items-center justify-center gap-4">
          <div class="text-center">
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Monthly</p>
            <p class="text-lg font-bold text-slate-800">₦ {{ formatMoney(group.monthly_contribution_amount) }}</p>
          </div>
          <div class="w-px h-8 bg-slate-100"></div>
          <div class="text-center">
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Members</p>
            <p class="text-lg font-bold text-slate-800">{{ group.active_members_count }}</p>
          </div>
        </div>

        <div class="mt-6 pt-6 border-t border-slate-50">
          <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-2">Target Investment</p>
          <div 
            v-if="group.project" 
            class="inline-flex items-center gap-2 bg-indigo-50 px-4 py-2 rounded-2xl cursor-pointer hover:bg-indigo-100 transition-colors"
            @click="$router.push(`/projects/${group.project_id}`)"
          >
            <span class="text-xs font-bold text-indigo-700 uppercase">{{ group.project.name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-400"><path d="m9 18 6-6-6-6"/></svg>
          </div>
          <span v-else class="text-xs font-bold text-slate-400 uppercase">General Fund</span>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-emerald-600 rounded-[2rem] p-5 text-white shadow-lg shadow-emerald-100">
          <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-1">Total Group Value</p>
          <p class="text-lg font-bold">₦ {{ formatMoney(stats.total_contributions) }}</p>
        </div>
        <div class="bg-slate-900 rounded-[2rem] p-5 text-white shadow-lg shadow-slate-200">
          <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-1">My Contribution</p>
          <p class="text-lg font-bold">₦ {{ formatMoney(stats.my_contributions) }}</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="space-y-3">
        <div v-if="isMember" class="flex flex-col gap-3">
          <div class="flex gap-3">
            <button 
              @click="showContributeModal = true"
              class="flex-1 bg-indigo-600 text-white p-5 rounded-3xl font-black uppercase tracking-widest text-xs shadow-xl shadow-indigo-100 active:scale-95 transition-all"
            >
              Contribute Now
            </button>
            <button 
              v-if="!isCreator"
              @click="leaveGroup"
              class="px-6 bg-white text-rose-500 border border-rose-100 p-5 rounded-3xl font-black uppercase tracking-widest text-xs active:scale-95 transition-all"
            >
              Leave
            </button>
          </div>
          
          <div v-if="isCreator" class="flex gap-3">
            <button 
              @click="showInviteModal = true"
              class="flex-1 bg-slate-900 text-white p-5 rounded-3xl font-black uppercase tracking-widest text-xs active:scale-95 transition-all"
            >
              Invite Member
            </button>
            <button 
              v-if="stats.total_contributions === 0"
              @click="dissolveGroup"
              class="px-6 bg-white text-rose-500 border border-rose-100 p-5 rounded-3xl font-black uppercase tracking-widest text-xs active:scale-95 transition-all"
            >
              Dissolve
            </button>
          </div>
        </div>

        <div v-else-if="isPending" class="bg-indigo-50 p-6 rounded-[2rem] text-center border border-indigo-100">
          <p class="text-xs font-bold text-indigo-700 mb-4">You have been invited to join this group!</p>
          <button 
            @click="acceptInvitation"
            class="w-full bg-indigo-600 text-white p-5 rounded-3xl font-black uppercase tracking-widest text-xs shadow-xl shadow-indigo-100 active:scale-95 transition-all"
          >
            Accept Invitation
          </button>
        </div>

        <button 
          v-else 
          @click="joinGroup"
          :disabled="joining"
          class="w-full bg-emerald-600 text-white p-5 rounded-3xl font-black uppercase tracking-widest text-xs shadow-xl shadow-emerald-100 active:scale-95 transition-all"
        >
          {{ joining ? 'Joining...' : 'Join this Group' }}
        </button>
      </div>

      <!-- Members -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Group Members</h3>
          <span class="text-[10px] font-bold text-slate-400">{{ group.active_members?.length || 0 }} total</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <div 
            v-for="m in group.active_members" 
            :key="m.id"
            class="flex items-center gap-2 bg-slate-50 pr-3 rounded-full border border-slate-100"
          >
            <div class="w-7 h-7 bg-indigo-100 rounded-full flex items-center justify-center text-[10px] font-bold text-indigo-700">
              {{ m.user?.name[0] }}
            </div>
            <span class="text-[11px] font-bold text-slate-700">{{ m.user?.name.split(' ')[0] }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Contributions -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100">
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Recent Activity</h3>
        <div v-if="recentContributions.length === 0" class="text-center py-6 text-slate-400 text-xs italic">
          No contributions yet. Be the first!
        </div>
        <div v-else class="space-y-4">
          <div v-for="c in recentContributions" :key="c.id" class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-800">{{ c.user?.name }}</p>
                <p class="text-[10px] text-slate-400">{{ formatDate(c.created_at) }}</p>
              </div>
            </div>
            <p class="text-xs font-black text-emerald-600">+ ₦ {{ formatMoney(c.amount) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contribute Modal -->
    <div v-if="showContributeModal" class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
      <div class="w-full max-w-md bg-white rounded-[2.5rem] p-8 shadow-2xl animate-in slide-in-from-bottom duration-300">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Make Contribution</h3>
          <button @click="showContributeModal = false" class="p-2 bg-slate-50 rounded-full">
             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="bg-indigo-50 p-6 rounded-3xl mb-6 text-center">
          <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Amount Due</p>
          <p class="text-3xl font-black text-indigo-700">₦ {{ formatMoney(group.monthly_contribution_amount) }}</p>
        </div>

        <p class="text-[11px] text-slate-500 mb-6 text-center leading-relaxed">
          Select your preferred payment method to complete your monthly contribution for <b>{{ group.name }}</b>.
        </p>

        <div class="space-y-3">
          <button 
            @click="payViaWallet"
            :disabled="paying"
            class="w-full flex items-center justify-between bg-slate-900 text-white p-5 rounded-3xl active:scale-95 transition-all disabled:opacity-50"
          >
            <div class="flex items-center gap-3">
              <div class="text-xl">💳</div>
              <div class="text-left">
                <p class="text-xs font-bold uppercase tracking-widest">Pay via Wallet</p>
                <p class="text-[10px] opacity-60">Balance: ₦ {{ formatMoney(userBalance) }}</p>
              </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="opacity-40"><path d="m9 18 6-6-6-6"/></svg>
          </button>

          <button 
            v-for="gw in enabledGateways" :key="gw"
            @click="payViaGateway(gw)"
            :disabled="paying"
            class="w-full flex items-center justify-between bg-white border-2 border-slate-100 p-5 rounded-3xl active:scale-95 transition-all disabled:opacity-50"
          >
            <div class="flex items-center gap-3">
              <div class="text-xl">🏦</div>
              <div class="text-left">
                <p class="text-xs font-black text-slate-800 uppercase tracking-widest">Pay via {{ gw }}</p>
                <p class="text-[10px] text-slate-400">Secure online payment</p>
              </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-slate-200"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>

        <button 
          @click="showContributeModal = false"
          class="w-full mt-6 text-xs font-bold text-slate-400 uppercase tracking-widest py-2"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Invite Modal -->
    <div v-if="showInviteModal" class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
      <div class="w-full max-w-md bg-white rounded-[2.5rem] p-8 shadow-2xl animate-in slide-in-from-bottom duration-300">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Invite Member</h3>
          <button @click="showInviteModal = false" class="p-2 bg-slate-50 rounded-full">
             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="space-y-4">
          <p class="text-xs text-slate-500 font-medium">Enter the phone number or membership number of the person you want to invite.</p>
          <input 
            v-model="inviteIdentifier"
            type="text" 
            placeholder="080... or MEMBER/..." 
            class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all"
          />
          <button 
            @click="sendInvite"
            :disabled="inviting"
            class="w-full bg-indigo-600 text-white p-5 rounded-3xl font-black uppercase tracking-widest text-xs shadow-xl shadow-indigo-100 active:scale-95 transition-all disabled:opacity-50"
          >
            {{ inviting ? 'Sending...' : 'Send Invitation' }}
          </button>
        </div>
      </div>
    </div>
    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '../http.js'
import { useAppStatusStore } from '../stores/appStatus'

const route = useRoute()
const router = useRouter()
const appStatusStore = useAppStatusStore()
const group = ref({})
const stats = ref({})
const recentContributions = ref([])
const isMember = ref(false)
const isPending = ref(false)
const isCreator = ref(false)
const loading = ref(true)
const joining = ref(false)
const paying = ref(false)
const inviting = ref(false)
const userBalance = ref(0)
const showContributeModal = ref(false)
const showInviteModal = ref(false)
const inviteIdentifier = ref('')

const enabledGateways = computed(() => {
  const gws = appStatusStore.paymentGateways || {}
  return Object.keys(gws).filter(k => k !== 'primary' && gws[k])
})

const fetchData = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/savings-groups/${route.params.id}`)
    group.value = data.group
    stats.value = data.stats
    isMember.value = data.is_member
    isPending.value = data.is_pending
    isCreator.value = data.is_creator
    recentContributions.value = data.recent_contributions || []

    // Fetch user balance for modal
    const profile = await axios.get('/api/profile')
    userBalance.value = profile.data.balance
  } catch (e) {
    console.error('Failed to load group details', e)
  } finally {
    loading.value = false
  }
}

const joinGroup = async () => {
  joining.value = true
  try {
    await axios.post(`/api/savings-groups/${route.params.id}/join`)
    await fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to join group')
  } finally {
    joining.value = false
  }
}

const leaveGroup = async () => {
  if (!confirm('Are you sure you want to leave this group?')) return
  try {
    await axios.post(`/api/savings-groups/${route.params.id}/leave`)
    await fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to leave group')
  }
}

const acceptInvitation = async () => {
  try {
    await axios.post(`/api/savings-groups/${route.params.id}/accept-invitation`)
    await fetchData()
    alert('Invitation accepted!')
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to accept invitation')
  }
}

const sendInvite = async () => {
  if (!inviteIdentifier.value) return
  inviting.value = true
  try {
    await axios.post(`/api/savings-groups/${route.params.id}/invite`, {
      identifier: inviteIdentifier.value
    })
    inviteIdentifier.value = ''
    showInviteModal.value = false
    alert('Invitation sent!')
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to send invitation')
  } finally {
    inviting.value = false
  }
}

const dissolveGroup = async () => {
  if (!confirm('Are you sure you want to dissolve this group? This cannot be undone.')) return
  try {
    await axios.post(`/api/savings-groups/${route.params.id}/dissolve`)
    router.push('/savings-groups')
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to dissolve group')
  }
}

const payViaWallet = async () => {
  paying.value = true
  try {
    const { data: contribData } = await axios.get(`/api/savings-groups/${route.params.id}/contribution-data`)
    
    const payload = {
      items: [{
        scheme_id: contribData.scheme.id,
        savings_group_id: group.value.id,
        amount: group.value.monthly_contribution_amount
      }],
      pin: '' // Will need a PIN modal or prompt
    }

    if (contribData.group.project?.is_unit_based) {
      payload.items[0].units = Math.floor(group.value.monthly_contribution_amount / contribData.group.project.unit_price)
    }

    // For simplicity in this UI, we might need to ask for PIN
    const pin = prompt('Enter your 4-digit transaction PIN:')
    if (!pin) {
      paying.value = false
      return
    }
    payload.pin = pin
    
    await axios.post('/api/wallet/allocate', payload)
    
    showContributeModal.value = false
    alert('Contribution successful!')
    await fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Wallet payment failed')
  } finally {
    paying.value = false
  }
}

const payViaGateway = async (gateway) => {
  paying.value = true
  try {
    const { data: contribData } = await axios.get(`/api/savings-groups/${route.params.id}/contribution-data`)
    
    const item = {
      scheme_id: contribData.scheme.id,
      savings_group_id: group.value.id,
      amount: group.value.monthly_contribution_amount
    }

    if (contribData.group.project?.is_unit_based) {
      item.units = Math.floor(group.value.monthly_contribution_amount / contribData.group.project.unit_price)
    }

    const { data } = await axios.post('/api/initiate-payment', {
      items: [item],
      gateway: gateway,
      callback_url: window.location.origin + '/payment-callback?gateway=' + gateway
    })
    
    if (data.authorization_url || data.checkout_url) {
      window.location.href = data.authorization_url || data.checkout_url
    } else {
       alert('Failed to initiate payment')
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Payment initiation failed')
  } finally {
    paying.value = false
  }
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(fetchData)
</script>
