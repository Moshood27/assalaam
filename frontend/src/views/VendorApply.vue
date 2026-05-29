<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <div class="flex items-center gap-3">
          <button @click="$router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-lg font-bold text-slate-800">{{ isEdit ? 'Update Business Profile' : 'Become a Vendor' }}</h1>
        </div>
      </div>
    </header>

    <div class="max-w-2xl mx-auto p-4 space-y-6">
      <!-- Steps Indicator -->
      <div class="flex items-center justify-between px-4 mb-2">
        <div v-for="s in 3" :key="s" class="flex items-center gap-2">
           <div :class="s <= currentStep ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-slate-200 text-slate-500'" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all">
             {{ s }}
           </div>
           <div v-if="s < 3" :class="s < currentStep ? 'bg-blue-600' : 'bg-slate-200'" class="w-12 h-1 rounded-full transition-all"></div>
        </div>
      </div>

      <!-- Step 1: Business Basics -->
      <div v-if="currentStep === 1" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8 relative overflow-hidden animate-in fade-in slide-in-from-right duration-500">
        <div class="absolute right-0 top-0 w-40 h-40 bg-blue-50 rounded-full -mr-20 -mt-20 opacity-40" />
        <div class="relative z-10">
          <div class="mb-8">
            <h2 class="text-2xl font-black text-slate-800 uppercase leading-tight mb-2">{{ isEdit ? 'Update Identity' : 'Business Identity' }}</h2>
            <p class="text-sm text-slate-500">Let members know who you are and what you offer.</p>
          </div>

          <div class="space-y-6">
            <div>
              <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Business Name</label>
              <input v-model="form.name" type="text" class="w-full mt-1 px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-800" placeholder="e.g. Al-Barakah Electronics" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Business Phone</label>
                <input v-model="form.phone" type="tel" class="w-full mt-1 px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-800" placeholder="08012345678" />
              </div>
              <div>
                <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Market Category</label>
                <div class="relative">
                  <select v-model="form.category" class="w-full mt-1 px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-800 appearance-none">
                    <option value="">Select Category</option>
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                  </select>
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 i-mdi-chevron-down text-slate-400 pointer-events-none"></span>
                </div>
              </div>
            </div>

            <div>
              <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Business Tagline</label>
              <input v-model="form.description" type="text" class="w-full mt-1 px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-800" placeholder="Briefly describe what you sell" />
            </div>

            <button @click="currentStep = 2" :disabled="!form.name || !form.category" class="w-full h-16 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-wider shadow-xl shadow-slate-900/10 active:scale-95 transition-all disabled:opacity-30">
              Continue to Address
            </button>
          </div>
        </div>
      </div>

      <!-- Step 2: Location -->
      <div v-if="currentStep === 2" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8 animate-in fade-in slide-in-from-right duration-500">
        <div class="mb-8 flex items-center gap-4">
           <button @click="currentStep = 1" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400"><span class="i-mdi-arrow-left"></span></button>
           <div>
             <h2 class="text-2xl font-black text-slate-800 uppercase leading-tight mb-1">Store Location</h2>
             <p class="text-sm text-slate-500">Physical address for product fulfillment.</p>
           </div>
        </div>

        <div class="space-y-6">
          <div>
            <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Store / Office Address</label>
            <textarea v-model="form.address" rows="4" class="w-full mt-1 px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-800" placeholder="Detailed store address..."></textarea>
          </div>

          <div class="p-6 bg-blue-50 rounded-3xl border border-blue-100 flex items-start gap-4">
            <div class="text-2xl mt-1">📍</div>
            <p class="text-xs text-blue-800 leading-relaxed font-medium">Providing an accurate address helps members trust your business and facilitates smoother logistics.</p>
          </div>

          <button @click="currentStep = 3" :disabled="!form.address" class="w-full h-16 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-wider shadow-xl shadow-slate-900/10 active:scale-95 transition-all disabled:opacity-30">
            Continue to Settlements
          </button>
        </div>
      </div>

      <!-- Step 3: Payouts -->
      <div v-if="currentStep === 3" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8 animate-in fade-in slide-in-from-right duration-500">
         <div class="mb-8 flex items-center gap-4">
           <button @click="currentStep = 2" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400"><span class="i-mdi-arrow-left"></span></button>
           <div>
             <h2 class="text-2xl font-black text-slate-800 uppercase leading-tight mb-1">Settlement Bank</h2>
             <p class="text-sm text-slate-500">Where we send your earnings.</p>
           </div>
        </div>

        <div class="space-y-6">
          <div class="relative">
            <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Bank Institution</label>
            <div class="mt-1 relative group">
              <input
                v-model="bankSearch"
                @focus="showBankDropdown = true"
                type="text"
                class="w-full px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-800"
                :placeholder="selectedBankName || 'Search for your bank...'"
              />
              <div v-if="showBankDropdown" class="absolute z-20 mt-2 w-full max-h-60 overflow-auto bg-white border border-slate-200 rounded-[1.5rem] shadow-2xl p-2 hide-scrollbar">
                <button
                  v-for="b in filteredBanks"
                  :key="b.code"
                  @click="selectBank(b)"
                  class="w-full text-left px-4 py-3 text-sm font-bold text-slate-700 hover:bg-blue-50 rounded-xl transition-colors flex items-center justify-between group"
                >
                  {{ b.name }}
                  <span class="i-mdi-chevron-right opacity-0 group-hover:opacity-100 transition-opacity"></span>
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-black uppercase tracking-widest ml-1">Account Number (NUBAN)</label>
            <input v-model="form.settlement_account_number" type="tel" maxlength="10" class="w-full mt-1 px-5 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white outline-none transition-all font-black text-slate-800 tracking-widest text-lg" placeholder="0000000000" />
          </div>

          <div v-if="resolvedAccountName" class="p-6 rounded-[2rem] bg-blue-600 text-white shadow-lg shadow-blue-200 animate-in zoom-in duration-300">
            <div class="flex items-center gap-4">
               <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-2xl">✨›ï¸</div>
               <div>
                  <p class="text-[10px] font-black uppercase tracking-widest opacity-70">Verified Account Holder</p>
                  <p class="text-base font-black uppercase">{{ resolvedAccountName }}</p>
               </div>
            </div>
          </div>
          
          <button
            v-if="!resolvedAccountName"
            @click="resolveAccount"
            :disabled="resolving || !form.settlement_bank_code || form.settlement_account_number.length !== 10"
            class="w-full h-14 rounded-2xl border-2 border-blue-600 text-blue-600 font-black uppercase tracking-widest hover:bg-blue-50 transition-all disabled:opacity-30 active:scale-95"
          >
            <span v-if="resolving" class="inline-flex items-center gap-2"><span class="i-mdi-loading animate-spin"></span> Verifying...</span>
            <span v-else>Verify Bank Account</span>
          </button>

          <button
            v-if="resolvedAccountName"
            @click="submit"
            :disabled="submitting"
            class="w-full h-16 rounded-[2rem] bg-blue-600 text-white font-black uppercase tracking-wider shadow-xl shadow-blue-600/30 disabled:opacity-30 transition-all active:scale-95"
          >
            {{ submitting ? 'Processing...' : (isEdit ? 'Update Business Profile' : 'Apply to Become a Vendor') }}
          </button>
        </div>
      </div>

      <p class="text-center text-[10px] text-slate-400 px-8 uppercase font-bold tracking-widest leading-relaxed">
        By submitting, you agree to the Cooperative Vendor Terms of Service and commission rates.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '../http'

const router = useRouter()
const form = ref({
  name: '',
  phone: '',
  address: '',
  description: '',
  category: '',
  settlement_bank_name: '',
  settlement_bank_code: '',
  settlement_account_number: '',
  settlement_account_name: ''
})

const categories = ['Electronics', 'Furniture', 'Clothing', 'Groceries', 'Automobiles', 'Services', 'Other']
const banks = ref([])
const bankSearch = ref('')
const showBankDropdown = ref(false)
const resolving = ref(false)
const resolvedAccountName = ref('')
const currentStep = ref(1)
const submitting = ref(false)
const isEdit = ref(false)

const filteredBanks = computed(() => {
  const q = bankSearch.value.toLowerCase()
  return banks.value.filter(b => b.name.toLowerCase().includes(q))
})

const selectedBankName = computed(() => {
  const b = banks.value.find(b => b.code === form.value.settlement_bank_code)
  return b ? b.name : ''
})

const selectBank = (b) => {
  form.value.settlement_bank_code = b.code
  form.value.settlement_bank_name = b.name
  bankSearch.value = ''
  showBankDropdown.value = false
  resolvedAccountName.value = ''
}

const resolveAccount = async () => {
  resolving.value = true
  try {
    const { data } = await axios.post('/api/profile/bank-details', {
      bank_code: form.value.settlement_bank_code,
      bank_name: form.value.settlement_bank_name,
      account_number: form.value.settlement_account_number,
      confirm: false
    })
    resolvedAccountName.value = data.resolved_name
    form.value.settlement_account_name = data.resolved_name
  } catch (err) {
    alert(err.response?.data?.message || 'Could not verify bank account')
  } finally {
    resolving.value = false
  }
}

const submit = async () => {
  submitting.value = true
  try {
    await axios.post('/api/vendor/profile', form.value)
    alert(isEdit.value ? 'Profile updated successfully!' : 'Application submitted successfully! It will be reviewed by the admin.')
    if (isEdit.value) {
      router.back()
    } else {
      router.push('/profile')
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to submit application')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const { data: profile } = await axios.get('/api/vendor/profile')
    if (profile && profile.id) {
      isEdit.value = true
      form.value = {
        name: profile.name || '',
        phone: profile.phone || '',
        address: profile.address || '',
        description: profile.description || '',
        category: profile.category || '',
        settlement_bank_name: profile.settlement_bank_name || '',
        settlement_bank_code: profile.settlement_bank_code || '',
        settlement_account_number: profile.settlement_account_number || '',
        settlement_account_name: profile.settlement_account_name || ''
      }
      resolvedAccountName.value = profile.settlement_account_name || ''
    }
  } catch (_) {}

  try {
    const { data } = await axios.get('/api/banks')
    banks.value = data.banks
  } catch (err) {
    console.error('Failed to load banks')
  }
})
</script>


