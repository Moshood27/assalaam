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
          <h1 class="text-lg font-bold text-slate-800">Profile Information</h1>
        </div>
        <button @click="$router.push('/support')" class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-3 py-2 rounded-xl hover:bg-emerald-100 transition-colors">Support</button>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 opacity-40" />

        <div class="flex items-center gap-4 mb-6 relative z-10">
          <div class="relative">
            <div class="w-20 h-20 rounded-3xl flex items-center justify-center text-3xl font-bold overflow-hidden bg-emerald-700 text-white shadow-lg shadow-emerald-700/20">
              <img v-if="profile.passport_url" :src="getImageUrl(profile.passport_url)" alt="Profile photo" class="w-full h-full object-cover" />
              <span v-else>{{ (profile.full_name || 'M')[0] }}</span>
            </div>
            <button @click="chooseFile" class="absolute -bottom-2 -right-2 bg-white p-2 rounded-xl shadow-md border border-slate-100 text-emerald-700 active:scale-90 transition-transform">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
            <input id="passport-input" ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
          </div>
          <div class="min-w-0">
            <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest mb-1">Membership Status</p>
            <h2 class="text-lg font-black text-slate-800 uppercase leading-tight truncate">{{ profile.full_name }}</h2>
            <p class="text-xs text-slate-500 font-medium">Joined {{ profile.date_joined || 'Recently' }}</p>
          </div>
        </div>

        <div class="space-y-3 relative z-10">
          <div v-for="item in [
            { label: 'Email Address', value: profile.email, icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
            { label: 'Membership ID', value: profile.membership_id, icon: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm5 3h-3a2 2 0 01-2-2V5' },
            { label: 'Phone Number', value: profile.phone || '—', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
            { label: 'Current Branch', value: profile.branch_name || '—', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' }
          ]" :key="item.label" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100 group transition-colors hover:border-emerald-200">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-emerald-600 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">{{ item.label }}</p>
                <p class="text-sm font-bold text-slate-800 truncate">{{ item.value }}</p>
              </div>
            </div>
            <button @click="copy(item.value)" class="p-2 text-emerald-700 hover:bg-emerald-50 rounded-lg transition-all active:scale-95">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
              </svg>
            </button>
          </div>
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-2">Residential Address</p>
            <p class="text-sm font-bold text-slate-800 leading-relaxed">{{ profile.address || '—' }}</p>
          </div>
        </div>
      </div>

      <!-- Attaqwa Score & Badges -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-32 h-32 bg-teal-50 rounded-full -mr-16 -mt-16 opacity-40" />
        <div class="relative z-10">
          <div class="flex items-center justify-between mb-4">
            <div>
              <p class="text-[10px] text-teal-600 font-black uppercase tracking-widest mb-1">Internal Credit Rating</p>
              <h3 class="text-xl font-black text-slate-800">Attaqwa Score</h3>
            </div>
            <div class="text-right">
              <span class="text-3xl font-black text-teal-600">{{ profile.attaqwa_score || 0 }}</span>
              <p class="text-[10px] text-slate-400 font-bold uppercase">{{ bandLabel(profile.attaqwa_band) }}</p>
            </div>
          </div>

          <p class="text-xs text-slate-500 mb-4 leading-relaxed">
            Your score is based on your cooperative behavior, consistent passbook contributions, and loan repayments. High scores unlock larger interest-free loans.
          </p>

          <div v-if="profile.attaqwa_tips && profile.attaqwa_tips.length > 0" class="mb-4 space-y-2">
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">How to improve your score</p>
            <div v-for="(tip, idx) in profile.attaqwa_tips" :key="idx" class="flex items-start gap-2 text-[11px] text-teal-700 bg-teal-50/50 p-2 rounded-lg border border-teal-100/50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ tip }}
            </div>
          </div>

          <div v-if="profile.badges && profile.badges.length > 0">
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Earned Badges</p>
            <div class="flex flex-wrap gap-2">
              <div v-for="badge in profile.badges" :key="badge.id" 
                   class="flex items-center gap-2 bg-emerald-50 border border-emerald-100 px-3 py-2 rounded-xl group relative cursor-help">
                <span class="text-lg" v-if="badge.type === 'consistency_savings_12'">📅</span>
                <span class="text-lg" v-else-if="badge.type === 'early_loan_repayment'">🚀</span>
                <span class="text-lg" v-else-if="badge.type === 'savings_milestone_100k'">💰</span>
                <span class="text-lg" v-else-if="badge.type === 'vtu_power_user'">⚡</span>
                <span class="text-lg" v-else-if="badge.type === 'loan_master'">🎓</span>
                <span class="text-lg" v-else>🏆</span>
                <div class="min-w-0">
                  <p class="text-[10px] font-bold text-emerald-800 leading-none">{{ badge.name }}</p>
                </div>
                
                <!-- Tooltip -->
                <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-48 bg-slate-800 text-white text-[10px] p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-xl">
                  {{ badge.description }}
                  <p class="mt-1 opacity-60">Earned: {{ new Date(badge.earned_at).toLocaleDateString() }}</p>
                  <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-800" />
                </div>
              </div>
            </div>
          </div>
          <div v-else class="p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
             <p class="text-xs text-slate-400">No badges earned yet. Keep saving consistently to earn your first badge!</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Verification</p>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-slate-50 p-3 rounded-xl">
            <p class="text-[10px] text-slate-400 font-bold uppercase">BVN</p>
            <div class="flex items-center gap-2">
              <span :class="bvnAssigned ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
                {{ bvnAssigned ? 'Assigned' : 'Not Assigned' }}
              </span>
            </div>
            <p class="text-[11px] text-slate-600 mt-1">
              Status:
              <span :class="profile.bvn_verified ? 'text-emerald-700 font-semibold' : 'text-slate-600'">
                {{ profile.bvn_verified ? 'Verified' : 'Not Verified' }}
              </span>
              <span v-if="profile.bvn_verified_at"> on {{ profile.bvn_verified_at }}</span>
            </p>
          </div>
          <div class="bg-slate-50 p-3 rounded-xl">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Verification Details</p>
            <p class="font-bold text-slate-800 text-sm">{{ profile.verification_details || '—' }}</p>
            <div class="mt-1 text-xs text-slate-600">
              <div>KYC Provider: <span class="font-semibold">{{ (profile.kyc && profile.kyc.provider) || '—' }}</span>
                <span v-if="profile.kyc && profile.kyc.score" class="ml-1">(score: {{ Number(profile.kyc.score).toFixed(2) }})</span>
              </div>
              <div v-if="profile.kyc && profile.kyc.status">KYC Status: <span class="font-semibold">{{ profile.kyc.status }}</span></div>
            </div>
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-500">KYC status is used to prevent fraud and verify identity.</div>
      </div>

      <!-- Membership Documents -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Membership Documents</p>
        <div class="space-y-3">
          <button @click="downloadMembershipForm" :disabled="downloadingDoc" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100 group transition-colors hover:border-emerald-200">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-emerald-600 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold text-slate-800">Membership Enrolment Form</p>
                <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Download PDF</p>
              </div>
            </div>
            <div class="text-emerald-700">
              <svg v-if="!downloadingForm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              <span v-else class="text-[10px] font-bold animate-pulse">...</span>
            </div>
          </button>

          <button @click="downloadImamAttestation" :disabled="downloadingDoc" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100 group transition-colors hover:border-emerald-200">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-emerald-600 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold text-slate-800">Attestation of Imam</p>
                <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Download PDF</p>
              </div>
            </div>
            <div class="text-emerald-700">
              <svg v-if="!downloadingImam" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              <span v-else class="text-[10px] font-bold animate-pulse">...</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Vendor Portal -->
      <div v-if="profile.vendor" class="bg-white rounded-3xl shadow-sm border border-emerald-100 p-5 overflow-hidden relative group">
        <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-full -mr-8 -mt-8 transition-transform group-hover:scale-110" />
        <div class="relative z-10">
          <div class="flex items-center justify-between mb-2">
            <div>
              <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest">Vendor Dashboard</p>
              <h3 class="text-lg font-black text-slate-800 uppercase">{{ profile.vendor.name }}</h3>
            </div>
            <div :class="profile.vendor.is_approved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'" class="px-2 py-1 rounded-lg text-[10px] font-black uppercase">
              {{ profile.vendor.is_approved ? 'Approved' : 'Pending Approval' }}
            </div>
          </div>
          <p class="text-xs text-slate-500 mb-4">Manage your products, track orders, and view payouts from your business.</p>
          <button @click="profile.vendor.is_approved ? $router.push('/vendor/dashboard') : alert('Your vendor profile is pending approval. You will be notified once approved.')" 
                  :class="profile.vendor.is_approved ? 'bg-emerald-700 hover:bg-emerald-800 shadow-emerald-700/20' : 'bg-slate-400 cursor-not-allowed'"
                  class="w-full h-12 rounded-xl text-white font-bold transition-colors flex items-center justify-center gap-2 shadow-lg">
            <span>Go to Vendor Portal</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </button>
        </div>
      </div>
      <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Local Business</p>
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">🏪</div>
          <div class="flex-1">
            <h3 class="text-sm font-bold text-slate-800">Become a Vendor</h3>
            <p class="text-xs text-slate-500">Sell your products to other members with cooperative financing.</p>
          </div>
          <button @click="$router.push('/vendor/apply')" class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-xs hover:bg-emerald-100 transition-colors">Apply</button>
        </div>
      </div>

      <!-- Islamic Finance Features -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Islamic Finance</p>
        <div class="space-y-4">
          <button @click="$router.push('/wasiyyah')" class="w-full flex items-center gap-4 text-left group">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-lg group-active:scale-90 transition-transform">📋</div>
            <div class="flex-1">
              <h3 class="text-sm font-bold text-slate-800">Wasiyyah (Next of Kin)</h3>
              <p class="text-[11px] text-slate-500 font-medium">Manage your beneficiaries and legacy details.</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300 group-hover:text-indigo-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          
          <button @click="$router.push('/junior-cooperative')" class="w-full flex items-center gap-4 text-left group pt-4 border-t border-slate-50">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-lg group-active:scale-90 transition-transform">👶</div>
            <div class="flex-1">
              <h3 class="text-sm font-bold text-slate-800">Junior Cooperative</h3>
              <p class="text-[11px] text-slate-500 font-medium">Locked savings for your children's education.</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300 group-hover:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Bank Settings -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Bank Settings</p>
        <div v-if="profile.bank_details?.has_verified" class="space-y-2">
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Bank</p>
              <p class="font-bold text-slate-800">{{ profile.bank_details.bank_name || profile.bank_details.bank_code }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Account Number</p>
              <p class="font-bold text-slate-800">{{ profile.bank_details.account_number }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase">Account Name (Verified)</p>
              <p class="font-bold text-slate-800">{{ profile.bank_details.account_name }}</p>
            </div>
          </div>
          <p class="text-[10px] text-slate-500 mt-2">Your bank details are verified. For security, changes may require OTP verification in a future update.</p>
        </div>
        <div v-else class="space-y-3">
          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">Bank</label>
              <!-- Searchable bank picker -->
              <div class="mt-1 relative">
                <div class="flex items-center gap-2 border rounded-xl bg-slate-50 px-3 py-2.5 focus-within:ring-2 focus-within:ring-emerald-200">
                  <span class="text-slate-400">🏦</span>
                  <input
                    v-model="bankSearch"
                    @focus="openBankDropdown"
                    @input="openBankDropdown"
                    @keydown.down.prevent="moveBankHighlight(1)"
                    @keydown.up.prevent="moveBankHighlight(-1)"
                    @keydown.enter.prevent="confirmBankHighlight"
                    @keydown.esc.prevent="closeBankDropdown"
                    type="text"
                    class="flex-1 bg-transparent outline-none text-sm placeholder-slate-400"
                    :placeholder="selectedBank ? selectedBank.name + ' (' + selectedBank.code + ')' : 'Search bank by name or code'"
                  />
                  <button v-if="selectedBank" @click="clearSelectedBank" class="text-[11px] text-emerald-700 font-bold">Change</button>
                </div>
                <!-- Dropdown -->
                <div v-if="showBankDropdown" class="absolute z-20 mt-1 w-full max-h-64 overflow-auto bg-white border border-slate-200 rounded-xl shadow-lg">
                  <template v-if="filteredBanks.length">
                    <button
                      v-for="(b, i) in filteredBanks"
                      :key="b.code"
                      @click="selectBank(b)"
                      class="w-full text-left px-3 py-2 text-sm flex items-center justify-between hover:bg-emerald-50"
                      :class="i===highlightedIndex ? 'bg-emerald-50' : ''"
                    >
                      <span class="truncate">{{ b.name }}</span>
                      <span class="text-[11px] text-slate-500 ml-2">{{ b.code }}</span>
                    </button>
                  </template>
                  <div v-else class="px-3 py-2 text-sm text-slate-500">No banks found</div>
                </div>
                <p v-if="!selectedBank && bankForm.bank_code" class="text-[11px] text-amber-700 mt-1">Unknown bank code selected. Please reselect.</p>
              </div>
            </div>
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">Account Number</label>
              <input v-model="bankForm.account_number" type="tel" inputmode="numeric" maxlength="10" placeholder="10-digit account number" class="mt-1 w-full border rounded-xl p-3 bg-slate-50 text-sm" />
              <p v-if="bankErrors.account_number" class="text-red-600 text-xs mt-1">{{ bankErrors.account_number }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="resolveBank" :disabled="bankBusy || !bankForm.bank_code || bankDigits.length!==10" class="px-4 py-2 rounded-xl text-white font-bold" :class="bankBusy ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">{{ bankBusy ? 'Resolving…' : 'Resolve Account Name' }}</button>
            <span v-if="bankMessage" :class="bankError ? 'text-rose-700' : 'text-emerald-700'" class="text-[12px]">{{ bankMessage }}</span>
          </div>
          <div v-if="resolvedName" class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800">
            Resolved Name: <span class="font-bold">{{ resolvedName }}</span>
          </div>
          <div v-if="resolvedName" class="flex items-center gap-2">
            <button @click="saveBank" :disabled="bankBusy" class="px-4 py-2 rounded-xl text-white font-bold" :class="bankBusy ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">{{ bankBusy ? 'Saving…' : 'Save Bank Details' }}</button>
            <button @click="clearResolved" :disabled="bankBusy" class="px-4 py-2 rounded-xl text-emerald-700 font-bold bg-emerald-50 hover:bg-emerald-100">Change</button>
          </div>
          <p class="text-[10px] text-slate-500">We verify your bank account via Paystack/Flutterwave to prevent errors. You’ll see the registered account name before saving.</p>
        </div>
      </div>

      <!-- Notification Preferences -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Notification Preferences</p>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-bold text-slate-800">Email Notifications</p>
              <p class="text-xs text-slate-500">Receive transaction alerts via email</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifPrefs.notify_email" class="sr-only peer" @change="saveNotifPrefs">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-700"></div>
            </label>
          </div>
          <div class="flex items-center justify-between border-t pt-4">
            <div>
              <p class="text-sm font-bold text-slate-800">SMS Notifications</p>
              <p class="text-xs text-slate-500">Receive alerts via SMS (charges apply)</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifPrefs.notify_sms" class="sr-only peer" @change="saveNotifPrefs">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-700"></div>
            </label>
          </div>
          <div class="flex items-center justify-between border-t pt-4">
            <div>
              <p class="text-sm font-bold text-slate-800">Push Notifications</p>
              <p class="text-xs text-slate-500">Real-time alerts on your device</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notifPrefs.notify_push" class="sr-only peer" @change="saveNotifPrefs">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-700"></div>
            </label>
          </div>
        </div>
        <p v-if="notifBusy" class="text-[10px] text-emerald-700 mt-3 font-bold">Saving preferences...</p>
      </div>

      <!-- Administrative Charges Preference -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Administrative Charges</p>
        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100 transition-colors hover:border-emerald-200">
          <div class="pr-4 flex-1">
            <p class="text-sm font-bold text-slate-700">Auto-Deduct Monthly Charge</p>
            <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">Automatically deduct the monthly ₦300 administrative charge from your wallet balance.</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="profile.admin_charge_auto_deduct" class="sr-only peer" @change="toggleAdminCharge" :disabled="updatingAdminCharge">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-700"></div>
          </label>
        </div>
        <p v-if="updatingAdminCharge" class="text-[10px] text-emerald-700 mt-3 font-bold uppercase tracking-wider">Updating preference...</p>
      </div>

      <!-- Change Email -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Update Email</p>
        <div class="space-y-3">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">New Email</label>
            <input v-model="emailForm.email" type="email" class="mt-1 w-full border rounded-xl p-3" placeholder="name@example.com" />
            <p v-if="emailErrors.email" class="text-red-600 text-xs mt-1">{{ emailErrors.email }}</p>
          </div>
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Current Password</label>
            <input v-model="emailForm.password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="••••••••" />
            <p v-if="emailErrors.password" class="text-red-600 text-xs mt-1">{{ emailErrors.password }}</p>
          </div>
          <button @click="updateEmail" :disabled="emailSaving" class="w-full h-12 rounded-xl font-bold text-white" :class="emailSaving ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">
            {{ emailSaving ? 'Updating...' : 'Update Email' }}
          </button>
        </div>
      </div>

      <!-- Change Password -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-3">Update Password</p>
        <div class="space-y-3">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Current Password</label>
            <input v-model="passForm.current_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="Current password" />
            <p v-if="passErrors.current_password" class="text-red-600 text-xs mt-1">{{ passErrors.current_password }}</p>
          </div>
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">New Password</label>
            <input v-model="passForm.new_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="New password" />
            <p v-if="passErrors.new_password" class="text-red-600 text-xs mt-1">{{ passErrors.new_password }}</p>
          </div>
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Confirm New Password</label>
            <input v-model="passForm.confirm_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="Confirm new password" />
            <p v-if="passErrors.confirm_password" class="text-red-600 text-xs mt-1">{{ passErrors.confirm_password }}</p>
          </div>
          <button @click="updatePassword" :disabled="passSaving" class="w-full h-12 rounded-xl font-bold text-white" :class="passSaving ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">
            {{ passSaving ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </div>

      <!-- Transaction PIN -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Transaction PIN</p>
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <span :class="profile.pin_set ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
              {{ profile.pin_set ? 'Set' : 'Not Set' }}
            </span>
            <span v-if="profile.pin_set_at" class="text-[10px] text-slate-500">since {{ profile.pin_set_at }}</span>
          </div>
          <span class="text-[11px] text-slate-500">4-digit PIN used for payments</span>
        </div>
        <div class="space-y-3">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase">Current Password</label>
            <input v-model="pinForm.current_password" type="password" class="mt-1 w-full border rounded-xl p-3" placeholder="Account password" />
            <p v-if="pinErrors.current_password" class="text-red-600 text-xs mt-1">{{ pinErrors.current_password }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">New PIN (4 digits)</label>
              <input v-model="pinForm.new_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3" placeholder="••••" />
              <p v-if="pinErrors.new_pin" class="text-red-600 text-xs mt-1">{{ pinErrors.new_pin }}</p>
            </div>
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase">Confirm PIN</label>
              <input v-model="pinForm.confirm_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3" placeholder="••••" />
              <p v-if="pinErrors.confirm_pin" class="text-red-600 text-xs mt-1">{{ pinErrors.confirm_pin }}</p>
            </div>
          </div>
          <button @click="setPin" :disabled="pinSaving" class="w-full h-12 rounded-xl font-bold text-white" :class="pinSaving ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">
            {{ pinSaving ? 'Saving…' : 'Save PIN' }}
          </button>

          <!-- Forgot PIN flow -->
          <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
            <div class="flex items-center justify-between">
              <p class="text-[11px] text-amber-800 font-bold uppercase tracking-widest">Forgot PIN?</p>
              <button @click="requestPinReset" :disabled="resetBusy" class="text-[11px] font-bold text-emerald-700 underline">
                {{ resetBusy ? 'Sending…' : 'Send Reset Code' }}
              </button>
            </div>
            <p v-if="resetSentTo" class="text-[11px] text-amber-700 mt-1">Code sent to: {{ resetSentTo }} (expires in ~10 minutes)</p>
            <div class="grid grid-cols-3 gap-2 mt-3">
              <div>
                <label class="text-[10px] text-slate-500 font-bold uppercase">6‑digit Code</label>
                <input v-model="resetForm.code" type="text" inputmode="numeric" pattern="\\d*" maxlength="6" class="mt-1 w-full border rounded-xl p-3 text-center" placeholder="123456" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-bold uppercase">New PIN</label>
                <input v-model="resetForm.new_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3 text-center" placeholder="••••" />
              </div>
              <div>
                <label class="text-[10px] text-slate-500 font-bold uppercase">Confirm</label>
                <input v-model="resetForm.confirm_pin" type="password" inputmode="numeric" pattern="\\d*" maxlength="4" class="mt-1 w-full border rounded-xl p-3 text-center" placeholder="••••" />
              </div>
            </div>
            <div class="mt-2 flex items-center gap-2">
              <button @click="confirmPinReset" :disabled="resetBusy" class="px-4 py-2 rounded-xl text-white font-bold" :class="resetBusy ? 'bg-slate-400' : 'bg-emerald-700 hover:bg-emerald-800'">{{ resetBusy ? 'Resetting…' : 'Reset PIN' }}</button>
              <span v-if="resetMessage" class="text-[12px]" :class="resetError ? 'text-rose-700' : 'text-emerald-700'">{{ resetMessage }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <nav class="bottom-nav" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
      <button class="bottom-nav-btn" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/wallet')">
        <span class="text-xl">👛</span>
        <span class="text-[10px] font-bold">Wallet</span>
      </button>
      <button class="bottom-nav-btn" @click="$router.push('/passbook')">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="bottom-nav-btn-active" @click="$router.push('/profile')">
        <span class="text-xl">👤</span>
        <span class="text-[10px] font-bold">Profile</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from '../http'
import getImageUrl from '../utils/image'

const router = useRouter()

const profile = ref({})
const bvnAssigned = ref(false)
const uploading = ref(false)
const fileInput = ref(null)

// Bank details (verification & save)
const bankForm = ref({ bank_code: '', account_number: '', gateway: 'paystack' })
const bankErrors = ref({})
const bankBusy = ref(false)
const bankMessage = ref('')
const bankError = ref(false)
const resolvedName = ref('')
// Dynamic list of Nigerian banks (fetched), with fallback
const bankOptions = ref([])
const fallbackBanks = [
  { code: '044', name: 'Access Bank' },
  { code: '023', name: 'CitiBank Nigeria' },
  { code: '050', name: 'Ecobank Nigeria' },
  { code: '070', name: 'Fidelity Bank' },
  { code: '011', name: 'First Bank of Nigeria' },
  { code: '214', name: 'First City Monument Bank (FCMB)' },
  { code: '058', name: 'Guaranty Trust Bank (GTBank)' },
  { code: '030', name: 'Heritage Bank' },
  { code: '082', name: 'Keystone Bank' },
  { code: '076', name: 'Polaris Bank' },
  { code: '221', name: 'Stanbic IBTC Bank' },
  { code: '232', name: 'Sterling Bank' },
  { code: '100', name: 'SunTrust Bank' },
  { code: '032', name: 'Union Bank' },
  { code: '033', name: 'United Bank for Africa (UBA)' },
  { code: '215', name: 'Unity Bank' },
  { code: '035', name: 'Wema Bank' },
  { code: '057', name: 'Zenith Bank' },
]
// UI state for dynamic, searchable bank picker
const bankSearch = ref('')
const showBankDropdown = ref(false)
const highlightedIndex = ref(0)
const selectedBank = computed(() => bankOptions.value.find(b => b.code === bankForm.value.bank_code) || null)
const filteredBanks = computed(() => {
  const q = bankSearch.value.trim().toLowerCase()
  const list = bankOptions.value && bankOptions.value.length ? bankOptions.value : fallbackBanks
  if (!q) return list
  return list.filter(b => b.name.toLowerCase().includes(q) || String(b.code).includes(q))
})
const openBankDropdown = () => { showBankDropdown.value = true; highlightedIndex.value = 0 }
const closeBankDropdown = () => { showBankDropdown.value = false }
const selectBank = (b) => {
  bankForm.value.bank_code = b.code
  bankSearch.value = ''
  showBankDropdown.value = false
}
const moveBankHighlight = (dir) => {
  if (!showBankDropdown.value) { showBankDropdown.value = true }
  const n = filteredBanks.value.length
  if (!n) { highlightedIndex.value = 0; return }
  highlightedIndex.value = (highlightedIndex.value + dir + n) % n
}
const confirmBankHighlight = () => {
  const b = filteredBanks.value[highlightedIndex.value]
  if (b) selectBank(b)
}
const clearSelectedBank = () => {
  bankForm.value.bank_code = ''
  bankSearch.value = ''
  showBankDropdown.value = true
}
const bankDigits = computed(() => String(bankForm.value.account_number || '').replace(/\D/g, ''))

// Update Email form state
const emailForm = ref({ email: '', password: '' })
const emailSaving = ref(false)
const emailErrors = ref({})

// Update Password form state
const passForm = ref({ current_password: '', new_password: '', confirm_password: '' })
const passSaving = ref(false)
const passErrors = ref({})

// Transaction PIN form state
const pinForm = ref({ current_password: '', new_pin: '', confirm_pin: '' })
const pinSaving = ref(false)
const pinErrors = ref({})

// PIN reset (forgot) state
const resetBusy = ref(false)
const resetSentTo = ref('')
const resetMessage = ref('')
const resetError = ref(false)
const resetForm = ref({ code: '', new_pin: '', confirm_pin: '' })

// Notification preferences state
const notifPrefs = ref({ notify_email: true, notify_sms: true, notify_push: true })
const notifBusy = ref(false)

// Administrative Charges state
const updatingAdminCharge = ref(false)
const toggleAdminCharge = async () => {
  updatingAdminCharge.value = true
  const newValue = !!profile.value.admin_charge_auto_deduct
  try {
    const resp = await axios.post('/api/profile/admin-charge-preference', {
      admin_charge_auto_deduct: newValue
    })
    profile.value.admin_charge_auto_deduct = !!resp.data.admin_charge_auto_deduct
    // Success - no alert needed if it's a toggle, or optional
  } catch (err) {
    console.error(err)
    // Revert on error
    profile.value.admin_charge_auto_deduct = !newValue
    alert(err?.response?.data?.message || 'Failed to update preference.')
  } finally {
    updatingAdminCharge.value = false
  }
}

const copy = async (text) => {
  try {
    await navigator.clipboard.writeText(String(text || ''))
    alert('Copied to clipboard')
  } catch (_) {
    // noop
  }
}

const goToWallet = () => router.push('/wallet')


const downloadingForm = ref(false)
const downloadingImam = ref(false)
const downloadingDoc = computed(() => downloadingForm.value || downloadingImam.value)

const downloadMembershipForm = async () => {
  downloadingForm.value = true
  try {
    const resp = await axios.get('/api/download-membership-form', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([resp.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Membership_Form_${profile.value.membership_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    alert('Failed to download membership form.')
  } finally {
    downloadingForm.value = false
  }
}

const downloadImamAttestation = async () => {
  downloadingImam.value = true
  try {
    const resp = await axios.get('/api/download-imam-attestation', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([resp.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Imam_Attestation_${profile.value.membership_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    alert('Failed to download Imam attestation.')
  } finally {
    downloadingImam.value = false
  }
}

const chooseFile = () => fileInput.value && fileInput.value.click()

const onFileChange = async (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const form = new FormData()
  form.append('passport', file)
  uploading.value = true
  try {
    const { data } = await axios.post('/api/profile/passport', form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    profile.value.passport_url = data.passport_url
  } catch (err) {
    alert(err?.response?.data?.message || 'Failed to upload. Please try a smaller image or a different format.')
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const resolveBank = async () => {
  bankErrors.value = {}
  bankMessage.value = ''
  bankError.value = false
  resolvedName.value = ''
  // Validate inputs
  if (!bankForm.value.bank_code) {
    bankMessage.value = 'Please select a bank.'
    bankError.value = true
    return
  }
  if (bankDigits.value.length !== 10) {
    bankErrors.value.account_number = 'Enter a valid 10-digit account number.'
    return
  }
  bankBusy.value = true
  try {
    const bankName = (bankOptions.value.find(b => b.code === bankForm.value.bank_code)?.name) || null
    const { data } = await axios.post('/api/profile/bank-details', {
      bank_code: bankForm.value.bank_code,
      bank_name: bankName,
      account_number: bankDigits.value,
      gateway: bankForm.value.gateway || 'paystack',
      confirm: false,
    })
    resolvedName.value = data?.resolved_name || ''
    bankMessage.value = resolvedName.value ? 'Is this your account name?' : (data?.message || 'Resolved.')
    bankError.value = false
  } catch (err) {
    bankError.value = true
    bankMessage.value = err?.response?.data?.message || 'Failed to resolve bank account.'
  } finally {
    bankBusy.value = false
  }
}

const saveBank = async () => {
  if (!resolvedName.value) {
    bankMessage.value = 'Resolve your bank account first.'
    bankError.value = true
    return
  }
  bankBusy.value = true
  try {
    const bankName = (bankOptions.value.find(b => b.code === bankForm.value.bank_code)?.name) || null
    const { data } = await axios.post('/api/profile/bank-details', {
      bank_code: bankForm.value.bank_code,
      bank_name: bankName,
      account_number: bankDigits.value,
      gateway: bankForm.value.gateway || 'paystack',
      confirm: true,
    })
    // Update profile object with verified details
    profile.value.bank_details = data?.bank_details || {
      bank_code: bankForm.value.bank_code,
      bank_name: bankName,
      account_number: bankDigits.value,
      account_name: resolvedName.value,
      has_verified: true,
    }
    bankMessage.value = data?.message || 'Bank details saved successfully.'
    bankError.value = false
  } catch (err) {
    bankError.value = true
    bankMessage.value = err?.response?.data?.message || 'Failed to save bank details.'
  } finally {
    bankBusy.value = false
  }
}

const clearResolved = () => {
  resolvedName.value = ''
  bankMessage.value = ''
  bankError.value = false
}

const updateEmail = async () => {
  emailErrors.value = {}
  // basic client-side validation
  if (!emailForm.value.email) {
    emailErrors.value.email = 'Email is required.'
    return
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailForm.value.email)) {
    emailErrors.value.email = 'Please enter a valid email address.'
    return
  }
  if (!emailForm.value.password) {
    emailErrors.value.password = 'Current password is required.'
    return
  }
  emailSaving.value = true
  try {
    const { data } = await axios.post('/api/profile/email', {
      email: emailForm.value.email,
      password: emailForm.value.password,
    })
    // Update local profile email
    profile.value.email = data?.email || emailForm.value.email
    alert('Email updated successfully.')
    // Clear password field
    emailForm.value.password = ''
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      // Laravel validation errors
      emailErrors.value = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
    } else if (e?.message) {
      alert(e.message)
    } else {
      alert('Failed to update email. Please try again.')
    }
  } finally {
    emailSaving.value = false
  }
}

const updatePassword = async () => {
  passErrors.value = {}
  // basic validation
  if (!passForm.value.current_password) {
    passErrors.value.current_password = 'Current password is required.'
    return
  }
  if (!passForm.value.new_password) {
    passErrors.value.new_password = 'New password is required.'
    return
  }
  if (passForm.value.new_password.length < 6) {
    passErrors.value.new_password = 'New password must be at least 6 characters.'
    return
  }
  if (passForm.value.confirm_password !== passForm.value.new_password) {
    passErrors.value.confirm_password = 'Password confirmation does not match.'
    return
  }
  passSaving.value = true
  try {
    await axios.post('/api/profile/password', {
      current_password: passForm.value.current_password,
      new_password: passForm.value.new_password,
      confirm_password: passForm.value.confirm_password,
    })
    alert('Password updated successfully.')
    passForm.value = { current_password: '', new_password: '', confirm_password: '' }
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      passErrors.value = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
    } else if (e?.message) {
      alert(e.message)
    } else {
      alert('Failed to update password. Please try again.')
    }
  } finally {
    passSaving.value = false
  }
}

const setPin = async () => {
  pinErrors.value = {}
  // client-side validation
  if (!pinForm.value.current_password) {
    pinErrors.value.current_password = 'Current password is required.'
    return
  }
  if (!pinForm.value.new_pin) {
    pinErrors.value.new_pin = 'PIN is required.'
    return
  }
  if (!/^\d{4}$/.test(String(pinForm.value.new_pin))) {
    pinErrors.value.new_pin = 'PIN must be exactly 4 digits.'
    return
  }
  if (String(pinForm.value.confirm_pin) !== String(pinForm.value.new_pin)) {
    pinErrors.value.confirm_pin = 'PIN confirmation does not match.'
    return
  }
  pinSaving.value = true
  try {
    await axios.post('/api/security/pin/set', {
      current_password: pinForm.value.current_password,
      new_pin: String(pinForm.value.new_pin),
      confirm_pin: String(pinForm.value.confirm_pin),
    })
    alert('Transaction PIN saved successfully.')
    pinForm.value = { current_password: '', new_pin: '', confirm_pin: '' }
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      pinErrors.value = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]))
    } else if (e?.message) {
      alert(e.message)
    } else {
      alert('Failed to save PIN. Please try again.')
    }
  } finally {
    pinSaving.value = false
  }
}

const requestPinReset = async () => {
  resetMessage.value = ''
  resetError.value = false
  resetSentTo.value = ''
  resetBusy.value = true
  try {
    const { data } = await axios.post('/api/security/pin/reset/request')
    resetSentTo.value = data?.sent_to || ''
    resetMessage.value = data?.message || 'Code sent if contact exists.'
  } catch (err) {
    resetError.value = true
    resetMessage.value = err?.response?.data?.message || 'Failed to send reset code.'
  } finally {
    resetBusy.value = false
  }
}

const confirmPinReset = async () => {
  resetMessage.value = ''
  resetError.value = false
  // Basic validation
  if (!/^\d{6}$/.test(String(resetForm.value.code || ''))) {
    resetError.value = true
    resetMessage.value = 'Enter the 6-digit code sent to you.'
    return
  }
  if (!/^\d{4}$/.test(String(resetForm.value.new_pin || ''))) {
    resetError.value = true
    resetMessage.value = 'New PIN must be exactly 4 digits.'
    return
  }
  if (String(resetForm.value.confirm_pin) !== String(resetForm.value.new_pin)) {
    resetError.value = true
    resetMessage.value = 'PIN confirmation does not match.'
    return
  }
  resetBusy.value = true
  try {
    const { data } = await axios.post('/api/security/pin/reset/confirm', {
      code: String(resetForm.value.code),
      new_pin: String(resetForm.value.new_pin),
      confirm_pin: String(resetForm.value.confirm_pin),
    })
    resetMessage.value = data?.message || 'PIN reset successfully.'
    resetError.value = false
    // Clear inputs
    resetForm.value = { code: '', new_pin: '', confirm_pin: '' }
  } catch (err) {
    const status = err?.response?.status
    const msg = err?.response?.data?.message || 'Failed to reset PIN.'
    resetMessage.value = msg
    resetError.value = true
    if (status === 429) {
      alert('Too many invalid attempts. Please request a new code.')
    }
  } finally {
    resetBusy.value = false
  }
}

const saveNotifPrefs = async () => {
  notifBusy.value = true
  try {
    await axios.post('/api/profile/notifications', {
      notify_email: !!notifPrefs.value.notify_email,
      notify_sms: !!notifPrefs.value.notify_sms,
      notify_push: !!notifPrefs.value.notify_push,
    })
  } catch (err) {
    alert(err?.response?.data?.message || 'Failed to save notification preferences.')
  } finally {
    notifBusy.value = false
  }
}

const bandLabel = (band) => {
  switch (band) {
    case 'excellent': return 'Excellent Trust'
    case 'very_good': return 'Very Good Trust'
    case 'good': return 'Good Trust'
    case 'fair': return 'Fair Trust'
    case 'low': return 'Low Trust'
    case 'very_low': return 'Very Low Trust'
    default: return 'Points'
  }
}

onMounted(async () => {
  // Load profile
  try {
    const { data } = await axios.get('/api/profile')
    profile.value = data
    emailForm.value.email = data?.email || ''
    
    // Sync notification preferences
    notifPrefs.value.notify_email = !!data?.notify_email
    notifPrefs.value.notify_sms = !!data?.notify_sms
    notifPrefs.value.notify_push = !!data?.notify_push

    const assigned = Boolean(data?.bvn_assigned ?? JSON.parse(localStorage.getItem('bvn_assigned') || 'false'))
    bvnAssigned.value = assigned
    try { localStorage.setItem('bvn_assigned', JSON.stringify(assigned)) } catch (_) {}
  } catch (_) {
    // Fallback mock values
    profile.value = {
      full_name: 'Member',
      email: 'member@example.com',
      membership_id: 'M-000000',
      virtual_account: ''
    }
    emailForm.value.email = profile.value.email
    bvnAssigned.value = JSON.parse(localStorage.getItem('bvn_assigned') || 'false')
  }

  // Load banks list dynamically
  try {
    const { data } = await axios.get('/api/banks')
    if (Array.isArray(data?.banks) && data.banks.length) {
      bankOptions.value = data.banks
    } else {
      bankOptions.value = fallbackBanks
    }
  } catch (_) {
    bankOptions.value = fallbackBanks
  }
})
</script>
