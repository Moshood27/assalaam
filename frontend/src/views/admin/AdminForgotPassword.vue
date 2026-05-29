<template>
  <div class="min-h-screen auth-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="card card-elevated p-6 sm:p-8">
        <div class="flex flex-col items-center text-center mb-6">
          <div class="w-16 h-16 rounded-2xl bg-amber-600 flex items-center justify-center text-white text-2xl shadow-lg">
            Ã°Å¸â€â€˜
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
            <span>{{ loading ? 'SendingÃ¢â‚¬Â¦' : 'Send reset link' }}</span>
          </button>

          <p v-if="error" class="text-center text-rose-600 text-sm">{{ error }}</p>
          <div v-if="success" class="space-y-3 text-center">
            <p class="text-blue-600 text-sm font-bold">{{ success }}</p>
            <div v-if="sentTo" class="bg-blue-50 rounded-xl p-4 text-xs text-blue-800 space-y-2 inline-block mx-auto text-left min-w-[200px]">
              <div v-if="sentTo.email" class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                <span>Sent to: {{ sentTo.email }}</span>
              </div>
              <div v-if="sentTo.push" class="flex items-center gap-2 font-bold text-blue-900">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                <span>{{ sentTo.push }}</span>
              </div>
            </div>
          </div>

          <div class="text-xs text-center">
            <router-link class="text-amber-700 font-semibold hover:underline" to="/admin/login">Back to login</router-link>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center text-[11px] text-slate-500 font-medium px-4 relative">
        <div class="px-6 py-4 bg-amber-50/40 rounded-2xl border border-amber-100/40 text-slate-600 leading-relaxed max-w-[280px] mx-auto">
          Having trouble resetting your password or want to know more?
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
import SupportContacts from '../../components/SupportContacts.vue'

const showSupportModal = ref(false)
const loading = ref(false)
const error = ref('')
const success = ref('')
const sentTo = ref(null)

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
    sentTo.value = data?.sent_to || null
  } catch (e) {
    error.value = e?.response?.data?.message || 'Could not send reset link'
  } finally {
    loading.value = false
  }
}
</script>


