<template>
  <transition name="fade">
    <div v-if="visible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-brand-800 text-white">
      <div class="flex flex-col items-center">
        <img src="/images/assalam-logo.svg" alt="ASSALAM CO-OPERATIVE" class="h-16 w-auto drop-shadow-lg" />
        <p class="mt-3 text-xs tracking-[0.3em] font-semibold text-emerald-100 uppercase">ASSALAM CO-OPERATIVE</p>
        <div class="mt-6 w-8 h-8 rounded-full border-2 border-emerald-200 border-t-transparent animate-spin"></div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  visible: { type: Boolean, default: true }
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 300ms ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
