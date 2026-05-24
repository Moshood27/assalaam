<template>
  <div :class="pillClass">
    <div class="flex items-center gap-1.5 opacity-70">
      <span v-if="icon" class="text-xs">{{ icon }}</span>
      <span class="text-[9px] uppercase tracking-wider font-bold truncate">{{ label }}</span>
    </div>
    <div class="min-w-0">
      <p class="text-base font-black leading-tight">{{ value }}</p>
      <p v-if="hint" :class="['text-[9px] leading-tight truncate font-bold uppercase tracking-tighter mt-0.5', hintClass]">{{ hint }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: '' },
  intent: { type: String, default: 'default' }, // default | success | warning | danger
  icon: { type: String, default: '' },
})

const colorMap = {
  default: 'bg-slate-50 text-slate-900 border-slate-200/50',
  success: 'bg-emerald-50 text-emerald-900 border-emerald-200/50',
  warning: 'bg-amber-50 text-amber-900 border-amber-200/50',
  info: 'bg-blue-50 text-blue-900 border-blue-200/50',
  danger: 'bg-rose-50 text-rose-900 border-rose-200/50',
}

const hintMap = {
  default: 'text-slate-500',
  success: 'text-emerald-600',
  warning: 'text-amber-600',
  info: 'text-blue-600',
  danger: 'text-rose-600',
}

const pillClass = computed(() => [
  'px-4 py-3 rounded-2xl flex flex-col gap-1',
  'border shadow-sm',
  colorMap[props.intent] || colorMap.default,
])

const hintClass = computed(() => hintMap[props.intent] || hintMap.default)
</script>
