<template>
  <div class="min-h-screen bg-gradient-to-br from-amber-50 to-amber-100 p-4 sm:p-8">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="text-xs font-semibold tracking-widest text-amber-700 uppercase">Admin Portal</p>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Bulk Import</h1>
          <p class="text-slate-600">Import Members, Schemes, and Loans from CSV files.</p>
        </div>
        <div class="flex items-center gap-2">
          <router-link to="/admin/vtu" class="btn-muted">VTU Monitor</router-link>
          <router-link to="/admin/login" class="btn-muted">Admin Login</router-link>
        </div>
      </div>

      <div v-if="!adminToken" class="mb-6 p-4 border border-amber-300 bg-amber-50 rounded">
        <p class="text-amber-800 text-sm">You are not logged in as admin. Please <router-link to="/admin/login" class="underline font-semibold">login</router-link> to continue.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Members Import -->
        <div class="card card-elevated p-5">
          <h2 class="font-bold text-lg text-slate-800 mb-2">Import Members</h2>
          <p class="text-sm text-slate-600 mb-4">Upload a CSV file containing members data.</p>
          <div class="mb-3">
            <input type="file" accept=".csv,text/csv" @change="onFileChange($event, 'members')" class="block w-full text-sm" />
          </div>
          <div class="flex items-center gap-3 mb-4">
            <a :href="templates.members" download="members-template.csv" class="text-amber-700 text-sm underline">Download template</a>
            <button :disabled="!files.members || loading.members" @click="upload('members')" class="btn-primary">
              <span v-if="loading.members" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4 mr-2"></span>
              Upload
            </button>
          </div>
          <ImportResult :result="results.members" />
        </div>

        <!-- Schemes Import -->
        <div class="card card-elevated p-5">
          <h2 class="font-bold text-lg text-slate-800 mb-2">Import Schemes</h2>
          <p class="text-sm text-slate-600 mb-4">Upload a CSV file containing schemes data.</p>
          <div class="mb-3">
            <input type="file" accept=".csv,text/csv" @change="onFileChange($event, 'schemes')" class="block w-full text-sm" />
          </div>
          <div class="flex items-center gap-3 mb-4">
            <a :href="templates.schemes" download="schemes-template.csv" class="text-amber-700 text-sm underline">Download template</a>
            <button :disabled="!files.schemes || loading.schemes" @click="upload('schemes')" class="btn-primary">
              <span v-if="loading.schemes" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4 mr-2"></span>
              Upload
            </button>
          </div>
          <ImportResult :result="results.schemes" />
        </div>

        <!-- Loans Import -->
        <div class="card card-elevated p-5">
          <h2 class="font-bold text-lg text-slate-800 mb-2">Import Loans</h2>
          <p class="text-sm text-slate-600 mb-4">Upload a CSV file containing loans data.</p>
          <div class="mb-3">
            <input type="file" accept=".csv,text/csv" @change="onFileChange($event, 'loans')" class="block w-full text-sm" />
          </div>
          <div class="flex items-center gap-3 mb-4">
            <a :href="templates.loans" download="loans-template.csv" class="text-amber-700 text-sm underline">Download template</a>
            <button :disabled="!files.loans || loading.loans" @click="upload('loans')" class="btn-primary">
              <span v-if="loading.loans" class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4 mr-2"></span>
              Upload
            </button>
          </div>
          <ImportResult :result="results.loans" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axiosBase from '../../http.js'

const adminToken = ref(localStorage.getItem('admin_token') || '')

const files = ref({ members: null, schemes: null, loans: null })
const loading = ref({ members: false, schemes: false, loans: false })
const results = ref({ members: null, schemes: null, loans: null })

const templates = {
  members: '/templates/members-template.csv',
  schemes: '/templates/schemes-template.csv',
  loans: '/templates/loans-template.csv',
}

const onFileChange = (e, type) => {
  const file = e.target.files?.[0]
  files.value[type] = file || null
}

const client = computed(() => {
  const instance = axiosBase.create ? axiosBase.create() : axiosBase
  return instance
})

const upload = async (type) => {
  if (!adminToken.value) {
    alert('Please login as admin to perform imports.')
    return
  }
  const map = {
    members: '/api/admin/import/members',
    schemes: '/api/admin/import/schemes',
    loans: '/api/admin/import/loans',
  }
  const url = map[type]
  const fd = new FormData()
  fd.append('file', files.value[type])
  loading.value[type] = true
  results.value[type] = null
  try {
    const { data } = await client.value.post(url, fd, {
      headers: {
        'Authorization': `Bearer ${adminToken.value}`,
      }
    })
    results.value[type] = data
  } catch (e) {
    results.value[type] = { error: e?.response?.data?.message || 'Upload failed' }
  } finally {
    loading.value[type] = false
  }
}
</script>

<script>
// Local component for showing result summary
export default {
  components: {
    ImportResult: {
      props: ['result'],
      template: `
      <div v-if="result" class="mt-3 text-sm">
        <div v-if="result.summary" class="text-slate-700">
          <p><span class="font-semibold">Processed:</span> {{ result.summary.processed }}</p>
          <p><span class="font-semibold">Created:</span> {{ result.summary.created }}</p>
          <p><span class="font-semibold">Updated:</span> {{ result.summary.updated }}</p>
          <p><span class="font-semibold">Failed:</span> <span :class="result.summary.failed ? 'text-rose-600' : ''">{{ result.summary.failed }}</span></p>
        </div>
        <div v-if="result.errors && result.errors.length" class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded text-rose-700 max-h-32 overflow-auto">
          <p class="font-semibold mb-1">Errors</p>
          <ul class="list-disc pl-5">
            <li v-for="(err, idx) in result.errors" :key="idx">Row {{ err.row }}: {{ err.error }}</li>
          </ul>
        </div>
        <p v-if="result.error" class="text-rose-600">{{ result.error }}</p>
      </div>
      `
    }
  }
}
</script>

<style scoped>
@reference '../../style.css';
.btn-primary { @apply bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg shadow; }
.btn-muted { @apply bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-lg shadow-sm; }
.card { @apply bg-white rounded-2xl border border-slate-200; }
.card-elevated { @apply shadow-sm; }
</style>


