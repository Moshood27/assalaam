<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <AppHeader title="Project Details" :showBack="true" />

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4"></div>
      <p class="text-sm font-medium">Loading project...</p>
    </div>

    <div v-else-if="!project" class="text-center py-20">
      <p class="text-slate-500">Project not found.</p>
    </div>

    <div v-else class="pb-20">
      <div class="h-64 bg-slate-200 relative overflow-hidden">
        <template v-if="project.media_urls && project.media_urls.length">
          <video 
            v-if="isVideo(project.media_urls[0])" 
            :src="getImageUrl(project.media_urls[0])" 
            class="w-full h-full object-cover"
            controls
            autoplay
            muted
            loop
          ></video>
          <img 
            v-else
            :src="getImageUrl(project.media_urls[0])" 
            class="w-full h-full object-cover"
            alt="Project image"
          />
        </template>
        <div v-else class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100">
           <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
        </div>
      </div>

      <div class="px-4 -mt-10 relative z-10">
        <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-50">
          <div class="flex justify-between items-start mb-4">
            <h2 class="text-2xl font-black text-slate-800 leading-tight">{{ project.name }}</h2>
            <span class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-100">
              {{ project.type }}
            </span>
          </div>

          <div class="prose prose-sm text-slate-600 mb-8 whitespace-pre-wrap">
            {{ project.description }}
          </div>

          <div v-if="project.media_urls && project.media_urls.length > 1" class="mb-8">
            <p class="text-[10px] text-slate-400 font-bold uppercase mb-3 tracking-widest">Project Gallery</p>
            <div class="flex gap-3 overflow-x-auto pb-2 -mx-2 px-2 no-scrollbar snap-x">
              <div v-for="(media, index) in project.media_urls" :key="index" class="min-w-[140px] aspect-square bg-slate-100 rounded-2xl overflow-hidden snap-start border border-slate-50 shadow-sm">
                <video v-if="isVideo(media)" :src="getImageUrl(media)" class="w-full h-full object-cover" controls></video>
                <img v-else :src="getImageUrl(media)" class="w-full h-full object-cover" />
              </div>
            </div>
          </div>

          <div class="space-y-4 pt-4 border-t border-slate-50">
            <div class="flex justify-between items-end">
              <div>
                <p class="text-[10px] text-slate-400 font-bold uppercase">Raised so far</p>
                <p class="text-2xl font-black text-emerald-600">₦ {{ formatMoney(project.raised_amount) }}</p>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase">Target</p>
                <p class="text-sm font-bold text-slate-600">₦ {{ formatMoney(project.target_amount) }}</p>
              </div>
            </div>

            <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden">
              <div 
                class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-1000" 
                :style="{ width: getProgress() + '%' }"
              ></div>
            </div>
            
            <p class="text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">
              {{ getProgress() }}% Complete
            </p>
          </div>
        </div>
      </div>

      <div v-if="project.contributions && project.contributions.length" class="mt-8 px-4">
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4 px-2">Recent Benefactors</h3>
        <div class="space-y-2">
          <div v-for="c in project.contributions" :key="c.id" class="bg-white p-4 rounded-2xl flex items-center gap-3 border border-slate-50 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold">
               {{ (c.user?.name || 'A')[0] }}
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-800">{{ c.user?.name || 'Anonymous' }}</p>
              <p class="text-[10px] text-slate-400">{{ formatDate(c.created_at) }}</p>
            </div>
            <p class="text-sm font-black text-emerald-600">₦ {{ formatMoney(c.amount) }}</p>
          </div>
        </div>
      </div>

      <div class="mt-8 px-4">
        <div v-if="project.active" class="bg-slate-800 rounded-[2rem] p-6 text-white shadow-xl">
          <h3 class="text-lg font-bold mb-4">Make a Contribution</h3>
          
          <div class="space-y-4">
            <div class="flex justify-between items-center mb-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase">Amount (NGN)</label>
              <div v-if="balance !== null" class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">
                Wallet: ₦ {{ formatMoney(balance) }}
              </div>
            </div>
            <div>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-500">₦</span>
                <input 
                  type="number" 
                  v-model="form.amount"
                  class="w-full bg-white/10 border border-white/20 rounded-2xl py-4 pl-10 pr-4 text-white font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all"
                  placeholder="0.00"
                />
              </div>
            </div>

            <div class="flex items-center gap-3 py-2">
              <input type="checkbox" id="anon" v-model="form.is_anonymous" class="w-5 h-5 rounded-lg accent-emerald-500" />
              <label for="anon" class="text-sm text-slate-300">Contribute anonymously</label>
            </div>

            <div class="grid grid-cols-3 gap-3 mt-4">
              <button 
                @click="initiateContribution('wallet')"
                :disabled="submitting || !form.amount || (balance !== null && form.amount > balance)"
                class="bg-white text-slate-900 rounded-2xl py-4 font-black text-[9px] uppercase tracking-tighter hover:bg-emerald-50 active:scale-95 transition-all disabled:opacity-50"
              >
                Wallet
              </button>
              <button 
                @click="initiateContribution('paystack')"
                :disabled="submitting || !form.amount"
                class="bg-emerald-600 text-white rounded-2xl py-4 font-black text-[9px] uppercase tracking-tighter hover:bg-emerald-700 active:scale-95 transition-all disabled:opacity-50"
              >
                Paystack
              </button>
              <button 
                @click="initiateContribution('flutterwave')"
                :disabled="submitting || !form.amount"
                class="bg-teal-600 text-white rounded-2xl py-4 font-black text-[9px] uppercase tracking-tighter hover:bg-teal-700 active:scale-95 transition-all disabled:opacity-50"
              >
                Flutterwave
              </button>
            </div>
            <p class="text-[9px] text-center text-slate-500 mt-4 px-4 uppercase tracking-tighter leading-tight">
              By contributing, you agree to our terms and conditions. May your contribution be rewarded.
            </p>
          </div>
        </div>
        <div v-else class="bg-emerald-600 rounded-[2rem] p-8 text-white shadow-xl text-center">
           <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
           </div>
           <h3 class="text-xl font-black uppercase tracking-widest">Project Completed</h3>
           <p class="text-emerald-50 text-sm mt-2 font-medium">Jazakallah Khair to all who contributed. The physical work is now complete. Scroll up to see the proof of impact.</p>
        </div>
      </div>
    </div>
    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http.js'

const route = useRoute()
const project = ref(null)
const loading = ref(false)
const submitting = ref(false)
const balance = ref(null)

const form = ref({
  amount: '',
  is_anonymous: false
})

const fetchProfile = async () => {
  try {
    const { data } = await axios.get('/api/dashboard')
    balance.value = data.balance
  } catch (e) {
    console.warn('Failed to load profile for balance', e)
  }
}

const fetchProject = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/sadaqah/projects/${route.params.id}`)
    project.value = data
  } catch (e) {
    console.error('Failed to load project details', e)
  } finally {
    loading.value = false
  }
}

const initiateContribution = async (gateway) => {
  if (!form.value.amount || form.value.amount < 1) return
  
  submitting.value = true
  try {
    const { data } = await axios.post(`/api/sadaqah/projects/${route.params.id}/contribute`, {
      amount: form.value.amount,
      is_anonymous: form.value.is_anonymous,
      gateway: gateway,
      callback_url: window.location.origin + '/payment-callback'
    })
    
    if (data.authorization_url) {
      window.location.href = data.authorization_url
    } else if (data.success) {
      alert(data.message || 'Contribution successful. Jazakallah Khair.')
      form.value.amount = ''
      fetchProject()
      fetchProfile()
    }
  } catch (e) {
    console.error('Failed to initiate contribution', e)
    alert(e.response?.data?.message || 'Failed to initiate payment. Please try again.')
  } finally {
    submitting.value = false
  }
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const getProgress = () => {
  if (!project.value || !project.value.target_amount || project.value.target_amount <= 0) return 0
  const pct = (Number(project.value.raised_amount) / Number(project.value.target_amount)) * 100
  return Math.min(100, Math.round(pct))
}

const isVideo = (url) => {
  if (!url) return false
  const ext = url.split('.').pop().toLowerCase()
  return ['mp4', 'webm', 'ogg', 'mov'].includes(ext)
}

const getImageUrl = (url) => {
  if (!url) return ''
  if (url.startsWith('http')) return url
  return `${axios.defaults.baseURL}/storage/${url}`
}

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
}

onMounted(() => {
  fetchProject()
  fetchProfile()
})
</script>
