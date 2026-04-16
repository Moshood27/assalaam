<template>
  <div class="min-h-screen auth-bg relative flex items-center justify-center p-4 overflow-hidden">
    <!-- Decorative fintech gradient blobs -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <div class="absolute -top-24 -right-20 w-72 h-72 bg-gradient-to-br from-emerald-400/25 to-sky-400/25 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-28 -left-16 w-80 h-80 bg-gradient-to-tr from-emerald-300/20 to-indigo-300/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-2xl relative">
      <!-- Background glow effect -->
      <div aria-hidden="true" class="pointer-events-none absolute -inset-1 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 rounded-[2.5rem] blur-2xl opacity-50"></div>

      <div class="card card-elevated relative overflow-hidden p-8 sm:p-10 bg-white/90 backdrop-blur-2xl border border-white/80 shadow-2xl rounded-[2.5rem]">
        <!-- Top accent gradient line -->
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-400 via-teal-500 to-emerald-400 opacity-80"></div>

        <div class="flex flex-col items-center text-center mb-8">
          <div class="mb-4 transform hover:scale-105 transition-transform duration-300">
            <img :src="brand.logo" :alt="brand.name" class="h-16 sm:h-20 w-auto drop-shadow-sm" />
          </div>
          <p class="text-[10px] font-bold tracking-[0.2em] text-emerald-800 uppercase opacity-80 mb-1">{{ brand.name }}</p>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Join the Cooperative</h1>
          <p class="text-slate-500 text-sm mt-2 font-medium">Complete registration to access member benefits</p>
        </div>

        <!-- Step indicator -->
        <div class="flex items-center justify-center gap-1 sm:gap-4 mb-10 overflow-x-auto no-scrollbar py-1">
          <template v-for="s in 4" :key="s">
            <div class="flex items-center gap-2">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300',
                step === s ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30 scale-110' :
                step > s ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400']">
                <span v-if="step > s">✓</span>
                <span v-else>{{ s }}</span>
              </div>
              <span v-if="step === s" class="text-[10px] font-bold text-emerald-800 uppercase tracking-widest hidden sm:block">
                {{ ['Details', 'Documents', 'Verify', 'Complete'][s-1] }}
              </span>
            </div>
            <div v-if="s < 4" class="w-4 sm:w-8 h-px" :class="step > s ? 'bg-emerald-200' : 'bg-slate-100'"></div>
          </template>
        </div>

        <!-- Step 1: Details -->
        <div v-if="step === 1" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div class="sm:col-span-2">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">1. Basic Personal Information</h3>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Your Branch</label>
            <SearchableSelect v-model="form.branch_id" :items="branches" placeholder="Select your branch" searchPlaceholder="Search branches…" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Full Name</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </span>
              <input v-model="form.name" type="text" placeholder="First Name" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Surname (Last Name)</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <input v-model="form.surname" type="text" placeholder="Surname" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Other Names</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <input v-model="form.other_names" type="text" placeholder="Middle Names" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Gender</label>
            <select v-model="form.gender" class="input h-14 font-semibold bg-slate-50/50 border-slate-200/60">
              <option value="">Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Native (State/Town)</label>
            <input v-model="form.native_place" type="text" placeholder="State or Town of Origin" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Date of Birth</label>
            <input v-model="form.dob" type="date" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Marital Status</label>
            <select v-model="form.marital_status" class="input h-14 font-semibold bg-slate-50/50 border-slate-200/60">
              <option value="">Select Status</option>
              <option value="single">Single</option>
              <option value="married">Married</option>
              <option value="divorced">Divorced</option>
              <option value="widow">Widow</option>
            </select>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Occupation</label>
            <input v-model="form.occupation" type="text" placeholder="Your Job" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">2. Contact Information</h3>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Email Address</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </span>
              <input v-model="form.email" type="email" placeholder="you@example.com" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Primary Phone</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </span>
              <input v-model="form.phone" type="tel" placeholder="0803 123 4567" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Secondary Phone</label>
            <input v-model="form.secondary_phone" type="tel" placeholder="Optional" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Residential Address</label>
            <textarea v-model="form.residential_address" placeholder="Current living address" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Permanent Home Address</label>
            <textarea v-model="form.permanent_address" placeholder="Hometown address" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
            <input v-model="form.address" type="hidden" />
          </div>

          <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">3. Business & Professional Information</h3>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Nature of Business</label>
            <input v-model="form.nature_of_business" type="text" placeholder="Type of trade or profession" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Business Address</label>
            <input v-model="form.business_address" type="text" placeholder="Office/Shop address" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="sm:col-span-2 flex items-center gap-3 bg-slate-50/50 p-4 rounded-2xl border border-slate-200/60">
            <input v-model="form.has_other_cooperatives" type="checkbox" id="has_coop" class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
            <label for="has_coop" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Other Cooperative Affiliations?</label>
          </div>

          <div v-if="form.has_other_cooperatives" class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Other Cooperative Details</label>
            <textarea v-model="form.other_cooperative_details" placeholder="Specify names and details" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
          </div>

          <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">4. Next of Kin</h3>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Next of Kin Name</label>
            <input v-model="form.nok_name" type="text" placeholder="Full Name" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Relationship</label>
            <input v-model="form.nok_relationship" type="text" placeholder="e.g. Spouse, Brother" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Next of Kin Phone</label>
            <input v-model="form.nok_phone" type="tel" placeholder="080..." class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Next of Kin Address</label>
            <textarea v-model="form.nok_address" placeholder="Residential address of Next of Kin" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
          </div>

          <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">5. Guarantor Details</h3>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Guarantor Name</label>
            <input v-model="form.guarantor_name" type="text" placeholder="Full Name" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Guarantor Phone</label>
            <input v-model="form.guarantor_phone" type="tel" placeholder="080..." class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Guarantor Occupation</label>
            <input v-model="form.guarantor_occupation" type="text" placeholder="Profession" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Guarantor Address</label>
            <textarea v-model="form.guarantor_address" placeholder="Guarantor's home/office address" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
          </div>

          <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">6. Religious Information</h3>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Religious Society Name</label>
            <input v-model="form.religious_society_name" type="text" placeholder="Group within CICN" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Imam/Amir Name</label>
            <input v-model="form.imam_name" type="text" placeholder="Name" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Imam/Amir Phone</label>
            <input v-model="form.imam_phone" type="tel" placeholder="080..." class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Duration of Jamma Membership</label>
            <input v-model="form.duration_of_jamma_membership" type="text" placeholder="e.g. 5 years" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Mosque Address</label>
            <textarea v-model="form.mosque_address" placeholder="Address of the mosque group" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
          </div>

          <div v-if="form.gender === 'female'" class="sm:col-span-2 mt-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">7. Wali/Spouse Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="relative group">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Father/Spouse Name</label>
                <input v-model="form.spouse_father_name" type="text" placeholder="Full Name" class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
              </div>
              <div class="relative group">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Father/Spouse Phone</label>
                <input v-model="form.spouse_father_phone" type="tel" placeholder="080..." class="input px-6 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
              </div>
              <div class="sm:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Residential Address</label>
                <textarea v-model="form.spouse_father_address" placeholder="Residential address" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
              </div>
              <div class="sm:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Business Address</label>
                <textarea v-model="form.spouse_father_business_address" placeholder="Business address" class="input p-4 h-24 font-semibold bg-slate-50/50 border-slate-200/60"></textarea>
              </div>
            </div>
          </div>

          <div class="sm:col-span-2 mt-4">
            <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-widest mb-4 border-b border-emerald-100 pb-2">8. Security</h3>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Create Password</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="relative group">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Confirm Password</label>
            <div class="relative transition-all duration-200 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
              <input v-model="form.confirm_password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="input pl-12 h-14 font-semibold bg-slate-50/50 border-slate-200/60" />
            </div>
          </div>

          <div class="sm:col-span-2 flex flex-col sm:flex-row gap-4 pt-4">
            <button @click="handleStart" :disabled="loadingStart" class="flex-1 h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] disabled:opacity-50">
              <span v-if="loadingStart" class="inline-block animate-spin border-3 border-white/30 border-t-white rounded-full w-6 h-6 mr-2 align-middle"></span>
              <span>{{ loadingStart ? 'Submitting…' : 'Next: Upload Documents' }}</span>
            </button>
            <button @click="goLogin" type="button" class="h-14 px-8 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 font-bold transition-all">Back to Login</button>
          </div>
          <p v-if="errorStart" class="sm:col-span-2 text-center p-3 bg-rose-50 rounded-xl text-rose-600 text-sm font-medium animate-pulse">{{ errorStart }}</p>
        </div>

        <!-- Step 2: Documents -->
        <div v-if="step === 2" class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Profile Passport Photo</label>
              <div class="relative group">
                <input @change="e => files.passport = e.target.files?.[0] || null" type="file" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all border border-slate-200/60 rounded-2xl bg-slate-50/30 p-2" />
                <p class="text-[10px] text-slate-400 mt-1 font-medium ml-1">JPEG/PNG up to 5MB.</p>
              </div>
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Valid ID Card</label>
              <div class="relative group">
                <input @change="e => files.id_card = e.target.files?.[0] || null" type="file" accept="image/*,application/pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all border border-slate-200/60 rounded-2xl bg-slate-50/30 p-2" />
                <p class="text-[10px] text-slate-400 mt-1 font-medium ml-1">NIN/Passport/DL (Max 7MB)</p>
              </div>
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Proof of Address</label>
              <div class="relative group">
                <input @change="e => files.proof_of_address = e.target.files?.[0] || null" type="file" accept="image/*,application/pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all border border-slate-200/60 rounded-2xl bg-slate-50/30 p-2" />
                <p class="text-[10px] text-slate-400 mt-1 font-medium ml-1">Utility bill or Letter (Max 7MB)</p>
              </div>
            </div>
            <div class="space-y-2">
              <SignaturePad 
                v-model="signatures.guarantor"
                label="Guarantor Signature"
                hint="Sign inside the box above"
              />
            </div>
            <div class="space-y-2">
              <SignaturePad 
                v-model="signatures.imam"
                label="Imam/Amir Signature"
                hint="Attestation by your Mosque leader"
              />
            </div>
            <div v-if="form.gender === 'female'" class="sm:col-span-2 space-y-2 animate-in fade-in slide-in-from-top-4 duration-500">
              <SignaturePad 
                v-model="signatures.spouse_father"
                label="Father/Spouse Consent Signature"
                hint="Required for female members"
              />
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <button @click="handleUpload" :disabled="loadingUpload" class="flex-1 h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] disabled:opacity-50">
              <span v-if="loadingUpload" class="inline-block animate-spin border-3 border-white/30 border-t-white rounded-full w-6 h-6 mr-2 align-middle"></span>
              <span>{{ loadingUpload ? 'Uploading…' : 'Upload & Continue' }}</span>
            </button>
            <button @click="() => step = 1" type="button" class="h-14 px-8 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 font-bold transition-all">Back</button>
          </div>
          <p v-if="errorUpload" class="text-center p-3 bg-rose-50 rounded-xl text-rose-600 text-sm font-medium">{{ errorUpload }}</p>
          <div v-if="uploaded.passport_path && uploaded.id_card_path && uploaded.proof_of_address_path" class="text-center text-xs text-emerald-700 font-bold bg-emerald-50 p-2 rounded-lg">Required documents uploaded successfully ✓</div>
        </div>

        <!-- Step 3: Verify -->
        <div v-if="step === 3" class="space-y-6">
          <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200/60 p-5 bg-slate-50/30 space-y-3">
              <div class="flex items-center justify-between">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Email Verification</div>
                <div v-if="emailVerified" class="badge-success px-2 py-1 rounded-lg">Verified</div>
              </div>
              <p class="text-xs text-slate-500 font-medium">Code sent to <span class="text-slate-800 font-bold">{{ maskedEmail || form.email }}</span></p>
              <div class="flex items-center gap-3">
                <input v-model="emailCode" :disabled="emailVerified" type="text" inputmode="numeric" maxlength="6" class="input flex-1 h-12 text-center text-lg font-black tracking-[0.5em] bg-white border-slate-200" placeholder="000000" />
                <button @click="handleVerifyEmail" :disabled="emailVerified || loadingVerifyEmail" class="h-12 px-6 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 transition-colors disabled:opacity-50 shadow-sm">
                  <span v-if="loadingVerifyEmail" class="inline-block animate-spin border-2 border-white/30 border-t-white rounded-full w-4 h-4 mr-2"></span>
                  <span>Verify</span>
                </button>
              </div>
              <p v-if="errorVerifyEmail" class="text-rose-600 text-[10px] font-bold mt-1">{{ errorVerifyEmail }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200/60 p-5 bg-slate-50/30 space-y-3">
              <div class="flex items-center justify-between">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Phone Verification</div>
                <div v-if="phoneVerified" class="badge-success px-2 py-1 rounded-lg">Verified</div>
              </div>
              <p class="text-xs text-slate-500 font-medium">SMS sent to <span class="text-slate-800 font-bold">{{ maskedPhone || form.phone }}</span></p>
              <div class="flex items-center gap-3">
                <input v-model="smsCode" :disabled="phoneVerified" type="text" inputmode="numeric" maxlength="6" class="input flex-1 h-12 text-center text-lg font-black tracking-[0.5em] bg-white border-slate-200" placeholder="000000" />
                <button @click="handleVerifySms" :disabled="phoneVerified || loadingVerifySms" class="h-12 px-6 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 transition-colors disabled:opacity-50 shadow-sm">
                  <span v-if="loadingVerifySms" class="inline-block animate-spin border-2 border-white/30 border-t-white rounded-full w-4 h-4 mr-2"></span>
                  <span>Verify</span>
                </button>
              </div>
              <p v-if="errorVerifySms" class="text-rose-600 text-[10px] font-bold mt-1">{{ errorVerifySms }}</p>
            </div>
          </div>

          <div class="flex items-center justify-between px-1">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Expires in: <span class="text-rose-500">{{ countdown }}s</span></div>
            <button @click="handleResend" :disabled="resendCooldown > 0" class="text-[11px] font-black text-emerald-700 uppercase tracking-widest hover:underline disabled:opacity-40">Resend Codes <span v-if="resendCooldown>0">({{ resendCooldown }})</span></button>
          </div>
          <p v-if="errorSendOtps" class="text-rose-600 text-[10px] font-bold mt-1 text-center bg-rose-50 p-2 rounded-lg">{{ errorSendOtps }}</p>

          <div class="rounded-2xl border border-slate-200/60 p-5 bg-emerald-50/30 space-y-3">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Identity Verification (BVN)</div>
            <p class="text-[10px] text-slate-500 font-medium">Enter your 11‑digit Bank Verification Number.</p>
            <div class="relative">
              <input v-model="bvn" @input="onBvnInput" type="text" inputmode="numeric" maxlength="11" class="input w-full h-12 text-center text-lg font-black tracking-[0.2em] bg-white border-slate-200" placeholder="***********" />
              <p v-if="bvn && !isBvnValid" class="text-rose-600 text-[10px] font-bold mt-1 text-center">BVN must be exactly 11 digits.</p>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <button @click="handleFinalize" :disabled="!emailVerified || !phoneVerified || !isBvnValid || loadingFinalize" class="flex-1 h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] disabled:opacity-50">
              <span v-if="loadingFinalize" class="inline-block animate-spin border-3 border-white/30 border-t-white rounded-full w-6 h-6 mr-2 align-middle"></span>
              <span>Finish Registration</span>
            </button>
            <button @click="() => step = 2" type="button" class="h-14 px-8 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 font-bold transition-all">Back</button>
          </div>
          <p v-if="errorFinalize" class="text-center p-3 bg-rose-50 rounded-xl text-rose-600 text-sm font-medium">{{ errorFinalize }}</p>
        </div>

        <!-- Step 4: Complete -->
        <div v-if="step === 4" class="text-center space-y-8 py-4">
          <div class="relative">
            <div class="w-24 h-24 mx-auto rounded-[2rem] bg-gradient-to-br from-emerald-500 to-teal-600 text-white text-4xl flex items-center justify-center shadow-xl shadow-emerald-500/20 transform rotate-12">✓</div>
            <div class="absolute -top-2 -right-2 w-8 h-8 bg-emerald-100 rounded-full blur-md opacity-50"></div>
          </div>
          <div class="space-y-2">
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Registration Complete!</h2>
            <p class="text-slate-500 font-medium">Welcome to the Cooperative family.</p>
          </div>
          
          <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">Your Membership ID</p>
            <div class="text-4xl font-black text-emerald-800 tracking-wider">{{ result.membership_number }}</div>
          </div>

          <button @click="goLogin" class="w-full h-14 text-lg rounded-2xl font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all">
            Proceed to Secure Login
          </button>
        </div>
      </div>

      <div class="mt-8 text-center text-sm text-slate-500 space-y-4 font-medium relative">
        <p>Already have an account? 
          <router-link to="/login" class="text-emerald-700 font-bold hover:text-emerald-800 ml-1">Sign in here</router-link>
        </p>
        <div class="px-6 py-4 bg-emerald-50/40 rounded-2xl border border-emerald-100/40 text-slate-600 text-[13px] leading-relaxed max-w-[280px] mx-auto">
          Want to know more about our Cooperative or having trouble joining?
          <br />
          <button @click="showSupportModal = true" class="text-emerald-700 font-bold hover:text-emerald-800 inline-flex items-center justify-center gap-1 mt-2 w-full">
            <span>Contact Support</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Public Support Modal (Immediate Help) -->
    <div v-if="showSupportModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showSupportModal = false"></div>
      <div class="relative w-full max-w-md bg-slate-50 rounded-t-[2rem] sm:rounded-[2rem] shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-300">
        <div class="p-6 bg-white border-b flex items-center justify-between text-left">
          <h2 class="text-xl font-bold text-slate-800">Contact Support</h2>
          <button @click="showSupportModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 transition-colors">✕</button>
        </div>
        <div class="p-6 text-left">
          <SupportContacts />
          <div class="mt-6 text-center">
            <router-link to="/support" class="text-sm font-bold text-emerald-700 hover:underline">View full support page</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from '../http.js'
import SearchableSelect from '../components/SearchableSelect.vue'
import SignaturePad from '../components/SignaturePad.vue'
import SupportContacts from '../components/SupportContacts.vue'
import brand from '../brand'
import { useRouter } from 'vue-router'

const router = useRouter()
const showSupportModal = ref(false)

const step = ref(1)
const token = ref(localStorage.getItem('reg_token') || '')
const branches = ref([])

const showPassword = ref(false)
const loadingStart = ref(false)
const errorStart = ref('')
const loadingUpload = ref(false)
const errorUpload = ref('')
const loadingVerifyEmail = ref(false)
const errorVerifyEmail = ref('')
const loadingVerifySms = ref(false)
const errorVerifySms = ref('')
const loadingFinalize = ref(false)
const errorFinalize = ref('')
const errorSendOtps = ref('')

const form = ref({
  branch_id: '',
  name: '',
  surname: '',
  other_names: '',
  gender: '',
  native_place: '',
  dob: '',
  marital_status: '',
  occupation: '',
  email: '',
  phone: '',
  secondary_phone: '',
  address: '',
  residential_address: '',
  permanent_address: '',
  password: '',
  confirm_password: '',

  // Business & Professional Information
  nature_of_business: '',
  business_address: '',
  has_other_cooperatives: false,
  other_cooperative_details: '',

  // Next of Kin
  nok_name: '',
  nok_address: '',
  nok_phone: '',
  nok_relationship: '',

  // Guarantor Details
  guarantor_name: '',
  guarantor_address: '',
  guarantor_phone: '',
  guarantor_occupation: '',

  // Religious Information & Imam's Attestation
  religious_society_name: '',
  imam_name: '',
  mosque_address: '',
  imam_phone: '',
  duration_of_jamma_membership: '',

  // Information for Female Members
  spouse_father_name: '',
  spouse_father_address: '',
  spouse_father_business_address: '',
  spouse_father_phone: '',
})

const files = ref({
  passport: null,
  id_card: null,
  proof_of_address: null,
})
const signatures = ref({
  guarantor: '',
  spouse_father: '',
  imam: ''
})
const uploaded = ref({
  passport_path: '',
  id_card_path: '',
  proof_of_address_path: '',
  guarantor_signature_path: '',
  imam_signature_path: '',
  spouse_father_consent_signature_path: '',
})

const emailCode = ref('')
const smsCode = ref('')
const emailVerified = ref(false)
const phoneVerified = ref(false)
const maskedEmail = ref('')
const maskedPhone = ref('')
const countdown = ref(0)
let timer = null
const resendCooldown = ref(0)
let resendTimer = null

// BVN state
const bvn = ref('')
const isBvnValid = computed(() => /^[0-9]{11}$/.test(bvn.value))
function onBvnInput() {
  // Keep only digits and max 11
  bvn.value = (bvn.value || '').replace(/[^0-9]/g, '').slice(0, 11)
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/branches')
    // Always keep branches in ascending order by name as requested
    branches.value = (data || []).sort((a, b) => (a.name || '').localeCompare(b.name || ''))
  } catch (e) {}

  if (token.value) {
    try {
      const { data } = await axios.get('/api/register/status', { params: { token: token.value } })
      const app = data?.application || {}
      // If already finalized, clear token and send to login
      if (app.finalized) {
        localStorage.removeItem('reg_token')
        return router.replace({ name: 'login' })
      }
      // Restore uploaded docs state
      uploaded.value = {
        passport_path: app.passport_path || '',
        id_card_path: app.id_card_path || '',
        proof_of_address_path: app.proof_of_address_path || '',
        guarantor_signature_path: app.guarantor_signature_path || '',
        imam_signature_path: app.imam_signature_path || '',
        spouse_father_consent_signature_path: app.spouse_father_consent_signature_path || ''
      }
      // Determine next step: 3 if all docs present, else 2
      const docsComplete = !!(uploaded.value.passport_path && uploaded.value.id_card_path && uploaded.value.proof_of_address_path)
      step.value = docsComplete ? 3 : 2
      // Restore verification flags and masked contacts
      emailVerified.value = !!app.email_verified
      phoneVerified.value = !!app.phone_verified
      maskedEmail.value = app.masked_email || ''
      maskedPhone.value = app.masked_phone || ''
      // Restore countdown if available
      countdown.value = typeof app.seconds_to_expiry === 'number' ? app.seconds_to_expiry : 0
      if (countdown.value > 0) startTimers()
    } catch (e) {
      // Fallback: continue from documents step
      step.value = 2
    }
  }
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
  if (resendTimer) clearInterval(resendTimer)
})

function goLogin() { router.push({ name: 'login' }) }

async function handleStart() {
  loadingStart.value = true
  errorStart.value = ''
  
  // Sync residential_address to address if needed
  if (!form.value.address && form.value.residential_address) {
    form.value.address = form.value.residential_address
  }

  try {
    const { data } = await axios.post('/api/register/start', form.value)
    token.value = data.token
    localStorage.setItem('reg_token', token.value)

    // Immediately fetch status to resume if an existing application was reused
    try {
      const res = await axios.get('/api/register/status', { params: { token: token.value } })
      const app = res?.data?.application || {}
      uploaded.value = {
        passport_path: app.passport_path || '',
        id_card_path: app.id_card_path || '',
        proof_of_address_path: app.proof_of_address_path || '',
        guarantor_signature_path: app.guarantor_signature_path || '',
        imam_signature_path: app.imam_signature_path || '',
        spouse_father_consent_signature_path: app.spouse_father_consent_signature_path || ''
      }
      const docsComplete = !!(uploaded.value.passport_path && uploaded.value.id_card_path && uploaded.value.proof_of_address_path)
      step.value = docsComplete ? 3 : 2
      emailVerified.value = !!app.email_verified
      phoneVerified.value = !!app.phone_verified
      maskedEmail.value = app.masked_email || ''
      maskedPhone.value = app.masked_phone || ''
      countdown.value = typeof app.seconds_to_expiry === 'number' ? app.seconds_to_expiry : 0
      if (countdown.value > 0) startTimers()
    } catch (_) {
      step.value = 2
    }
  } catch (e) {
    const errors = e?.response?.data?.errors
    if (errors) {
      const firstError = Object.values(errors)[0][0]
      errorStart.value = firstError
    } else {
      errorStart.value = e?.response?.data?.message || 'Could not start application'
    }
  } finally {
    loadingStart.value = false
  }
}

async function handleUpload() {
  if (!files.value.passport || !files.value.id_card || !files.value.proof_of_address) {
    errorUpload.value = 'Please select all required documents before continuing.'
    return
  }
  loadingUpload.value = true
  errorUpload.value = ''
  try {
    const fd = new FormData()
    fd.append('token', token.value)
    fd.append('passport', files.value.passport)
    fd.append('id_card', files.value.id_card)
    fd.append('proof_of_address', files.value.proof_of_address)
    if (signatures.value.guarantor) fd.append('guarantor_signature_base64', signatures.value.guarantor)
    if (signatures.value.imam) fd.append('imam_signature_base64', signatures.value.imam)
    if (signatures.value.spouse_father) fd.append('spouse_father_consent_signature_base64', signatures.value.spouse_father)
    const { data } = await axios.post('/api/register/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    uploaded.value = data.application || {}
    await handleSendOtps()
    step.value = 3
  } catch (e) {
    const errors = e?.response?.data?.errors
    if (errors) {
      const firstError = Object.values(errors)[0][0]
      errorUpload.value = firstError
    } else {
      errorUpload.value = e?.response?.data?.message || 'Upload failed'
    }
  } finally {
    loadingUpload.value = false
  }
}

async function handleSendOtps() {
  errorSendOtps.value = ''
  try {
    const { data } = await axios.post('/api/register/send-otps', { token: token.value })
    maskedEmail.value = data?.sent_to?.email || ''
    maskedPhone.value = data?.sent_to?.phone || ''
    countdown.value = data?.expires_in || 600
    startTimers()
    startResendCooldown()
  } catch (e) {
    if (e?.response?.status === 429) {
      errorSendOtps.value = e?.response?.data?.message || 'Please wait before requesting codes again.'
    } else {
      errorSendOtps.value = 'Failed to send verification codes.'
    }
  }
}

function startTimers() {
  if (timer) clearInterval(timer)
  countdown.value = countdown.value || 600
  timer = setInterval(() => {
    if (countdown.value > 0) countdown.value--
    else clearInterval(timer)
  }, 1000)
}
function startResendCooldown() {
  resendCooldown.value = 30
  if (resendTimer) clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    if (resendCooldown.value > 0) resendCooldown.value--
    else clearInterval(resendTimer)
  }, 1000)
}

async function handleVerifyEmail() {
  loadingVerifyEmail.value = true
  errorVerifyEmail.value = ''
  try {
    await axios.post('/api/register/verify-email', { token: token.value, code: emailCode.value })
    emailVerified.value = true
  } catch (e) {
    errorVerifyEmail.value = e?.response?.data?.message || 'Invalid code'
  } finally {
    loadingVerifyEmail.value = false
  }
}

async function handleVerifySms() {
  loadingVerifySms.value = true
  errorVerifySms.value = ''
  try {
    await axios.post('/api/register/verify-sms', { token: token.value, code: smsCode.value })
    phoneVerified.value = true
  } catch (e) {
    errorVerifySms.value = e?.response?.data?.message || 'Invalid code'
  } finally {
    loadingVerifySms.value = false
  }
}

async function handleResend() {
  if (resendCooldown.value > 0) return
  await handleSendOtps()
}

const result = ref({ membership_number: '', branch_id: '' })
async function handleFinalize() {
  loadingFinalize.value = true
  errorFinalize.value = ''
  try {
    const { data } = await axios.post('/api/register/finalize', { token: token.value, bvn: bvn.value })
    result.value = data
    localStorage.removeItem('reg_token')
    step.value = 4
  } catch (e) {
    errorFinalize.value = e?.response?.data?.message || 'Could not complete registration'
    const details = e?.response?.data?.details
    if (details && typeof details === 'object') {
      // Optionally show normalized reason for KYC failure
      const reason = details?.message || details?.status
      if (reason) errorFinalize.value += ` (${reason})`
    }
  } finally {
    loadingFinalize.value = false
  }
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
