<template>
  <transition name="fade">
    <div v-if="state.open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/40" @click="close()"></div>

      <!-- Panel -->
      <div
        class="relative w-full sm:w-auto bg-white rounded-t-2xl sm:rounded-2xl shadow-xl border border-slate-200 mx-0 sm:mx-4"
        :class="panelSize"
        role="dialog"
        aria-modal="true"
      >
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-base sm:text-lg font-bold text-slate-800">{{ state.title || 'Notice' }}</h3>
          <button class="text-slate-400 hover:text-slate-600" @click="close()" aria-label="Close">✕</button>
        </div>
        <div class="p-4 sm:p-5 text-slate-700 whitespace-pre-line">{{ state.message }}</div>
        <div class="p-3 sm:p-4 border-t border-slate-100 flex justify-end gap-2">
          <button
            v-for="(a, i) in (state.actions && state.actions.length ? state.actions : defaultActions)"
            :key="i"
            @click="a.handler && a.handler()"
            :class="a.primary ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700'"
            class="px-4 py-2 rounded-xl text-sm font-bold"
          >
            {{ a.label }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue'
import { useModal } from '../composables/useModal'

const { state, close } = useModal()

const panelSize = computed(() => {
  switch (state.size) {
    case 'sm':
      return 'sm:max-w-sm'
    case 'lg':
      return 'sm:max-w-2xl'
    default:
      return 'sm:max-w-md'
  }
})

const defaultActions = [
  { label: 'OK', primary: true, handler: () => close(true) },
]

function onKey(e) {
  if (e.key === 'Escape') {
    close(false)
  }
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>
