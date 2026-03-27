<template>
  <div class="min-h-screen auth-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card card-elevated p-6 sm:p-8">
        <div class="flex flex-col items-center text-center mb-6">
          <div class="mb-2">
            <img :src="brand.logo" :alt="brand.name" class="h-16 sm:h-20 w-auto" />
          </div>
          <p class="text-[11px] mt-1 font-semibold tracking-widest text-emerald-700 uppercase">{{ brand.name }}</p>
          <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Member Login</h1>
          <p class="text-slate-500 text-sm mt-1">Securely access your account</p>
        </div>

        <div class="space-y-5">
          <div>
            <SearchableSelect
              v-model="form.branch_id"
              :items="branches"
              label="Select Branch"
              placeholder="Choose your branch"
              searchPlaceholder="Search branches…"
            />
          </div>

          <div>
            <label class="form-label">Membership Number</label>
            <input v-model="form.membership_number" type="text" placeholder="e.g. 052286" class="input" />
          </div>

          <div class="relative">
            <label class="form-label">Password</label>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Enter your password" class="input pr-12" />
            <button @click="showPassword = !showPassword" type="button" class="absolute right-3 top-9 text-gray-400 hover:text-slate-600" aria-label="Toggle password visibility">
              <span v-if="showPassword">🙈</span>
              <span v-else>👁️</span>
            </button>
          </div>

          <div v-if="biometricSupported" class="flex items-center gap-2 text-sm text-slate-600">
            <input id="rememberBio" v-model="rememberWithBiometrics" type="checkbox" class="h-4 w-4" />
            <label for="rememberBio" class="select-none">Remember me with biometrics on this device</label>
          </div>

          <button v-if="biometricSupported && canBiometricQuickLogin" @click="handleQuickLogin" :disabled="quickLoading" class="w-full h-12 text-base bg-white border border-emerald-200 text-emerald-800 rounded-xl font-bold flex items-center justify-center gap-2">
            <span v-if="quickLoading" class="inline-block animate-spin border-2 border-emerald-600 border-t-transparent rounded-full w-5 h-5"></span>
            <span v-else>🔒 Quick Login with Biometrics</span>
          </button>

          <button @click="handleLogin" :disabled="loading" class="btn-primary w-full h-12 text-base">
            <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            <span>{{ loading ? 'Signing in…' : 'Sign in' }}</span>
          </button>

          <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>
        </div>

        <p class="mt-6 text-[11px] text-center text-slate-500">By signing in you agree to our
          <router-link to="/policy" class="text-emerald-700 font-semibold hover:underline">Terms</router-link>
          and
          <router-link to="/privacy" class="text-emerald-700 font-semibold hover:underline">Privacy Policy</router-link>.
        </p>
      </div>

      <p class="mt-6 text-center text-xs text-slate-500">Having trouble?
        <router-link to="/support" class="text-emerald-700 font-semibold hover:underline">Contact Support</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter, useRoute } from 'vue-router'
import SearchableSelect from '../components/SearchableSelect.vue'
import brand from '../brand'
import { getBiometricAvailabilityDetails, canQuickLogin as canQuickLoginSvc, quickLoginViaBiometric, storeBiometricCredentials } from '../services/biometric'

const router = useRouter()
const route = useRoute()
const branches = ref([])
const loading = ref(false)
const showPassword = ref(false)
const error = ref('')

const biometricSupported = ref(false)
const canBiometricQuickLogin = ref(false)
const rememberWithBiometrics = ref(false)
const quickLoading = ref(false)

const form = ref({
  branch_id: '',
  membership_number: '',
  password: ''
})

onMounted(async () => {
  console.log('LOGIN PAGE MOUNTED')

  // 1. Load branches first
  try {
    const { data } = await axios.get('/api/branches')
    branches.value = data
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

const afterLogin = async (token) => {
  localStorage.setItem('token', token)

  // If we have a pending push token captured earlier, flush it now that we're authenticated
  try {
    const pending = localStorage.getItem('pending_push_token')
    if (pending) {
      await axios.post('/api/push/token', { token: pending }, { timeout: Math.max(30000, Number(axios.defaults.timeout) || 0) })
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

    afterLogin(data.token)
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
