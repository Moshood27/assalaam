<template>
  <div class="min-h-screen bg-slate-50/50 pb-24">
    <header class="header-fintech">
      <div class="navbar-inner">
        <button @click="$router.back()" class="p-2 -ml-2 hover:bg-slate-100 rounded-full transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg font-bold text-slate-800">Wasiyyah (Next of Kin)</h1>
        <button @click="openAdd" class="btn-ghost text-xs py-1.5 px-3">Add New</button>
      </div>
    </header>

    <div class="p-4 max-w-2xl mx-auto space-y-4">
      <div class="bg-indigo-50 border border-indigo-100 text-indigo-900 rounded-2xl p-4 flex gap-3">
        <div class="text-indigo-500 mt-0.5">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        </div>
        <p class="text-[13px] leading-relaxed">
          In Islamic inheritance (Wasiyyah), documenting your beneficiaries is crucial. Ensure the total percentage adds up correctly according to Sharia principles or your specific legacy wishes.
        </p>
      </div>

      <div v-if="beneficiaries.length" class="grid gap-4">
        <div v-for="b in beneficiaries" :key="b.id" class="card p-5 group">
          <div class="flex items-start justify-between">
            <div class="flex gap-4">
              <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-xl">👤</div>
              <div>
                <h3 class="font-bold text-slate-800">{{ b.name }}</h3>
                <p class="text-xs text-slate-500 font-medium">{{ b.relationship }} • {{ b.percentage }}% Allocation</p>
              </div>
            </div>
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button @click="editBeneficiary(b)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
              </button>
              <button @click="deleteBeneficiary(b.id)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-rose-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
              </button>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-slate-50 grid grid-cols-2 gap-4 text-xs">
            <div v-if="b.phone" class="flex items-center gap-2 text-slate-500">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              {{ b.phone }}
            </div>
            <div v-if="b.email" class="flex items-center gap-2 text-slate-500">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              {{ b.email }}
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12 bg-white rounded-3xl border border-dashed border-slate-300">
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">📋</div>
        <p class="text-slate-500 font-medium mb-4">No beneficiaries listed yet.</p>
        <button @click="openAdd" class="btn-primary">Add your first next of kin</button>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="modal">
      <div class="modal-card">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-800">{{ editingId ? 'Edit' : 'Add' }} Beneficiary</h3>
          <button @click="showModal=false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="lbl">Full Name</label>
            <input v-model="form.name" placeholder="John Doe" class="inp" />
          </div>
          <div>
            <label class="lbl">Relationship</label>
            <select v-model="form.relationship" class="inp">
              <option value="" disabled>Select Relationship</option>
              <option value="Spouse">Spouse</option>
              <option value="Child">Child</option>
              <option value="Parent">Parent</option>
              <option value="Sibling">Sibling</option>
              <option value="Relative">Relative</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="lbl">Phone Number</label>
              <input v-model="form.phone" placeholder="080..." class="inp" />
            </div>
            <div>
              <label class="lbl">Email Address</label>
              <input v-model="form.email" type="email" placeholder="john@example.com" class="inp" />
            </div>
          </div>
          <div>
            <label class="lbl">Allocation Percentage (%)</label>
            <input v-model.number="form.percentage" type="number" min="0" max="100" class="inp" placeholder="0" />
          </div>
          <div>
            <label class="lbl">Residential Address</label>
            <textarea v-model="form.address" rows="2" class="inp" placeholder="Optional"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button @click="showModal=false" class="btn-muted">Cancel</button>
            <button @click="saveBeneficiary" class="btn-primary" :disabled="loading">{{ loading ? 'Saving...' : 'Save Beneficiary' }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../http'

const beneficiaries = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingId = ref(null)
const form = ref({
  name: '',
  relationship: '',
  phone: '',
  email: '',
  address: '',
  percentage: 0
})

const openAdd = () => {
  editingId.value = null
  form.value = { name: '', relationship: '', phone: '', email: '', address: '', percentage: 0 }
  showModal.value = true
}

const editBeneficiary = (b) => {
  editingId.value = b.id
  form.value = { ...b }
  showModal.value = true
}

async function load() {
  try {
    const { data } = await axios.get('/api/wasiyyah')
    beneficiaries.value = data.beneficiaries || []
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load beneficiaries')
  }
}

async function saveBeneficiary() {
  try {
    loading.value = true
    if (editingId.value) {
      await axios.patch(`/api/wasiyyah/${editingId.value}`, form.value)
    } else {
      await axios.post('/api/wasiyyah', form.value)
    }
    showModal.value = false
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to save beneficiary')
  } finally {
    loading.value = false
  }
}

async function deleteBeneficiary(id) {
  if (!confirm('Are you sure you want to remove this beneficiary?')) return
  try {
    await axios.delete(`/api/wasiyyah/${id}`)
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to delete beneficiary')
  }
}

onMounted(load)
</script>

<style scoped>
@reference "../style.css";
.modal { @apply fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4; }
.modal-card { @apply w-full max-w-md bg-white rounded-[2rem] p-8 shadow-2xl; }
</style>
