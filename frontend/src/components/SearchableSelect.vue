<template>
  <div class="relative" ref="root">
    <label v-if="label" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">{{ label }}</label>
    <button type="button" @click="toggle" class="w-full bg-slate-50/50 border border-slate-200/60 focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 h-14 px-4 rounded-2xl outline-none text-left flex items-center justify-between transition-all duration-200">
      <span class="truncate text-lg font-semibold" :class="!selectedLabel ? 'text-slate-400' : 'text-slate-700'">
        {{ selectedLabel || placeholder }}
      </span>
      <span class="ml-2 text-slate-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
      </span>
    </button>

    <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
      <div v-if="open" class="absolute z-30 mt-2 w-full bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/60 overflow-hidden">
        <div class="p-3 border-b border-slate-100">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input v-model="query" type="text" :placeholder="searchPlaceholder" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 rounded-xl border-none outline-none text-sm focus:ring-2 focus:ring-blue-500/10" />
          </div>
        </div>
        <div class="max-h-64 overflow-y-auto py-2 scrollbar-thin scrollbar-thumb-slate-200">
          <button v-for="it in filtered" :key="valueOf(it)" @click="select(it)" class="w-full text-left px-4 py-3 hover:bg-blue-50/50 flex items-center justify-between group transition-colors">
            <span class="truncate font-medium text-slate-700 group-hover:text-blue-700">{{ labelOf(it) }}</span>
            <span v-if="modelValue === valueOf(it)" class="text-blue-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </span>
          </button>
          <div v-if="filtered.length === 0" class="px-4 py-8 text-center">
            <p class="text-sm text-slate-400 font-medium">No results found</p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  modelValue: [String, Number],
  placeholder: { type: String, default: 'Select an option' },
  searchPlaceholder: { type: String, default: 'SearchÃ¢â‚¬Â¦' },
  labelField: { type: String, default: 'name' },
  valueField: { type: String, default: 'id' },
  label: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const root = ref(null)

const labelOf = (it) => it?.[props.labelField]
const valueOf = (it) => it?.[props.valueField]

const selected = computed(() => props.items.find(i => valueOf(i) === props.modelValue))
const selectedLabel = computed(() => selected.value ? labelOf(selected.value) : '')

const filtered = computed(() => {
  if (!query.value) return props.items
  const q = query.value.toLowerCase()
  return props.items.filter(i => String(labelOf(i) || '').toLowerCase().includes(q))
})

const toggle = () => {
  open.value = !open.value
  if (open.value) {
    setTimeout(() => {
      const input = root.value?.querySelector('input')
      if (input) input.focus()
    }, 0)
  }
}

const select = (it) => {
  emit('update:modelValue', valueOf(it))
  open.value = false
}

const onDocClick = (e) => {
  if (!root.value) return
  if (!root.value.contains(e.target)) open.value = false
}

onMounted(() => document.addEventListener('click', onDocClick))

onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>

<style scoped>
</style>


