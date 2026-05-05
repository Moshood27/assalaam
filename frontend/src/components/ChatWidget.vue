<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from '../http.js'
import { getEcho } from '../realtime/echo.js'

const messages = ref([])
const loading = ref(true)
const sending = ref(false)
const input = ref('')
const attachment = ref(null)
const attachmentPreview = ref(null)
const userId = ref(null)
const fileInput = ref(null)
const adminIsTyping = ref(false)
let typingTimeout = null
let adminTypingTimeout = null

function onInput() {
  if (typingTimeout) clearTimeout(typingTimeout)
  
  // Notify backend only once every few seconds
  if (!typingTimeout) {
    axios.post('/api/support/typing', { is_typing: true }).catch(() => {})
  }

  typingTimeout = setTimeout(() => {
    axios.post('/api/support/typing', { is_typing: false }).catch(() => {})
    typingTimeout = null
  }, 3000)
}

function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  attachment.value = file
  if (file.type.startsWith('image/')) {
    attachmentPreview.value = URL.createObjectURL(file)
  } else {
    attachmentPreview.value = null
  }
}

function removeAttachment() {
  attachment.value = null
  attachmentPreview.value = null
  if (fileInput.value) fileInput.value.value = ''
}
let channel = null
let page = 1
let lastPage = 1
const listEl = ref(null)

async function fetchProfile() {
  const token = localStorage.getItem('token')
  if (!token) return

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
  if (!body && !attachment.value || sending.value) return
  try {
    sending.value = true
    const formData = new FormData()
    if (body) formData.append('body', body)
    if (attachment.value) formData.append('attachment', attachment.value)

    const { data } = await axios.post('/api/support/messages', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    input.value = ''
    removeAttachment()
    
    const m = data?.data
    if (m) {
      // Check if already exists (might have come through Echo)
      if (!messages.value.find(existing => existing.id === m.id)) {
        messages.value.push(m)
      }
    }
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
          if (!messages.value.find(m => m.id === e.message.id)) {
            messages.value.push(e.message)
          }
          scrollToBottom()
        }
      })
      .listen('.SupportTyping', (e) => {
        if (e && e.senderType === 'admin') {
          adminIsTyping.value = e.isTyping
          if (adminTypingTimeout) clearTimeout(adminTypingTimeout)
          if (e.isTyping) {
            adminTypingTimeout = setTimeout(() => {
              adminIsTyping.value = false
            }, 5000)
          }
          scrollToBottom()
        }
      })
      .listen('.SupportMessagesRead', (e) => {
        if (e && e.readerType === 'admin') {
          messages.value.forEach(m => {
            if (m.sender_type === 'member' && !m.read_at) {
              m.read_at = new Date().toISOString()
            }
          })
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
            <div v-if="m.type === 'image' && m.attachment" class="mb-1">
              <a :href="m.attachment" target="_blank">
                <img :src="m.attachment" class="rounded-lg max-h-48 w-auto object-cover" />
              </a>
            </div>
            <div v-else-if="m.type === 'file' && m.attachment" class="mb-1 p-2 bg-black/10 rounded flex items-center gap-2">
              <span class="i-mdi-file-document-outline text-xl opacity-50"></span>
              <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold truncate">{{ m.attachment_name }}</p>
                <a :href="m.attachment" target="_blank" class="text-[9px] underline opacity-70">Download</a>
              </div>
            </div>

            <p class="whitespace-pre-wrap">{{ m.body }}</p>
            <div class="flex justify-between items-center mt-1 gap-2">
              <p class="text-[9px] opacity-70">{{ new Date(m.created_at||Date.now()).toLocaleTimeString() }}</p>
              <span v-if="m.sender_type==='member'" class="text-[9px] font-bold">
                {{ m.read_at ? 'Read' : 'Sent' }}
              </span>
            </div>
          </div>
        </div>
        <div v-if="adminIsTyping" class="flex justify-start">
          <div class="bg-white border rounded-2xl px-3 py-1 text-[10px] text-slate-500 italic animate-pulse shadow-sm">
            Rep is typing...
          </div>
        </div>
      </template>
    </div>

    <div v-if="attachment" class="px-4 py-2 bg-slate-100 border-t flex items-center justify-between">
      <div class="flex items-center gap-2 overflow-hidden">
        <img v-if="attachmentPreview" :src="attachmentPreview" class="w-8 h-8 object-cover rounded" />
        <span v-else class="i-mdi-file text-xl text-slate-400"></span>
        <span class="text-[10px] truncate text-slate-600">{{ attachment.name }}</span>
      </div>
      <button @click="removeAttachment" class="text-rose-500 text-lg i-mdi-close-circle"></button>
    </div>

    <div class="p-3 border-t flex items-center gap-2">
      <button @click="$refs.fileInput.click()" class="text-slate-400 hover:text-emerald-600 transition p-1">
        <span class="i-mdi-paperclip text-2xl"></span>
      </button>
      <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
      
      <input v-model="input" type="text" placeholder="Type your message…" class="flex-1 border rounded-xl px-3 py-2 outline-none focus:ring-2 ring-emerald-500" @input="onInput" @keydown.enter.prevent="send" />
      <button :disabled="sending || (!input.trim() && !attachment)" @click="send" class="px-4 py-2 rounded-xl bg-emerald-600 text-white disabled:opacity-50 flex items-center justify-center min-w-[70px]">
        <span v-if="sending" class="i-mdi-loading animate-spin text-xl"></span>
        <span v-else>Send</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
</style>
