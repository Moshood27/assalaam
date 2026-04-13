<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-6 text-center">
    <div class="max-w-md w-full">
      <div class="mb-8">
        <div class="w-24 h-24 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Update Required</h1>
        <p class="text-gray-600">
          A new version of the app is available. Please update to the latest version to continue using our services.
        </p>
      </div>

      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 flex items-center space-x-4">
        <div class="flex-shrink-0 w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
          </svg>
        </div>
        <div class="text-left">
          <p class="text-sm text-gray-500">New Version Available</p>
          <p class="text-lg font-semibold text-gray-900">Improved Security & Features</p>
        </div>
      </div>

      <button
        @click="goToPlayStore"
        class="w-full bg-primary text-white py-4 rounded-xl font-bold shadow-lg shadow-primary/20 hover:opacity-90 active:scale-95 transition-all"
      >
        Update Now
      </button>

      <p class="mt-8 text-xs text-gray-400">
        Using an outdated version may expose you to security risks and prevent you from accessing certain features.
      </p>
    </div>
  </div>
</template>

<script setup>
import { Browser } from '@capacitor/browser'

const props = defineProps({
  url: {
    type: String,
    default: 'https://play.google.com/store/apps/details?id=com.attaqwa.app'
  }
})

const goToPlayStore = async () => {
  await Browser.open({ url: props.url })
}
</script>

<style scoped>
.text-primary {
  color: #10b981;
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
