<template>
  <div class="relative" ref="root">
    <label v-if="label" class="block text-blue-900 font-bold mb-1">{{ label }}</label>
    <button type="button" @click="toggle" class="w-full bg-slate-100 border-2 border-transparent focus:border-emerald-400 p-4 rounded-2xl outline-none text-left flex items-center justify-between">
      <span class="truncate text-slate-700" :class="!selectedLabel ? 'text-gray-400' : ''">
        {{ selectedLabel || placeholder }}
      </span>
      <span class="ml-2">▾</span>
    </button>

    <div v-if="open" class="absolute z-20 mt-2 w-full bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
      <div class="p-2 border-b">
        <input v-model="query" type="text" :placeholder="searchPlaceholder" class="w-full p-2 bg-slate-50 rounded-lg border outline-none" />
      </div>
      <div class="max-h-56 overflow-y-auto">
        <button v-for="it in filtered" :key="valueOf(it)" @click="select(it)" class="w-full text-left px-4 py-2 hover:bg-slate-50 flex items-center justify-between">
          <span class="truncate">{{ labelOf(it) }}</span>
          <span v-if="modelValue === valueOf(it)">✓</span>
        </button>
        <div v-if="filtered.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">No matches</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  items: { type: [Array, Object], default: () => [] },
  modelValue: [String, Number],
  placeholder: { type: String, default: 'Select an option' },
  searchPlaceholder: { type: String, default: 'Search…' },
  labelField: { type: String, default: 'name' },
  valueField: { type: String, default: 'id' },
  label: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const root = ref(null)

const itemsArray = computed(() => {
  const it = props.items
  if (Array.isArray(it)) return it
  if (!it) return []
  if (Array.isArray(it.items)) return it.items
  if (Array.isArray(it.data)) return it.data
  if (typeof it === 'object') return Object.values(it)
  return []
})

const labelOf = (it) => (it && typeof it === 'object') ? it[props.labelField] : String(it ?? '')
const valueOf = (it) => (it && typeof it === 'object') ? it[props.valueField] : it

const selected = computed(() => itemsArray.value.find(i => valueOf(i) === props.modelValue))
const selectedLabel = computed(() => selected.value ? labelOf(selected.value) : '')

const filtered = computed(() => {
  if (!query.value) return itemsArray.value
  const q = query.value.toLowerCase()
  return itemsArray.value.filter(i => String(labelOf(i) || '').toLowerCase().includes(q))
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
