<script setup>
import { ref, onMounted, computed, onBeforeUnmount } from 'vue'
import axios from '../http.js'
import IslamicChat from '../components/IslamicChat.vue'
import { getEcho } from '../realtime/echo.js'

const rooms = ref([])
const loading = ref(true)
const selectedRoomId = ref(null)
const searchQuery = ref('')
const pinnedRoomIds = ref(JSON.parse(localStorage.getItem('pinned_chats') || '[]'))
const user = ref(null)
const showBroadcastModal = ref(false)
const broadcastBody = ref('')
const sendingBroadcast = ref(false)

let userChannel = null

async function fetchRooms() {
  try {
    loading.value = true
    const { data } = await axios.get('/api/chat/rooms')
    rooms.value = data
    if (rooms.value.length > 0 && !selectedRoomId.value) {
      selectedRoomId.value = rooms.value[0].id
    }
    await fetchProfile()
  } catch (e) {
    console.error('Failed to fetch rooms', e)
  } finally {
    loading.value = false
  }
}

async function fetchProfile() {
  const { data } = await axios.get('/api/profile')
  user.value = data
  setupUserRealtime()
}

function setupUserRealtime() {
  const echo = getEcho()
  if (!echo || !user.value) return
  
  userChannel = echo.private(`App.Models.User.${user.value.id}`)
    .notification((notification) => {
      if (notification.type === 'chat_message') {
        fetchRooms() // Refresh list on new message
      }
    })
}

async function sendBroadcast() {
  if (!broadcastBody.value.trim()) return
  try {
    sendingBroadcast.value = true
    await axios.post('/api/chat/broadcast', { body: broadcastBody.value })
    showBroadcastModal.value = false
    broadcastBody.value = ''
    fetchRooms()
    alert('Broadcast sent successfully!')
  } catch (e) {
    alert('Failed to send broadcast')
  } finally {
    sendingBroadcast.value = false
  }
}

async function startSupportChat() {
  try {
    const { data } = await axios.get('/api/chat/support-room')
    if (!rooms.value.find(r => r.id === data.room.id)) {
      rooms.value.unshift(data.room)
    }
    selectedRoomId.value = data.room.id
  } catch (e) {
    console.error('Could not start support chat', e)
  }
}

async function joinRoom(room) {
  try {
    const { data } = await axios.post(`/api/chat/rooms/${room.id}/join`)
    // Update local room state
    const index = rooms.value.findIndex(r => r.id === room.id)
    if (index !== -1) {
      rooms.value[index] = data.room
    }
    selectedRoomId.value = room.id
  } catch (e) {
    alert('Failed to join room')
  }
}

const isMember = (room) => {
  if (!user.value) return false
  return room.users?.some(u => u.id === user.value.id)
}

const filteredRooms = computed(() => {
  let list = rooms.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(r => r.name?.toLowerCase().includes(q) || r.last_message?.body?.toLowerCase().includes(q))
  }
  
  // Sort: Pinned first, then by last message
  return [...list].sort((a, b) => {
    const aPinned = pinnedRoomIds.value.includes(a.id)
    const bPinned = pinnedRoomIds.value.includes(b.id)
    if (aPinned && !bPinned) return -1
    if (!aPinned && bPinned) return 1
    
    const aTime = a.last_message?.created_at ? new Date(a.last_message.created_at).getTime() : 0
    const bTime = b.last_message?.created_at ? new Date(b.last_message.created_at).getTime() : 0
    return bTime - aTime
  })
})

function togglePin(roomId) {
  if (pinnedRoomIds.value.includes(roomId)) {
    pinnedRoomIds.value = pinnedRoomIds.value.filter(id => id !== roomId)
  } else {
    pinnedRoomIds.value.push(roomId)
  }
  localStorage.setItem('pinned_chats', JSON.stringify(pinnedRoomIds.value))
}

function formatTime(dateStr) {
  const date = new Date(dateStr)
  const now = new Date()
  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

onMounted(fetchRooms)
onBeforeUnmount(() => {
  if (userChannel) userChannel.unsubscribe()
})
</script>

<template>
  <div class="flex h-[calc(100vh-80px)] overflow-hidden relative">
    <!-- Sidebar -->
    <div :class="['w-full md:w-80 bg-white dark:bg-gray-800 border-r flex flex-col absolute md:relative inset-y-0 left-0 z-10 transition-transform duration-300 ease-in-out md:translate-x-0', 
                  selectedRoomId ? '-translate-x-full md:translate-x-0' : 'translate-x-0']">
      <div class="p-4 border-b">
        <div class="flex justify-between items-center mb-3">
          <div class="flex items-center space-x-2">
            <button @click="$router.push('/dashboard')" class="p-1 -ml-1 text-gray-500 hover:text-blue-600 transition" title="Back to Dashboard">
              <span class="material-icons">arrow_back</span>
            </button>
            <h2 class="text-xl font-bold dark:text-white">Chat</h2>
          </div>
          <button v-if="user?.is_admin" 
                  @click="showBroadcastModal = true"
                  class="p-1 text-blue-600 hover:bg-blue-50 rounded-full transition"
                  title="Send Broadcast">
            <span class="material-icons">campaign</span>
          </button>
        </div>
        <div class="relative">
          <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">search</span>
          <input v-model="searchQuery" 
                 placeholder="Search chats..." 
                 class="w-full pl-9 pr-4 py-2 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="p-4 text-center text-gray-500">Loading rooms...</div>
        <div v-else-if="filteredRooms.length === 0" class="p-8 text-center">
          <span class="material-icons text-48 text-gray-300 mb-2">forum</span>
          <p class="text-sm text-gray-500 mb-4">{{ searchQuery ? 'No chats match your search' : 'No active chats yet' }}</p>
          <button v-if="!searchQuery" 
                  @click="startSupportChat"
                  class="w-full py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition">
            Start Support Chat
          </button>
        </div>
        <div v-else v-for="room in filteredRooms" :key="room.id" 
             @click="isMember(room) ? selectedRoomId = room.id : null"
             :class="['p-4 border-b cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition relative group', 
                      selectedRoomId === room.id ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-600' : '',
                      !isMember(room) ? 'opacity-80' : '']">
          
          <button v-if="isMember(room)" @click.stop="togglePin(room.id)" 
                  :class="['absolute right-2 top-2 p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition opacity-0 group-hover:opacity-100', 
                           pinnedRoomIds.includes(room.id) ? 'opacity-100 text-blue-600' : 'text-gray-400']">
            <span class="material-icons text-xs">{{ pinnedRoomIds.includes(room.id) ? 'push_pin' : 'push_pin' }}</span>
          </button>

          <div class="flex items-center space-x-3">
            <div :class="['w-12 h-12 rounded-full flex items-center justify-center font-bold flex-shrink-0', 
                          room.type === 'official' ? 'bg-amber-100 text-amber-700' : 
                          room.type === 'support' ? 'bg-blue-100 text-blue-700' : 'bg-blue-100 text-blue-700']">
              <span v-if="room.type === 'official'" class="material-icons text-xl">gavel</span>
              <span v-else-if="room.type === 'support'" class="material-icons text-xl">help_outline</span>
              <span v-else>{{ room.name?.[0] || 'C' }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-center mb-1">
                <div class="flex items-center space-x-1 truncate">
                  <p class="font-bold text-sm dark:text-white truncate">{{ room.name || 'Chat Room' }}</p>
                  <span v-if="room.type === 'official'" class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[8px] rounded font-bold uppercase">Official</span>
                  <span v-if="room.type === 'support'" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[8px] rounded font-bold uppercase">Support</span>
                </div>
                <span class="text-[10px] text-gray-500 whitespace-nowrap">
                  {{ room.last_message?.created_at ? formatTime(room.last_message.created_at) : '' }}
                </span>
              </div>
              <div class="flex justify-between items-center">
                <p v-if="isMember(room)" class="text-xs text-gray-500 truncate">{{ room.last_message?.body || 'No messages yet' }}</p>
                <button v-else @click.stop="joinRoom(room)" 
                        class="px-3 py-1 bg-blue-600 text-white text-[10px] rounded-lg font-bold hover:bg-blue-700 transition">
                  Join Room
                </button>
                <div v-if="isMember(room) && room.unread_count" class="ml-2 px-1.5 py-0.5 bg-blue-600 text-white text-[10px] rounded-full font-bold">
                  {{ room.unread_count }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div :class="['flex-1 bg-gray-100 dark:bg-gray-900 h-full absolute md:relative inset-y-0 right-0 w-full md:w-auto transition-transform duration-300 ease-in-out md:translate-x-0', 
                  selectedRoomId ? 'translate-x-0' : 'translate-x-full md:translate-x-0']">
      <IslamicChat v-if="selectedRoomId" :key="selectedRoomId" :room-id="selectedRoomId" :show-back="true" @back="selectedRoomId = null" />
      <div v-else class="h-full flex flex-col items-center justify-center text-gray-500">
        <span class="material-icons text-64 mb-4">chat_bubble_outline</span>
        <p>Select a conversation to start chatting</p>
      </div>
    </div>

    <!-- Broadcast Modal -->
    <div v-if="showBroadcastModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full p-6 shadow-2xl">
        <h3 class="text-lg font-bold dark:text-white mb-4">Send Broadcast Announcement</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          This message will be sent to all active group and public chat rooms. Use it for important announcements only.
        </p>
        <textarea v-model="broadcastBody" 
                  placeholder="Type your announcement here..." 
                  class="w-full h-32 p-3 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg resize-none mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        <div class="flex justify-end space-x-3">
          <button @click="showBroadcastModal = false" 
                  class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            Cancel
          </button>
          <button @click="sendBroadcast" 
                  :disabled="sendingBroadcast || !broadcastBody.trim()"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg disabled:opacity-50 flex items-center">
            <span v-if="sendingBroadcast" class="material-icons animate-spin text-sm mr-2">sync</span>
            {{ sendingBroadcast ? 'Sending...' : 'Send Broadcast' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.text-48 { font-size: 48px; }
.text-64 { font-size: 64px; }
</style>


