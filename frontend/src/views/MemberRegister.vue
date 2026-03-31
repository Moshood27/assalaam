<template>
  <div class="min-h-screen auth-bg relative flex items-center justify-center p-4 overflow-hidden">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -top-24 -right-20 w-72 h-72 bg-gradient-to-br from-emerald-400/25 to-sky-400/25 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-28 -left-16 w-80 h-80 bg-gradient-to-tr from-emerald-300/20 to-indigo-300/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-2xl">
      <div class="card card-elevated relative overflow-hidden p-6 sm:p-8 bg-white/80 backdrop-blur-xl border border-white/60 shadow-xl">
        <div aria-hidden="true" class="absolute inset-x-0 -top-px h-px bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"></div>

        <div class="flex flex-col items-center text-center mb-6">
          <div class="mb-2">
            <img :src="brand.logo" :alt="brand.name" class="h-14 sm:h-16 w-auto" />
          </div>
          <p class="text-[11px] mt-1 font-semibold tracking-widest text-emerald-700 uppercase">{{ brand.name }}</p>
          <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Join the Cooperative</h1>
          <p class="text-slate-600 text-sm mt-1">Complete your registration, upload documents, and verify your contact details.</p>
        </div>

        <!-- Step indicator -->
        <div class="flex items-center justify-center gap-2 mb-6 text-xs font-semibold">
          <div :class="['px-3 py-1 rounded-full', step >= 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500']">1. Details</div>
          <div class="text-slate-400">→</div>
          <div :class="['px-3 py-1 rounded-full', step >= 2 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500']">2. Documents</div>
          <div class="text-slate-400">→</div>
          <div :class="['px-3 py-1 rounded-full', step >= 3 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500']">3. Verify</div>
          <div class="text-slate-400">→</div>
          <div :class="['px-3 py-1 rounded-full', step >= 4 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500']">4. Complete</div>
        </div>

        <!-- Step 1: Details -->
        <div v-if="step === 1" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <SearchableSelect v-model="form.branch_id" :items="branches" label="Select Branch" placeholder="Choose your branch" searchPlaceholder="Search branches…" />
          </div>
          <div>
            <label class="form-label">Full Name</label>
            <input v-model="form.name" type="text" placeholder="Jane Doe" class="input" />
          </div>
          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" placeholder="you@example.com" class="input" />
          </div>
          <div>
            <label class="form-label">Phone</label>
            <input v-model="form.phone" type="tel" placeholder="0803 123 4567" class="input" />
          </div>
          <div>
            <label class="form-label">Address</label>
            <input v-model="form.address" type="text" placeholder="House/Street/City" class="input" />
          </div>
          <div>
            <label class="form-label">Password</label>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Min 6 characters" class="input" />
          </div>
          <div>
            <label class="form-label">Confirm Password</label>
            <input v-model="form.confirm_password" :type="showPassword ? 'text' : 'password'" placeholder="Re-enter password" class="input" />
          </div>
          <div class="sm:col-span-2 flex items-center gap-3">
            <button @click="handleStart" :disabled="loadingStart" class="btn-primary h-12 px-6">
              <span v-if="loadingStart" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
              <span>{{ loadingStart ? 'Submitting…' : 'Continue' }}</span>
            </button>
            <button @click="goLogin" type="button" class="h-12 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Back to Login</button>
          </div>
          <p v-if="errorStart" class="sm:col-span-2 text-rose-600 text-sm">{{ errorStart }}</p>
        </div>

        <!-- Step 2: Documents -->
        <div v-if="step === 2" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Passport Photo</label>
              <input @change="e => files.passport = e.target.files?.[0] || null" type="file" accept="image/*" class="input" />
              <p class="text-xs text-slate-500 mt-1">JPEG/PNG/WEBP up to 5MB.</p>
            </div>
            <div>
              <label class="form-label">Valid ID Card (NIN/Int'l passport/Driver's License)</label>
              <input @change="e => files.id_card = e.target.files?.[0] || null" type="file" accept="image/*,application/pdf" class="input" />
              <p class="text-xs text-slate-500 mt-1">Image or PDF up to 7MB.</p>
            </div>
            <div class="sm:col-span-2">
              <label class="form-label">Proof of Address (Utility bill, Letter, etc.)</label>
              <input @change="e => files.proof_of_address = e.target.files?.[0] || null" type="file" accept="image/*,application/pdf" class="input" />
              <p class="text-xs text-slate-500 mt-1">Image or PDF up to 7MB.</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button @click="handleUpload" :disabled="loadingUpload" class="btn-primary h-12 px-6">
              <span v-if="loadingUpload" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
              <span>{{ loadingUpload ? 'Uploading…' : 'Upload & Continue' }}</span>
            </button>
            <button @click="() => step = 1" type="button" class="h-12 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Back</button>
          </div>
          <p v-if="errorUpload" class="text-rose-600 text-sm">{{ errorUpload }}</p>
          <div v-if="uploaded.passport_path || uploaded.id_card_path || uploaded.proof_of_address_path" class="text-xs text-emerald-700">Uploaded ✓</div>
        </div>

        <!-- Step 3: Verify -->
        <div v-if="step === 3" class="space-y-5">
          <div class="rounded-xl border border-slate-200 p-4 bg-white/60">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-slate-800">Email Verification</div>
              <div v-if="emailVerified" class="text-emerald-700 text-sm font-semibold">Verified ✓</div>
            </div>
            <p class="text-xs text-slate-600 mt-1">We sent a 6‑digit code to {{ maskedEmail || form.email }}. Enter it below.</p>
            <div class="mt-3 flex items-center gap-3">
              <input v-model="emailCode" :disabled="emailVerified" type="text" inputmode="numeric" maxlength="6" class="input w-40" placeholder="123456" />
              <button @click="handleVerifyEmail" :disabled="emailVerified || loadingVerifyEmail" class="h-11 px-4 rounded-xl bg-emerald-700 text-white font-semibold disabled:opacity-50">
                <span v-if="loadingVerifyEmail" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4"></span>
                <span>Verify</span>
              </button>
            </div>
            <p v-if="errorVerifyEmail" class="text-rose-600 text-sm mt-1">{{ errorVerifyEmail }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 p-4 bg-white/60">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-slate-800">Phone Verification</div>
              <div v-if="phoneVerified" class="text-emerald-700 text-sm font-semibold">Verified ✓</div>
            </div>
            <p class="text-xs text-slate-600 mt-1">We sent a 6‑digit SMS code to {{ maskedPhone || form.phone }}. Enter it below.</p>
            <div class="mt-3 flex items-center gap-3">
              <input v-model="smsCode" :disabled="phoneVerified" type="text" inputmode="numeric" maxlength="6" class="input w-40" placeholder="123456" />
              <button @click="handleVerifySms" :disabled="phoneVerified || loadingVerifySms" class="h-11 px-4 rounded-xl bg-emerald-700 text-white font-semibold disabled:opacity-50">
                <span v-if="loadingVerifySms" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4"></span>
                <span>Verify</span>
              </button>
            </div>
            <p v-if="errorVerifySms" class="text-rose-600 text-sm mt-1">{{ errorVerifySms }}</p>
          </div>

          <div class="flex items-center justify-between">
            <div class="text-xs text-slate-600">Codes expire in: <span class="font-semibold">{{ countdown }}s</span></div>
            <button @click="handleResend" :disabled="resendCooldown > 0" class="text-sm font-semibold text-emerald-700 hover:underline disabled:opacity-50">Resend Codes <span v-if="resendCooldown>0">({{ resendCooldown }})</span></button>
          </div>

          <div class="flex items-center gap-3">
            <button @click="handleFinalize" :disabled="!emailVerified || !phoneVerified || loadingFinalize" class="btn-primary h-12 px-6">
              <span v-if="loadingFinalize" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
              <span>Finish Registration</span>
            </button>
            <button @click="() => step = 2" type="button" class="h-12 px-4 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Back</button>
          </div>
          <p v-if="errorFinalize" class="text-rose-600 text-sm">{{ errorFinalize }}</p>
        </div>

        <!-- Step 4: Complete -->
        <div v-if="step === 4" class="text-center space-y-4">
          <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-600 text-white text-3xl flex items-center justify-center">✓</div>
          <h2 class="text-2xl font-extrabold text-slate-900">You're all set!</h2>
          <p class="text-slate-600">Welcome to the Cooperative. Your membership number is <span class="font-semibold">{{ result.membership_number }}</span>.</p>
          <div class="flex items-center justify-center gap-3">
            <button @click="goLogin" class="h-12 px-6 rounded-xl bg-emerald-700 text-white font-semibold">Proceed to Login</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from '../http.js'
import SearchableSelect from '../components/SearchableSelect.vue'
import brand from '../brand'
import { useRouter } from 'vue-router'

const router = useRouter()

const step = ref(1)
const token = ref(localStorage.getItem('reg_token') || '')
const branches = ref([])

const showPassword = ref(false)
const loadingStart = ref(false)
const errorStart = ref('')
const loadingUpload = ref(false)
const errorUpload = ref('')
const loadingVerifyEmail = ref(false)
const errorVerifyEmail = ref('')
const loadingVerifySms = ref(false)
const errorVerifySms = ref('')
const loadingFinalize = ref(false)
const errorFinalize = ref('')

const form = ref({
  branch_id: '',
  name: '',
  email: '',
  phone: '',
  address: '',
  password: '',
  confirm_password: '',
})

const files = ref({ passport: null, id_card: null, proof_of_address: null })
const uploaded = ref({ passport_path: '', id_card_path: '', proof_of_address_path: '' })

const emailCode = ref('')
const smsCode = ref('')
const emailVerified = ref(false)
const phoneVerified = ref(false)
const maskedEmail = ref('')
const maskedPhone = ref('')
const countdown = ref(600)
let timer = null
const resendCooldown = ref(0)
let resendTimer = null

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/branches')
    branches.value = data
  } catch (e) {}

  if (token.value) {
    // If token exists, assume returning to continue: go to docs step
    step.value = 2
    startTimers()
  }
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
  if (resendTimer) clearInterval(resendTimer)
})

function goLogin() { router.push({ name: 'login' }) }

async function handleStart() {
  loadingStart.value = true
  errorStart.value = ''
  try {
    const { data } = await axios.post('/api/register/start', form.value)
    token.value = data.token
    localStorage.setItem('reg_token', token.value)
    step.value = 2
  } catch (e) {
    errorStart.value = e?.response?.data?.message
      || e?.response?.data?.errors?.email?.[0]
      || e?.response?.data?.errors?.phone?.[0]
      || e?.response?.data?.errors?.branch_id?.[0]
      || 'Could not start application'
  } finally {
    loadingStart.value = false
  }
}

async function handleUpload() {
  if (!files.value.passport || !files.value.id_card || !files.value.proof_of_address) {
    errorUpload.value = 'Please select all required documents before continuing.'
    return
  }
  loadingUpload.value = true
  errorUpload.value = ''
  try {
    const fd = new FormData()
    fd.append('token', token.value)
    fd.append('passport', files.value.passport)
    fd.append('id_card', files.value.id_card)
    fd.append('proof_of_address', files.value.proof_of_address)
    const { data } = await axios.post('/api/register/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    uploaded.value = data.application || {}
    await handleSendOtps()
    step.value = 3
  } catch (e) {
    errorUpload.value = e?.response?.data?.message || 'Upload failed'
  } finally {
    loadingUpload.value = false
  }
}

async function handleSendOtps() {
  try {
    const { data } = await axios.post('/api/register/send-otps', { token: token.value })
    maskedEmail.value = data?.sent_to?.email || ''
    maskedPhone.value = data?.sent_to?.phone || ''
    countdown.value = data?.expires_in || 600
    startTimers()
    startResendCooldown()
  } catch (e) {}
}

function startTimers() {
  if (timer) clearInterval(timer)
  countdown.value = countdown.value || 600
  timer = setInterval(() => {
    if (countdown.value > 0) countdown.value--
    else clearInterval(timer)
  }, 1000)
}
function startResendCooldown() {
  resendCooldown.value = 60
  if (resendTimer) clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    if (resendCooldown.value > 0) resendCooldown.value--
    else clearInterval(resendTimer)
  }, 1000)
}

async function handleVerifyEmail() {
  loadingVerifyEmail.value = true
  errorVerifyEmail.value = ''
  try {
    await axios.post('/api/register/verify-email', { token: token.value, code: emailCode.value })
    emailVerified.value = true
  } catch (e) {
    errorVerifyEmail.value = e?.response?.data?.message || 'Invalid code'
  } finally {
    loadingVerifyEmail.value = false
  }
}

async function handleVerifySms() {
  loadingVerifySms.value = true
  errorVerifySms.value = ''
  try {
    await axios.post('/api/register/verify-sms', { token: token.value, code: smsCode.value })
    phoneVerified.value = true
  } catch (e) {
    errorVerifySms.value = e?.response?.data?.message || 'Invalid code'
  } finally {
    loadingVerifySms.value = false
  }
}

async function handleResend() {
  if (resendCooldown.value > 0) return
  await handleSendOtps()
}

const result = ref({ membership_number: '', branch_id: '' })
async function handleFinalize() {
  loadingFinalize.value = true
  errorFinalize.value = ''
  try {
    const { data } = await axios.post('/api/register/finalize', { token: token.value })
    result.value = data
    localStorage.removeItem('reg_token')
    step.value = 4
  } catch (e) {
    errorFinalize.value = e?.response?.data?.message || 'Could not complete registration'
  } finally {
    loadingFinalize.value = false
  }
}
</script>

<style scoped>
.input { @apply w-full p-3 rounded-xl border border-slate-200 bg-white/90 focus:outline-none focus:ring-2 focus:ring-emerald-600; }
.form-label { @apply block text-sm font-semibold text-slate-700 mb-1; }
.btn-primary { @apply rounded-xl font-semibold text-white bg-gradient-to-r from-emerald-700 to-teal-600 hover:from-emerald-800 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-600 shadow-md disabled:opacity-50 disabled:cursor-not-allowed; }
.card { @apply rounded-2xl bg-white; }
.auth-bg { background: radial-gradient(1200px circle at 0% 0%, rgba(16,185,129,0.06), transparent 40%), radial-gradient(800px circle at 100% 100%, rgba(59,130,246,0.06), transparent 35%); }
</style>
