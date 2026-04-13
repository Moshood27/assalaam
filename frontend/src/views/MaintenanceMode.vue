<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-6 text-center">
    <div class="max-w-md w-full">
      <div class="mb-8">
        <div class="w-24 h-24 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.83-5.83m0 0a2.968 2.968 0 010-4.186L15.937 5.07a3.375 3.375 0 00-4.773 0L9.317 6.924a3.375 3.375 0 000 4.773L11.17 13.55a2.968 2.968 0 010 4.186L10.32 18.58a3.375 3.375 0 01-4.773 0L3.694 16.73a3.375 3.375 0 010-4.773l1.854-1.854a3.375 3.375 0 014.773 0l.854.854a2.968 2.968 0 004.186 0z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Maintenance Mode</h1>
        <p class="text-gray-600">
          {{ maintenanceMessage || 'We are currently performing scheduled maintenance to improve our services. We\'ll be back shortly.' }}
        </p>
      </div>

      <div v-if="maintenanceUntil" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
        <p class="text-sm text-gray-500 mb-1">Estimated duration</p>
        <p class="text-lg font-semibold text-gray-900">{{ maintenanceUntil }}</p>
      </div>

      <button
        @click="checkAgain"
        class="w-full bg-primary text-white py-4 rounded-xl font-bold shadow-lg shadow-primary/20 hover:opacity-90 active:scale-95 transition-all"
      >
        Check Again
      </button>

      <p class="mt-8 text-xs text-gray-400">
        Thank you for your patience and understanding.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { checkAppStatus } from '../services/appStatus'

const router = useRouter()
const maintenanceMessage = ref('')
const maintenanceUntil = ref('')

onMounted(async () => {
  const status = await checkAppStatus()
  if (!status.maintenanceMode) {
    router.replace('/')
    return
  }
  maintenanceMessage.value = status.maintenanceMessage
  maintenanceUntil.value = status.maintenanceUntil
})

const checkAgain = () => {
  window.location.href = '/'
}
</script>

<style scoped>
.text-primary {
  color: #10b981; /* Example primary color, adjust if needed */
}
.bg-primary {
  background-color: #10b981;
}
.bg-primary\/10 {
  background-color: rgba(16, 185, 129, 0.1);
}
.shadow-primary\/20 {
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
}
</style>
