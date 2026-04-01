<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="$router.back()" class="text-2xl">⬅️</button>
        <h1 class="text-xl font-bold">Profile Information</h1>
      </div>
      <button @click="$router.push('/support')" class="text-sm font-bold text-emerald-700">Support</button>
    </header>

    <div class="p-4 space-y-4">
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold overflow-hidden bg-emerald-700 text-white">
            <img v-if="profile.passport_url" :src="getImageUrl(profile.passport_url)" alt="Profile photo" class="w-12 h-12 object-cover" />
            <span v-else>{{ (profile.full_name || 'M')[0] }}</span>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-medium">Member</p>
            <h2 class="text-sm font-bold text-slate-800 uppercase">{{ profile.full_name }}</h2>
            <div class="mt-1">
              <input id="passport-input" ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
              <button @click="chooseFile" class="text-[10px] font-bold text-emerald-700 underline" :disabled="uploading">
                {{ uploading ? 'Uploading...' : (profile.passport_url ? 'Change Photo' : 'Upload Photo') }}
              </button>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-3">
          <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Email</p>
              <p class="font-bold text-slate-800">{{ profile.email }}</p>
            </div>
            <button @click="copy(profile.email)" class="text-sm text-emerald-700">Copy</button>
          </div>
          <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Membership ID</p>
              <p class="font-bold text-slate-800">{{ profile.membership_id }}</p>
            </div>
            <button @click="copy(profile.membership_id)" class="text-sm text-emerald-700">Copy</button>
          </div>
          <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Virtual Account</p>
              <p class="font-bold text-slate-800">{{ profile.virtual_account || '—' }}</p>
            </div>
            <button v-if="profile.virtual_account" @click="copy(profile.virtual_account || '')" class="text-sm text-emerald-700">Copy</button>
            <button v-else @click="goToWallet" class="text-sm text-white bg-emerald-700 px-3 py-1.5 rounded-lg font-bold">Generate</button>
          </div>
          <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Phone</p>
              <p class="font-bold text-slate-800">{{ profile.phone || '—' }}</p>
            </div>
            <button @click="copy(profile.phone || '')" class="text-sm text-emerald-700">Copy</button>
          </div>
          <div class="flex items-start justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Address</p>
              <p class="font-bold text-slate-800 whitespace-pre-line break-words max-w-[70%]">{{ profile.address || '—' }}</p>
            </div>
            <button @click="copy(profile.address || '')" class="text-sm text-emerald-700">Copy</button>
          </div>
          <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Branch</p>
              <p class="font-bold text-slate-800">{{ profile.branch_name || '—' }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Date Joined</p>
              <p class="font-bold text-slate-800">{{ profile.date_joined || '—' }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Verification</p>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-slate-50 p-3 rounded-xl">
            <p class="text-[10px] text-slate-400 font-bold uppercase">BVN</p>
            <div class="flex items-center gap-2">
              <span :class="bvnAssigned ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
                {{ bvnAssigned ? 'Assigned' : 'Not Assigned' }}
              </span>
            </div>
          </div>
          <div class="bg-slate-50 p-3 rounded-xl">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Verification Details</p>
            <p class="font-bold text-slate-800 text-sm">{{ profile.verification_details || '—' }}</p>
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500">KYC status is used to prevent fraud and verify identity.</div>
      </div>

      <!-- Bank Settings -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Bank Settings</p>
        <div v-if="profile.bank_details?.has_verified" class="space-y-2">
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Bank</p>
              <p class="font-bold text-slate-800">{{ profile.bank_details.bank_name || profile.bank_details.bank_code }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Account Number</p>
              <p class="font-bold text-slate-800">{{ profile.bank_details.account_number }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Account Name (Verified)</p>
              <p class="font-bold text-slate-800">{{ profile.bank_details.account_name }}</p>
            </div>
          </div>
          <p class="text-[10px] text-slate-500 mt-2">Your bank details are verified. For security, changes may require OTP verification in a future update.</p>
        </div>
        <div v-else class="space-y-3">
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">Bank</label>
              <select v-model="bankForm.bank_code" class="mt-1 w-full border rounded-xl p-3 bg-slate-50 text-sm">
                <option disabled value="">Select Bank</option>
                <option v-for="b in bankOptions" :key="b.code" :value="b.code">{{ b.name }} ({{ b.code }})</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">Account Number</label>
              <input v-model="bankForm.account_number" type="tel" inputmode="numeric" maxlength="10" placeholder="10-digit account number" class="mt-1 w-full border rounded-xl p-3 bg-slate-50 text-sm" />
              <p v-if="bankErrors.account_number" class="text-red-600 text-xs mt-1">{{ bankErrors.account_number }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="resolveBank" :disabled="bankBusy || !bankForm.bank_code || bankDigits.length!==10" class="px-4 py-2 rounded-xl text-white font-bold" :class="bankBusy ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">{{ bankBusy ? 'Resolving…' : 'Resolve Account Name' }}</button>
            <span v-if="bankMessage" :class="bankError ? 'text-rose-700' : 'text-emerald-700'" class="text-[12px]">{{ bankMessage }}</span>
          </div>
          <div v-if="resolvedName" class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800">
            Resolved Name: <span class="font-bold">{{ resolvedName }}</span>
          </div>
          <div v-if="resolvedName" class="flex items-center gap-2">
            <button @click="saveBank" :disabled="bankBusy" class="px-4 py-2 rounded-xl text-white font-bold" :class="bankBusy ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">{{ bankBusy ? 'Saving…' : 'Save Bank Details' }}</button>
            <button @click="clearResolved" :disabled="bankBusy" class="px-4 py-2 rounded-xl text-emerald-700 font-bold bg-emerald-50 hover:bg-emerald-100">Change</button>
          </div>
          <p class="text-[10px] text-slate-500">We verify your bank account via Paystack/Flutterwave to prevent errors. You’ll see the registered account name before saving.</p>
        </div>
      </div>

      <!-- Change Email -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Update Email</p>
        <div class="space-y-3">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">New Email</label>
            <input v-model="emailForm.email" type="email" class="mt-1 w-full border rounded-xl p-3" placeholder="name@example.com" />
            <p v-if="emailErrors.email" class="text-red-600 text-xs mt-1">{{ emailErrors.email }}</p>
          </div>
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Current Password</label>
            <input v-model="emailForm.password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="••••••••" />
            <p v-if="emailErrors.password" class="text-red-600 text-xs mt-1">{{ emailErrors.password }}</p>
          </div>
          <button @click="updateEmail" :disabled="emailSaving" class="w-full h-12 rounded-xl font-bold text-white" :class="emailSaving ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">
            {{ emailSaving ? 'Updating...' : 'Update Email' }}
          </button>
        </div>
      </div>

      <!-- Change Password -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Update Password</p>
        <div class="space-y-3">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Current Password</label>
            <input v-model="passForm.current_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="Current password" />
            <p v-if="passErrors.current_password" class="text-red-600 text-xs mt-1">{{ passErrors.current_password }}</p>
          </div>
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">New Password</label>
            <input v-model="passForm.new_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="New password" />
            <p v-if="passErrors.new_password" class="text-red-600 text-xs mt-1">{{ passErrors.new_password }}</p>
          </div>
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Confirm New Password</label>
            <input v-model="passForm.confirm_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="Confirm new password" />
            <p v-if="passErrors.confirm_password" class="text-red-600 text-xs mt-1">{{ passErrors.confirm_password }}</p>
          </div>
          <button @click="updatePassword" :disabled="passSaving" class="w-full h-12 rounded-xl font-bold text-white" :class="passSaving ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">
            {{ passSaving ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </div>

      <!-- Transaction PIN -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Transaction PIN</p>
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <span :class="profile.pin_set ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
              {{ profile.pin_set ? 'Set' : 'Not Set' }}
            </span>
            <span v-if="profile.pin_set_at" class="text-[10px] text-slate-500">since {{ profile.pin_set_at }}</span>
          </div>
          <span class="text-[11px] text-slate-500">4-digit PIN used for payments</span>
        </div>
        <div class="space-y-3">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Current Password</label>
            <input v-model="pinForm.current_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="Account password" />
            <p v-if="pinErrors.current_password" class="text-red-600 text-xs mt-1">{{ pinErrors.current_password }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">New PIN (4 digits)</label>
              <input v-model="pinForm.new_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3" placeholder="••••" />
              <p v-if="pinErrors.new_pin" class="text-red-600 text-xs mt-1">{{ pinErrors.new_pin }}</p>
            </div>
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">Confirm PIN</label>
              <input v-model="pinForm.confirm_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3" placeholder="••••" />
              <p v-if="pinErrors.confirm_pin" class="text-red-600 text-xs mt-1">{{ pinErrors.confirm_pin }}</p>
            </div>
          </div>
          <button @click="setPin" :disabled="pinSaving" class="w-full h-12 rounded-xl font-bold text-white" :class="pinSaving ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">
            {{ pinSaving ? 'Saving…' : 'Save PIN' }}
          </button>

          <!-- Forgot PIN flow -->
          <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
            <div class="flex items-center justify-between">
              <p class="text-[11px] text-amber-800 font-bold uppercase tracking-widest">Forgot PIN?</p>
              <button @click="requestPinReset" :disabled="resetBusy" class="text-[11px] font-bold text-emerald-700 underline">
                {{ resetBusy ? 'Sending…' : 'Send Reset Code' }}
              </button>
            </div>
            <p v-if="resetSentTo" class="text-[11px] text-amber-700 mt-1">Code sent to: {{ resetSentTo }} (expires in ~10 minutes)</p>
            <div class="grid grid-cols-3 gap-2 mt-3">
              <div>
                <label class="text-[10px] text-slate-500 font-bold uppercase">6‑digit Code</label>
                <input v-model="resetForm.code" type="text" inputmode="numeric" pattern="\\d*" maxlength="6" class="mt-1 w-full border rounded-xl p-3 text-center" placeholder="123456" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-bold uppercase">New PIN</label>
                <input v-model="resetForm.new_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3 text-center" placeholder="••••" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-bold uppercase">Confirm</label>
                <input v-model="resetForm.confirm_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3 text-center" placeholder="••••" />
              </div>
            </div>
            <div class="mt-2 flex items-center gap-2">
              <button @click="confirmPinReset" :disabled="resetBusy" class="px-4 py-2 rounded-xl text-white font-bold" :class="resetBusy ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">{{ resetBusy ? 'Resetting…' : 'Reset PIN' }}</button>
              <span v-if="resetMessage" class="text-[12px]" :class="resetError ? 'text-rose-700' : 'text-emerald-700'">{{ resetMessage }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/settings')">
        <span class="text-xl">🛟</span>
        <span class="text-[10px] font-bold">Support</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1">
        <span class="text-xl">👤</span>
        <span class="text-[10px] font-bold">Profile</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from '../http'
import getImageUrl from '../utils/image'

const router = useRouter()

const profile = ref({})
const bvnAssigned = ref(false)
const uploading = ref(false)
const fileInput = ref(null)

// Bank details (verification & save)
const bankForm = ref({ bank_code: '', account_number: '', gateway: 'paystack' })
const bankErrors = ref({})
const bankBusy = ref(false)
const bankMessage = ref('')
const bankError = ref(false)
const resolvedName = ref('')
const bankOptions = ref([
  { code: '011', name: 'First Bank of Nigeria' },
  { code: '058', name: 'Guaranty Trust Bank (GTBank)' },
  { code: '044', name: 'Access Bank' },
  { code: '057', name: 'Zenith Bank' },
  { code: '033', name: 'United Bank for Africa (UBA)' },
  { code: '214', name: 'First City Monument Bank (FCMB)' },
  { code: '070', name: 'Fidelity Bank' },
  { code: '032', name: 'Union Bank' },
  { code: '076', name: 'Polaris Bank' },
  { code: '035', name: 'Wema Bank' },
  { code: '232', name: 'Sterling Bank' },
  { code: '050', name: 'Ecobank Nigeria' },
  { code: '082', name: 'Keystone Bank' },
])
const bankDigits = computed(() => String(bankForm.value.account_number || '').replace(/\D/g, ''))

// Update Email form state
const emailForm = ref({ email: '', password: '' })
const emailSaving = ref(false)
const emailErrors = ref({})

// Update Password form state
const passForm = ref({ current_password: '', new_password: '', confirm_password: '' })
const passSaving = ref(false)
const passErrors = ref({})

// Transaction PIN form state
const pinForm = ref({ current_password: '', new_pin: '', confirm_pin: '' })
const pinSaving = ref(false)
const pinErrors = ref({})

// PIN reset (forgot) state
const resetBusy = ref(false)
const resetSentTo = ref('')
const resetMessage = ref('')
const resetError = ref(false)
const resetForm = ref({ code: '', new_pin: '', confirm_pin: '' })

const copy = async (text) => {
  try {
    await navigator.clipboard.writeText(String(text || ''))
    alert('Copied to clipboard')
  } catch (_) {
    // noop
  }
}

const goToWallet = () => router.push('/wallet')


const chooseFile = () => fileInput.value && fileInput.value.click()

const onFileChange = async (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const form = new FormData()
  form.append('passport', file)
  uploading.value = true
  try {
    const { data } = await axios.post('/api/profile/passport', form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    profile.value.passport_url = data.passport_url
  } catch (err) {
    alert(err?.response?.data?.message || 'Failed to upload. Please try a smaller image or a different format.')
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const resolveBank = async () => {
  bankErrors.value = {}
  bankMessage.value = ''
  bankError.value = false
  resolvedName.value = ''
  // Validate inputs
  if (!bankForm.value.bank_code) {
    bankMessage.value = 'Please select a bank.'
    bankError.value = true
    return
  }
  if (bankDigits.value.length !== 10) {
    bankErrors.value.account_number = 'Enter a valid 10-digit account number.'
    return
  }
  bankBusy.value = true
  try {
    const bankName = (bankOptions.value.find(b => b.code === bankForm.value.bank_code)?.name) || null
    const { data } = await axios.post('/api/profile/bank-details', {
      bank_code: bankForm.value.bank_code,
      bank_name: bankName,
      account_number: bankDigits.value,
      gateway: bankForm.value.gateway || 'paystack',
      confirm: false,
    })
    resolvedName.value = data?.resolved_name || ''
    bankMessage.value = resolvedName.value ? 'Is this your account name?' : (data?.message || 'Resolved.')
    bankError.value = false
  } catch (err) {
    bankError.value = true
    bankMessage.value = err?.response?.data?.message || 'Failed to resolve bank account.'
  } finally {
    bankBusy.value = false
  }
}

const saveBank = async () => {
  if (!resolvedName.value) {
    bankMessage.value = 'Resolve your bank account first.'
    bankError.value = true
    return
  }
  bankBusy.value = true
  try {
    const bankName = (bankOptions.value.find(b => b.code === bankForm.value.bank_code)?.name) || null
    const { data } = await axios.post('/api/profile/bank-details', {
      bank_code: bankForm.value.bank_code,
      bank_name: bankName,
      account_number: bankDigits.value,
      gateway: bankForm.value.gateway || 'paystack',
      confirm: true,
    })
    // Update profile object with verified details
    profile.value.bank_details = data?.bank_details || {
      bank_code: bankForm.value.bank_code,
      bank_name: bankName,
      account_number: bankDigits.value,
      account_name: resolvedName.value,
      has_verified: true,
    }
    bankMessage.value = data?.message || 'Bank details saved successfully.'
    bankError.value = false
  } catch (err) {
    bankError.value = true
    bankMessage.value = err?.response?.data?.message || 'Failed to save bank details.'
  } finally {
    bankBusy.value = false
  }
}

const clearResolved = () => {
  resolvedName.value = ''
  bankMessage.value = ''
  bankError.value = false
}

const updateEmail = async () => {
  emailErrors.value = {}
  // basic client-side validation
  if (!emailForm.value.email) {
    emailErrors.value.email = 'Email is required.'
    return
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailForm.value.email)) {
    emailErrors.value.email = 'Please enter a valid email address.'
    return
  }
  if (!emailForm.value.password) {
    emailErrors.value.password = 'Current password is required.'
    return
  }
  emailSaving.value = true
  try {
    const { data } = await axios.post('/api/profile/email', {
      email: emailForm.value.email,
      password: emailForm.value.password,
    })
    // Update local profile email
    profile.value.email = data?.email || emailForm.value.email
    alert('Email updated successfully.')
    // Clear password field
    emailForm.value.password = ''
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      // Laravel validation errors
      emailErrors.value = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
    } else if (e?.message) {
      alert(e.message)
    } else {
      alert('Failed to update email. Please try again.')
    }
  } finally {
    emailSaving.value = false
  }
}

const updatePassword = async () => {
  passErrors.value = {}
  // basic validation
  if (!passForm.value.current_password) {
    passErrors.value.current_password = 'Current password is required.'
    return
  }
  if (!passForm.value.new_password) {
    passErrors.value.new_password = 'New password is required.'
    return
  }
  if (passForm.value.new_password.length < 6) {
    passErrors.value.new_password = 'New password must be at least 6 characters.'
    return
  }
  if (passForm.value.confirm_password !== passForm.value.new_password) {
    passErrors.value.confirm_password = 'Password confirmation does not match.'
    return
  }
  passSaving.value = true
  try {
    await axios.post('/api/profile/password', {
      current_password: passForm.value.current_password,
      new_password: passForm.value.new_password,
      confirm_password: passForm.value.confirm_password,
    })
    alert('Password updated successfully.')
    passForm.value = { current_password: '', new_password: '', confirm_password: '' }
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      passErrors.value = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
    } else if (e?.message) {
      alert(e.message)
    } else {
      alert('Failed to update password. Please try again.')
    }
  } finally {
    passSaving.value = false
  }
}

const setPin = async () => {
  pinErrors.value = {}
  // client-side validation
  if (!pinForm.value.current_password) {
    pinErrors.value.current_password = 'Current password is required.'
    return
  }
  if (!pinForm.value.new_pin) {
    pinErrors.value.new_pin = 'PIN is required.'
    return
  }
  if (!/^\d{4}$/.test(String(pinForm.value.new_pin))) {
    pinErrors.value.new_pin = 'PIN must be exactly 4 digits.'
    return
  }
  if (String(pinForm.value.confirm_pin) !== String(pinForm.value.new_pin)) {
    pinErrors.value.confirm_pin = 'PIN confirmation does not match.'
    return
  }
  pinSaving.value = true
  try {
    await axios.post('/api/security/pin/set', {
      current_password: pinForm.value.current_password,
      new_pin: String(pinForm.value.new_pin),
      confirm_pin: String(pinForm.value.confirm_pin),
    })
    alert('Transaction PIN saved successfully.')
    pinForm.value = { current_password: '', new_pin: '', confirm_pin: '' }
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      pinErrors.value = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
    } else if (e?.message) {
      alert(e.message)
    } else {
      alert('Failed to save PIN. Please try again.')
    }
  } finally {
    pinSaving.value = false
  }
}

const requestPinReset = async () => {
  resetMessage.value = ''
  resetError.value = false
  resetSentTo.value = ''
  resetBusy.value = true
  try {
    const { data } = await axios.post('/api/security/pin/reset/request')
    resetSentTo.value = data?.sent_to || ''
    resetMessage.value = data?.message || 'Code sent if contact exists.'
  } catch (err) {
    resetError.value = true
    resetMessage.value = err?.response?.data?.message || 'Failed to send reset code.'
  } finally {
    resetBusy.value = false
  }
}

const confirmPinReset = async () => {
  resetMessage.value = ''
  resetError.value = false
  // Basic validation
  if (!/^\d{6}$/.test(String(resetForm.value.code || ''))) {
    resetError.value = true
    resetMessage.value = 'Enter the 6-digit code sent to you.'
    return
  }
  if (!/^\d{4}$/.test(String(resetForm.value.new_pin || ''))) {
    resetError.value = true
    resetMessage.value = 'New PIN must be exactly 4 digits.'
    return
  }
  if (String(resetForm.value.confirm_pin) !== String(resetForm.value.new_pin)) {
    resetError.value = true
    resetMessage.value = 'PIN confirmation does not match.'
    return
  }
  resetBusy.value = true
  try {
    const { data } = await axios.post('/api/security/pin/reset/confirm', {
      code: String(resetForm.value.code),
      new_pin: String(resetForm.value.new_pin),
      confirm_pin: String(resetForm.value.confirm_pin),
    })
    resetMessage.value = data?.message || 'PIN reset successfully.'
    resetError.value = false
    // Clear inputs
    resetForm.value = { code: '', new_pin: '', confirm_pin: '' }
  } catch (err) {
    const status = err?.response?.status
    const msg = err?.response?.data?.message || 'Failed to reset PIN.'
    resetMessage.value = msg
    resetError.value = true
    if (status === 429) {
      alert('Too many invalid attempts. Please request a new code.')
    }
  } finally {
    resetBusy.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/profile')
    profile.value = data
    emailForm.value.email = data?.email || ''
    const assigned = Boolean(data?.bvn_assigned ?? JSON.parse(localStorage.getItem('bvn_assigned') || 'false'))
    bvnAssigned.value = assigned
    try { localStorage.setItem('bvn_assigned', JSON.stringify(assigned)) } catch (_) {}
  } catch (_) {
    // Fallback mock values
    profile.value = {
      full_name: 'Member',
      email: 'member@example.com',
      membership_id: 'M-000000',
      virtual_account: ''
    }
    emailForm.value.email = profile.value.email
    bvnAssigned.value = JSON.parse(localStorage.getItem('bvn_assigned') || 'false')
  }
})
</script>
