<template>
  <div v-if="modelValue" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-6" @click.self="onClose">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in duration-200">
      <div class="p-6 text-center">
        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-3xl"
             :class="{
               'bg-emerald-100 text-emerald-600': type === 'success',
               'bg-red-100 text-red-600': type === 'error',
               'bg-amber-100 text-amber-600': type !== 'success' && type !== 'error'
             }">
          <span v-if="type==='success'">✅</span>
          <span v-else-if="type==='error'">⚠️</span>
          <span v-else>ℹ️</span>
        </div>
        <h3 class="text-xl font-black mb-2 text-slate-800">{{ title }}</h3>
        <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-line">{{ message }}</p>
      </div>
      <button @click="onClose" class="w-full p-4 bg-slate-50 border-t border-slate-100 font-bold text-emerald-700 hover:bg-emerald-50 transition-colors uppercase tracking-widest text-xs">
        Dismiss
      </button>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  type: { type: String, default: 'info' },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'close'])

function onClose() {
  emit('update:modelValue', false)
  emit('close')
}
</script>
