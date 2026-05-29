<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue'
import axios from '../http.js'
import { getEcho } from '../realtime/echo.js'

const props = defineProps({
  roomId: {
    type: [Number, String],
    required: true
  },
  showBack: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['back'])

const messages = ref([])
const loading = ref(true)
const sending = ref(false)
const input = ref('')
const attachment = ref(null)
const attachmentPreview = ref(null)
const userId = ref(null)
const userRole = ref('member')
const room = ref(null)
const greetings = ref([])
const cannedResponses = ref({})
const showGreetings = ref(false)
const showCanned = ref(false)
const showFinActions = ref(false)
const status = ref({ away_message: null, is_prayer_time: false })
const typingUsers = ref({})
const editingMessage = ref(null)
const replyingTo = ref(null)

const listEl = ref(null)
const inputEl = ref(null)
let channel = null
let typingTimeout = null

async function fetchInitialData() {
  try {
    loading.value = true
    const [profileRes, roomRes, greetingsRes, cannedRes, statusRes] = await Promise.all([
      axios.get('/api/profile'),
      axios.get(`/api/chat/rooms/${props.roomId}`),
      axios.get('/api/chat/greetings'),
      axios.get('/api/chat/canned-responses'),
      axios.get('/api/chat/status')
    ])
    
    userId.value = profileRes.data.id
    userRole.value = profileRes.data.is_admin ? 'admin' : (profileRes.data.is_staff ? 'staff' : 'member')
    room.value = roomRes.data.room
    messages.value = roomRes.data.messages.data.reverse()
    greetings.value = greetingsRes.data
    cannedResponses.value = cannedRes.data
    status.value = statusRes.data
    
    scrollToBottom()
    setupRealtime()
    markRead()
  } catch (e) {
    console.error('Failed to fetch chat data', e)
  } finally {
    loading.value = false
  }
}

function setupRealtime() {
  const echo = getEcho()
  if (!echo) return

  channel = echo.private(`chat.room.${props.roomId}`)
    .listen('.message.sent', (e) => {
      if (!messages.value.find(m => m.id === e.message.id)) {
        messages.value.push(e.message)
        scrollToBottom()
        markRead()
      }
    })
    .listen('.message.updated', (e) => {
      const index = messages.value.findIndex(m => m.id === e.message.id)
      if (index !== -1) {
        messages.value[index] = e.message
      }
    })
    .listen('.message.deleted', (e) => {
      messages.value = messages.value.filter(m => m.id !== e.messageId)
    })
    .listen('.typing', (e) => {
      if (e.userId === userId.value) return
      
      typingUsers.value[e.userId] = e.userName
      
      setTimeout(() => {
        delete typingUsers.value[e.userId]
      }, 3000)
    })
}

function scrollToBottom() {
  nextTick(() => {
    if (listEl.value) {
      listEl.value.scrollTop = listEl.value.scrollHeight
    }
  })
}

async function markRead() {
  try {
    await axios.post(`/api/chat/rooms/${props.roomId}/read`)
  } catch (e) {}
}

function handleTyping() {
  // Auto-resize textarea
  if (inputEl.value) {
    inputEl.value.style.height = 'auto'
    const newHeight = Math.min(inputEl.value.scrollHeight, 120)
    inputEl.value.style.height = newHeight + 'px'
  }

  if (typingTimeout) clearTimeout(typingTimeout)
  
  axios.post(`/api/chat/rooms/${props.roomId}/typing`, { is_typing: true })
  
  typingTimeout = setTimeout(() => {
    axios.post(`/api/chat/rooms/${props.roomId}/typing`, { is_typing: false })
  }, 2000)
}

async function send(type = 'text', customData = null) {
  if (sending.value) return
  
  if (editingMessage.value) {
    saveEdit()
    return
  }

  const payload = customData || {
    body: input.value,
    type: type,
    metadata: replyingTo.value ? { reply_to: replyingTo.value } : null
  }

  if (!payload.body && !attachment.value && type === 'text') return

  try {
    sending.value = true
    const { data } = await axios.post(`/api/chat/rooms/${props.roomId}/messages`, payload)
    messages.value.push(data)
    input.value = ''
    showGreetings.value = false
    replyingTo.value = null
    if (inputEl.value) {
      inputEl.value.style.height = 'auto'
    }
    scrollToBottom()
  } catch (e) {
    console.error('Failed to send message', e)
  } finally {
    sending.value = false
  }
}

function startEdit(msg) {
  editingMessage.value = { ...msg }
  input.value = msg.body
  nextTick(() => {
    if (inputEl.value) {
      inputEl.value.focus()
      handleTyping()
    }
  })
}

async function saveEdit() {
  try {
    const { data } = await axios.patch(`/api/chat/messages/${editingMessage.value.id}`, {
      body: input.value
    })
    const index = messages.value.findIndex(m => m.id === data.id)
    if (index !== -1) messages.value[index] = data
    editingMessage.value = null
    input.value = ''
    if (inputEl.value) {
      inputEl.value.style.height = 'auto'
    }
  } catch (e) {
    console.error('Failed to edit', e)
  }
}

async function deleteMessage(id) {
  if (!confirm('Are you sure you want to delete this message? (Soft-delete for audit trail)')) return
  try {
    await axios.delete(`/api/chat/messages/${id}`)
    messages.value = messages.value.filter(m => m.id !== id)
  } catch (e) {
    console.error('Failed to delete', e)
  }
}

function setReply(msg) {
  replyingTo.value = {
    id: msg.id,
    body: msg.body,
    user_name: msg.user?.name || 'User'
  }
}

function useGreeting(greeting) {
  input.value = greeting
  send()
}

function sendFinAction(type) {
  showFinActions.value = false
  if (type === 'transaction') {
    input.value = "Salam, please make your monthly contribution payment."
    send('transaction', {
       body: input.value,
       type: 'transaction',
       metadata: { amount: 10000, category: 'Contribution', status: 'pending' }
    })
  } else if (type === 'peer_transfer') {
    input.value = "Salam, I've sent you the money for the shared expenses."
    send('peer_transfer', {
       body: input.value,
       type: 'peer_transfer',
       metadata: { amount: '₦5,000.00', note: 'Contribution for Sadaqah', status: 'completed' }
    })
  } else if (type === 'peer_request') {
    input.value = "Salam, I'm requesting the payment for the lunch we had."
    send('peer_request', {
       body: input.value,
       type: 'peer_request',
       metadata: { amount: '₦2,500.00', purpose: 'Lunch sharing', status: 'pending' }
    })
  } else if (type === 'bill_payment') {
    input.value = "Salam, I've paid the electricity bill for our branch."
    send('bill_payment', {
       body: input.value,
       type: 'bill_payment',
       metadata: { bill_type: 'Electricity', amount: '₦12,000.00', status: 'completed', paid_at: new Date().toLocaleString() }
    })
  } else if (type === 'mudarabah_update') {
    input.value = "Assalamu Alaikum, here is the update for our ongoing Rice Farming Mudarabah project."
    send('mudarabah_update', {
       body: input.value,
       type: 'mudarabah_update',
       metadata: { project_name: 'Rice Farming (Batch B)', roi: '15.5%', amount: '₦45,200.00', status: 'distributed' }
    })
  } else if (type === 'approval') {
    input.value = "Please review and sign the Qard Hasan loan agreement."
    send('approval', {
       body: input.value,
       type: 'approval',
       metadata: { title: 'Loan Agreement', description: 'Qard Hasan Contract', status: 'pending' }
    })
  } else if (type === 'inquiry') {
    input.value = "I have a question about my recent loan application."
    send('text', {
       body: input.value,
       type: 'text',
       metadata: { loan_inquiry: true, loan_id: 'L-7782' }
    })
  }
}

async function respondToMessage(message, action) {
  try {
    const { data } = await axios.post(`/api/chat/messages/${message.id}/respond`, { action })
    // Update message in local list
    const index = messages.value.findIndex(m => m.id === message.id)
    if (index !== -1) {
      messages.value[index] = data
    }
  } catch (e) {
    console.error('Failed to respond', e)
    const msg = e.response?.data?.error || 'Failed to complete action. Please check your balance or try again.'
    alert(msg)
  }
}

onMounted(fetchInitialData)
onBeforeUnmount(() => {
  if (channel) {
    channel.unsubscribe()
  }
})

const typingText = computed(() => {
  const users = Object.values(typingUsers.value)
  if (users.length === 0) return ''
  if (users.length === 1) return `${users[0]} is typing...`
  return 'Multiple people are typing...'
})

function hasBadge(user, type) {
  return user?.badges?.some(b => b.badge_type === type)
}

</script>

<template>
  <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900 border rounded-lg overflow-hidden shadow-xl">
    <!-- Header -->
    <div class="p-4 bg-white dark:bg-gray-800 border-b flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <button v-if="showBack" @click="emit('back')" class="md:hidden p-1 -ml-1 mr-1 text-gray-500 hover:text-blue-600 transition">
          <span class="material-icons">arrow_back</span>
        </button>
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold">
          {{ room?.name?.[0] || 'C' }}
        </div>
        <div>
          <h3 class="font-bold dark:text-white flex items-center max-w-[120px] sm:max-w-none">
            <span class="truncate">{{ room?.name || 'Cooperative Chat' }}</span>
            <span v-if="room?.type === 'private' && room?.users?.some(u => u.id !== userId && hasBadge(u, 'verified'))" 
                  class="material-icons text-blue-500 text-xs ml-1 flex-shrink-0" 
                  title="Member Verified">verified</span>
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ room?.type }}
            <span v-if="room?.metadata?.assigned_staff_id" class="ml-2 text-blue-600 font-medium">
              • Assigned to Staff
            </span>
          </p>
        </div>
      </div>
      <div class="flex items-center space-x-2">
        <div v-if="room?.metadata?.requires_2fa" class="flex items-center space-x-1 px-2 py-1 bg-amber-100 text-amber-700 text-[10px] rounded-full font-bold">
          <span class="material-icons text-xs">lock</span>
          <span class="hidden xs:inline">SENSITIVE (2FA)</span>
          <span class="xs:hidden">2FA</span>
        </div>
        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] rounded-full font-bold">
          <span class="hidden xs:inline">SHARIA COMPLIANT (ADAB)</span>
          <span class="xs:hidden">ADAB</span>
        </span>
      </div>
    </div>

    <!-- Messages -->
    <div ref="listEl" class="flex-1 overflow-y-auto p-4 space-y-4">
      <!-- Away Message / Status -->
      <div v-if="status.away_message" class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs mb-4">
        <div class="flex items-center space-x-2">
          <span class="material-icons text-sm">schedule</span>
          <span>{{ status.away_message }}</span>
        </div>
      </div>
      <div v-if="status.is_prayer_time" class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-xs mb-4">
        <div class="flex items-center space-x-2">
          <span class="material-icons text-sm">notifications_off</span>
          <span>Notifications are muted for prayer time. Assalamu Alaikum.</span>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center p-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
      
      <template v-else>
        <div v-for="msg in messages" :key="msg.id" 
             :class="['flex flex-col', msg.user_id === userId ? 'items-end' : 'items-start']">
          
          <div v-if="msg.user_id !== userId" class="flex items-center space-x-1 mb-1 ml-2">
            <span class="text-[10px] font-bold text-gray-500">{{ msg.user?.name }}</span>
            <span v-if="hasBadge(msg.user, 'verified')" class="material-icons text-[10px] text-blue-500">verified</span>
          </div>

          <div class="group relative flex items-center">
            <!-- Message Actions -->
            <div :class="['hidden group-hover:flex items-center space-x-1 absolute top-0 px-2 py-1 bg-white dark:bg-gray-800 rounded shadow-md border z-10', 
                          msg.user_id === userId ? 'right-full mr-2' : 'left-full ml-2']">
              <button @click="setReply(msg)" class="text-gray-500 hover:text-blue-600">
                <span class="material-icons text-xs">reply</span>
              </button>
              <button v-if="msg.user_id === userId && msg.type === 'text'" @click="startEdit(msg)" class="text-gray-500 hover:text-blue-600">
                <span class="material-icons text-xs">edit</span>
              </button>
              <button v-if="msg.user_id === userId" @click="deleteMessage(msg.id)" class="text-gray-500 hover:text-red-600">
                <span class="material-icons text-xs">delete</span>
              </button>
            </div>

            <div :class="[
              'max-w-[80vw] sm:max-w-[400px] rounded-2xl px-4 py-2 shadow-sm',
              msg.user_id === userId ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white dark:bg-gray-800 dark:text-white rounded-tl-none'
            ]">
              <!-- Reply context -->
              <div v-if="msg.metadata?.reply_to" 
                   class="mb-2 p-2 bg-black/5 dark:bg-white/5 rounded-lg border-l-4 border-blue-300 text-[10px] opacity-80 truncate">
                <p class="font-bold">{{ msg.metadata.reply_to.user_name }}</p>
                <p>{{ msg.metadata.reply_to.body }}</p>
              </div>

              <!-- Text message -->
              <p v-if="msg.type === 'text'" class="text-sm whitespace-pre-wrap">{{ msg.body }}</p>

              <!-- Transaction Card -->
              <div v-else-if="msg.type === 'transaction'" class="p-2 border rounded-lg bg-blue-50 dark:bg-blue-900/20 mt-1">
                <div class="flex items-center space-x-2 text-blue-700 dark:text-blue-400 mb-2">
                  <span class="material-icons text-sm">payments</span>
                  <span class="font-bold text-xs uppercase">Transaction Request</span>
                </div>
                <p class="text-sm mb-3 dark:text-gray-200">{{ msg.body }}</p>
                <button v-if="msg.metadata?.status === 'pending' && msg.user_id !== userId" 
                        @click="respondToMessage(msg, 'paid')"
                        class="w-full py-2 bg-blue-600 text-white text-xs rounded-md font-bold hover:bg-blue-700 transition">
                  Pay Instantly
                </button>
                <div v-else class="text-xs font-bold text-blue-600 bg-white dark:bg-gray-800 px-2 py-1 rounded text-center border border-blue-100">
                  {{ msg.metadata?.status.toUpperCase() }}
                </div>
              </div>

              <!-- Peer Transfer Card -->
              <div v-else-if="msg.type === 'peer_transfer'" class="p-2 border rounded-lg bg-blue-100 dark:bg-blue-800/40 mt-1 min-w-[200px]">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center space-x-1 text-blue-800 dark:text-blue-300">
                    <span class="material-icons text-sm">check_circle</span>
                    <span class="font-bold text-[10px] uppercase">Transfer Sent</span>
                  </div>
                  <span class="text-xs font-black text-blue-700 dark:text-blue-300">{{ msg.metadata?.amount }}</span>
                </div>
                <p class="text-xs dark:text-gray-200">{{ msg.metadata?.note || 'Funds transferred successfully.' }}</p>
              </div>

              <!-- Peer Request Card -->
              <div v-else-if="msg.type === 'peer_request'" class="p-2 border rounded-lg bg-amber-50 dark:bg-amber-900/20 mt-1">
                <div class="flex items-center space-x-2 text-amber-700 dark:text-amber-400 mb-2">
                  <span class="material-icons text-sm">request_quote</span>
                  <span class="font-bold text-xs uppercase">Payment Request</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                  <p class="text-sm font-bold dark:text-gray-200">{{ msg.metadata?.amount }}</p>
                  <span class="text-[10px] px-1 bg-amber-100 text-amber-700 rounded">{{ msg.metadata?.status }}</span>
                </div>
                <p v-if="msg.metadata?.purpose" class="text-xs mb-3 text-gray-600 dark:text-gray-400">{{ msg.metadata?.purpose }}</p>
                <button v-if="msg.metadata?.status === 'pending' && msg.user_id !== userId" 
                        @click="respondToMessage(msg, 'paid')"
                        class="w-full py-2 bg-amber-600 text-white text-xs rounded-md font-bold hover:bg-amber-700 transition">
                  Pay Now
                </button>
              </div>

              <!-- Bill Payment Card -->
              <div v-else-if="msg.type === 'bill_payment'" class="p-2 border rounded-lg bg-indigo-50 dark:bg-indigo-900/20 mt-1">
                <div class="flex items-center space-x-2 text-indigo-700 dark:text-indigo-400 mb-2">
                  <span class="material-icons text-sm">receipt_long</span>
                  <span class="font-bold text-xs uppercase">Bill Paid</span>
                </div>
                <div class="flex justify-between items-center">
                  <div>
                    <p class="text-sm font-bold dark:text-gray-200">{{ msg.metadata?.bill_type }}</p>
                    <p class="text-[10px] text-gray-500">{{ msg.metadata?.paid_at }}</p>
                  </div>
                  <p class="font-bold text-indigo-600">{{ msg.metadata?.amount }}</p>
                </div>
              </div>

              <!-- Mudarabah Update Card -->
              <div v-else-if="msg.type === 'mudarabah_update'" class="p-2 border rounded-lg bg-blue-50 dark:bg-blue-900/20 mt-1">
                <div class="flex items-center space-x-2 text-blue-700 dark:text-blue-400 mb-2">
                  <span class="material-icons text-sm">trending_up</span>
                  <span class="font-bold text-xs uppercase">Investment Update</span>
                </div>
                <p class="text-xs font-bold text-gray-700 dark:text-gray-200 mb-1">{{ msg.metadata?.project_name }}</p>
                <div class="flex justify-between items-center bg-white dark:bg-gray-800 p-2 rounded border border-blue-100 dark:border-blue-900">
                  <div class="text-center">
                    <p class="text-[8px] uppercase text-gray-400">Profit Share</p>
                    <p class="text-xs font-black text-blue-600">{{ msg.metadata?.roi }}</p>
                  </div>
                  <div class="text-center">
                    <p class="text-[8px] uppercase text-gray-400">Amount</p>
                    <p class="text-xs font-black text-blue-600">{{ msg.metadata?.amount }}</p>
                  </div>
                  <div class="text-center">
                    <p class="text-[8px] uppercase text-gray-400">Status</p>
                    <p class="text-[10px] font-bold text-blue-600">Distributed</p>
                  </div>
                </div>
              </div>

              <!-- Approval Card -->
              <div v-else-if="msg.type === 'approval'" class="p-2 border rounded-lg bg-blue-50 dark:bg-blue-900/20 mt-1">
                <div class="flex items-center space-x-2 text-blue-700 dark:text-blue-400 mb-2">
                  <span class="material-icons text-sm">verified</span>
                  <span class="font-bold text-xs uppercase">E-Signature Required</span>
                </div>
                <p class="text-sm font-bold dark:text-gray-200">{{ msg.metadata?.title }}</p>
                <p class="text-xs mb-3 text-gray-600 dark:text-gray-400">{{ msg.metadata?.description }}</p>
                <div v-if="msg.metadata?.status === 'pending' && msg.user_id !== userId" class="flex space-x-2">
                  <button @click="respondToMessage(msg, 'approved')"
                          class="flex-1 py-2 bg-blue-600 text-white text-xs rounded-md font-bold hover:bg-blue-700 transition">
                    Accept (Ikhlas)
                  </button>
                  <button @click="respondToMessage(msg, 'declined')"
                          class="flex-1 py-2 bg-gray-200 text-gray-700 text-xs rounded-md font-bold hover:bg-gray-300 transition">
                    Decline
                  </button>
                </div>
                <div v-else class="text-xs font-bold text-blue-600 bg-white dark:bg-gray-800 px-2 py-1 rounded text-center border border-blue-100">
                  {{ msg.metadata?.status.toUpperCase() }}
                </div>
              </div>

              <div class="flex items-center justify-end space-x-1 text-[10px] opacity-70 mt-1">
                <span v-if="msg.edited_at" class="mr-1 italic">(edited)</span>
                <span>{{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                <span v-if="msg.user_id === userId" class="material-icons text-[10px]" :class="msg.metadata?.read_at ? 'text-blue-300' : 'text-gray-300'">
                  {{ msg.metadata?.read_at ? 'done_all' : 'done' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-if="typingText" class="text-[10px] text-gray-500 italic px-4 py-1 animate-pulse">
          {{ typingText }}
        </div>
      </template>
    </div>

    <!-- Input -->
    <div class="p-4 bg-white dark:bg-gray-800 border-t">
      <!-- Reply Preview -->
      <div v-if="replyingTo" class="mb-2 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg flex justify-between items-center text-xs">
        <div class="truncate">
          <span class="font-bold text-blue-600">Replying to {{ replyingTo.user_name }}:</span>
          <span class="ml-1 opacity-70">{{ replyingTo.body }}</span>
        </div>
        <button @click="replyingTo = null" class="text-gray-500"><span class="material-icons text-sm">close</span></button>
      </div>

      <!-- Edit Indicator -->
      <div v-if="editingMessage" class="mb-2 px-2 text-[10px] font-bold text-blue-600 flex justify-between items-center">
        <span>EDITING MESSAGE</span>
        <button @click="editingMessage = null; input = ''" class="text-gray-500 uppercase">Cancel</button>
      </div>

      <!-- Greetings suggestions -->
      <div v-if="showGreetings" class="flex flex-wrap gap-2 mb-3">
        <button v-for="g in greetings" :key="g" 
                @click="useGreeting(g)"
                class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-xs hover:bg-blue-100 transition">
          {{ g }}
        </button>
      </div>

      <!-- Canned responses -->
      <div v-if="showCanned" class="flex flex-col space-y-2 mb-3 max-h-40 overflow-y-auto">
        <button v-for="(text, key) in cannedResponses" :key="key" 
                @click="input = text; showCanned = false"
                class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 text-left text-xs rounded-lg hover:bg-blue-100 transition">
          <p class="font-bold">{{ key }}</p>
          <p class="truncate opacity-70">{{ text }}</p>
        </button>
      </div>

      <!-- Fintech Quick Actions -->
      <div v-if="showFinActions" class="flex flex-col space-y-2 mb-3 bg-white dark:bg-gray-800 border rounded-xl p-3 shadow-lg">
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Quick Fintech Actions</p>
        <div class="grid grid-cols-2 gap-2">
          <button v-if="userRole !== 'member'" 
                  @click="sendFinAction('transaction')"
                  class="flex items-center space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition text-xs font-bold">
            <span class="material-icons text-sm">payments</span>
            <span>Request Payment</span>
          </button>
          <button @click="sendFinAction('peer_transfer')"
                  class="flex items-center space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition text-xs font-bold">
            <span class="material-icons text-sm">send</span>
            <span>Send Money</span>
          </button>
          <button @click="sendFinAction('peer_request')"
                  class="flex items-center space-x-2 p-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded-lg hover:bg-amber-100 transition text-xs font-bold">
            <span class="material-icons text-sm">request_quote</span>
            <span>Request Money</span>
          </button>
          <button @click="sendFinAction('bill_payment')"
                  class="flex items-center space-x-2 p-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 transition text-xs font-bold">
            <span class="material-icons text-sm">receipt_long</span>
            <span>Pay Bill</span>
          </button>
          <button v-if="userRole !== 'member'" 
                  @click="sendFinAction('approval')"
                  class="flex items-center space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition text-xs font-bold">
            <span class="material-icons text-sm">draw</span>
            <span>E-Signature</span>
          </button>
          <button @click="sendFinAction('inquiry')"
                  class="flex items-center space-x-2 p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 rounded-lg hover:bg-purple-100 transition text-xs font-bold">
            <span class="material-icons text-sm">help_outline</span>
            <span>Inquiry</span>
          </button>
          <button v-if="userRole !== 'member'" 
                  @click="sendFinAction('mudarabah_update')"
                  class="flex items-center space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition text-xs font-bold">
            <span class="material-icons text-sm">trending_up</span>
            <span>Investment Update</span>
          </button>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <button @click="showFinActions = !showFinActions; showGreetings = false; showCanned = false" 
                class="p-2 text-blue-600 hover:text-blue-700">
          <span class="material-icons">add_circle</span>
        </button>
        <button @click="showGreetings = !showGreetings; showCanned = false; showFinActions = false" 
                class="p-2 text-gray-500 hover:text-blue-600">
          <span class="material-icons">sentiment_satisfied_alt</span>
        </button>
        <button @click="showCanned = !showCanned; showGreetings = false; showFinActions = false" 
                class="p-2 text-gray-500 hover:text-blue-600">
          <span class="material-icons">quickreply</span>
        </button>
        <textarea v-model="input" 
               ref="inputEl"
               @input="handleTyping"
               @keyup.enter.exact.prevent="send('text')"
               placeholder="Type a message (Maintain Adab)..."
               rows="1"
               class="flex-1 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-xl px-4 py-2 text-base focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none overflow-y-auto max-h-[120px]"></textarea>
        <button @click="send('text')" 
                :disabled="sending || (!input.trim() && !attachment)"
                class="p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 disabled:opacity-50 transition-colors">
          <span class="material-icons">{{ editingMessage ? 'check' : 'send' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>


