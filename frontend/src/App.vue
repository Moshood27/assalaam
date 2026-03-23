<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import BaseModal from './components/BaseModal.vue'
import MobileSplash from './components/MobileSplash.vue'

const showSplash = ref(true)

function onAppReady() {
  // Small delay to allow first paint to settle
  setTimeout(() => (showSplash.value = false), 100)
}

onMounted(() => {
  window.addEventListener('app:ready', onAppReady)
  // Fallback: auto-hide after 5s in case ready event never fires
  const fallback = setTimeout(() => (showSplash.value = false), 5000)
  onBeforeUnmount(() => clearTimeout(fallback))
})

onBeforeUnmount(() => {
  window.removeEventListener('app:ready', onAppReady)
})
</script>

<template>
  <MobileSplash :visible="showSplash" />
  <router-view />
  <BaseModal />
</template>

<style scoped>
</style>
