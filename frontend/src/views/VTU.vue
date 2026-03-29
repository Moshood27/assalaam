<template>
  <div class="min-h-screen bg-slate-50 pb-24 font-sans">
    <!-- Header -->
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="text-2xl hover:opacity-70 transition">⬅️</button>
        <h1 class="text-lg sm:text-xl font-bold text-slate-800">Airtime, Data & Bills</h1>
        <router-link to="/vtu/history" class="btn-ghost text-xs">History</router-link>
      </div>
    </header>

    <div class="p-4 space-y-6 max-w-md mx-auto">
      <!-- Balance Card -->
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-7 text-white shadow-xl transform transition-all active:scale-95">
        <p class="text-emerald-100 text-sm font-medium">Available Wallet Balance</p>
        <h2 class="text-4xl font-bold mt-1 tracking-tight">₦ {{ formatMoney(balance) }}</h2>
      </div>

      <!-- Tab Switcher -->
      <div class="segmented grid grid-cols-4 gap-1">
        <button class="segment" :class="tab==='airtime' ? 'segment-active' : ''" @click="tab='airtime'">Airtime</button>
        <button class="segment" :class="tab==='data' ? 'segment-active' : ''" @click="tab='data'">Data</button>
        <button class="segment" :class="tab==='electricity' ? 'segment-active' : ''" @click="tab='electricity'">Electricity</button>
        <button class="segment" :class="tab==='cable' ? 'segment-active' : ''" @click="tab='cable'">Cable TV</button>
      </div>

      <!-- Airtime Form -->
      <div v-if="tab==='airtime'" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 space-y-4">
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Select Network</label>
          <select v-model="airtime.network" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500 transition-colors">
            <option value="mtn">MTN</option>
            <option value="airtel">Airtel</option>
            <option value="glo">Glo</option>
            <option value="9mobile">9mobile</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Phone Number</label>
          <input v-model="airtime.phone" type="tel" placeholder="0803 000 0000" class="w-full bg-slate-50 p-4 rounded-xl border border-slate-200 text-sm outline-none focus:border-emerald-500" />
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Amount (₦)</label>
          <input v-model.number="airtime.amount" type="number" min="50" placeholder="e.g. 100" class="w-full bg-slate-50 p-4 rounded-xl border border-slate-200 text-sm outline-none focus:border-emerald-500" />
        </div>
        <button @click="buyAirtime" :disabled="loadingAirtime || !canBuyAirtime" class="w-full bg-emerald-700 disabled:bg-slate-300 text-white px-5 py-4 rounded-2xl font-bold shadow-lg shadow-emerald-200 transition-all active:scale-95">
          <span v-if="loadingAirtime" class="flex items-center justify-center gap-2">
             <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
             Processing...
          </span>
          <span v-else>Buy Airtime</span>
        </button>
      </div>

      <!-- Data Form -->
      <div v-if="tab==='data'" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Network</label>
            <select v-model="dataForm.network" @change="loadBundles" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500">
              <option value="mtn">MTN</option>
              <option value="airtel">Airtel</option>
              <option value="glo">Glo</option>
              <option value="9mobile">9mobile</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Phone Number</label>
            <input v-model="dataForm.phone" type="tel" placeholder="0803..." class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500" />
          </div>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Select Bundle</label>
          <select v-model="dataForm.bundleCode" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500">
            <option disabled value="">Choose a plan...</option>
            <option v-for="b in bundles" :key="b.code" :value="b.code">
              {{ b.name }} — ₦ {{ formatMoney(b.amount) }}
            </option>
          </select>
          <p v-if="selectedBundle" class="mt-2 text-xs text-slate-500 ml-1 italic text-center">
            Total to be debited: <span class="font-bold text-emerald-700">₦ {{ formatMoney(selectedBundle.total_debit) }}</span>
          </p>
        </div>
        <button @click="buyData" :disabled="loadingData || !canBuyData" class="w-full bg-emerald-700 disabled:bg-slate-300 text-white px-5 py-4 rounded-2xl font-bold shadow-lg shadow-emerald-200 transition-all active:scale-95">
          <span v-if="loadingData">Processing...</span>
          <span v-else>Buy Data Bundle</span>
        </button>
      </div>

      <!-- Electricity Form -->
      <div v-if="tab==='electricity'" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Disco</label>
            <select v-model="electricity.disco" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500">
              <option value="aedc">AEDC</option>
              <option value="ekedc">EKEDC</option>
              <option value="ikeja-electric">IKEDC</option>
              <option value="ibedc">IBEDC</option>
              <option value="eedc">EEDC</option>
              <option value="kedco">KEDCO</option>
              <option value="phed">PHED</option>
              <option value="jed">JED</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Meter Type</label>
            <select v-model="electricity.meterType" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500">
              <option value="prepaid">Prepaid</option>
              <option value="postpaid">Postpaid</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Meter Number</label>
          <input v-model="electricity.meter" type="text" placeholder="e.g. 1234567890" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500" />
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Amount (₦)</label>
          <input v-model.number="electricity.amount" type="number" min="100" placeholder="e.g. 1000" class="w-full bg-slate-50 p-4 rounded-xl border border-slate-200 text-sm outline-none focus:border-emerald-500" />
          <p class="mt-2 text-[10px] text-slate-500 italic ml-1">Note: A small convenience fee may apply.</p>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Phone (Optional)</label>
          <input v-model="electricity.phone" type="tel" placeholder="0803..." class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500" />
        </div>
        <button @click="buyElectricity" :disabled="loadingElectricity || !canBuyElectricity" class="w-full bg-emerald-700 disabled:bg-slate-300 text-white px-5 py-4 rounded-2xl font-bold shadow-lg shadow-emerald-200 transition-all active:scale-95">
          <span v-if="loadingElectricity">Processing...</span>
          <span v-else>Vend Electricity</span>
        </button>
      </div>

      <!-- Cable TV Form -->
      <div v-if="tab==='cable'" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Service</label>
            <select v-model="cable.service" @change="loadTvBundles" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500">
              <option value="dstv">DSTV</option>
              <option value="gotv">GOTV</option>
              <option value="startimes">Startimes</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Smartcard Number</label>
            <input v-model="cable.smartcard" type="text" placeholder="e.g. 1234567890" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500" />
          </div>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Select Package</label>
          <select v-model="cable.bundleCode" class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500">
            <option disabled value="">Choose a package...</option>
            <option v-for="b in tvBundles" :key="b.code" :value="b.code">
              {{ b.name }} — ₦ {{ formatMoney(b.amount) }}
            </option>
          </select>
          <p v-if="selectedTvBundle" class="mt-2 text-xs text-slate-500 ml-1 italic text-center">
            Total to be debited: <span class="font-bold text-emerald-700">₦ {{ formatMoney(selectedTvBundle.total_debit) }}</span>
          </p>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Phone (Optional)</label>
          <input v-model="cable.phone" type="tel" placeholder="0803..." class="w-full bg-slate-50 p-4 rounded-xl border-slate-200 text-sm outline-none focus:border-emerald-500" />
        </div>
        <button @click="buyCable" :disabled="loadingCable || !canBuyCable" class="w-full bg-emerald-700 disabled:bg-slate-300 text-white px-5 py-4 rounded-2xl font-bold shadow-lg shadow-emerald-200 transition-all active:scale-95">
          <span v-if="loadingCable">Processing...</span>
          <span v-else>Subscribe</span>
        </button>
      </div>
    </div>

    <!-- Custom Notice Modal (reusable) -->
    <CustomNotice
      v-model="notice.visible"
      :type="notice.type"
      :title="notice.title"
      :message="notice.message"
      @close="closeNotice"
    />

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn bottom-nav-btn-active" @click="$router.push('/vtu')">
        <span class="text-xl">📶</span>
        <span class="text-[10px] font-bold">Services</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/wallet')">
        <span class="text-xl">👛</span>
        <span class="text-[10px] font-bold">Wallet</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'

// State
const balance = ref(0)
const tab = ref('airtime')
const bundles = ref([])
const tvBundles = ref([])
const loadingAirtime = ref(false)
const loadingData = ref(false)
const loadingElectricity = ref(false)
const loadingCable = ref(false)

const airtime = ref({ network: 'mtn', phone: '', amount: '' })
const dataForm = ref({ network: 'mtn', phone: '', bundleCode: '' })
const electricity = ref({ disco: 'aedc', meterType: 'prepaid', meter: '', amount: '', phone: '' })
const cable = ref({ service: 'dstv', smartcard: '', bundleCode: '', phone: '' })

// Custom Notice State (shared)
const { notice, showNotice, closeNotice } = useNotice()
// Keep backward-compatible naming inside this file
const showCustomNotice = (title, message, type = 'info') => showNotice(title, message, type)

// Helpers
const formatMoney = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const canBuyAirtime = computed(() => !!airtime.value.network && airtime.value.phone?.length >= 10 && Number(airtime.value.amount) >= 50)
const canBuyData = computed(() => !!dataForm.value.network && dataForm.value.phone?.length >= 10 && !!dataForm.value.bundleCode)
const canBuyElectricity = computed(() => !!electricity.value.disco && !!electricity.value.meterType && electricity.value.meter?.length >= 6 && Number(electricity.value.amount) >= 100)
const canBuyCable = computed(() => !!cable.value.service && cable.value.smartcard?.length >= 6 && !!cable.value.bundleCode)
const selectedBundle = computed(() => bundles.value.find(b => b.code === dataForm.value.bundleCode))
const selectedTvBundle = computed(() => tvBundles.value.find(b => b.code === cable.value.bundleCode))

// Data Loading
const loadWallet = async () => {
  try {
    const { data } = await axios.get('/api/wallet')
    balance.value = data.balance || 0
  } catch (e) { console.error('Wallet error', e) }
}

const loadBundles = async () => {
  if (!dataForm.value.network) return
  try {
    const { data } = await axios.get('/api/vtu/data/bundles', { params: { network: dataForm.value.network } })
    bundles.value = data.bundles || []
  } catch (e) {
    console.error('Bundles load error', e)
  }
}

const loadTvBundles = async () => {
  if (!cable.value.service) return
  try {
    const { data } = await axios.get('/api/vtu/tv/bundles', { params: { service: cable.value.service } })
    tvBundles.value = data.bundles || []
  } catch (e) {
    console.error('TV bundles load error', e)
  }
}

// THE FIX: Check if provider data inside an ERROR response actually indicates success
const checkIfActuallySuccess = (errorResponse) => {
  const provider = errorResponse?.provider || errorResponse?.data?.provider
  if (!provider) return false

  const status = String(provider?.status || provider?.data?.status || '').toLowerCase()
  const code = String(provider?.code || provider?.data?.code || '')
  const respDesc = String(provider?.response_description || provider?.data?.response_description || '').toLowerCase()

  return ['delivered', 'success', 'successful', 'completed'].includes(status) ||
      code === '000' ||
      respDesc.includes('success') ||
      respDesc.includes('delivered')
}

// Actions
const buyAirtime = async () => {
  if (!canBuyAirtime.value) return

  loadingAirtime.value = true
  notice.value.visible = false // Reset modal

  try {
    const payload = {
      network: airtime.value.network,
      phone_number: airtime.value.phone,
      amount: Number(airtime.value.amount)
    }
    // Prompt for 4-digit Transaction PIN
    let pin = window.prompt('Enter your 4-digit Transaction PIN to confirm purchase:')
    if (pin === null) { loadingAirtime.value = false; return }
    pin = String(pin || '').trim()
    if (!/^\d{4}$/.test(pin)) {
      showCustomNotice('Invalid PIN', 'Please enter a valid 4-digit PIN.', 'error')
      loadingAirtime.value = false
      return
    }
    payload.pin = pin
    const { data } = await axios.post('/api/vtu/airtime', payload)

    if (data.status === 'success' || data.status === 'pending') {
      showCustomNotice('Success', data.message || 'Transaction processing...', 'success')
      airtime.value.amount = ''
      await loadWallet()
    } else {
      showCustomNotice('Notice', data.message || 'Check transaction history', 'info')
    }
  } catch (e) {
    // Check for "Success hidden inside error" (common in VTpass Sandbox)
    if (checkIfActuallySuccess(e.response?.data)) {
      showCustomNotice('Success', 'Airtime sent successfully!', 'success')
      airtime.value.amount = ''
      await loadWallet()
    } else {
      const status = e?.response?.status
      const msg = e?.response?.data?.message || 'Transaction could not be completed at this time.'
      if (status === 409) {
        showCustomNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
      } else if (status === 403) {
        showCustomNotice('Invalid PIN', 'Invalid Transaction PIN. Please try again.', 'error')
      } else {
        showCustomNotice('Failed', msg, 'error')
      }
    }
  } finally {
    loadingAirtime.value = false
  }
}

const buyData = async () => {
  if (!canBuyData.value || !selectedBundle.value) return

  loadingData.value = true
  notice.value.visible = false

  try {
    const payload = {
      network: dataForm.value.network,
      phone_number: dataForm.value.phone,
      bundle_code: dataForm.value.bundleCode,
      amount: Number(selectedBundle.value?.amount ?? 0)
    }
    // Prompt for 4-digit Transaction PIN
    let pin = window.prompt('Enter your 4-digit Transaction PIN to confirm data purchase:')
    if (pin === null) { loadingData.value = false; return }
    pin = String(pin || '').trim()
    if (!/^\d{4}$/.test(pin)) {
      showCustomNotice('Invalid PIN', 'Please enter a valid 4-digit PIN.', 'error')
      loadingData.value = false
      return
    }
    payload.pin = pin
    const { data } = await axios.post('/api/vtu/data', payload)

    if (data.status === 'success' || data.status === 'pending') {
      showCustomNotice('Success', data.message || 'Data purchase processing...', 'success')
      dataForm.value.bundleCode = ''
      await loadWallet()
    }
  } catch (e) {
    if (checkIfActuallySuccess(e.response?.data)) {
      showCustomNotice('Success', 'Data bundle purchased successfully!', 'success')
      dataForm.value.bundleCode = ''
      await loadWallet()
    } else {
      const status = e?.response?.status
      const msg = e?.response?.data?.message || 'Data purchase failed'
      if (status === 409) {
        showCustomNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
      } else if (status === 403) {
        showCustomNotice('Invalid PIN', 'Invalid Transaction PIN. Please try again.', 'error')
      } else {
        showCustomNotice('Error', msg, 'error')
      }
    }
  } finally {
    loadingData.value = false
  }
}

const buyElectricity = async () => {
  if (!canBuyElectricity.value) return
  loadingElectricity.value = true
  notice.value.visible = false
  try {
    const payload = {
      disco: electricity.value.disco,
      meter_number: electricity.value.meter,
      meter_type: electricity.value.meterType,
      amount: Number(electricity.value.amount),
    }
    if (electricity.value.phone) payload.phone_number = electricity.value.phone
    // Prompt for 4-digit Transaction PIN
    let pin = window.prompt('Enter your 4-digit Transaction PIN to confirm electricity vend:')
    if (pin === null) { loadingElectricity.value = false; return }
    pin = String(pin || '').trim()
    if (!/^\d{4}$/.test(pin)) {
      showCustomNotice('Invalid PIN', 'Please enter a valid 4-digit PIN.', 'error')
      loadingElectricity.value = false
      return
    }
    payload.pin = pin
    const { data } = await axios.post('/api/vtu/electricity', payload)
    if (data.status === 'success' || data.status === 'pending') {
      showCustomNotice('Success', data.message || 'Electricity vend processing...', 'success')
      await loadWallet()
    }
  } catch (e) {
    if (checkIfActuallySuccess(e.response?.data)) {
      showCustomNotice('Success', 'Electricity token vended successfully!', 'success')
      await loadWallet()
    } else {
      const status = e?.response?.status
      const msg = e?.response?.data?.message || 'Electricity vend failed'
      if (status === 409) {
        showCustomNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
      } else if (status === 403) {
        showCustomNotice('Invalid PIN', 'Invalid Transaction PIN. Please try again.', 'error')
      } else {
        showCustomNotice('Error', msg, 'error')
      }
    }
  } finally {
    loadingElectricity.value = false
  }
}

const buyCable = async () => {
  if (!canBuyCable.value || !selectedTvBundle.value) return
  loadingCable.value = true
  notice.value.visible = false
  try {
    const payload = {
      service: cable.value.service,
      smartcard_number: cable.value.smartcard,
      bundle_code: cable.value.bundleCode,
      amount: Number(selectedTvBundle.value?.amount ?? 0),
    }
    if (cable.value.phone) payload.phone_number = cable.value.phone
    // Prompt for 4-digit Transaction PIN
    let pin = window.prompt('Enter your 4-digit Transaction PIN to confirm cable subscription:')
    if (pin === null) { loadingCable.value = false; return }
    pin = String(pin || '').trim()
    if (!/^\d{4}$/.test(pin)) {
      showCustomNotice('Invalid PIN', 'Please enter a valid 4-digit PIN.', 'error')
      loadingCable.value = false
      return
    }
    payload.pin = pin
    const { data } = await axios.post('/api/vtu/cable', payload)
    if (data.status === 'success' || data.status === 'pending') {
      showCustomNotice('Success', data.message || 'Cable subscription processing...', 'success')
      await loadWallet()
    }
  } catch (e) {
    if (checkIfActuallySuccess(e.response?.data)) {
      showCustomNotice('Success', 'Cable subscription successful!', 'success')
      await loadWallet()
    } else {
      const status = e?.response?.status
      const msg = e?.response?.data?.message || 'Cable subscription failed'
      if (status === 409) {
        showCustomNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
      } else if (status === 403) {
        showCustomNotice('Invalid PIN', 'Invalid Transaction PIN. Please try again.', 'error')
      } else {
        showCustomNotice('Error', msg, 'error')
      }
    }
  } finally {
    loadingCable.value = false
  }
}

// Initial Load
onMounted(() => {
  loadWallet()
  loadBundles()
  loadTvBundles()
})

// Watchers
watch(() => dataForm.value.network, () => loadBundles())
watch(() => cable.value.service, () => loadTvBundles())
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>