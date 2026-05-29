<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <AppHeader title="Attendance" :showBack="true" />

    <div class="p-4">
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-slate-500 font-medium">Checking for meetings...</p>
      </div>

      <div v-else-if="!meeting" class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 text-center">
        <div class="text-5xl mb-4">Ã°Å¸â€”â€œÃ¯Â¸Â</div>
        <h2 class="text-xl font-bold text-slate-800">No active or upcoming meeting</h2>
        <p class="text-slate-500 mt-2 text-sm">There is no meeting currently active or scheduled for your branch.</p>
        <button @click="fetchCurrentMeeting" class="mt-8 w-full bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-blue-100 uppercase tracking-widest text-xs active:scale-95 transition-all">Refresh</button>
      </div>

      <div v-else>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-4 overflow-hidden relative">
           <div class="absolute -right-6 -top-6 w-20 h-20 bg-blue-50 rounded-full opacity-50" />
          <div class="flex items-center justify-between mb-4 relative z-10">
            <span v-if="meeting.status === 'ongoing'" class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-widest rounded-full">Ongoing</span>
            <span v-else class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-widest rounded-full">Upcoming</span>
            <span class="text-slate-400 text-[10px] font-bold uppercase">{{ formatDate(meeting.date) }}</span>
          </div>
          <h2 class="text-xl font-black text-slate-800 relative z-10">{{ meeting.name }}</h2>
          <p class="text-slate-500 text-xs mt-1 relative z-10 leading-relaxed">{{ meeting.description }}</p>
          
          <div class="mt-4 flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest relative z-10">
            <span>Ã°Å¸â€¢â€™ {{ meeting.start_time }} - {{ meeting.end_time }}</span>
          </div>
        </div>

        <!-- Timer -->
        <div v-if="meeting.status === 'scheduled' || meeting.status === 'ongoing'" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-4 flex items-center justify-between relative overflow-hidden">
           <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-slate-50 rounded-full opacity-50" />
           <div class="relative z-10">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ meeting.status === 'scheduled' ? 'Starts In' : 'Ends In' }}</p>
            <p class="text-3xl font-black text-slate-800 tabular-nums tracking-tight">{{ timeRemaining || '--:--:--' }}</p>
          </div>
          <div class="h-14 w-14 bg-blue-50 rounded-2xl flex items-center justify-center text-3xl relative z-10">Ã¢ÂÂ³</div>
        </div>

        <!-- Already Marked -->
        <div v-if="record && record.status === 'present'" class="bg-blue-600 p-8 rounded-[2.5rem] text-center shadow-xl shadow-blue-100 text-white">
          <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 backdrop-blur-md">Ã¢Å“â€¦</div>
          <h3 class="text-xl font-black uppercase tracking-tight">Attendance Marked</h3>
          <p class="text-blue-50 text-xs mt-2 font-medium">You successfully marked your attendance at {{ formatTime(record.attended_at) }}.</p>
        </div>

        <!-- Excused -->
        <div v-else-if="record && (record.status === 'excused' || record.status === 'pending_excuse')" :class="[
          'p-8 rounded-[2.5rem] text-center shadow-xl text-white',
          record.status === 'excused' ? 'bg-blue-600 shadow-blue-100' : 'bg-slate-600 shadow-slate-100'
        ]">
          <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 backdrop-blur-md">
            {{ record.status === 'excused' ? 'Ã°Å¸â„¢Â' : 'Ã¢ÂÂ³' }}
          </div>
          <h3 class="text-xl font-black uppercase tracking-tight">
            {{ record.status === 'excused' ? 'Apology Approved' : 'Apology Pending' }}
          </h3>
          <p class="text-white/80 text-xs mt-2 font-medium">
            <template v-if="record.status === 'excused'">
              Your apology for this meeting was approved on {{ formatTime(record.excused_at) }}. You will not be charged.
            </template>
            <template v-else>
              Your apology has been submitted and is awaiting admin approval. You will not be charged automatically for now.
            </template>
          </p>
        </div>


        <!-- Mark Attendance Form -->
        <div v-else class="space-y-4">
          <div v-if="meeting.status === 'ongoing'" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
            <div class="flex items-center gap-2 mb-6">
               <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-lg">Ã°Å¸â€â€˜</div>
               <h3 class="font-black text-slate-800 text-sm uppercase tracking-tight">Verify Presence</h3>
            </div>
            
            <div class="space-y-6">
              <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Enter Meeting PIN</label>
                <input v-model="pin" type="text" maxlength="10" placeholder="Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢" 
                       class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl p-5 text-center text-3xl font-black tracking-[0.4em] focus:bg-white focus:border-blue-500 focus:ring-0 transition-all placeholder:tracking-normal placeholder:text-slate-200" />
                <p class="text-[9px] text-slate-400 mt-2 text-center font-bold uppercase">The PIN is announced by the Imam or Chairman</p>
              </div>

              <div class="p-5 bg-slate-50 rounded-2xl flex items-center gap-4 border border-slate-100">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-2xl shadow-sm">Ã°Å¸â€œÂ</div>
                <div class="flex-1 min-w-0">
                  <p class="text-[11px] font-black text-slate-800 uppercase tracking-tight">GPS Location</p>
                  <p class="text-[10px] text-slate-500 font-medium">Required radius: {{ meeting.radius_meters }}m</p>
                </div>
                <div v-if="locating" class="animate-spin rounded-full h-5 w-5 border-2 border-blue-600 border-t-transparent"></div>
                <div v-else-if="location" class="flex flex-col items-end">
                   <span class="text-blue-600 text-[10px] font-black uppercase tracking-widest">Captured</span>
                   <button @click="getLocation" class="text-[9px] text-slate-400 font-bold underline mt-0.5 uppercase">Reset</button>
                </div>
                <button v-else @click="getLocation" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md shadow-blue-100 active:scale-95 transition-all">Get</button>
              </div>

              <button @click="submitAttendance" :disabled="submitting || !pin || !location" 
                      class="w-full bg-blue-600 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-100 flex items-center justify-center gap-3 uppercase tracking-widest text-xs disabled:opacity-50 disabled:shadow-none active:scale-[0.98] transition-all mt-4">
                <span v-if="submitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                <span v-else>Ã°Å¸â€œÂ Mark Attendance</span>
              </button>
            </div>
          </div>

        <!-- Apology Form -->
        <div v-if="canSubmitApology && !inGracePeriod && (!record || record.status === 'absent')" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
             <div class="flex items-center gap-2 mb-4">
               <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-lg">Ã°Å¸â€œÂ</div>
               <h3 class="font-black text-slate-800 text-sm uppercase tracking-tight">Submit Apology</h3>
            </div>
            <p class="text-[11px] text-slate-500 mb-4">If you cannot attend or will be late, provide a reason here before the meeting starts to avoid fines.</p>
            
            <div class="space-y-4">
              <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Excuse Type</label>
                <select v-model="excuse_type" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-xs font-bold text-slate-800 focus:ring-1 focus:ring-blue-500 transition-all">
                   <option value="medical">Medical</option>
                   <option value="nursing_mother">Nursing Mother</option>
                   <option value="travel">Official Travel</option>
                   <option value="official">Official Duty</option>
                   <option value="other">Other</option>
                </select>
              </div>

              <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Reason</label>
                <textarea v-model="reason" placeholder="Explain your reason..." 
                          class="w-full bg-slate-50 border-none rounded-2xl p-4 text-xs font-medium focus:ring-1 focus:ring-blue-500 min-h-[80px]"></textarea>
              </div>

              <div v-if="excuse_type === 'medical' || excuse_type === 'nursing_mother' || excuse_type === 'travel'">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Attach Proof (Required for {{ excuse_type }})</label>
                <input type="file" @change="e => excuse_proof = e.target.files[0]" accept="image/*,application/pdf"
                       class="w-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl p-4 text-xs font-bold text-slate-800 focus:ring-1 focus:ring-blue-500" />
              </div>

              <button @click="submitApology" :disabled="submittingApology || !reason || ((excuse_type === 'medical' || excuse_type === 'nursing_mother' || excuse_type === 'travel') && !excuse_proof)" 
                      class="w-full bg-slate-800 text-white font-black py-4 rounded-2xl flex items-center justify-center gap-3 uppercase tracking-widest text-[10px] disabled:opacity-50 active:scale-95 transition-all">
                <span v-if="submittingApology" class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></span>
                <span v-else>Submit Apology</span>
              </button>
            </div>
          </div>

          <!-- Grace Period Info -->
          <div v-if="inGracePeriod && !record" class="bg-blue-50 p-6 rounded-3xl border border-blue-100 flex items-start gap-4">
            <div class="text-2xl">Ã°Å¸ÂÂ¼</div>
            <div>
              <h4 class="text-xs font-black text-blue-800 uppercase tracking-tight">Automatic Grace Period</h4>
              <p class="text-[10px] text-blue-600 font-medium mt-1">You are currently in the nursing mother grace period. You will not be charged for absence or lateness in this meeting.</p>
            </div>
          </div>
        </div>

        <!-- History -->
        <div class="mt-10 mb-6">
          <h3 class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Recent History</h3>
          
          <div v-if="loadingHistory && history.length === 0" class="space-y-3">
             <div v-for="i in 3" :key="i" class="h-20 bg-slate-100 rounded-3xl animate-pulse"></div>
          </div>
          
          <div v-else-if="history.length === 0" class="bg-white p-8 rounded-3xl border border-dashed border-slate-200 text-center">
             <p class="text-xs text-slate-400 font-bold uppercase">No history records yet</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="item in history" :key="item.id" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-4">
               <div :class="[
                 'w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-sm',
                 item.status === 'present' ? 'bg-blue-50 text-blue-600' : 
                 item.status === 'fine_paid' ? 'bg-orange-50 text-orange-600' :
                 item.status === 'excused' ? 'bg-blue-50 text-blue-600' :
                 item.status === 'pending_excuse' ? 'bg-slate-50 text-slate-600' :
                 item.status === 'fine_pending' ? 'bg-red-50 text-red-600' : 'bg-slate-50 text-slate-400'
               ]">
                 {{ item.status === 'present' ? 'Ã¢Å“â€¦' : item.status === 'fine_paid' ? 'Ã°Å¸â€™Â°' : 
                    item.status === 'excused' ? 'Ã°Å¸â„¢Â' : item.status === 'pending_excuse' ? 'Ã¢ÂÂ³' : 'Ã¢ÂÅ’' }}
               </div>
               
               <div class="flex-1 min-w-0">
                 <h4 class="text-sm font-black text-slate-800 truncate">{{ item.meeting?.name || 'Unknown Meeting' }}</h4>
                 <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[9px] font-bold text-slate-400 uppercase">{{ formatDate(item.created_at) }}</span>
                    <span v-if="item.status === 'fine_pending'" class="text-[8px] font-black bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full uppercase">Fine Pending</span>
                    <span v-if="item.status === 'fine_paid'" class="text-[8px] font-black bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full uppercase">Fine Paid</span>
                 </div>
               </div>
               
               <div class="text-right">
                  <p :class="[
                    'text-[10px] font-black uppercase tracking-tight',
                    item.status === 'present' ? 'text-blue-600' : 
                    item.status === 'fine_paid' ? 'text-orange-600' : 
                    item.status === 'excused' ? 'text-blue-600' :
                    item.status === 'pending_excuse' ? 'text-slate-600' : 'text-red-600'
                  ]">
                    {{ item.status.replace('_', ' ') }}
                  </p>
                  <p v-if="item.status === 'fine_pending' || item.status === 'fine_paid'" class="text-[9px] text-slate-400 font-bold mt-0.5">
                    Ã¢â€šÂ¦{{ formatMoney(item.meeting?.fine_amount) }}
                  </p>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <AppBottomNav />
  </div>
</template>

<script setup>
import {ref, onMounted, onUnmounted, computed} from 'vue'
import { Geolocation } from '@capacitor/geolocation'
import { Device } from '@capacitor/device'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'
import { useRouter } from 'vue-router'
import { useModal } from '../composables/useModal'
import { getEcho } from '../realtime/echo'

const router = useRouter()
const modal = useModal()

const loading = ref(true)
const meeting = ref(null)
const record = ref(null)
const inGracePeriod = ref(false)
const history = ref([])
const loadingHistory = ref(false)
const pin = ref('')
const reason = ref('')
const excuse_type = ref('other')
const excuse_proof = ref(null)
const location = ref(null)
const locating = ref(false)
const submitting = ref(false)
const submittingApology = ref(false)
const timeRemaining = ref('')
const countdownInterval = ref(null)
const refreshingStatus = ref(false)

const canSubmitApology = computed(() => {
  if (!meeting.value) return false
  if (meeting.value.status !== 'scheduled' && meeting.value.status !== 'ongoing') return false
  
  // Strict block: Only allow if now is before meeting start_time
  const now = new Date()
  const start = new Date(meeting.value.start_at)
  return now < start
})

const formatMoney = (val) => Number(val ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
const formatTime = (val) => val ? new Date(val).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''

const updateCountdown = () => {
  if (!meeting.value) return
  
  const now = new Date()
  const targetIso = meeting.value.status === 'scheduled' ? meeting.value.start_at : meeting.value.end_at
  const target = new Date(targetIso)
  const diff = target - now
  
  if (diff <= 0) {
    timeRemaining.value = '00:00:00'
    if (meeting.value.status !== 'completed' && meeting.value.status !== 'audited') {
       if (!refreshingStatus.value) {
         refreshingStatus.value = true
         setTimeout(() => {
           fetchCurrentMeeting().then(() => {
             refreshingStatus.value = false
           })
         }, 10000) // Wait 10s before next refresh if status didn't change
       }
    }
    return
  }
  
  const h = Math.floor(diff / 3600000)
  const m = Math.floor((diff % 3600000) / 60000)
  const s = Math.floor((diff % 60000) / 1000)
  
  timeRemaining.value = [h, m, s].map(v => v.toString().padStart(2, '0')).join(':')
}

const startCountdown = () => {
  if (countdownInterval.value) clearInterval(countdownInterval.value)
  updateCountdown()
  countdownInterval.value = setInterval(updateCountdown, 1000)
}

const fetchCurrentMeeting = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/attendance/current')
    meeting.value = res.data.meeting
    record.value = res.data.attendance_record
    inGracePeriod.value = res.data.in_grace_period
    
    // Auto-request location if meeting is ongoing and attendance not marked
    if (meeting.value && meeting.value.status === 'ongoing' && (!record.value || record.value.status !== 'present')) {
      getLocation()
    }

    if (meeting.value) {
      startCountdown()
    }
  } catch (err) {
    console.error('Attendance Check:', err)
  } finally {
    loading.value = false
    fetchHistory()
  }
}

const fetchHistory = async () => {
  loadingHistory.value = true
  try {
    const res = await axios.get('/api/attendance/history')
    history.value = res.data.data
  } catch (err) {
    console.error('Attendance History:', err)
  } finally {
    loadingHistory.value = false
  }
}

const getLocation = async () => {
  locating.value = true
  try {
    const position = await Geolocation.getCurrentPosition({
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    })
    
    location.value = {
      lat: position.coords.latitude,
      lng: position.coords.longitude
    }
  } catch (error) {
    console.error('Location Error:', error)
    const perms = await Geolocation.checkPermissions()
    if (perms.location !== 'granted') {
      const title = "Permission Required"
      const msg = "Location permission is required to verify your presence and mark attendance during a meeting. Please allow access in your device settings."
      const retry = await modal.confirm(msg, { confirmText: 'Try Again', title })
      if (retry) {
        await getLocation()
      }
    } else {
      await modal.alert("Could not get your location. Please check your GPS and try again.", "Location Error")
    }
  } finally {
    locating.value = false
  }
}

const submitAttendance = async () => {
  if (!pin.value) return
  submitting.value = true
  try {
    const info = await Device.getId()
    const res = await axios.post(`/api/meetings/${meeting.value.id}/mark-attendance`, {
      pin: pin.value,
      lat: location.value.lat,
      lng: location.value.lng,
      device_uuid: info.identifier
    })
    record.value = res.data.record
    modal.alert(res.data.message || "Attendance marked successfully!")
    fetchHistory()
  } catch (err) {
    modal.alert(err.response?.data?.message || "Failed to mark attendance")
  } finally {
    submitting.value = false
  }
}

const submitApology = async () => {
  if (!reason.value) return
  
  submittingApology.value = true
  const formData = new FormData()
  formData.append('reason', reason.value)
  formData.append('excuse_type', excuse_type.value)
  if (excuse_proof.value) {
    formData.append('proof', excuse_proof.value)
  }

  try {
    const res = await axios.post(`/api/meetings/${meeting.value.id}/apology`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    modal.alert(res.data.message || "Apology submitted successfully!")
    record.value = res.data.record
    reason.value = ''
    excuse_proof.value = null
    fetchHistory()
  } catch (err) {
    modal.alert(err.response?.data?.message || "Failed to submit apology")
  } finally {
    submittingApology.value = false
  }
}

onMounted(async () => {
  await fetchCurrentMeeting()

  // Real-time listener
  try {
    const echo = getEcho()
    const token = localStorage.getItem('token')
    if (token) {
      const { data: userData } = await axios.get('/api/profile', { headers: { Authorization: `Bearer ${token}` } })
      const userId = userData.id

      if (userId) {
        echo.private(`user.${userId}`)
          .listen('UserAccountUpdated', (e) => {
            console.log('Real-time update received in Attendance:', e)
            fetchCurrentMeeting()
          })
      }
    }
  } catch (err) {
    console.error('Failed to initialize real-time listener in Attendance:', err)
  }
})
onUnmounted(() => {
  if (countdownInterval.value) clearInterval(countdownInterval.value)
})
</script>


