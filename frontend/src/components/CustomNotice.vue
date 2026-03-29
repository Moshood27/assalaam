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

        <!-- Prompt input (optional) -->
        <div v-if="prompt" class="mt-4 text-left">
          <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-1">{{ inputLabel }}</label>
          <input
            v-model="inputValue"
            :type="inputType"
            :pattern="inputPattern"
            :maxlength="inputMaxlength"
            inputmode="numeric"
            class="w-full border border-slate-200 rounded-xl p-3 text-center tracking-[0.5em] font-black text-slate-900"
            placeholder="••••"
            @keyup.enter="onConfirm"
          />
        </div>
      </div>

      <!-- Actions -->
      <div v-if="prompt" class="flex divide-x border-t border-slate-100">
        <button @click="onClose" :disabled="busy" class="flex-1 p-4 font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-widest text-xs disabled:opacity-50">{{ cancelText }}</button>
        <button @click="onConfirm" :disabled="busy" class="flex-1 p-4 font-bold text-white bg-emerald-700 hover:bg-emerald-800 transition-colors uppercase tracking-widest text-xs disabled:opacity-50">{{ confirmText }}</button>
      </div>
      <button v-else @click="onClose" class="w-full p-4 bg-slate-50 border-t border-slate-100 font-bold text-emerald-700 hover:bg-emerald-50 transition-colors uppercase tracking-widest text-xs">
        Dismiss
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  type: { type: String, default: 'info' },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  // Prompt mode props
  prompt: { type: Boolean, default: false },
  inputLabel: { type: String, default: 'Enter PIN' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  inputType: { type: String, default: 'password' },
  inputPattern: { type: String, default: '\\d*' },
  inputMaxlength: { type: [Number, String], default: 4 },
  busy: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'close', 'confirm', 'cancel'])
const inputValue = ref('')

watch(() => props.modelValue, (v) => {
  if (v) inputValue.value = ''
})

function onClose() {
  emit('update:modelValue', false)
  emit('close')
  emit('cancel')
}

function onConfirm() {
  emit('confirm', inputValue.value)
}
</script>
