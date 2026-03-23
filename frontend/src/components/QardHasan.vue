<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const list = ref([])
const form = ref({ user_id: 1, principal_amount: 0, total_installments: 1, interval: 'Monthly', admin_fee_flat: 0, admin_fee_pct: 0 })
const repayForm = ref({ id: null, amount: 0 })
const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get('/api/qard-hasan')
    list.value = data
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function createQard() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.post('/api/qard-hasan', form.value)
    list.value.unshift(data)
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function repay() {
  if (!repayForm.value.id) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.post(`/api/qard-hasan/${repayForm.value.id}/repay`, { amount: repayForm.value.amount })
    const idx = list.value.findIndex(x => x.id === data.qard.id)
    if (idx !== -1) list.value[idx] = data.qard
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="max-w-3xl mx-auto text-left">
    <h1 class="text-2xl font-bold mb-2">Qard Hasan (Interest‑Free)</h1>
    <p class="text-sm text-slate-600 mb-6">
      This is a Qard Hasan (benevolent, interest‑free loan). No interest (riba) will ever be charged.
      Optional administrative fee only covers real processing costs and is capped by policy.
      Any late penalty, if applied, goes to charity and is not cooperative income.
    </p>

    <div v-if="error" class="mb-4 p-3 bg-rose-100 text-rose-700 rounded">{{ error }}</div>

    <div class="bg-white rounded shadow p-4 mb-6">
      <h2 class="font-semibold mb-3">Create Qard Hasan</h2>
      <div class="grid grid-cols-2 gap-3">
        <label class="text-sm">User ID
          <input v-model.number="form.user_id" type="number" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
        <label class="text-sm">Principal Amount
          <input v-model.number="form.principal_amount" type="number" min="0" step="0.01" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
        <label class="text-sm">Total Installments
          <input v-model.number="form.total_installments" type="number" min="1" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
        <label class="text-sm">Interval
          <select v-model="form.interval" class="mt-1 w-full border rounded px-2 py-1">
            <option>Monthly</option>
            <option>Weekly</option>
          </select>
        </label>
        <label class="text-sm">Admin Fee (flat)
          <input v-model.number="form.admin_fee_flat" type="number" min="0" step="0.01" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
        <label class="text-sm">Admin Fee (%)
          <input v-model.number="form.admin_fee_pct" type="number" min="0" max="2" step="0.01" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
      </div>
      <button :disabled="loading" @click="createQard" class="mt-3 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded">Create</button>
    </div>

    <div class="bg-white rounded shadow p-4 mb-6">
      <h2 class="font-semibold mb-3">Repay</h2>
      <div class="grid grid-cols-2 gap-3">
        <label class="text-sm">Qard ID
          <input v-model.number="repayForm.id" type="number" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
        <label class="text-sm">Amount
          <input v-model.number="repayForm.amount" type="number" min="0.01" step="0.01" class="mt-1 w-full border rounded px-2 py-1"/>
        </label>
      </div>
      <button :disabled="loading" @click="repay" class="mt-3 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded">Submit Repayment</button>
    </div>

    <div class="bg-white rounded shadow p-4">
      <h2 class="font-semibold mb-3">My Qard Hasan</h2>
      <div v-if="loading">Loading…</div>
      <div v-else class="space-y-3">
        <div v-for="q in list" :key="q.id" class="border rounded p-3">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs uppercase text-slate-400 font-bold">ID: {{ q.qard_id_string }}</div>
              <div class="font-semibold">Principal: {{ Number(q.principal_amount).toLocaleString() }} | Paid: {{ Number(q.paid_amount).toLocaleString() }}</div>
              <div class="text-sm text-slate-600">Status: {{ q.status }} • {{ q.total_installments }} installments • {{ q.interval }}</div>
            </div>
            <div class="text-right text-xs text-slate-500">
              Admin fee flat: {{ q.admin_fee_flat }} | %: {{ q.admin_fee_pct }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
