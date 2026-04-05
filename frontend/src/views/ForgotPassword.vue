<template>
  <div class="min-h-screen auth-bg relative flex items-center justify-center p-4 overflow-hidden">
    <div class="w-full max-w-md">
      <div class="card card-elevated relative overflow-hidden p-6 sm:p-8 bg-white/80 backdrop-blur-xl border border-white/60 shadow-xl">
        <div aria-hidden="true" class="absolute inset-x-0 -top-px h-px bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"></div>
        <div class="flex flex-col items-center text-center mb-6">
          <div class="w-16 h-16 rounded-2xl bg-emerald-600 flex items-center justify-center text-white text-2xl shadow-lg">🔑</div>
          <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold text-slate-900">Reset your password</h1>
          <p class="text-slate-500 text-sm mt-1">Receive a reset code by email or SMS</p>
        </div>

        <div class="space-y-5">
          <!-- Step 1: Request code -->
          <div v-if="step === 1" class="space-y-4">
            <div>
              <label class="form-label">Delivery Method</label>
              <div class="flex gap-2">
                <button @click="channel = 'email'" type="button" :class="['px-4 h-10 rounded-lg border', channel==='email' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-200']">Email</button>
                <button @click="channel = 'sms'" type="button" :class="['px-4 h-10 rounded-lg border', channel==='sms' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-200']">SMS</button>
              </div>
            </div>

            <div v-if="channel === 'email'">
              <label class="form-label">Email</label>
              <input v-model="requestForm.email" type="email" placeholder="you@example.com" class="input" />
            </div>

            <div v-else class="space-y-3">
              <details class="rounded-lg border border-slate-200 bg-slate-50/60 p-3">
                <summary class="cursor-pointer text-sm text-slate-700">Use phone number (recommended)</summary>
                <div class="mt-3">
                  <label class="form-label">Phone Number</label>
                  <input v-model="requestForm.phone" type="tel" placeholder="e.g. 0803 123 4567" class="input" />
                  <p class="text-xs text-slate-500 mt-1">We'll send the code to this number if it matches your account.</p>
                </div>
              </details>

              <div class="text-center text-xs text-slate-400">— OR —</div>

              <div>
                <label class="form-label">Branch</label>
                <SearchableSelect
                  v-model="requestForm.branch_id"
                  :items="branches"
                  label="Select Branch"
                  placeholder="Choose your branch"
                  searchPlaceholder="Search branches…"
                />
              </div>
              <div>
                <label class="form-label">Membership Number</label>
                <input v-model="requestForm.membership_number" type="text" placeholder="e.g. 052286" class="input" />
              </div>
            </div>

            <button @click="handleRequest" :disabled="loading" class="btn-primary w-full h-12 text-base">
              <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
              <span>{{ loading ? 'Sending…' : 'Send reset code' }}</span>
            </button>

            <p v-if="success" class="text-center text-emerald-700 text-sm">{{ success }}</p>
            <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>

            <div class="text-xs text-center">
              <router-link class="text-emerald-700 font-semibold hover:underline" to="/login">Back to login</router-link>
            </div>
          </div>

          <!-- Step 2: Enter code and new password -->
          <div v-else class="space-y-4">
            <div class="rounded-lg border border-emerald-200/60 bg-emerald-50/50 p-3 text-sm text-emerald-800">
              <strong>Check your {{ channel === 'email' ? 'email' : 'SMS' }}.</strong>
              <div class="mt-1">We sent a 6‑digit code. It expires in 10 minutes.</div>
            </div>

            <div>
              <label class="form-label">6‑digit Code</label>
              <input v-model="resetForm.code" type="text" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="123456" class="input" />
            </div>
            <div>
              <label class="form-label">New Password</label>
              <input v-model="resetForm.password" :type="showPassword ? 'text' : 'password'" placeholder="Enter new password" class="input pr-12" />
              <button @click="showPassword = !showPassword" type="button" class="-mt-10 float-right mr-3 text-gray-400 hover:text-slate-600" aria-label="Toggle password visibility">
                <span v-if="showPassword">🙈</span>
                <span v-else>👁️</span>
              </button>
            </div>
            <div>
              <label class="form-label">Confirm Password</label>
              <input v-model="resetForm.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Confirm new password" class="input" />
            </div>

            <button @click="handleReset" :disabled="loading" class="btn-primary w-full h-12 text-base">
              <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
              <span>{{ loading ? 'Resetting…' : 'Reset password' }}</span>
            </button>

            <p v-if="success" class="text-center text-emerald-700 text-sm">{{ success }}</p>
            <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>

            <div class="text-xs text-center">
              <button @click="backToRequest" class="text-emerald-700 font-semibold hover:underline">Resend or change method</button>
            </div>
          </div>
        </div>
      </div>

      <p class="mt-6 text-center text-xs text-slate-500">Need help? <router-link to="/support" class="text-emerald-700 font-semibold hover:underline">Contact Support</router-link></p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http.js'
import SearchableSelect from '../components/SearchableSelect.vue'

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
    branches.value = data?.map?.(b => ({ value: b.id, label: b.name })) || []
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
