<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from '../http.js'
import { getEcho } from '../realtime/echo.js'

const messages = ref([])
const loading = ref(true)
const sending = ref(false)
const input = ref('')
const userId = ref(null)
let channel = null
let page = 1
let lastPage = 1
const listEl = ref(null)

async function fetchProfile() {
  try {
    const { data } = await axios.get('/api/profile')
    userId.value = data?.id || null
  } catch (e) {
    console.warn('fetchProfile failed', e?.message || e)
    userId.value = null
  }
}

function scrollToBottom() {
  nextTick(() => {
    try {
      const el = listEl.value
      if (el) el.scrollTop = el.scrollHeight
    } catch (_) {}
  })
}

async function loadMessages(initial = false) {
  if (!userId.value) return
  try {
    loading.value = true
    const { data } = await axios.get('/api/support/messages', { params: { per_page: 50, page } })
    lastPage = Number(data?.last_page || 1)
    const rows = Array.isArray(data?.data) ? data.data.slice().reverse() : []
    if (initial) {
      messages.value = rows
      // Mark all admin messages as read when opening
      try { await axios.post('/api/support/read') } catch (_) {}
      scrollToBottom()
    } else {
      messages.value = rows
    }
  } catch (e) {
    console.warn('Failed to load messages', e?.message || e)
  } finally {
    loading.value = false
  }
}

async function send() {
  const body = input.value.trim()
  if (!body || sending.value) return
  try {
    sending.value = true
    const { data } = await axios.post('/api/support/messages', { body })
    input.value = ''
    // The server will broadcast; we can optimistically append too
    const m = data?.data
    if (m) messages.value.push(m)
    scrollToBottom()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to send')
  } finally {
    sending.value = false
  }
}

function subscribe() {
  try {
    if (!userId.value) return
    const Echo = getEcho()
    channel = Echo.private(`support.${userId.value}`)
      .listen('.SupportMessageSent', (e) => {
        // e.message contains the message payload
        if (e && e.message) {
          messages.value.push(e.message)
          scrollToBottom()
        }
      })
  } catch (e) {
    console.warn('Echo subscribe failed', e)
  }
}

function unsubscribe() {
  try {
    if (channel && userId.value) {
      const Echo = getEcho()
      Echo.leave(`support.${userId.value}`)
      channel = null
    }
  } catch (_) {}
}

onMounted(async () => {
  try {
    loading.value = true
    await fetchProfile()
    if (userId.value) {
      await loadMessages(true)
      subscribe()
    }
  } catch (err) {
    console.error('Initialization failed', err)
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => unsubscribe())
</script>

<template>
  <div class="rounded-3xl border bg-white shadow-sm">
    <div class="p-4 border-b flex items-center gap-2">
      <span class="i-mdi-headset text-emerald-600 text-2xl"></span>
      <div>
        <p class="font-bold">In-App Chat</p>
        <p class="text-xs text-slate-500">Chat with a co-op rep</p>
      </div>
    </div>

    <div ref="listEl" class="p-4 h-80 overflow-y-auto space-y-2 bg-slate-50">
      <div v-if="loading" class="text-center text-slate-500 text-sm">Loading…</div>
      <template v-else>
        <div v-if="messages.length===0" class="text-center text-slate-400 text-sm py-8">No messages yet. Say hi 👋</div>
        <div v-for="m in messages" :key="m.id" class="flex" :class="m.sender_type==='member' ? 'justify-end' : 'justify-start'">
          <div :class="m.sender_type==='member' ? 'bg-emerald-600 text-white' : 'bg-white border'" class="max-w-[80%] rounded-2xl px-3 py-2 text-sm shadow">
            <p class="whitespace-pre-wrap">{{ m.body }}</p>
            <p class="text-[10px] opacity-70 mt-1 text-right">{{ new Date(m.created_at||Date.now()).toLocaleString() }}</p>
          </div>
        </div>
      </template>
    </div>

    <div class="p-3 border-t flex items-center gap-2">
      <input v-model="input" type="text" placeholder="Type your message…" class="flex-1 border rounded-xl px-3 py-2 outline-none focus:ring-2 ring-emerald-500" @keydown.enter.prevent="send" />
      <button :disabled="sending || !input.trim()" @click="send" class="px-4 py-2 rounded-xl bg-emerald-600 text-white disabled:opacity-50">Send</button>
    </div>
  </div>
</template>

<style scoped>
</style>
