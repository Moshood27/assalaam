<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <AppHeader title="Qard Hasan Records" :showBack="true" />

    <div class="container-app py-4">
      <div v-if="loading" class="text-center text-slate-500 py-10">Loading…</div>
      <div v-else-if="error" class="card p-4 text-rose-700 bg-rose-50 border-rose-200">{{ error }}</div>

      <div v-else class="space-y-4">
              <!-- Feature Disabled Alert -->
              <div v-if="appStatusStore.features['apply-for-loan'] === false" class="card bg-amber-50 border-amber-200 p-8 rounded-[2rem] text-center space-y-4 shadow-sm mb-6">
                <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-inner">
                  🤝
                </div>
                <div>
                  <h3 class="text-lg font-black text-slate-800">Qard Hasan Applications Paused</h3>
                  <p class="text-xs text-slate-500 mt-2 leading-relaxed px-4">
                    Qard Hasan applications are currently restricted. This might be due to monthly budget limits or system maintenance.
                    Please check back later or contact your branch admin.
                  </p>
                </div>
              </div>

              <!-- Eligibility and Create Loan (Fintech Redesign) -->
              <div class="space-y-6" v-if="canCreateLoanVisible">
                <!-- Eligibility Summary Card -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2rem] p-6 text-white shadow-xl relative overflow-hidden">
                  <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full"></div>
                  <div class="flex items-center justify-between mb-6 relative z-10">
                    <div>
                      <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Available Credit Limit</p>
                      <h2 class="text-3xl font-black mt-1">₦ {{ n(eligibility.eligibility_with_score || eligibility.eligibility_adjusted || eligibility.eligibility) }}</h2>
                    </div>
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-emerald-500/30">
                      💰
                    </div>
                  </div>
                  
                  <div class="grid grid-cols-3 gap-4 border-t border-white/10 pt-6 relative z-10">
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Savings</p>
                      <p class="text-sm font-bold">₦ {{ n(eligibility.savings) }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Shares</p>
                      <p class="text-sm font-bold">₦ {{ n(eligibility.shares) }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Base</p>
                      <p class="text-sm font-bold">₦ {{ n(eligibility.base) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Trust Score & Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div v-if="eligibility.attaqwa_score !== undefined" class="card p-4 flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-xl shadow-inner border border-indigo-100">
                      🛡️
                    </div>
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Trust Score</p>
                      <p class="text-lg font-black text-slate-800">
                        {{ Math.round((eligibility.attaqwa_score || 0) * 10) / 10 }}
                        <span v-if="eligibility.band" class="text-xs text-indigo-600 font-bold">({{ bandLabel(eligibility.band) }})</span>
                      </p>
                    </div>
                  </div>

                  <div v-if="eligibility.meeting_attendance_count !== undefined" class="card p-4 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-xl shadow-inner border border-amber-100">
                      📅
                    </div>
                    <div>
                      <p class="text-[10px] text-slate-400 font-bold uppercase">Attendance</p>
                      <p class="text-lg font-black" :class="eligibility.meeting_attendance_count >= eligibility.required_loan_meetings ? 'text-emerald-600' : 'text-amber-600'">
                        {{ eligibility.meeting_attendance_count || 0 }} / {{ eligibility.required_loan_meetings || 8 }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Create Loan Form -->
                <div class="card overflow-hidden">
                  <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Configure Qard Hasan</h3>
                    <div class="flex gap-2">
                      <router-link to="/loans/analysis" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100 hover:bg-emerald-100 transition-colors">Analysis</router-link>
                      <button class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-slate-100 px-3 py-1 rounded-full border border-slate-200 hover:bg-slate-200 transition-colors" @click="fetchEligibility">Refresh</button>
                    </div>
                  </div>
                  
                  <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                      <div class="space-y-2">
                        <label class="text-[11px] text-slate-500 font-black uppercase tracking-widest">Repayment Period</label>
                        <div class="relative">
                          <input v-model.number="createForm.total_installments" type="number" min="1" :max="eligibility.recommended_duration" class="input pl-10 h-12" placeholder="e.g. 12"/>
                          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">⏱️</span>
                        </div>
                        <p v-if="eligibility.recommended_duration" class="text-[9px] text-slate-400 font-black mt-1 uppercase tracking-wider">Policy Max: {{ eligibility.recommended_duration }} months</p>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="text-[11px] text-slate-500 font-black uppercase tracking-widest">Payment Frequency</label>
                        <div class="relative">
                          <select v-model="createForm.interval" class="input pl-10 h-12 appearance-none">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                          </select>
                          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🔄</span>
                        </div>
                      </div>
                    </div>

                    <!-- Guarantors with Search -->
                    <div v-if="(eligibility.required_guarantors || 0) > 0" class="space-y-4">
                      <div class="flex items-center justify-between">
                        <label class="text-[11px] text-slate-500 font-black uppercase tracking-widest">Guarantors Required ({{ eligibility.required_guarantors }})</label>
                        <span class="text-[10px] text-slate-400 italic">Select up to 3</span>
                      </div>
                      
                      <div class="grid grid-cols-1 gap-3">
                        <div v-for="i in 3" :key="i" class="relative group">
                          <div class="relative">
                            <input 
                              v-model="createForm['guarantor' + i]" 
                              type="text" 
                              class="input h-12 pl-12 pr-10 border-slate-200 focus:border-emerald-500 transition-all" 
                              :placeholder="'Search Guarantor ' + i + (i > eligibility.required_guarantors ? ' (Optional)' : '')"
                              @focus="startGuarantorSearch(i)"
                              @input="searchGuarantors(createForm['guarantor' + i])"
                            />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl grayscale group-focus-within:grayscale-0 transition-all">
                              {{ createForm['guarantor' + i] ? '✅' : '👤' }}
                            </span>
                            <button v-if="createForm['guarantor' + i]" @click="createForm['guarantor' + i] = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-rose-500 transition-colors">
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                          </div>

                          <!-- Search Results Dropdown -->
                          <div v-if="activeGuarantorIndex === i && (searchingGuarantor || guarantorResults.length || (createForm['guarantor' + i] && createForm['guarantor' + i].length >= 2))" class="absolute z-30 w-full mt-2 bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                            <div v-if="searchingGuarantor" class="p-6 text-center space-y-2">
                              <div class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                              <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Searching Members...</p>
                            </div>
                            <div v-else-if="guarantorResults.length === 0 && createForm['guarantor' + i] && createForm['guarantor' + i].length >= 2" class="p-6 text-center">
                              <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">No members found</p>
                              <p class="text-[10px] text-slate-400 mt-1">Try searching by full name or ID</p>
                            </div>
                            <ul v-else class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
                              <li v-for="member in guarantorResults" :key="member.id" @click="selectGuarantor(member)" class="p-4 hover:bg-slate-50 cursor-pointer transition-colors flex items-center justify-between group">
                                <div>
                                  <p class="text-sm font-black text-slate-800">{{ member.name }}</p>
                                  <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">{{ member.membership_number }} • {{ member.branch }}</p>
                                </div>
                                <span class="text-emerald-500 opacity-0 group-hover:opacity-100 transition-all">Add ➜</span>
                              </li>
                            </ul>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div v-else class="p-4 rounded-3xl bg-emerald-50 border border-emerald-100 flex items-start gap-3">
                      <span class="text-xl">✨</span>
                      <div>
                        <p class="text-sm font-bold text-emerald-900">Instant Approval Eligible</p>
                        <p class="text-xs text-emerald-700 leading-relaxed mt-0.5">No guarantors required. Your loan will be credited automatically upon submission.</p>
                      </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                      <div class="flex items-center justify-between mb-4">
                        <div>
                          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Total Repayment Amount</p>
                          <p class="text-xl font-black text-slate-800">₦ {{ n(eligibility.eligibility_with_score || eligibility.eligibility_adjusted || eligibility.eligibility) }}</p>
                        </div>
                        <div class="text-right">
                          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Installment</p>
                          <p class="text-lg font-bold text-slate-600">₦ {{ n((eligibility.eligibility_with_score || eligibility.eligibility_adjusted || eligibility.eligibility) / (createForm.total_installments || 1)) }}</p>
                        </div>
                      </div>

                      <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white h-14 rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-emerald-200 transition-all disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2" :disabled="creating" @click="createLoan">
                        <span v-if="!creating">Apply for Qard Hasan</span>
                        <template v-else>
                          <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                          <span>Processing...</span>
                        </template>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notice when creation is not available -->
              <div class="card p-8 text-center space-y-4" v-else>
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-inner grayscale">
                  🔒
                </div>
                <div class="space-y-2">
                  <h3 class="text-lg font-black text-slate-800">Application Restricted</h3>
                  <p class="text-xs text-slate-500 leading-relaxed px-6" v-if="hasOpenLoan">
                    Our policy requires members to complete their active Qard Hasan before applying for a new one. 
                    Finish your current plan to unlock more credit.
                  </p>
                  <p class="text-xs text-slate-500 leading-relaxed px-6" v-else>
                    {{ eligibility.reason || 'You are currently not eligible to request Qard Hasan. Ensure you have at least 6 months of membership and sufficient contributions.' }}
                  </p>
                </div>
                <div class="pt-2">
                  <router-link to="/loans/analysis" class="text-[11px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-6 py-2 rounded-full border border-emerald-100 hover:bg-emerald-100 transition-colors">
                    View My Analysis
                  </router-link>
                </div>
              </div>

              <!-- Guarantor Requests (Fintech Redesign) -->
              <div class="card overflow-hidden" v-if="grLoading || (guarantorRequests && guarantorRequests.length)">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                  <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Guarantor Invites</h3>
                  <button class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-slate-100 px-3 py-1 rounded-full border border-slate-200 hover:bg-slate-200 transition-colors" @click="fetchGuarantorRequests">Refresh</button>
                </div>
                
                <div class="p-8 text-center" v-if="grLoading">
                   <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                   <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Loading Requests...</p>
                </div>

                <div class="p-0" v-else>
                  <p v-if="grError" class="m-4 text-xs text-rose-700 bg-rose-50 border border-rose-100 rounded-2xl p-4">{{ grError }}</p>
                  
                  <ul class="divide-y divide-slate-50">
                    <li v-for="req in guarantorRequests" :key="req.id" class="p-5 hover:bg-slate-50/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-xl shadow-inner">
                          🤝
                        </div>
                        <div>
                          <p class="font-black text-slate-800">{{ req.member?.name || 'Member' }}</p>
                          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ req.member?.branch || 'Branch' }} • {{ req.qard_id_string }}</p>
                          <p class="text-xs font-bold text-slate-600 mt-1">Requested: <span class="text-slate-900">₦ {{ n(req.principal_amount) }}</span></p>
                        </div>
                      </div>

                      <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-4 sm:pt-0">
                        <div class="text-right sm:block hidden">
                          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Status</p>
                          <span :class="req.guarantor_status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : (req.guarantor_status === 'declined' ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-700')" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                            {{ req.guarantor_status }}
                          </span>
                        </div>

                        <div class="flex items-center gap-2" v-if="req.guarantor_status === 'pending'">
                          <button class="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100 transition-all flex items-center justify-center min-w-[80px]" :disabled="!!grAction[req.id]" @click="acceptGuarantor(req)">
                            <span v-if="!grAction[req.id]">Accept</span>
                            <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                          </button>
                          <button class="h-10 px-4 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all min-w-[80px]" :disabled="!!grAction[req.id]" @click="declineGuarantor(req)">
                            <span v-if="!grAction[req.id]">Decline</span>
                            <div v-else class="w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"></div>
                          </button>
                        </div>
                        <div v-else class="sm:hidden">
                           <span :class="req.guarantor_status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : (req.guarantor_status === 'declined' ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-700')" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                            {{ req.guarantor_status }}
                          </span>
                        </div>
                      </div>
                      <p v-if="grMsg[req.id]" class="w-full text-[11px] text-emerald-700 font-bold bg-emerald-50 p-2 rounded-lg text-center">{{ grMsg[req.id] }}</p>
                    </li>
                  </ul>
                </div>
              </div>
        <!-- Loan Records (Fintech Redesign) -->
        <div v-for="(loan, idx) in loans" :key="loan.id" class="card overflow-hidden group hover:shadow-xl transition-all duration-300">
          <!-- Card Header -->
          <div class="p-5 flex items-center justify-between bg-slate-50/50 border-b border-slate-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-inner border transition-transform group-hover:scale-110" :class="loan.is_completed ? 'bg-emerald-50 border-emerald-100' : (['defaulted', 'rejected'].includes(loan.status) ? 'bg-rose-50 border-rose-100' : 'bg-white border-slate-200')">
                {{ loan.is_completed ? '✅' : (loan.status === 'defaulted' ? '⚠️' : (loan.status === 'rejected' ? '❌' : '💳')) }}
              </div>
              <div>
                <h3 class="font-black text-slate-800">Qard Hasan Loan</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ loan.qard_id_string }}</p>
              </div>
            </div>
            <div class="text-right">
              <span :class="getStatusBadgeClass(loan)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                {{ loan.is_completed ? 'Completed' : (loan.overdue_amount > 0 ? 'Action Required' : loan.status) }}
              </span>
            </div>
          </div>

          <div class="p-6 space-y-6">
            <!-- Repayment Progress -->
            <div>
              <div class="flex justify-between items-end mb-3">
                <div>
                  <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Repayment Progress</p>
                  <p class="text-xl font-black text-slate-800">{{ Math.round((loan.paid_amount / loan.principal_amount) * 100) }}% <span class="text-xs text-slate-400 font-bold">Repaid</span></p>
                </div>
                <div class="text-right">
                  <p v-if="loan.overdue_amount > 0" class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1">Expected to Pay: ₦ {{ n(loan.overdue_amount) }}</p>
                  <p class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">₦ {{ n(loan.paid_amount) }} / ₦ {{ n(loan.principal_amount) }}</p>
                </div>
              </div>
              <div class="h-3 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000 relative" :style="{ width: (loan.paid_amount / loan.principal_amount * 100) + '%' }">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white/20 animate-pulse"></div>
                </div>
              </div>
              <div class="flex justify-between mt-2">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Started: {{ new Date(loan.created_at).toLocaleDateString() }}</p>
                <p class="text-[10px] text-rose-500 font-black uppercase tracking-widest" v-if="!loan.is_completed">₦ {{ n(loan.remaining_principal ?? (loan.principal_amount - loan.paid_amount)) }} Remaining</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-3xl border border-slate-100">
              <div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Installment</p>
                <p class="text-lg font-black text-slate-800">₦ {{ n(loan.per_installment) }}</p>
                <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">{{ loan.total_installments }} × {{ loan.interval }}</p>
              </div>
              <div class="text-right border-l border-slate-200 pl-6" v-if="loan.status !== 'rejected'">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Next Action</p>
                <button @click="openSchedule(loan)" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mt-2 inline-flex items-center gap-1 bg-indigo-50 px-3 py-1.5 rounded-xl border border-indigo-100 hover:bg-indigo-100 transition-colors">
                  Schedule <span class="text-xs">➜</span>
                </button>
              </div>
            </div>

            <!-- Guarantors list -->
            <div v-if="loan.guarantors && loan.guarantors.length" class="space-y-3">
              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Guarantors</p>
              <div class="flex flex-wrap gap-2">
                <div v-for="g in loan.guarantors" :key="g.id" class="flex items-center gap-2 bg-white border border-slate-200 px-3 py-1.5 rounded-2xl shadow-sm">
                  <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500">
                    {{ (g.name || 'M').charAt(0) }}
                  </div>
                  <div>
                    <p class="text-[11px] font-black text-slate-800 leading-none">{{ g.name }}</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">{{ g.branch?.name || 'Main' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Rejection Notice -->
            <div v-if="loan.status === 'rejected'" class="p-4 rounded-[2rem] bg-rose-50 border border-rose-100 space-y-2">
              <div class="flex items-center gap-2 text-rose-700">
                <span class="text-lg">❌</span>
                <h4 class="text-xs font-black uppercase tracking-widest">Application Rejected</h4>
              </div>
              <p class="text-[11px] text-rose-600 font-bold leading-relaxed italic">
                {{ loan.rejection_reason || 'Unfortunately, your Qard Hasan application was not approved by the committee at this time. Please contact your branch administrator for more details.' }}
              </p>
            </div>

            <!-- Agreement Section -->
            <div class="p-4 rounded-[2rem] border border-amber-100 bg-gradient-to-br from-amber-50 to-orange-50 space-y-4" v-if="(loan.status === 'pending' || loan.signed_agreement) && loan.status !== 'rejected'">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-xl shadow-sm border border-amber-100">
                  📜
                </div>
                <div>
                  <h4 class="text-xs font-black text-amber-900 uppercase tracking-widest">Qard Hasan Agreement</h4>
                  <p class="text-[10px] text-amber-700 font-bold" v-if="loan.approved_at">Action required to disburse funds</p>
                  <p class="text-[10px] text-amber-700 font-bold" v-else>Awaiting committee approval</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" v-if="loan.approved_at || loan.signed_agreement">
                <a v-if="loan.agreement_template" :href="getImageUrl(loan.agreement_template)" target="_blank" class="flex items-center justify-center gap-2 h-11 bg-white border border-amber-200 rounded-xl text-[11px] font-black text-amber-900 uppercase tracking-widest hover:bg-amber-100 transition-colors">
                  <span>📥</span> Download PDF
                </a>
                <a v-else :href="getAgreementDownloadUrl(loan.id)" target="_blank" class="flex items-center justify-center gap-2 h-11 bg-white border border-amber-200 rounded-xl text-[11px] font-black text-amber-900 uppercase tracking-widest hover:bg-amber-100 transition-colors">
                  <span>⚙️</span> Generate PDF
                </a>

                <div v-if="loan.agreement_verified_at" class="h-11 bg-emerald-500 text-white rounded-xl flex items-center justify-center gap-2 text-[11px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100">
                  <span>✅</span> Verified
                </div>
                <div v-else-if="loan.signed_agreement" class="h-11 bg-white border border-amber-200 rounded-xl flex items-center justify-center gap-2 text-[11px] font-black text-amber-600 uppercase tracking-widest italic relative">
                   <div class="w-4 h-4 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></div>
                   Reviewing...
                   <button @click="triggerAgreementUpload(loan.id)" class="absolute -top-2 -right-2 w-6 h-6 bg-white border border-slate-200 rounded-full flex items-center justify-center text-[10px] shadow-sm">🔄</button>
                </div>
                <div v-else class="sm:col-span-2">
                  <input :id="'agreement-input-' + loan.id" type="file" accept="application/pdf,image/*" class="hidden" @change="(e) => onAgreementFileChange(e, loan.id)" />
                  <button @click="triggerAgreementUpload(loan.id)" class="w-full h-11 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg shadow-amber-200 transition-all flex items-center justify-center gap-2" :disabled="uploadingAgreement[loan.id]">
                    <span v-if="!uploadingAgreement[loan.id]">📤 Upload Signed Copy</span>
                    <span v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                  </button>
                </div>
              </div>
              <div v-else class="p-4 bg-white/60 border border-dashed border-amber-200 rounded-2xl text-center">
                <p class="text-[10px] text-amber-800 font-black uppercase tracking-widest opacity-40">Agreement Locked</p>
                <p class="text-[9px] text-amber-700 font-bold mt-1">Available after committee approval</p>
              </div>
              
              <p v-if="loan.agreement_rejection_reason" class="p-3 bg-rose-100 border border-rose-200 rounded-xl text-[10px] text-rose-700 font-bold italic">
                ⚠️ Rejected: {{ loan.agreement_rejection_reason }}
              </p>
            </div>

            <!-- Repayment Section -->
            <div class="pt-6 border-t border-slate-100 space-y-4" v-if="!loan.is_completed && ['active', 'defaulted'].includes(loan.status)">
              <div class="flex items-center justify-between">
                <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Make a Repayment</h4>
                <span class="text-[10px] text-emerald-600 font-bold">Auto-source enabled</span>
              </div>

              <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                  <input type="number" min="0.01" step="0.01" class="input h-12 pl-10" :disabled="loan.is_completed || paying[loan.id]" v-model.number="payAmount[loan.id]" placeholder="Enter amount" />
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">₦</span>
                </div>
                <select class="input h-12 sm:w-40 appearance-none bg-slate-50 border-slate-100" v-model="paySource[loan.id]" :disabled="loan.is_completed || paying[loan.id]">
                  <option value="auto">Auto (Smart)</option>
                  <option value="wallet">My Wallet</option>
                  <option v-for="gw in enabledGateways" :key="gw" :value="gw">
                    {{ gw.charAt(0).toUpperCase() + gw.slice(1) }}
                  </option>
                </select>
                <button class="bg-slate-900 hover:bg-black text-white h-12 px-6 rounded-2xl font-black uppercase tracking-widest transition-all disabled:opacity-50 flex items-center justify-center gap-2" :disabled="loan.is_completed || paying[loan.id]" @click="pay(loan)">
                  <span v-if="!paying[loan.id]">Pay Now</span>
                  <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                </button>
              </div>

              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="flex items-center justify-between mb-3">
                   <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recent Payments</h5>
                   <button class="text-[9px] font-black text-indigo-600 uppercase hover:underline" v-if="loan.repayments?.length > 3">View All</button>
                </div>
                <ul class="space-y-2" v-if="loan.repayments?.length">
                  <li v-for="r in loan.repayments.slice(0,3)" :key="r.id" class="flex justify-between items-center bg-white p-2.5 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-2">
                       <span class="text-emerald-500">💰</span>
                       <span class="text-xs font-black text-slate-800">₦ {{ n(r.amount) }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold">{{ formatRepaymentDate(r) }}</span>
                  </li>
                </ul>
                <p v-else class="text-[10px] text-slate-400 italic text-center py-2">No repayment history yet.</p>
              </div>
            </div>
            <p v-if="payMsg[loan.id]" class="text-xs text-emerald-700">{{ payMsg[loan.id] }}</p>
            <p v-if="payErr[loan.id]" class="text-xs text-rose-700">{{ payErr[loan.id] }}</p>
          </div>
        </div>

        <div v-if="!loans.length" class="card p-6 text-center text-slate-500">No records found.</div>

        <div class="card p-8 text-center space-y-4">
          <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-inner">
            💼
          </div>
          <div>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Business Payments</h3>
            <p class="text-xs text-slate-400 mt-1 font-bold">SME MICRO-FINANCE RECORDS</p>
          </div>
          <div class="p-6 border-2 border-dashed border-slate-100 rounded-[2rem]">
            <p class="text-xs text-slate-400 italic">No business records found on your profile.</p>
          </div>
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

        <LoanScheduleModal
          :isOpen="scheduleModalOpen"
          :loan="selectedLoanForSchedule"
          @close="scheduleModalOpen = false"
        />

        <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import axios from '../http'
import { useAppStatusStore } from '../stores/appStatus'
import getImageUrl from '../utils/image'
import CustomNotice from '../components/CustomNotice.vue'
import LoanScheduleModal from '../components/LoanScheduleModal.vue'
import { useNotice } from '../composables/useNotice'
import { verifyBiometricIdentity, isBiometricAvailable } from '../services/biometric'
import { getEcho } from '../realtime/echo'

const appStatusStore = useAppStatusStore()

// Policy defaults for admin fees (can be overridden via environment variables)
const DEFAULT_ADMIN_FEE_FLAT = Number(import.meta.env.VITE_DEFAULT_ADMIN_FEE_FLAT ?? 0)
const DEFAULT_ADMIN_FEE_PCT = Number(import.meta.env.VITE_DEFAULT_ADMIN_FEE_PCT ?? 0)

const loans = ref([])
const loading = ref(false)
const error = ref('')

// Schedule Modal
const scheduleModalOpen = ref(false)
const selectedLoanForSchedule = ref(null)

const openSchedule = (loan) => {
  selectedLoanForSchedule.value = loan
  scheduleModalOpen.value = true
}

// Notice modal (shared VTU-style)
const { notice, showNotice, closeNotice } = useNotice()

// Eligibility and create loan
const eligibility = ref({ savings: 0, shares: 0, base: 0, eligibility: 0, eligibility_adjusted: 0, months_in_system: 0, is_first_loan: true, can_request: false, reason: '', attaqwa_score: 0, score_bonus_pct: 0, band: '', instant_approval: false, required_guarantors: 2 })
const createForm = ref({ total_installments: 1, interval: 'monthly', admin_fee_flat: DEFAULT_ADMIN_FEE_FLAT, admin_fee_pct: DEFAULT_ADMIN_FEE_PCT, guarantor1: '', guarantor2: '', guarantor3: '' })
const creating = ref(false)
const createMsg = ref('')
const createErr = ref('')

// Guarantor Search
const guarantorSearch = ref('')
const guarantorResults = ref([])
const searchingGuarantor = ref(false)
const activeGuarantorIndex = ref(null)

let searchTimeout = null
const searchGuarantors = async (query) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (!query || query.length < 2) {
    guarantorResults.value = []
    return
  }
  
  searchTimeout = setTimeout(async () => {
    searchingGuarantor.value = true
    try {
      const { data } = await axios.get(`/api/guarantor/search?q=${encodeURIComponent(query)}`)
      guarantorResults.value = data
    } catch (err) {
      console.error('Guarantor search failed', err)
    } finally {
      searchingGuarantor.value = false
    }
  }, 400)
}

const selectGuarantor = (member) => {
  if (activeGuarantorIndex.value === 1) createForm.value.guarantor1 = member.membership_number
  if (activeGuarantorIndex.value === 2) createForm.value.guarantor2 = member.membership_number
  if (activeGuarantorIndex.value === 3) createForm.value.guarantor3 = member.membership_number
  guarantorResults.value = []
  guarantorSearch.value = ''
  activeGuarantorIndex.value = null
}

const startGuarantorSearch = (index) => {
  activeGuarantorIndex.value = index
  guarantorSearch.value = ''
  guarantorResults.value = []
}

const enabledGateways = computed(() => {
  const gws = appStatusStore.paymentGateways || {}
  return Object.keys(gws).filter(k => k !== 'primary' && gws[k])
})
const primaryGatewayName = computed(() => {
  const p = appStatusStore.paymentGateways?.primary || 'paystack'
  return p.charAt(0).toUpperCase() + p.slice(1)
})
const hasAnyLoan = computed(() => (loans.value || []).length > 0)
const hasOpenLoan = computed(() => (loans.value || []).some(l => ['pending', 'active', 'defaulted'].includes(l?.status) && !l?.is_completed))
const hasCompletedLoan = computed(() => (loans.value || []).some(l => l?.is_completed || l?.status === 'completed'))
// Creation is allowed only if no open loan and backend policy allows request (6-month rule and first-loan cap)
const canCreateLoanVisible = computed(() => !hasOpenLoan.value && !!eligibility.value?.can_request && appStatusStore.features['apply-for-loan'] !== false)

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
    showNotice('Error', err?.response?.data?.message || 'Failed to upload agreement (max 10MB).', 'error')
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

const getStatusBadgeClass = (loan) => {
  if (loan.is_completed) return 'bg-emerald-100 text-emerald-700'
  if (loan.overdue_amount > 0) return 'bg-rose-100 text-rose-700'
  if (loan.status === 'active') return 'bg-emerald-100 text-emerald-700'
  if (loan.status === 'pending') return 'bg-amber-100 text-amber-700'
  if (loan.status === 'defaulted' || loan.status === 'rejected') return 'bg-rose-100 text-rose-700'
  return 'bg-slate-100 text-slate-600'
}
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
    if (data.features) {
      appStatusStore.setFeatures(data.features)
    }
    // Auto-apply admin fees from policy defaults and lock the inputs
    const feeFlat = Number.isFinite(DEFAULT_ADMIN_FEE_FLAT) ? Number(DEFAULT_ADMIN_FEE_FLAT) : 0
    let feePct = Number.isFinite(DEFAULT_ADMIN_FEE_PCT) ? Number(DEFAULT_ADMIN_FEE_PCT) : 0
    // Clamp to policy: 0 - 2%
    if (feePct < 0) feePct = 0
    if (feePct > 2) feePct = 2
    createForm.value.admin_fee_flat = feeFlat
    createForm.value.admin_fee_pct = feePct

    if (data.recommended_duration) {
      createForm.value.total_installments = data.recommended_duration
    }
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
      createMsg.value = data?.message || `Instant approval! ₦ ${n(credited)} has been credited to your wallet.`
    } else {
      createMsg.value = data?.message || 'Loan application submitted successfully. Awaiting guarantor approvals and admin review. You will be notified when the agreement document is ready for signing.'
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

onMounted(async () => { 
  await load(); 
  await fetchEligibility(); 
  await fetchGuarantorRequests(); 

  // Real-time listener for loan status updates
  try {
    const echo = getEcho()
    const token = localStorage.getItem('token')
    if (token) {
      // We need user ID to listen on private channel. 
      // We can get it from the loans list or a separate call, 
      // but usually it's stored in a user store or we can just try to fetch it if not available.
      // For now, let's assume we can get it from the first loan if exists, or just do a quick profile fetch if needed.
      // Better yet, if we don't have it, we can't listen.
      
      // Let's check if we have it in some local storage or if we can get it from loans
      let userId = null
      if (loans.value && loans.value.length > 0) {
        userId = loans.value[0].user_id
      }
      
      if (!userId) {
         // try to get from user store if available, or just fetch profile
         try {
           const { data: userData } = await axios.get('/api/profile', { headers: { Authorization: `Bearer ${token}` } })
           userId = userData.id
         } catch(_) {}
      }

      if (userId) {
        echo.private(`user.${userId}`)
          .listen('UserAccountUpdated', (e) => {
            console.log('Real-time update received in Loans:', e)
            
            // If the message or action indicates a loan-related change, refresh
            const isLoanAction = e.action && (
              e.action.type === 'loan_approved' || 
              e.action.type === 'loan_disbursed' || 
              e.action.type === 'loan_repayment' ||
              e.action.type === 'guarantor_request'
            )
            
            // To be safe and ensure everything is fresh, just refresh
            load()
            fetchEligibility()
            fetchGuarantorRequests()

            if (e.message) {
               showNotice('Update', e.message, 'success')
            }
          })
      }
    }
  } catch (err) {
    console.error('Failed to initialize real-time listener in Loans:', err)
  }
})

onUnmounted(() => {
  try {
    const echo = getEcho()
    const token = localStorage.getItem('token')
    // We don't necessarily need to leave the channel if other components use it,
    // but we should stop listening to this specific event if we want to be clean.
    // However, Laravel Echo's .listen() doesn't easily un-listen without leaving the channel.
    // Usually, we just leave it be or leave channel if it's page-specific.
  } catch(_) {}
})
</script>
