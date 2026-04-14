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
          <h1 class="text-lg font-bold text-slate-800">Membership Details</h1>
        </div>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <!-- Actions Section -->
      <div class="grid grid-cols-2 gap-4">
        <button @click="downloadEnrolment" class="bg-emerald-700 text-white p-4 rounded-3xl shadow-sm flex flex-col items-center gap-2 active:scale-95 transition-transform">
          <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <span class="text-xs font-bold">Enrolment Form</span>
        </button>
        <button @click="downloadImamAttestation" class="bg-teal-700 text-white p-4 rounded-3xl shadow-sm flex flex-col items-center gap-2 active:scale-95 transition-transform">
          <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <span class="text-xs font-bold">Imam Attestation</span>
        </button>
      </div>

      <!-- Membership Data Sections -->
      <div v-for="section in membershipSections" :key="section.title" class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6">
        <h3 class="text-xs font-black text-emerald-700 uppercase tracking-widest mb-4 flex items-center gap-2">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          {{ section.title }}
        </h3>
        <div class="space-y-4">
          <div v-for="field in section.fields" :key="field.label" class="flex flex-col gap-1">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ field.label }}</label>
            <p class="text-sm font-bold text-slate-800">{{ profile[field.key] || '—' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()
const profile = ref({})

const membershipSections = [
  {
    title: '1. Personal Information',
    fields: [
      { label: 'Surname', key: 'surname' },
      { label: 'Other Names', key: 'other_names' },
      { label: 'Gender', key: 'gender' },
      { label: 'State/Town of Origin', key: 'native_place' },
      { label: 'Date of Birth', key: 'dob' },
      { label: 'Marital Status', key: 'marital_status' },
      { label: 'Occupation', key: 'occupation' }
    ]
  },
  {
    title: '2. Contact Information',
    fields: [
      { label: 'Primary Phone', key: 'phone' },
      { label: 'Secondary Phone', key: 'secondary_phone' },
      { label: 'Residential Address', key: 'residential_address' },
      { label: 'Permanent Address', key: 'permanent_address' }
    ]
  },
  {
    title: '3. Business Information',
    fields: [
      { label: 'Nature of Business', key: 'nature_of_business' },
      { label: 'Business Address', key: 'business_address' },
      { label: 'Other Cooperatives?', key: 'has_other_cooperatives' }
    ]
  },
  {
    title: '4. Next of Kin',
    fields: [
      { label: 'Next of Kin Name', key: 'nok_name' },
      { label: 'Relationship', key: 'nok_relationship' },
      { label: 'Phone Number', key: 'nok_phone' },
      { label: 'Address', key: 'nok_address' }
    ]
  },
  {
    title: '5. Guarantor Details',
    fields: [
      { label: 'Guarantor Name', key: 'guarantor_name' },
      { label: 'Occupation', key: 'guarantor_occupation' },
      { label: 'Phone Number', key: 'guarantor_phone' },
      { label: 'Address', key: 'guarantor_address' }
    ]
  },
  {
    title: '6. Religious Information',
    fields: [
      { label: 'Religious Society', key: 'religious_society_name' },
      { label: 'Imam/Amir Name', key: 'imam_name' },
      { label: 'Mosque Address', key: 'mosque_address' },
      { label: 'Imam Phone', key: 'imam_phone' },
      { label: 'Duration of Membership', key: 'duration_of_jamma_membership' }
    ]
  },
  {
    title: '7. Spouse/Father (Wali) Details',
    fields: [
      { label: 'Name', key: 'spouse_father_name' },
      { label: 'Address', key: 'spouse_father_address' },
      { label: 'Business Address', key: 'spouse_father_business_address' },
      { label: 'Phone Number', key: 'spouse_father_phone' }
    ]
  },
  {
    title: '8. Official Use',
    fields: [
      { label: 'Admission Form No.', key: 'admission_form_number' },
      { label: 'Admission Date', key: 'admission_date' },
      { label: 'Admission Officer', key: 'admission_officer_name' },
      { label: 'Officer Recommendation', key: 'officer_recommendation' },
      { label: 'Status', key: 'approval_status' }
    ]
  }
]

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/profile')
    profile.value = data
  } catch (error) {
    toast.error('Failed to load membership details')
  }
})

const downloadEnrolment = async () => {
  try {
    toast.info('Generating enrolment form...')
    const response = await axios.get('/api/download-membership-enrolment', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Membership_Enrolment_${profile.value.membership_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    toast.success('Downloaded successfully')
  } catch (e) {
    toast.error('Failed to download enrolment form')
  }
}

const downloadImamAttestation = async () => {
  try {
    toast.info('Generating attestation...')
    const response = await axios.get('/api/download-imam-attestation', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Imam_Attestation_${profile.value.membership_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    toast.success('Downloaded successfully')
  } catch (e) {
    toast.error('Failed to download attestation')
  }
}
</script>

<style scoped>
.header-fintech {
  @apply bg-white border-b border-slate-100 sticky top-0 z-40 px-4 py-3;
}
.navbar-inner {
  @apply flex items-center justify-between max-w-lg mx-auto;
}
</style>
