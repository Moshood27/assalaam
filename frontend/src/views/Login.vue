<template>
  <div class="min-h-screen auth-bg relative flex items-center justify-center p-4 overflow-hidden">
    <!-- Decorative fintech gradient blobs -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -top-24 -right-20 w-72 h-72 bg-gradient-to-br from-blue-400/25 to-sky-400/25 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-28 -left-16 w-80 h-80 bg-gradient-to-tr from-blue-300/20 to-indigo-300/20 rounded-full blur-3xl"></div>
    </div>
    <div class="w-full max-w-md relative">
      <!-- Background glow effect -->
      <div aria-hidden="true" class="pointer-events-none absolute -inset-1 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 rounded-[2.5rem] blur-2xl opacity-50"></div>

      <div class="card card-elevated relative overflow-hidden p-8 sm:p-10 bg-white/90 backdrop-blur-2xl border border-white/80 shadow-2xl rounded-[2rem]">
        <!-- Top accent gradient line -->
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-blue-400 via-indigo-500 to-blue-400 opacity-80"></div>

        <div class="flex flex-col items-center text-center mb-8">
          <div class="mb-4 transform hover:scale-105 transition-transform duration-300">
            <img :src="brand.logo" :alt="brand.name" class="h-20 sm:h-24 w-auto drop-shadow-sm" />
          </div>
<!--          <p class="text-[10px] font-bold tracking-[0.2em] text-blue-800 uppercase opacity-80 mb-1">{{ brand.name }}</p>-->
          <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Assalamu Alaikum</h1>
          <p class="text-slate-500 text-sm mt-2 font-medium">Securely access your membership account</p>
        </div>

        <div class="space-y-6">
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Your Branch</label>
            <SearchableSelect
              v-model="form.branch_id"
              :items="branches"
              placeholder="Select your branch"
              searchPlaceholder="Search branches..."
            />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Membership Number / Phone Number</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-blue-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
              </span>
              <input v-model="form.membership_number" type="text" placeholder="e.g. 052286 or 08012345678" class="input pl-12 h-14 text-lg font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Secure Password</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-blue-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="input pl-12 pr-14 h-14 text-lg font-semibold bg-slate-50/50 border-slate-200/60" />
              <button @click="showPassword = !showPassword" type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors p-1" aria-label="Toggle password visibility">
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

          <div class="flex items-center gap-3 px-1">
            <label class="relative inline-flex items-center cursor-pointer select-none">
              <input v-model="form.remember" type="checkbox" class="sr-only peer" />
              <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
              <span class="ml-3 text-sm font-medium text-slate-600">Remember Me</span>
            </label>
          </div>

          <div v-if="biometricSupported" class="flex items-center gap-3 px-1">
            <label class="relative inline-flex items-center cursor-pointer select-none">
              <input v-model="rememberWithBiometrics" type="checkbox" class="sr-only peer" />
              <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
              <span class="ml-3 text-sm font-medium text-slate-600">Enable biometric login</span>
            </label>
          </div>

          <div class="space-y-4 pt-2">
            <button @click="handleLogin" :disabled="loading" class="w-full h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-500/30 shadow-lg shadow-blue-500/20 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="loading" class="inline-block animate-spin border-3 border-white/30 border-t-white rounded-full w-6 h-6 mr-2 align-middle"></span>
              <span>{{ loading ? 'Signing in...' : 'Sign In' }}</span>
            </button>

            <button v-if="biometricSupported && canBiometricQuickLogin" @click="handleQuickLogin" :disabled="quickLoading" class="w-full h-14 text-lg bg-blue-50 hover:bg-blue-100/80 text-blue-700 border-2 border-blue-100 rounded-2xl font-bold flex items-center justify-center gap-3 transition-all active:scale-[0.98] disabled:opacity-50">
              <div v-if="quickLoading" class="inline-block animate-spin border-3 border-blue-600/30 border-t-blue-600 rounded-full w-6 h-6"></div>
              <template v-else>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0112 3c1.268 0 2.39.246 3.411.691m1.273 1.273a10.014 10.014 0 011.564 3.076M12 11a10.014 10.014 0 01-1.564-3.076M12 11c0-1.268.246-2.39.691-3.411m1.273-1.273c.445-.445.963-.82 1.536-1.109m-4.04 12.064l.054.09A10.003 10.003 0 0012 21c1.268 0 2.39-.246 3.411-.691m1.273-1.273a10.014 10.014 0 001.564-3.076M12 21v-1m0-11V7m0 11v-1m0-11V7m0 11v-1m0-11V7" />
                </svg>
                <span>Quick Biometric Login</span>
              </template>
            </button>
          </div>

          <p v-if="error" class="text-center p-3 bg-rose-50 rounded-xl text-rose-600 text-sm font-medium animate-pulse">{{ error }}</p>
        </div>

        <p class="mt-8 text-[12px] text-center text-slate-400 leading-relaxed px-4">
          By signing in you agree to our
          <router-link to="/policy" class="text-blue-700 font-bold hover:underline">Terms</router-link>
          and
          <router-link to="/privacy" class="text-blue-700 font-bold hover:underline">Privacy Policy</router-link>.
        </p>
      </div>

      <div class="mt-8 text-center text-sm text-slate-500 space-y-3 font-medium relative">
        <p>
          <router-link to="/forgot" class="text-blue-700 font-bold hover:text-blue-800 flex items-center justify-center gap-1">
            <span>Forgot password?</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </router-link>
        </p>
        <div class="w-12 h-px bg-slate-200 mx-auto"></div>
        <p>New to the Cooperative?
          <router-link to="/register" class="text-blue-700 font-bold hover:text-blue-800 ml-1">Create membership</router-link>
        </p>
        <div class="px-6 py-4 bg-blue-50/40 rounded-2xl border border-blue-100/40 text-slate-600 text-[13px] leading-relaxed max-w-[280px] mx-auto">
          Finding it difficult to sign in or want to know more about our Cooperative?
          <br />
          <button @click="showSupportModal = true" class="text-blue-700 font-bold hover:text-blue-800 inline-flex items-center justify-center gap-1 mt-2 w-full">
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
      <div class="relative w-full max-w-md bg-slate-50 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-300">
        <div class="p-6 bg-white border-b flex items-center justify-between">
          <h2 class="text-xl font-bold text-slate-800">Contact Support</h2>
          <button @click="showSupportModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 transition-colors">✖</button>
        </div>
        <div class="p-6">
          <SupportContacts />
          <div class="mt-6 text-center">
            <router-link to="/support" class="text-sm font-bold text-blue-700 hover:underline">View full support page</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http.js'
import { Capacitor } from '@capacitor/core'
import { useRouter, useRoute } from 'vue-router'
import SearchableSelect from '../components/SearchableSelect.vue'
import SupportContacts from '../components/SupportContacts.vue'
import brand from '../brand'
import { getBiometricAvailabilityDetails, canQuickLogin as canQuickLoginSvc, quickLoginViaBiometric, storeBiometricCredentials } from '../services/biometric'

const router = useRouter()
const route = useRoute()
const branches = ref([])
const loading = ref(false)
const showPassword = ref(false)
const error = ref('')
const showSupportModal = ref(false)

const biometricSupported = ref(false)
const canBiometricQuickLogin = ref(false)
const rememberWithBiometrics = ref(false)
const quickLoading = ref(false)

const form = ref({
  branch_id: '',
  membership_number: '',
  password: '',
  remember: false
})

onMounted(async () => {
  console.log('LOGIN PAGE MOUNTED')

  const lastBranchId = localStorage.getItem('last_branch_id')
  const rememberedMembership = localStorage.getItem('remembered_membership_number')
  const rememberChoice = localStorage.getItem('remember_me_choice') === 'true'

  if (rememberChoice && rememberedMembership) {
    form.value.membership_number = rememberedMembership
    form.value.remember = true
  }

  // 1. Load branches first
  try {
    const { data } = await axios.get('/api/branches')
    // Always keep branches in ascending order by name as requested
    branches.value = (data || []).sort((a, b) => (a.name || '').localeCompare(b.name || '', undefined, { numeric: true, sensitivity: 'base' }))

    // Pre-select last branch if exists (without re-ordering the list)
    if (lastBranchId && !form.value.branch_id) {
      const found = (data || []).find(b => String(b.id) === String(lastBranchId))
      if (found) {
        form.value.branch_id = found.id
      }
    }
  } catch (e) { console.error(e) }

  // 2. WAIT for the system to be clear before checking Biometrics
  // We use a longer delay (2.5s) to ensure the Notification popup is gone
  setTimeout(async () => {
    console.log('Checking biometrics now that system dialogs are likely gone...')
    try {
      const result = await getBiometricAvailabilityDetails()
      console.log('Biometric Result:', result)

      if (result?.isAvailable) {
        biometricSupported.value = true
        canBiometricQuickLogin.value = await canQuickLoginSvc()
      } else {
        biometricSupported.value = false
        // If Error Code is -2, it might still be a timing issue.
        if (result?.errorCode === -2) {
          console.warn('Hardware reported unavailable (Error -2).')
        }
      }
    } catch (err) {
      console.error('Biometric check crashed:', err)
    }
  }, 2500)
})

const afterLogin = async (token, user) => {
  localStorage.setItem('token', token)
  if (user) {
    localStorage.setItem('user_id', user.id)
    localStorage.setItem('is_admin', user.is_admin ? 'true' : 'false')
  }

  // If we have a pending push token captured earlier, flush it now that we're authenticated
  try {
    const pending = localStorage.getItem('pending_push_token')
    if (pending) {
      await axios.post('/api/push/token', { token: pending, platform: (Capacitor?.getPlatform?.() || 'web').toString() }, { timeout: Math.max(30000, Number(axios.defaults.timeout) || 0) })
      localStorage.removeItem('pending_push_token')
    }
  } catch (e) {
    console.warn('Failed to flush pending push token after login:', e?.message || e)
  }

  const redirect = route.query.redirect || '/dashboard'
  router.push(redirect)
}

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.post('/api/login', form.value)

    // If user opted in and biometrics are supported, store membership + branch + password
    if (rememberWithBiometrics.value && biometricSupported.value) {
      try {
        await storeBiometricCredentials({
          membership_number: form.value.membership_number,
          branch_id: form.value.branch_id,
          password: form.value.password,
        })
      } catch (_) {}
    }

    // Remember last branch for "Auto arrange" on next login
    if (form.value.branch_id) {
      localStorage.setItem('last_branch_id', String(form.value.branch_id))
    }

    // Handle "Remember Me" for membership number
    if (form.value.remember) {
      localStorage.setItem('remembered_membership_number', form.value.membership_number)
      localStorage.setItem('remember_me_choice', 'true')
    } else {
      localStorage.removeItem('remembered_membership_number')
      localStorage.setItem('remember_me_choice', 'false')
    }

    afterLogin(data.token, data.user)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Login Failed'
  } finally {
    loading.value = false
  }
}

const handleQuickLogin = async () => {
  quickLoading.value = true
  error.value = ''
  try {
    const { ok, error: err } = await quickLoginViaBiometric()
    if (!ok) throw new Error(err || 'Biometric login failed')
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
  } catch (e) {
    error.value = e?.message || 'Biometric login failed'
  } finally {
    quickLoading.value = false
  }
}
</script>


