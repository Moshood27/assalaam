<template>
  <div class="min-h-screen auth-bg relative flex items-center justify-center p-4 overflow-hidden">
    <!-- Decorative fintech gradient blobs -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -top-24 -right-20 w-72 h-72 bg-gradient-to-br from-emerald-400/25 to-sky-400/25 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-28 -left-16 w-80 h-80 bg-gradient-to-tr from-emerald-300/20 to-indigo-300/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative">
      <!-- Background glow effect -->
      <div aria-hidden="true" class="pointer-events-none absolute -inset-1 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 rounded-[2.5rem] blur-2xl opacity-50"></div>

      <div class="card card-elevated relative overflow-hidden p-8 sm:p-10 bg-white/90 backdrop-blur-2xl border border-white/80 shadow-2xl rounded-[2.5rem]">
        <!-- Top accent gradient line -->
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-400 via-teal-500 to-emerald-400 opacity-80"></div>

        <div class="flex flex-col items-center text-center mb-8">
          <div class="w-20 h-20 rounded-[1.75rem] bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-3xl shadow-xl shadow-emerald-500/20 mb-4 transform hover:scale-105 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Recover Account</h1>
          <p class="text-slate-500 text-sm mt-2 font-medium">Get a secure reset code to your preferred channel</p>
        </div>

        <div class="space-y-6">
          <!-- Step 1: Request code -->
          <div v-if="step === 1" class="space-y-6">
            <div class="space-y-3">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Delivery Method</label>
              <div class="flex p-1 bg-slate-100 rounded-2xl">
                <button @click="channel = 'email'" type="button" :class="['flex-1 py-3 rounded-xl font-bold text-sm transition-all', channel==='email' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                  Email
                </button>
                <button @click="channel = 'sms'" type="button" :class="['flex-1 py-3 rounded-xl font-bold text-sm transition-all', channel==='sms' ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                  SMS
                </button>
              </div>
            </div>

            <div v-if="channel === 'email'" class="relative group">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Email Address</label>
              <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </span>
                <input v-model="requestForm.email" type="email" placeholder="you@example.com" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
              </div>
            </div>

            <div v-else class="space-y-6 animate-in fade-in slide-in-from-top-4 duration-300">
              <div class="relative group">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Phone Number</label>
                <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </span>
                  <input v-model="requestForm.phone" type="tel" placeholder="e.g. 0803 123 4567" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
                </div>
              </div>

              <div class="relative py-2">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                  <div class="w-full border-t border-slate-100"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase font-black tracking-widest text-slate-300">
                  <span class="bg-white px-3">or identify via branch & membership</span>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Your Branch</label>
                  <SearchableSelect
                    v-model="requestForm.branch_id"
                    :items="branches"
                    placeholder="Choose your branch"
                    searchPlaceholder="Search branches…"
                  />
                </div>
                <div class="relative group">
                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Membership Number / Phone Number</label>
                  <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                      </svg>
                    </span>
                    <input v-model="requestForm.membership_number" type="text" placeholder="e.g. 052286 or 08012345678" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
                  </div>
                </div>
              </div>
            </div>

            <button @click="handleRequest" :disabled="loading" class="w-full h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] disabled:opacity-50">
              <span v-if="loading" class="inline-block animate-spin border-3 border-white/30 border-t-white rounded-full w-6 h-6 mr-2 align-middle"></span>
              <span>{{ loading ? 'Sending…' : 'Send Reset Code' }}</span>
            </button>

            <div v-if="success" class="p-3 bg-emerald-50 rounded-xl text-emerald-700 text-sm font-bold text-center animate-bounce">{{ success }}</div>
            <div v-if="error" class="p-3 bg-rose-50 rounded-xl text-rose-600 text-sm font-medium text-center animate-pulse">{{ error }}</div>

            <div class="text-center">
              <router-link class="text-emerald-700 font-bold hover:text-emerald-800 flex items-center justify-center gap-1" to="/login">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                <span>Back to Login</span>
              </router-link>
            </div>
          </div>

          <!-- Step 2: Enter code and new password -->
          <div v-else class="space-y-6">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 text-sm">
              <div class="font-bold text-emerald-800 mb-1">Secure code sent!</div>
              <div class="text-emerald-700">Check your {{ channel === 'email' ? 'email' : 'SMS' }}. Code expires in 10 mins.</div>
            </div>

            <div class="relative group">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">6‑digit Code</label>
              <input v-model="resetForm.code" type="text" maxlength="6" inputmode="numeric" placeholder="000000" class="input h-14 text-center text-2xl font-black tracking-[0.5em] bg-slate-50/50 border-slate-200/60" />
            </div>

            <div class="relative group">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">New Secure Password</label>
              <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </span>
                <input v-model="resetForm.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
                <button @click="showPassword = !showPassword" type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors p-1" aria-label="Toggle password visibility">
                  <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.076m3.313-3.313A9.959 9.959 0 0112 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-1.447 0-2.811-.31-4.04-.864m1.107-1.107l1.107-1.107m2.774-2.774l.553-.553m2.21-2.21l.553-.553" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="relative group">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Confirm New Password</label>
              <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </span>
                <input v-model="resetForm.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
              </div>
            </div>

            <button @click="handleReset" :disabled="loading" class="w-full h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] disabled:opacity-50">
              <span v-if="loading" class="inline-block animate-spin border-3 border-white/30 border-t-white rounded-full w-6 h-6 mr-2 align-middle"></span>
              <span>{{ loading ? 'Resetting…' : 'Reset Password' }}</span>
            </button>

            <div v-if="success" class="p-3 bg-emerald-50 rounded-xl text-emerald-700 text-sm font-bold text-center">{{ success }}</div>
            <div v-if="error" class="p-3 bg-rose-50 rounded-xl text-rose-600 text-sm font-medium text-center animate-pulse">{{ error }}</div>

            <div class="text-center">
              <button @click="backToRequest" class="text-[11px] font-black text-emerald-700 uppercase tracking-widest hover:underline">Resend or change method</button>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center text-sm text-slate-500 space-y-4 font-medium px-4 relative">
        <div class="px-6 py-4 bg-emerald-50/40 rounded-2xl border border-emerald-100/40 text-slate-600 text-[13px] leading-relaxed max-w-[280px] mx-auto">
          Finding it difficult to reset your password or want to know more about our Cooperative?
          <br />
          <button @click="showSupportModal = true" class="text-emerald-700 font-bold hover:text-emerald-800 inline-flex items-center justify-center gap-1 mt-2 w-full">
            <span>Contact Support</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Public Support Modal (Immediate Help) -->
    <div v-if="showSupportModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showSupportModal = false"></div>
      <div class="relative w-full max-w-md bg-slate-50 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-300 text-left">
        <div class="p-6 bg-white border-b flex items-center justify-between">
          <h2 class="text-xl font-bold text-slate-800">Contact Support</h2>
          <button @click="showSupportModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 transition-colors">✕</button>
        </div>
        <div class="p-6">
          <SupportContacts />
          <div class="mt-6 text-center">
            <router-link to="/support" class="text-sm font-bold text-emerald-700 hover:underline">View full support page</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http.js'
import SearchableSelect from '../components/SearchableSelect.vue'
import SupportContacts from '../components/SupportContacts.vue'

const showSupportModal = ref(false)
const branches = ref([])
const loading = ref(false)
const error = ref('')
const success = ref('')
const channel = ref('email')
const step = ref(1)
const showPassword = ref(false)

const requestForm = ref({
  email: '',
  phone: '',
  branch_id: '',
  membership_number: ''
})

const resetForm = ref({
  code: '',
  password: '',
  password_confirmation: ''
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/branches')
    // Ensure branches are in ascending order by name
    branches.value = (data || []).sort((a, b) => (a.name || '').localeCompare(b.name || ''))
  } catch (_) {}
})

const handleRequest = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const payload = { channel: channel.value }
    if (channel.value === 'email') {
      payload.email = requestForm.value.email
    } else {
      if (requestForm.value.phone) {
        payload.phone = requestForm.value.phone
      } else {
        payload.branch_id = requestForm.value.branch_id
        payload.membership_number = requestForm.value.membership_number
      }
    }
    const { data } = await axios.post('/api/forgot-password', payload)
    success.value = data?.message || 'If the account exists, a reset code has been sent.'
    step.value = 2
  } catch (e) {
    error.value = e?.response?.data?.message || 'Could not send reset code'
  } finally {
    loading.value = false
  }
}

const handleReset = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const payload = { code: resetForm.value.code, password: resetForm.value.password, password_confirmation: resetForm.value.password_confirmation }
    if (channel.value === 'email') {
      payload.email = requestForm.value.email
    } else {
      if (requestForm.value.phone) {
        payload.phone = requestForm.value.phone
      } else {
        payload.branch_id = requestForm.value.branch_id
        payload.membership_number = requestForm.value.membership_number
      }
    }
    const { data } = await axios.post('/api/reset-password', payload)
    success.value = data?.message || 'Password has been reset. You can now login.'
  } catch (e) {
    error.value = e?.response?.data?.message || 'Could not reset password'
  } finally {
    loading.value = false
  }
}

const backToRequest = () => {
  step.value = 1
  error.value = ''
  success.value = ''
  resetForm.value = { code: '', password: '', password_confirmation: '' }
}
</script>
