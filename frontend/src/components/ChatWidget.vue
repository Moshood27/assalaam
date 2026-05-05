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
const textarea = ref(null)
const adminIsTyping = ref(false)
let typingTimeout = null
let adminTypingTimeout = null

function onInput(e) {
  if (e?.target) {
    e.target.style.height = 'auto'
    e.target.style.height = (e.target.scrollHeight) + 'px'
  }

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
    if (textarea.value) textarea.value.style.height = 'auto'
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
    <div class="p-4 border-b flex items-center justify-between bg-white sticky top-0 z-10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shadow-inner">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 .621-.504 1.125-1.125 1.125h-1.5a1.125 1.125 0 0 1-1.125-1.125v-4.25c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125Zm-16.5 0v4.25c0 .621.504 1.125 1.125 1.125h1.5a1.125 1.125 0 0 0 1.125-1.125v-4.25c0-.621-.504-1.125-1.125-1.125h-1.5a1.125 1.125 0 0 0-1.125 1.125ZM12 3c4.97 0 9 4.03 9 9.375v.125c0 .414-.336.75-.75.75h-1.5a.75.75 0 0 1-.75-.75V12c0-4.142-3.358-7.5-7.5-7.5S4.5 7.858 4.5 12v.5c0 .414-.336.75-.75.75h-1.5a.75.75 0 0 1-.75-.75v-.125C1.5 7.03 5.53 3 12 3Z" />
          </svg>
        </div>
        <div>
          <p class="font-bold text-slate-800 leading-none">Support Team</p>
          <div class="flex items-center gap-1.5 mt-1">
            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Online</p>
          </div>
        </div>
      </div>
    </div>

    <div ref="listEl" class="p-4 h-80 overflow-y-auto space-y-2 bg-slate-50">
      <div v-if="loading" class="text-center text-slate-500 text-sm">Loading…</div>
      <template v-else>
        <div v-if="messages.length===0" class="text-center text-slate-400 text-sm py-8">No messages yet. Say hi 👋</div>
        <div v-for="m in messages" :key="m.id" class="flex" :class="m.sender_type==='member' ? 'justify-end' : 'justify-start'">
          <div :class="m.sender_type==='member' ? 'bg-emerald-600 text-white rounded-br-none' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-none'" class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-sm relative group">
            <div v-if="m.type === 'image' && m.attachment" class="mb-2 -mx-1 -mt-1">
              <a :href="m.attachment" target="_blank">
                <img :src="m.attachment" class="rounded-xl max-h-64 w-full object-cover border border-white/10" />
              </a>
            </div>
            <div v-else-if="m.type === 'file' && m.attachment" class="mb-2 p-3 bg-black/5 rounded-xl flex items-center gap-3 border border-black/5">
              <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 opacity-60">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-[11px] font-bold truncate">{{ m.attachment_name }}</p>
                <a :href="m.attachment" target="_blank" class="text-[10px] font-medium underline opacity-80 hover:opacity-100 transition-opacity">Download File</a>
              </div>
            </div>

            <p class="whitespace-pre-wrap leading-relaxed">{{ m.body }}</p>
            <div class="flex justify-end items-center mt-1 gap-1.5">
              <p class="text-[10px] opacity-60 font-medium">{{ new Date(m.created_at||Date.now()).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
              <div v-if="m.sender_type==='member'" class="flex items-center">
                <svg v-if="m.read_at" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-blue-300">
                  <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                  <path fill-rule="evenodd" d="M12.566 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" transform="translate(4,0)" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 opacity-60">
                  <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                </svg>
              </div>
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

    <div v-if="attachment" class="px-4 py-3 bg-slate-50 border-t flex items-center justify-between">
      <div class="flex items-center gap-3 overflow-hidden">
        <div v-if="attachmentPreview" class="relative shrink-0">
          <img :src="attachmentPreview" class="w-10 h-10 object-cover rounded-lg border border-slate-200 shadow-sm" />
        </div>
        <div v-else class="w-10 h-10 bg-slate-200 rounded-lg flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-slate-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-xs font-bold truncate text-slate-700">{{ attachment.name }}</p>
          <p class="text-[10px] text-slate-400 capitalize">{{ (attachment.type || 'file').split('/')[0] }} attachment</p>
        </div>
      </div>
      <button @click="removeAttachment" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="p-4 border-t bg-white flex items-end gap-3">
      <button @click="$refs.fileInput.click()" class="p-2.5 text-slate-400 hover:text-emerald-600 transition-colors hover:bg-emerald-50 rounded-xl">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32a1.5 1.5 0 1 1-2.121-2.121l10.94-10.94" />
        </svg>
      </button>
      <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
      
      <div class="flex-1 relative">
        <textarea 
          ref="textarea"
          v-model="input" 
          placeholder="Message..." 
          class="w-full border-none bg-slate-100 rounded-2xl px-4 py-3 outline-none focus:ring-2 ring-emerald-500/50 resize-none text-sm transition-all min-h-[46px] max-h-32" 
          rows="1"
          @input="onInput" 
          @keydown.enter.prevent="send"
        ></textarea>
      </div>

      <button 
        :disabled="sending || (!input.trim() && !attachment)" 
        @click="send" 
        class="w-12 h-12 rounded-full bg-emerald-600 text-white disabled:opacity-40 disabled:bg-slate-300 shadow-lg shadow-emerald-200 disabled:shadow-none flex items-center justify-center shrink-0 transition-all hover:bg-emerald-700 active:scale-95"
      >
        <svg v-if="sending" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 ml-0.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
</style>
