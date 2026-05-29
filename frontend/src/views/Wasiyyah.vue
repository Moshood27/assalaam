<template>
  <div class="min-h-screen bg-slate-50/50 pb-24">
    <AppHeader title="Wasiyyah (Next of Kin)" :showBack="true">
      <template #right>
        <button @click="openAdd" class="text-blue-700 text-xs font-bold mr-2">Add New</button>
      </template>
    </AppHeader>

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
        <div v-for="(val, type) in summary" :key="type" class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ type === 'all' ? 'General' : type }} Assets</span>
            <span class="text-xs font-bold" :class="val > 33.33 ? 'text-amber-600' : 'text-blue-600'">{{ val }}% Allocated</span>
          </div>
          <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" :class="val > 33.33 ? 'bg-amber-500' : 'bg-blue-500'" :style="{ width: val + '%' }"></div>
          </div>
          <p v-if="val > 33.33" class="text-[10px] text-amber-600 font-medium mt-1">Note: Bequests exceeding 1/3 (33.33%) may require heirs' consent under Sharia.</p>
        </div>

        <div v-for="b in beneficiaries" :key="b.id" @click="editBeneficiary(b)" class="card p-5 group cursor-pointer active:bg-slate-50 hover:border-indigo-100 transition-all">
          <div class="flex items-start justify-between">
            <div class="flex gap-4">
              <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-xl">Ã°Å¸â€˜Â¤</div>
              <div>
                <h3 class="font-bold text-slate-800">{{ b.name }}</h3>
                <p class="text-xs text-slate-500 font-medium">{{ b.relationship }} Ã¢â‚¬Â¢ {{ b.percentage }}% Allocation ({{ b.asset_type === 'all' ? 'General' : b.asset_type }})</p>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click.stop="editBeneficiary(b)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-indigo-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
              </button>
              <button @click.stop="deleteBeneficiary(b.id)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-rose-600 transition-colors">
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
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">Ã°Å¸â€œâ€¹</div>
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
          <div>
            <label class="lbl">Asset Type</label>
            <select v-model="form.asset_type" class="inp">
              <option value="all">General (All Assets)</option>
              <option value="shares">Shares Only</option>
              <option value="savings">Savings Only</option>
              <option value="takaful">Takaful Benefit</option>
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
    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'

const beneficiaries = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingId = ref(null)
const summary = ref({})
const form = ref({
  name: '',
  relationship: '',
  phone: '',
  email: '',
  address: '',
  percentage: 0,
  asset_type: 'all'
})

const openAdd = () => {
  editingId.value = null
  form.value = { name: '', relationship: '', phone: '', email: '', address: '', percentage: 0, asset_type: 'all' }
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
    summary.value = data.summary || {}
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load beneficiaries')
  }
}

async function saveBeneficiary() {
  try {
    loading.value = true
    const payload = {
      name: form.value.name,
      relationship: form.value.relationship,
      phone: form.value.phone,
      email: form.value.email,
      address: form.value.address,
      percentage: form.value.percentage,
      asset_type: form.value.asset_type
    }
    if (editingId.value) {
      await axios.patch(`/api/wasiyyah/${editingId.value}`, payload)
    } else {
      await axios.post('/api/wasiyyah', payload)
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


