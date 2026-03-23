<template>
  <div class="min-h-screen auth-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card card-elevated p-6 sm:p-8">
        <div class="flex flex-col items-center text-center mb-6">
          <div class="w-16 h-16 rounded-2xl bg-amber-600 flex items-center justify-center text-white text-2xl shadow-lg">
            🔑
          </div>
          <p class="mt-4 text-xs font-semibold tracking-widest text-amber-700 uppercase">Admin Portal</p>
          <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">Forgot Password</h1>
          <p class="text-slate-500 text-sm mt-1">We'll email you a reset link</p>
        </div>

        <div class="space-y-5">
          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" placeholder="admin@example.com" class="input" />
          </div>

          <button @click="handleSubmit" :disabled="loading" class="btn-primary w-full h-12 text-base">
            <span v-if="loading" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            <span>{{ loading ? 'Sending…' : 'Send reset link' }}</span>
          </button>

          <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>
          <p v-if="success" class="text-center text-emerald-600 text-sm">{{ success }}</p>

          <div class="text-xs text-center">
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/login">Back to login</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const loading = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  email: ''
})

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const { data } = await axios.post('/api/admin/forgot-password', form.value)
    success.value = data?.status || 'If an account exists for that email, a reset link has been sent.'
  } catch (e) {
    error.value = e?.response?.data?.message || 'Could not send reset link'
  } finally {
    loading.value = false
  }
}
</script>
