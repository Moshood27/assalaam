<template>
  <div class="min-h-screen auth-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card card-elevated p-6 sm:p-8">
        <div class="flex flex-col items-center text-center mb-6">
          <div class="w-16 h-16 rounded-2xl bg-amber-600 flex items-center justify-center text-white text-2xl shadow-lg">
            ✳️
          </div>
          <p class="mt-4 text-xs font-semibold tracking-widest text-amber-700 uppercase">Admin Portal</p>
          <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Create Admin Account</h1>
          <p class="text-slate-500 text-sm mt-1">Register a new administrator</p>
        </div>

        <div class="space-y-5">
          <div>
            <label class="form-label">Full Name</label>
            <input v-model="form.name" type="text" placeholder="Jane Doe" class="input" />
          </div>

          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" placeholder="admin@example.com" class="input" />
          </div>

          <div>
            <label class="form-label">Password</label>
            <input v-model="form.password" type="password" placeholder="Min 8 characters" class="input" />
          </div>

          <div>
            <label class="form-label">Confirm Password</label>
            <input v-model="form.password_confirmation" type="password" placeholder="Re-enter password" class="input" />
          </div>

          <button @click="handleRegister" :disabled="loading" class="btn-primary w-full h-12 text-base">
            <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            <span>{{ loading ? 'Creating…' : 'Create account' }}</span>
          </button>

          <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>
          <p v-if="success" class="text-center text-emerald-600 text-sm">{{ success }}</p>

          <div class="text-xs text-center">
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/login">Back to login</router-link>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center text-[11px] text-slate-500 font-medium px-4 relative">
        <div class="px-6 py-4 bg-amber-50/40 rounded-2xl border border-amber-100/40 text-slate-600 leading-relaxed max-w-[280px] mx-auto">
          Having trouble creating an admin account or want to know more?
          <br />
          <button @click="showSupportModal = true" class="text-amber-700 font-bold hover:text-amber-800 inline-flex items-center justify-center gap-1 mt-2 w-full">
            <span>Contact Support</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Public Support Modal (Immediate Help) -->
    <div v-if="showSupportModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 text-left">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showSupportModal = false"></div>
      <div class="relative w-full max-w-md bg-slate-50 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-300">
        <div class="p-6 bg-white border-b flex items-center justify-between">
          <h2 class="text-xl font-bold text-slate-800">Admin Support</h2>
          <button @click="showSupportModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 transition-colors">✕</button>
        </div>
        <div class="p-6">
          <SupportContacts />
          <div class="mt-6 text-center">
            <router-link to="/support" class="text-[11px] font-bold text-amber-700 hover:underline">View full support page</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import SupportContacts from '../../components/SupportContacts.vue'

const showSupportModal = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const handleRegister = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const { data } = await axios.post('/api/admin/register', form.value)
    localStorage.setItem('admin_token', data.token)
    // Optionally redirect straight to Filament
    const origin = import.meta?.env?.VITE_BACKEND_ORIGIN || ''
    window.location.href = `${origin}/admin`
  } catch (e) {
    error.value = e?.response?.data?.message
      || e?.response?.data?.errors?.email?.[0]
      || e?.response?.data?.errors?.password?.[0]
      || 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>
