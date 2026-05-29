<template>
  <div class="min-h-screen auth-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card card-elevated p-6 sm:p-8">
        <div class="flex flex-col items-center text-center mb-6">
          <div class="mb-2">
            <img :src="brand.logo" :alt="brand.name" class="h-16 sm:h-20 w-auto" />
          </div>
          <p class="text-[11px] mt-1 font-semibold tracking-widest text-amber-700 uppercase">{{ brand.name }}</p>
          <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Admin Login</h1>
          <p class="text-slate-500 text-sm mt-1">Sign in to manage the cooperative</p>
        </div>

        <div class="space-y-5">
          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" placeholder="you@example.com" class="input" />
          </div>

          <div class="relative">
            <label class="form-label">Password</label>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Enter your password" class="input pr-12" />
            <button @click="showPassword = !showPassword" type="button" class="absolute right-3 top-9 text-gray-400 hover:text-slate-600" aria-label="Toggle password visibility">
              <span v-if="showPassword">Ã°Å¸â„¢Ë†</span>
              <span v-else>Ã°Å¸â€˜ÂÃ¯Â¸Â</span>
            </button>
          </div>

          <button @click="handleLogin" :disabled="loading" class="btn-primary w-full h-12 text-base">
            <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            <span>{{ loading ? 'Signing inÃ¢â‚¬Â¦' : 'Sign in' }}</span>
          </button>

          <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>

          <div class="text-xs text-center">
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/forgot">Forgot password?</router-link>
            <span class="mx-2 text-slate-400">Ã¢â‚¬Â¢</span>
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/register">Create admin</router-link>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center text-[11px] text-slate-500 font-medium px-4 relative">
        <div class="px-6 py-4 bg-amber-50/40 rounded-2xl border border-amber-100/40 text-slate-600 leading-relaxed max-w-[280px] mx-auto">
          Having trouble accessing the admin panel?
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
          <button @click="showSupportModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 transition-colors">Ã¢Å“â€¢</button>
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
import brand from '../../brand'
import SupportContacts from '../../components/SupportContacts.vue'

const loading = ref(false)
const showPassword = ref(false)
const error = ref('')
const showSupportModal = ref(false)

const form = ref({
  email: '',
  password: ''
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.post('/api/admin/login', form.value)
    localStorage.setItem('admin_token', data.token)
    // Redirect to Filament panel
    const origin = import.meta?.env?.VITE_BACKEND_ORIGIN || ''
    window.location.href = `${origin}/admin`
  } catch (e) {
    error.value = e?.response?.data?.message || e?.response?.data?.errors?.email?.[0] || 'Login Failed'
  } finally {
    loading.value = false
  }
}
</script>


