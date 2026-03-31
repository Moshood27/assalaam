<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '../http.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'unread'])

const open = ref(props.modelValue)
watch(() => props.modelValue, (v) => (open.value = v))
watch(open, (v) => emit('update:modelValue', v))

const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const unread = ref(0)

async function fetchList(p = 1) {
  try {
    loading.value = true
    error.value = ''
    const { data } = await axios.get('/api/notifications', { params: { page: p, per_page: 20 } })
    items.value = (data?.data || []).map(n => ({
      id: n.id,
      title: n.data?.title || 'Notification',
      message: n.data?.message || '',
      type: n.data?.type || '',
      read_at: n.read_at,
      created_at: n.created_at,
    }))
    page.value = data?.meta?.current_page || 1
    lastPage.value = data?.meta?.last_page || 1
    unread.value = data?.unread_count || 0
    emit('unread', unread.value)
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load notifications'
  } finally {
    loading.value = false
  }
}

async function markAll() {
  try {
    await axios.post('/api/notifications/read-all')
    items.value = items.value.map(it => ({ ...it, read_at: new Date().toISOString() }))
    unread.value = 0
    emit('unread', 0)
  } catch (_) {}
}

async function markOne(id) {
  try {
    await axios.post(`/api/notifications/${id}/read`)
    items.value = items.value.map(it => it.id === id ? { ...it, read_at: new Date().toISOString() } : it)
    unread.value = Math.max(0, unread.value - 1)
    emit('unread', unread.value)
  } catch (_) {}
}

onMounted(() => {
  if (open.value) fetchList()
})
watch(open, (v) => { if (v) fetchList(page.value) })
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-40 bg-black/40" @click="open = false"></div>
  </transition>
  <transition name="slide">
    <aside v-if="open" class="fixed right-0 top-0 h-full w-full sm:w-[420px] bg-white shadow-xl z-50 flex flex-col">
      <header class="p-4 border-b flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="i-mdi-bell-outline text-xl"></span>
          <h2 class="font-semibold">Inbox</h2>
        </div>
        <button class="text-sm text-blue-600 hover:underline" @click="markAll" :disabled="unread===0">Mark all as read</button>
      </header>
      <div class="p-3 text-sm text-slate-500" v-if="loading">Loading...</div>
      <div class="p-3 text-sm text-red-600" v-if="error">{{ error }}</div>
      <ul class="flex-1 overflow-y-auto divide-y">
        <li v-for="it in items" :key="it.id" class="p-4 flex gap-3 items-start" :class="!it.read_at ? 'bg-amber-50' : ''">
          <div>
            <div class="font-medium">{{ it.title }}</div>
            <div class="text-sm text-slate-600 whitespace-pre-line">{{ it.message }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ new Date(it.created_at).toLocaleString() }}</div>
          </div>
          <button v-if="!it.read_at" class="ml-auto text-xs text-blue-600 hover:underline" @click="markOne(it.id)">Mark read</button>
        </li>
        <li v-if="!loading && items.length===0" class="p-6 text-center text-slate-500">No notifications yet.</li>
      </ul>
      <footer class="p-3 border-t flex items-center justify-between text-sm">
        <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="page<=1" @click="fetchList(page-1)">Prev</button>
        <div>Page {{ page }} / {{ lastPage }}</div>
        <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="page>=lastPage" @click="fetchList(page+1)">Next</button>
      </footer>
    </aside>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-enter-active, .slide-leave-active { transition: transform .25s; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
