<template>
  <div :class="pillClass">
    <div class="flex items-center gap-2 min-w-0">
      <span v-if="icon" class="text-base">{{ icon }}</span>
      <span class="text-[10px] uppercase tracking-widest font-black opacity-70 truncate">{{ label }}</span>
    </div>
    <div class="text-right min-w-0">
      <p class="text-sm font-extrabold leading-5 truncate">{{ value }}</p>
      <p v-if="hint" :class="['text-[10px] leading-3 truncate', hintClass]">{{ hint }}</p>
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
  default: 'bg-slate-100 text-slate-700',
  success: 'bg-emerald-100 text-emerald-700',
  warning: 'bg-amber-100 text-amber-700',
  info: 'bg-blue-100 text-blue-700',
  danger: 'bg-rose-100 text-rose-700',
}

const hintMap = {
  default: 'text-slate-500',
  success: 'text-emerald-600',
  warning: 'text-amber-600',
  info: 'text-blue-600',
  danger: 'text-rose-600',
}

const pillClass = computed(() => [
  'px-3 py-2 rounded-2xl flex items-center justify-between gap-3',
  'border border-transparent',
  colorMap[props.intent] || colorMap.default,
])

const hintClass = computed(() => hintMap[props.intent] || hintMap.default)
</script>
