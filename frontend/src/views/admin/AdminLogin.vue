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
              <span v-if="showPassword">🙈</span>
              <span v-else>👁️</span>
            </button>
          </div>

          <button @click="handleLogin" :disabled="loading" class="btn-primary w-full h-12 text-base">
            <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            <span>{{ loading ? 'Signing in…' : 'Sign in' }}</span>
          </button>

          <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>

          <div class="text-xs text-center">
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/forgot">Forgot password?</router-link>
            <span class="mx-2 text-slate-400">•</span>
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/register">Create admin</router-link>
          </div>
        </div>
      </div>

      <p class="mt-6 text-center text-xs text-slate-500">Having trouble? <button class="text-amber-700 font-semibold hover:underline" @click="$router.push('/settings')">Contact Support</button></p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import brand from '../../brand'

const loading = ref(false)
const showPassword = ref(false)
const error = ref('')

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
