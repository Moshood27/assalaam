<template>
  <transition name="fade">
    <div v-if="state.open" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 sm:p-6 pb-[calc(2rem+env(safe-area-inset-bottom))]">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/40" @click="close()"></div>

      <!-- Panel -->
      <div
        class="relative w-full sm:w-auto bg-white rounded-2xl shadow-xl border border-slate-200 mx-0 sm:mx-4"
        :class="panelSize"
        role="dialog"
        aria-modal="true"
      >
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-base sm:text-lg font-bold text-slate-800">{{ state.title || 'Notice' }}</h3>
          <button class="text-slate-400 hover:text-slate-600" @click="close()" aria-label="Close">✕</button>
        </div>
        <div class="p-4 sm:p-5 text-slate-700 whitespace-pre-line">
          {{ state.message }}

          <div v-if="state.type === 'promptText'" class="mt-4">
            <textarea
              v-model="state.inputValue"
              :placeholder="state.inputPlaceholder"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none text-sm min-h-[100px]"
            ></textarea>
          </div>
        </div>
        <div class="p-3 sm:p-4 border-t border-slate-100 flex flex-wrap justify-end gap-2">
          <button
            v-for="(a, i) in (state.actions && state.actions.length ? state.actions : defaultActions)"
            :key="i"
            @click="a.handler && a.handler()"
            :class="[
              a.primary ? 'bg-emerald-700 text-white' : (a.danger ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700'),
              state.type === 'prompt' ? 'flex-1 sm:flex-none' : ''
            ]"
            class="px-6 py-3 rounded-xl text-sm font-bold min-w-[100px] active:scale-95 transition-transform"
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
