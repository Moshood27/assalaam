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
          <h1 class="text-lg font-bold text-slate-800">Become a Vendor</h1>
        </div>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 opacity-40" />
        <div class="relative z-10">
          <h2 class="text-xl font-black text-slate-800 uppercase mb-2">Business Profile</h2>
          <p class="text-sm text-slate-500 mb-6">Register your local business to start selling products to cooperative members.</p>

          <div class="space-y-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Business Name</label>
              <input v-model="form.name" type="text" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800" placeholder="e.g. Al-Barakah Electronics" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Business Phone</label>
                <input v-model="form.phone" type="tel" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800" placeholder="08012345678" />
              </div>
              <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Category</label>
                <select v-model="form.category" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800 appearance-none">
                  <option value="">Select Category</option>
                  <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
            </div>

            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Business Address</label>
              <textarea v-model="form.address" rows="3" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800" placeholder="Store address..."></textarea>
            </div>

            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Short Description</label>
              <input v-model="form.description" type="text" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800" placeholder="What do you sell?" />
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6">
        <h2 class="text-xl font-black text-slate-800 uppercase mb-2">Settlement Bank</h2>
        <p class="text-sm text-slate-500 mb-6">Where should we pay your earnings? (Payouts are made after order fulfillment)</p>

        <div class="space-y-4">
          <div class="relative">
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Bank Name</label>
            <div class="mt-1 relative">
              <input
                v-model="bankSearch"
                @focus="showBankDropdown = true"
                type="text"
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800"
                :placeholder="selectedBankName || 'Search bank...'"
              />
              <div v-if="showBankDropdown" class="absolute z-20 mt-1 w-full max-h-48 overflow-auto bg-white border border-slate-200 rounded-2xl shadow-xl">
                <button
                  v-for="b in filteredBanks"
                  :key="b.code"
                  @click="selectBank(b)"
                  class="w-full text-left px-4 py-3 text-sm font-bold text-slate-700 hover:bg-emerald-50 transition-colors"
                >
                  {{ b.name }}
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Account Number</label>
            <input v-model="form.settlement_account_number" type="tel" maxlength="10" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-emerald-500 outline-none transition-colors font-bold text-slate-800" placeholder="10-digit account number" />
          </div>

          <div v-if="resolvedAccountName" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100">
            <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest">Verified Account Name</p>
            <p class="text-sm font-bold text-emerald-800">{{ resolvedAccountName }}</p>
          </div>
          
          <button
            v-if="!resolvedAccountName"
            @click="resolveAccount"
            :disabled="resolving || !form.settlement_bank_code || form.settlement_account_number.length !== 10"
            class="w-full h-12 rounded-xl border-2 border-emerald-700 text-emerald-700 font-bold hover:bg-emerald-50 transition-colors disabled:opacity-50"
          >
            {{ resolving ? 'Verifying...' : 'Verify Bank Account' }}
          </button>
        </div>
      </div>

      <button
        @click="submit"
        :disabled="submitting || !resolvedAccountName"
        class="w-full h-16 rounded-3xl bg-emerald-700 text-white font-black uppercase tracking-wider shadow-lg shadow-emerald-700/30 disabled:bg-slate-300 disabled:shadow-none transition-all active:scale-95"
      >
        {{ submitting ? 'Processing...' : 'Submit Application' }}
      </button>

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
const submitting = ref(false)

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
    alert('Application submitted successfully! It will be reviewed by the admin.')
    router.push('/profile')
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
      router.replace('/vendor/dashboard')
      return
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
