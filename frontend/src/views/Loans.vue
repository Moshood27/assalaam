<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <AppHeader title="Loan Records" :showBack="true" />

    <div class="container-app py-4">
      <div v-if="loading" class="text-center text-slate-500 py-10">Loading…</div>
      <div v-else-if="error" class="card p-4 text-rose-700 bg-rose-50 border-rose-200">{{ error }}</div>

      <div v-else class="space-y-4">
              <!-- Eligibility and Create Loan -->
              <div class="card" v-if="canCreateLoanVisible">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                  <h3 class="section-title">Qard Hasan Eligibility</h3>
                  <button class="text-xs font-bold text-slate-500 hover:text-slate-700" @click="fetchEligibility">Refresh</button>
                </div>
                <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                  <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Savings</p>
                    <p class="money">₦ {{ n(eligibility.savings) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Shares</p>
                    <p class="money">₦ {{ n(eligibility.shares) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Base (S + Sh)</p>
                    <p class="money">₦ {{ n(eligibility.base) }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Eligible (policy + score)</p>
                    <p class="font-black text-emerald-700 text-lg">₦ {{ n(eligibility.eligibility_with_score || eligibility.eligibility_adjusted || eligibility.eligibility) }}</p>
                  </div>
                </div>
                <div class="px-5 pb-5 pt-0">
                  <div v-if="eligibility.attaqwa_score !== undefined" class="mb-3 p-3 rounded-xl border border-slate-200 bg-white flex items-center justify-between">
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Attaqwa Score</p>
                      <p class="text-lg font-black text-slate-800">
                        {{ Math.round((eligibility.attaqwa_score || 0) * 10) / 10 }}
                        <span v-if="eligibility.band" class="ml-2 text-[11px] text-slate-500 font-semibold">({{ bandLabel(eligibility.band) }})</span>
                      </p>
                    </div>
                    <div class="text-right">
                      <p class="text-[10px] text-emerald-500 font-bold uppercase">Limit Bonus</p>
                      <p class="text-lg font-black text-emerald-600">+{{ eligibility.score_bonus_pct || 0 }}%</p>
                    </div>
                  </div>
                  <p v-if="Number(eligibility.limit_boost_pct || 0) > 0" class="text-[10px] text-emerald-700 font-semibold mb-2">
                    Trust boost applied: +{{ eligibility.limit_boost_pct }}% to your loan limit.
                  </p>
                  <p class="text-[11px] text-slate-500 mb-2">
                    <span v-if="eligibility.is_first_loan">First loan is capped at 5% of your base (Savings + Shares).</span>
                    <span v-else>Eligible up to 2 × your base.</span>
                    <span class="font-semibold"> Eligible now: ₦ {{ n(eligibility.eligibility_with_score || eligibility.eligibility_adjusted || eligibility.eligibility) }}.</span>
                  </p>
                  <div class="grid grid-cols-2 gap-3">
                    <label class="text-[11px] text-slate-500 font-bold">Total Installments
                      <input v-model.number="createForm.total_installments" type="number" min="1" class="input mt-1"/>
                    </label>
                    <label class="text-[11px] text-slate-500 font-bold">Interval
                      <select v-model="createForm.interval" class="input mt-1">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                      </select>
                    </label>
                    <label class="text-[11px] text-slate-500 font-bold">Admin Fee (Flat)
                      <input v-model.number="createForm.admin_fee_flat" type="number" min="0" step="0.01" class="input mt-1" :disabled="true" readonly/>
                      <span class="text-[10px] text-slate-400">Auto-applied by policy</span>
                    </label>
                    <label class="text-[11px] text-slate-500 font-bold">Admin Fee (%)
                      <input v-model.number="createForm.admin_fee_pct" type="number" min="0" max="2" step="0.01" class="input mt-1" :disabled="true" readonly/>
                      <span class="text-[10px] text-slate-400">Auto-applied by policy</span>
                    </label>
                    <label v-if="(eligibility.required_guarantors || 0) > 0" class="text-[11px] text-slate-500 font-bold">Guarantor ID 1
                      <input v-model="createForm.guarantor1" type="text" class="input mt-1" placeholder="Enter membership number (e.g., AT-TAQWA/02/005)"/>
                    </label>
                    <label v-if="(eligibility.required_guarantors || 0) > 0" class="text-[11px] text-slate-500 font-bold">Guarantor ID 2
                      <input v-model="createForm.guarantor2" type="text" class="input mt-1" placeholder="Enter membership number (e.g., AT-TAQWA/02/005)"/>
                    </label>
                    <label v-if="(eligibility.required_guarantors || 0) > 0" class="text-[11px] text-slate-500 font-bold">Guarantor ID 3 (optional)
                      <input v-model="createForm.guarantor3" type="text" class="input mt-1" placeholder="Enter membership number (e.g., AT-TAQWA/02/005)"/>
                    </label>
                  </div>
                  <p class="mt-2 text-[10px] text-slate-500">
                    <template v-if="(eligibility.required_guarantors || 0) > 0">
                      Select at least {{ eligibility.required_guarantors }} and at most three guarantors. Guarantors must not be defaulters.
                    </template>
                    <template v-else>
                      No guarantors required due to Instant Approval eligibility. Proceed to create your loan — funds will be credited automatically after fees.
                    </template>
                  </p>
                  <div class="mt-3 flex items-center justify-between">
                    <div class="text-[11px] text-slate-500">Principal will be set automatically to your eligibility.</div>
                    <button class="btn-primary" :disabled="creating" @click="createLoan">
                      <span v-if="!creating">Create Loan</span>
                      <span v-else>Creating…</span>
                    </button>
                  </div>
                  <p v-if="createMsg" class="text-xs text-emerald-700 mt-2">{{ createMsg }}</p>
                  <p v-if="createErr" class="text-xs text-rose-700 mt-2">{{ createErr }}</p>
                </div>
              </div>

              <!-- Notice when creation is not available -->
              <div class="card p-5" v-else>
                <h3 class="section-title mb-2">Loan Creation Unavailable</h3>
                <p class="text-sm text-slate-600" v-if="hasOpenLoan">You must complete your current loan before creating a new one.</p>
                <p class="text-sm text-slate-600" v-else>{{ eligibility.reason || 'You are currently not eligible to create a loan. Ensure you have at least 6 months of membership and sufficient contributions.' }}</p>
              </div>

              <!-- Guarantor Requests -->
              <div class="card" v-if="grLoading || (guarantorRequests && guarantorRequests.length)">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                  <h3 class="section-title">Your Guarantor Requests</h3>
                  <button class="text-xs font-bold text-slate-500 hover:text-slate-700" @click="fetchGuarantorRequests">Refresh</button>
                </div>
                <div class="p-5" v-if="grLoading">Loading guarantor requests…</div>
                <div class="p-5" v-else>
                  <p v-if="grError" class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3 mb-3">{{ grError }}</p>
                  <ul class="divide-y divide-slate-100">
                    <li v-for="req in guarantorRequests" :key="req.id" class="py-3 flex items-start justify-between gap-3">
                      <div>
                        <p class="font-bold text-slate-800">{{ req.member?.name || 'Member #' + req.member?.id }}</p>
                        <p class="text-[10px] text-slate-500 uppercase">{{ req.member?.branch || '—' }} • {{ req.qard_id_string }}</p>
                        <p class="text-[12px] text-slate-600 mt-1">Amount: ₦ {{ n(req.principal_amount) }} • Installments: {{ req.total_installments }} × ₦ {{ n(req.per_installment) }}</p>
                        <p class="text-[10px] text-slate-500 mt-1">Accepted: {{ req.accepted_count || 0 }} • Pending: {{ req.pending_count || 0 }} • Declined: {{ req.declined_count || 0 }}</p>
                        <p v-if="req.all_accepted" class="text-[11px] text-emerald-700 mt-1">All guarantors have accepted. Awaiting admin disbursement.</p>
                      </div>
                      <div class="text-right min-w-[9rem]">
                        <span class="badge" :class="req.guarantor_status === 'accepted' ? 'badge-success' : (req.guarantor_status === 'declined' ? 'badge-muted' : 'badge-warning')">{{ req.guarantor_status }}</span>
                        <div class="mt-2 flex items-center gap-2 justify-end" v-if="req.guarantor_status === 'pending'">
                          <button class="btn-primary" :disabled="!!grAction[req.id]" @click="acceptGuarantor(req)">
                            <span v-if="!grAction[req.id]">Accept</span>
                            <span v-else>...</span>
                          </button>
                          <button class="px-3 py-2 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50" :disabled="!!grAction[req.id]" @click="declineGuarantor(req)">
                            <span v-if="!grAction[req.id]">Decline</span>
                            <span v-else>...</span>
                          </button>
                        </div>
                        <p v-if="grMsg[req.id]" class="text-[11px] text-emerald-700 mt-1">{{ grMsg[req.id] }}</p>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
        <div v-for="(loan, idx) in loans" :key="loan.id" class="card overflow-hidden">
          <div class="p-4 bg-slate-50 flex justify-between items-center border-b border-slate-100">
            <div>
              <span class="text-emerald-700 font-black">#{{ idx + 1 }}</span>
              <h3 class="inline ml-2 font-bold text-slate-800">Qard Hasan</h3>
              <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">ID: {{ loan.qard_id_string }}</p>
            </div>
            <div class="flex items-center gap-2">
              <a :href="getScheduleDownloadUrl(loan)" target="_blank" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-xs">Download Schedule</a>
              <span :class="loan.status === 'active' ? 'badge-success' : 'badge-muted'" class="badge">{{ loan.is_completed ? 'completed' : loan.status }}</span>
            </div>
          </div>

          <div class="p-5 grid grid-cols-2 gap-y-4 gap-x-2">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Loan Amount</p>
              <p class="money">₦ {{ n(loan.principal_amount) }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Total Installments</p>
              <p class="money">{{ loan.total_installments }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Interval</p>
              <p class="money">{{ loan.interval }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Per Installment</p>
              <p class="money">₦ {{ n(loan.per_installment) }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Paid Amount</p>
              <p class="font-black text-emerald-600 text-lg">₦ {{ n(loan.paid_amount) }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase text-right">Remaining Principal</p>
              <p class="font-black text-rose-600 text-lg text-right">₦ {{ n(loan.remaining_principal ?? (loan.principal_amount - loan.paid_amount)) }}</p>
            </div>
            <div class="col-span-2">
              <div class="h-2 bg-slate-200 rounded overflow-hidden">
                <div class="h-2 bg-emerald-500" :style="{ width: (loan.progress_pct || ((loan.paid_amount/loan.principal_amount)*100)).toFixed(2) + '%' }"></div>
              </div>
              <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                <span>{{ (loan.progress_pct ?? (loan.paid_amount/loan.principal_amount*100)).toFixed(2) }}%</span>
                <span>₦ {{ n(loan.remaining_principal ?? (loan.principal_amount - loan.paid_amount)) }} left</span>
              </div>
            </div>
            <div class="col-span-2" v-if="loan.guarantors && loan.guarantors.length">
              <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Guarantors</p>
              <ul class="flex flex-wrap gap-2 text-xs">
                <li v-for="g in loan.guarantors" :key="g.id" class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200">
                  {{ g.name || ('#' + g.id) }} <span v-if="g.branch" class="text-slate-500">• {{ g.branch.name }}</span>
                </li>
              </ul>
            </div>

            <!-- Agreement Section -->
            <div class="col-span-2 mt-2 p-3 rounded-2xl border border-amber-100 bg-amber-50" v-if="loan.status === 'pending' || loan.signed_agreement">
              <p class="text-[10px] text-amber-600 font-black uppercase tracking-widest mb-2">Loan Agreement</p>
              <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                  <p class="text-[11px] text-slate-700 font-medium">1. Download Document</p>
                  <a v-if="loan.agreement_template" :href="getImageUrl(loan.agreement_template)" target="_blank" class="text-[11px] font-bold text-emerald-700 underline">Download PDF</a>
                  <a v-else :href="getAgreementDownloadUrl(loan.id)" target="_blank" class="text-[11px] font-bold text-emerald-700 underline">Generate Agreement PDF</a>
                </div>
                <div class="border-t border-amber-100 pt-2">
                  <p class="text-[11px] text-slate-700 font-medium mb-1">2. Upload Signed Copy</p>
                  
                  <!-- If rejected (no file but template exists and it's pending) -->
                  <div v-if="!loan.signed_agreement && loan.status === 'pending'" class="mb-2">
                    <p class="text-[9px] text-rose-600 font-bold italic mb-2" v-if="loan.agreement_rejection_reason">
                      ⚠️ Rejected: {{ loan.agreement_rejection_reason }}
                    </p>
                    <p class="text-[9px] text-amber-600 font-medium italic mb-2" v-else-if="loan.agreement_template || loan.approved_at">
                      Please upload a clear signed copy to proceed.
                    </p>
                  </div>

                  <div v-if="loan.agreement_verified_at" class="flex items-center gap-1 text-emerald-700">
                    <span class="text-sm text-emerald-700">✅</span>
                    <span class="text-[11px] font-bold">Verified on {{ new Date(loan.agreement_verified_at).toLocaleDateString() }}</span>
                  </div>
                  <div v-else-if="loan.signed_agreement" class="flex items-center justify-between">
                    <div class="flex items-center gap-1 text-amber-700">
                      <span class="text-sm">⏳</span>
                      <span class="text-[11px] font-bold text-amber-700">Awaiting Admin Verification</span>
                    </div>
                    <button @click="triggerAgreementUpload(loan.id)" class="text-[10px] font-bold text-slate-500 underline" :disabled="uploadingAgreement[loan.id]">Change File</button>
                  </div>
                  <div v-else>
                    <input :id="'agreement-input-' + loan.id" type="file" accept="application/pdf,image/*" class="hidden" @change="(e) => onAgreementFileChange(e, loan.id)" />
                    <button @click="triggerAgreementUpload(loan.id)" class="btn-primary py-2 px-4 text-xs w-full sm:w-auto" :disabled="uploadingAgreement[loan.id]">
                      {{ uploadingAgreement[loan.id] ? 'Uploading...' : 'Upload Signed Agreement' }}
                    </button>
                    <p class="text-[9px] text-slate-500 mt-1 italic">Please print, sign, and scan/photo back as PDF or Image.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-4 pt-0 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center w-full">
                <input type="number" min="0.01" step="0.01" class="input w-full sm:flex-1" :disabled="loan.is_completed || paying[loan.id]" v-model.number="payAmount[loan.id]" placeholder="Enter amount to repay" />
                <select class="input w-full sm:w-36" v-model="paySource[loan.id]" :disabled="loan.is_completed || paying[loan.id]">
                  <option value="auto">Auto</option>
                  <option value="wallet">Wallet</option>
                  <option value="paystack">Paystack</option>
                  <option value="flutterwave">Flutterwave</option>
                </select>
                <button class="btn-primary w-full sm:w-auto" :disabled="loan.is_completed || paying[loan.id]" @click="pay(loan)">
                  <span v-if="!paying[loan.id]">Make payment</span>
                  <span v-else>Processing…</span>
                </button>
              </div>
            </div>
            <p class="text-[10px] text-slate-500">
              Auto uses your wallet if balance covers the amount, otherwise initializes Paystack.
            </p>
            <p class="text-[10px] text-slate-500" v-if="(paySource[loan.id] || 'auto') !== 'wallet'">
              Note: Online gateway payments require a valid email address. If you see an "Invalid Email Address" error from Paystack, please update your profile email to a supported address and try again, or use Wallet if you have sufficient balance.
            </p>
            <p v-if="payMsg[loan.id]" class="text-xs text-emerald-700">{{ payMsg[loan.id] }}</p>
            <p v-if="payErr[loan.id]" class="text-xs text-rose-700">{{ payErr[loan.id] }}</p>

            <div class="mt-2" v-if="loan.repayments && loan.repayments.length">
              <h4 class="text-[11px] font-black text-slate-500 uppercase tracking-widest mb-1">Repayment History</h4>
              <ul class="divide-y divide-slate-100">
                <li v-for="r in loan.repayments.slice(0,5)" :key="r.id" class="py-2 flex justify-between text-sm">
                  <span>₦ {{ n(r.amount) }}</span>
                  <span class="text-slate-400">{{ formatRepaymentDate(r) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div v-if="!loans.length" class="card p-6 text-center text-slate-500">No loan records found.</div>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="section-title">Business Payment Records</h3>
            <span class="badge badge-muted">SME</span>
          </div>
          <p class="text-sm text-slate-500">No Record Found</p>
        </div>
      </div>
    </div>

    <!-- Reusable Custom Notice Modal -->
        <CustomNotice
          v-model="notice.visible"
          :type="notice.type"
          :title="notice.title"
          :message="notice.message"
          @close="closeNotice"
        />

        <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'
import getImageUrl from '../utils/image'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'
import { verifyBiometricIdentity, isBiometricAvailable } from '../services/biometric'

// Policy defaults for admin fees (can be overridden via environment variables)
const DEFAULT_ADMIN_FEE_FLAT = Number(import.meta.env.VITE_DEFAULT_ADMIN_FEE_FLAT ?? 0)
const DEFAULT_ADMIN_FEE_PCT = Number(import.meta.env.VITE_DEFAULT_ADMIN_FEE_PCT ?? 0)

const loans = ref([])
const loading = ref(false)
const error = ref('')

// Notice modal (shared VTU-style)
const { notice, showNotice, closeNotice } = useNotice()

// Eligibility and create loan
const eligibility = ref({ savings: 0, shares: 0, base: 0, eligibility: 0, eligibility_adjusted: 0, months_in_system: 0, is_first_loan: true, can_request: false, reason: '', attaqwa_score: 0, score_bonus_pct: 0, band: '', instant_approval: false, required_guarantors: 2 })
const createForm = ref({ total_installments: 1, interval: 'monthly', admin_fee_flat: DEFAULT_ADMIN_FEE_FLAT, admin_fee_pct: DEFAULT_ADMIN_FEE_PCT, guarantor1: '', guarantor2: '', guarantor3: '' })
const creating = ref(false)
const createMsg = ref('')
const createErr = ref('')

const hasAnyLoan = computed(() => (loans.value || []).length > 0)
const hasOpenLoan = computed(() => (loans.value || []).some(l => (l?.status === 'pending' || l?.status === 'active') && !l?.is_completed))
const hasCompletedLoan = computed(() => (loans.value || []).some(l => l?.is_completed || l?.status === 'completed'))
// Creation is allowed only if no open loan and backend policy allows request (6-month rule and first-loan cap)
const canCreateLoanVisible = computed(() => !hasOpenLoan.value && !!eligibility.value?.can_request)

const payAmount = ref({})
const paySource = ref({})
const paying = ref({})
const payMsg = ref({})
const payErr = ref({})

// Agreement upload
const uploadingAgreement = ref({})

const hasRecentRejection = (loanId) => {
  return false 
}

const getAgreementDownloadUrl = (loanId) => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-loan-agreement/${loanId}?token=${encodeURIComponent(token)}`
}
const triggerAgreementUpload = (loanId) => {
  const input = document.getElementById('agreement-input-' + loanId)
  if (input) input.click()
}
const onAgreementFileChange = async (e, loanId) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const form = new FormData()
  form.append('signed_agreement', file)
  uploadingAgreement.value[loanId] = true
  try {
    const token = localStorage.getItem('token')
    await axios.post(`/api/loans/${loanId}/agreement`, form, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
    showNotice('Success', 'Agreement uploaded successfully. Admin will verify it shortly.', 'success')
    await load()
  } catch (err) {
    showNotice('Error', err?.response?.data?.message || 'Failed to upload agreement.', 'error')
  } finally {
    uploadingAgreement.value[loanId] = false
    e.target.value = ''
  }
}

// Guarantor requests
const guarantorRequests = ref([])
const grLoading = ref(false)
const grError = ref('')
const grAction = ref({})
const grMsg = ref({})

const n = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const bandLabel = (band) => {
  const map = {
    excellent: 'Excellent',
    very_good: 'Very Good',
    good: 'Good',
    fair: 'Fair',
    low: 'Low',
    very_low: 'Very Low',
  }
  return map[band] || band
}

const isValidDate = (d) => d instanceof Date && !isNaN(d.valueOf())

const formatRepaymentDate = (r) => {
  if (!r) return ''
  const status = (r.status || '').toString().toLowerCase()
  // If not successful or no paid_at, show status label instead of epoch
  if (status !== 'success' || !r.paid_at) {
    if (status === 'pending') return 'Pending'
    if (status === 'failed') return 'Failed'
    return 'Pending'
  }
  let d = new Date(r.paid_at)
  if (!isValidDate(d) && r.created_at) {
    d = new Date(r.created_at)
  }
  if (!isValidDate(d)) return 'Pending'
  // Format as e.g., 17 Mar 2026, 17:48
  const datePart = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
  const timePart = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  return `${datePart}, ${timePart}`
}

const fetchEligibility = async () => {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/loans/eligibility', {
      headers: { Authorization: `Bearer ${token}` }
    })
    eligibility.value = { ...eligibility.value, ...(data || {}) }
    // Auto-apply admin fees from policy defaults and lock the inputs
    const feeFlat = Number.isFinite(DEFAULT_ADMIN_FEE_FLAT) ? Number(DEFAULT_ADMIN_FEE_FLAT) : 0
    let feePct = Number.isFinite(DEFAULT_ADMIN_FEE_PCT) ? Number(DEFAULT_ADMIN_FEE_PCT) : 0
    // Clamp to policy: 0 - 2%
    if (feePct < 0) feePct = 0
    if (feePct > 2) feePct = 2
    createForm.value.admin_fee_flat = feeFlat
    createForm.value.admin_fee_pct = feePct
  } catch (e) {
    // silent; component also shows list even if eligibility fails
  }
}

const createLoan = async () => {
  // Guard: only allow when UI says it's visible
  if (!canCreateLoanVisible.value) {
    createErr.value = hasOpenLoan.value
      ? 'You must complete your current loan before taking a new one.'
      : (eligibility.value?.reason || 'You are currently not eligible to create a loan.')
    return
  }
  createMsg.value = ''
  createErr.value = ''
  if (!createForm.value.total_installments || createForm.value.total_installments < 1) {
    createErr.value = 'Enter total installments'
    return
  }
  // Collect guarantors based on dynamic requirement
  const req = Number(eligibility.value?.required_guarantors || 0)
  const entries = [createForm.value.guarantor1, createForm.value.guarantor2, createForm.value.guarantor3]
    .map(v => (v || '').toString().trim())
    .filter(v => v.length > 0)
  // Deduplicate case-insensitively while preserving original casing
  const seen = new Set()
  const uniqueMemberships = []
  for (const e of entries) {
    const key = e.toLowerCase()
    if (!seen.has(key)) {
      seen.add(key)
      uniqueMemberships.push(e)
    }
  }
  if (req > 0) {
    if (uniqueMemberships.length < req || uniqueMemberships.length > 3) {
      createErr.value = `Provide at least ${req} (max three) guarantor IDs.`
      return
    }
  } else {
    // Instant path: ignore any entered guarantors
    uniqueMemberships.length = 0
  }

  creating.value = true
  try {
    const token = localStorage.getItem('token')
    const payload = {
      total_installments: createForm.value.total_installments,
      interval: createForm.value.interval,
      admin_fee_flat: createForm.value.admin_fee_flat,
      admin_fee_pct: createForm.value.admin_fee_pct,
      ...(req > 0 ? { guarantor_memberships: uniqueMemberships } : {}),
    }
    const { data } = await axios.post('/api/loans', payload, {
      headers: { Authorization: `Bearer ${token}` }
    })

    if (data?.instant_approved) {
      const credited = Number(data?.credited_amount || 0)
      createMsg.value = `Instant approval! ₦ ${n(credited)} has been credited to your wallet.`
    } else {
      createMsg.value = 'Loan application submitted successfully. Awaiting guarantor approvals and admin review. You will be notified when the agreement document is ready for signing.'
    }
    showNotice('Success', createMsg.value, 'success')
    await load()
    await fetchEligibility()
  } catch (e) {
    createErr.value = e?.response?.data?.message || e.message
    showNotice('Error', createErr.value, 'error')
  } finally {
    creating.value = false
  }
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/loans', {
      headers: { Authorization: `Bearer ${token}` }
    })
    loans.value = data
    // Initialize default pay source to 'auto' for each loan
    for (const l of loans.value) {
      if (!paySource.value[l.id]) paySource.value[l.id] = 'auto'
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const pay = async (loan) => {
  payMsg.value[loan.id] = ''
  payErr.value[loan.id] = ''
  const amt = Number(payAmount.value[loan.id])
  if (!amt || amt <= 0) {
    payErr.value[loan.id] = 'Enter a valid amount'
    showNotice('Error', payErr.value[loan.id], 'error')
    return
  }
  paying.value[loan.id] = true
  try {
    const token = localStorage.getItem('token')
    const payload = {
      amount: amt,
      source: 'auto',
      callback_url: window.location.origin + '/loans'
    }
    const { data } = await axios.post(`/api/loans/${loan.id}/repay`, { ...payload, source: paySource.value[loan.id] || 'auto' }, {
      headers: { Authorization: `Bearer ${token}` }
    })

    // If Paystack flow was initiated, redirect user to authorization_url
    if (data?.authorization_url) {
      window.location.href = data.authorization_url
      return
    }

    // Otherwise, it was processed via wallet; refresh data
    await load()

    if (data?.summary?.capped) {
      payMsg.value[loan.id] = `Payment was capped to ₦ ${n(data.summary.amount_applied)} (remaining principal).`
    } else {
      payMsg.value[loan.id] = `Payment of ₦ ${n(data.summary?.amount_applied || amt)} recorded successfully.`
    }
    showNotice('Success', payMsg.value[loan.id], 'success')
    payAmount.value[loan.id] = ''
  } catch (e) {
    payErr.value[loan.id] = e?.response?.data?.message || e.message
    showNotice('Error', payErr.value[loan.id], 'error')
  } finally {
    paying.value[loan.id] = false
  }
}

const getScheduleDownloadUrl = (loan) => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-loan-schedule/${loan.id}?token=${encodeURIComponent(token)}`
}

// Guarantor request APIs
const fetchGuarantorRequests = async () => {
  grLoading.value = true
  grError.value = ''
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/guarantor/requests', {
      headers: { Authorization: `Bearer ${token}` }
    })
    guarantorRequests.value = data || []
  } catch (e) {
    grError.value = e?.response?.data?.message || e.message || 'Failed to load requests'
  } finally {
    grLoading.value = false
  }
}

const acceptGuarantor = async (req) => {
  if (!req?.id) return
  grMsg.value[req.id] = ''

  // 1) Require biometric confirmation when available
  try {
    const bioAvailable = await isBiometricAvailable()
    if (bioAvailable) {
      const ok = await verifyBiometricIdentity({
        reason: 'Sign as Guarantor',
        title: 'Guarantor Approval',
        subtitle: req?.qard_id_string ? `Loan ${req.qard_id_string}` : 'Confirm approval',
        description: `Approve loan of ₦ ${n(req?.principal_amount)} by ${req?.member?.name || 'member'}?`,
      })
      if (!ok) {
        showNotice('Authentication required', 'Biometric verification was cancelled or failed. Unable to sign as guarantor.', 'error')
        return
      }
    } else {
      // Fallback: explicit confirm prompt
      const proceed = window.confirm('Confirm you agree to be a guarantor for this loan?')
      if (!proceed) return
    }
  } catch (_) {
    // If biometric check throws, abort silently and let user try again
    showNotice('Authentication error', 'Could not verify biometrics at this time. Please try again.', 'error')
    return
  }

  grAction.value[req.id] = true
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.post(`/api/guarantor/requests/${req.id}/accept`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    grMsg.value[req.id] = data?.message || 'Accepted'
    if (data?.all_accepted) {
      showNotice('All approvals complete', 'All guarantors have accepted. Awaiting admin disbursement.', 'success')
    }
    await fetchGuarantorRequests()
  } catch (e) {
    grMsg.value[req.id] = e?.response?.data?.message || e.message || 'Failed to accept'
  } finally {
    grAction.value[req.id] = false
  }
}

const declineGuarantor = async (req) => {
  if (!req?.id) return
  grMsg.value[req.id] = ''

  // Confirm before declining
  const proceed = window.confirm('Are you sure you want to decline this guarantor request?')
  if (!proceed) return

  grAction.value[req.id] = true
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.post(`/api/guarantor/requests/${req.id}/decline`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    grMsg.value[req.id] = data?.message || 'Declined'
    await fetchGuarantorRequests()
  } catch (e) {
    grMsg.value[req.id] = e?.response?.data?.message || e.message || 'Failed to decline'
  } finally {
    grAction.value[req.id] = false
  }
}

onMounted(async () => { await load(); await fetchEligibility(); await fetchGuarantorRequests(); })
</script>
