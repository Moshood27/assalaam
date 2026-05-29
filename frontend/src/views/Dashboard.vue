<template>
  <div class="min-h-screen pb-28 overflow-x-hidden bg-slate-50">
    <AppHeader :user="dashboardData" :showSettings="true" />

    <div class="max-w-5xl mx-auto px-4 pb-10">
      <!-- Global System Announcement -->
      <div v-if="appStatusStore.systemAnnouncement" 
           class="mt-4 bg-blue-600 text-white px-4 py-3 rounded-2xl text-center text-xs font-bold flex items-center justify-center gap-3 shadow-md animate-in fade-in slide-in-from-top duration-500 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 shrink-0">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.007.51.011.77.011h3.39c.8 0 1.545-.466 1.89-1.159L18.42 12l-1.12-2.25c-.345-.693-1.09-1.159-1.89-1.159h-3.39c-.26 0-.517.004-.77.011m0 9.18c.612.016 1.221.031 1.83.042m-1.83-9.222c.61-.011 1.218-.026 1.83-.042m-1.83 9.222v-9.18m1.83 9.138A17.944 17.944 0 0 1 12 18c-1.353 0-2.65-.148-3.903-.432m10.343-9.43A17.944 17.944 0 0 0 12 6c-1.353 0-2.65.148-3.903.432" />
        </svg>
        <p class="leading-tight">{{ appStatusStore.systemAnnouncement }}</p>
      </div>

      <div class="lg:grid lg:grid-cols-12 lg:gap-8 items-start">
        <!-- Left Column: Primary Info & Warnings -->
        <div class="lg:col-span-7 space-y-4">
          <div id="balance-card" class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-[2rem] p-7 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full"></div>
        <div class="flex items-center gap-2 mb-2 relative z-10">
          <p class="text-blue-100 text-sm font-medium">Available Balance</p>
          <button @click="toggleBalances()" class="text-lg opacity-80 p-1 rounded-lg hover:bg-white/10 transition-colors" title="Toggle visibility">
            <svg v-if="hideBalances" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.076m3.313-3.313A9.959 9.959 0 0112 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-1.447 0-2.811-.31-4.04-.864m1.107-1.107l1.107-1.107m2.774-2.774l.553-.553m2.21-2.21l.553-.553" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
            </svg>
          </button>
        </div>
        <h1 class="text-3xl sm:text-4xl leading-tight font-bold relative z-10 tracking-tight">
          ₦ {{ hideBalances ? '***,***.**' : formatMoney(dashboardData.balance) }}
        </h1>
        <div class="mt-8 flex items-center justify-between flex-wrap gap-2 relative z-10">
          <div class="flex items-center gap-2">
            <p class="text-xs text-blue-100 font-mono tracking-widest">ID: {{ dashboardData.membership_id }}</p>
            <button @click="copy(dashboardData.membership_id)" class="text-xs text-white/80 underline">Copy</button>
          </div>
          <div class="flex gap-2">
            <button @click="$router.push('/pay')" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-lg border border-blue-400">
              Allocate Fund
            </button>
            <button @click="$router.push('/wallet')" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">
              + Add Money
            </button>
          </div>
        </div>
      </div>

      <!-- PIN Warning -->
      <div v-if="dashboardData.kpis && !dashboardData.kpis.has_pin"
           class="mt-4 p-4 rounded-3xl bg-amber-50 border border-amber-200 flex items-center gap-3"
           @click="$router.push('/profile')">
        <div class="text-2xl">Ã°Å¸â€â€˜</div>
        <div class="flex-1">
          <p class="text-sm font-bold text-amber-900">Transaction PIN not set</p>
          <p class="text-xs text-amber-700">You need a PIN to transfer or withdraw funds.</p>
        </div>
        <div class="text-amber-400">Ã¢Å¾Â¡Ã¯Â¸Â</div>
      </div>

      <!-- Attendance Reminder -->
      <div v-if="dashboardData.kpis && dashboardData.kpis.has_ongoing_meeting"
           class="mt-4 p-4 rounded-3xl bg-blue-900 text-white flex items-center gap-3 shadow-lg shadow-blue-200 cursor-pointer"
           @click="$router.push('/attendance')">
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl animate-pulse">Ã°Å¸â€œÂ</div>
        <div class="flex-1">
          <p class="text-sm font-bold">Meeting Ongoing</p>
          <p class="text-[10px] text-white/70 uppercase tracking-widest font-black">Tap to mark attendance</p>
        </div>
        <div class="text-white/40">Ã¢Å¾Â¡Ã¯Â¸Â</div>
      </div>

      <!-- Outstanding Fines Warning -->
      <div v-if="dashboardData.kpis && dashboardData.kpis.outstanding_fines > 0"
           class="mt-4 p-4 rounded-3xl bg-rose-50 border border-rose-200 flex items-center gap-3"
           @click="$router.push('/passbook')">
        <div class="text-2xl">Ã¢Å¡Â Ã¯Â¸Â</div>
        <div class="flex-1">
          <p class="text-sm font-bold text-rose-900">Outstanding Fines: Ã¢â€šÂ¦{{ formatMoney(dashboardData.kpis.outstanding_fines) }}</p>
          <p class="text-xs text-rose-700">These will be deducted from your next wallet funding.</p>
        </div>
        <div class="text-rose-400">Ã¢Å¾Â¡Ã¯Â¸Â</div>
      </div>

      <!-- Tahkim Dispute Warning -->
      <div v-if="kpis.active_disputes_count > 0"
           class="mt-4 p-4 rounded-3xl bg-slate-900 text-white flex items-center gap-3 shadow-lg shadow-slate-200 cursor-pointer"
           @click="$router.push('/sharia-board/history')">
        <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center text-xl">Ã¢Å¡â€“Ã¯Â¸Â</div>
        <div class="flex-1">
          <p class="text-sm font-bold">Active Tahkim ({{ kpis.active_disputes_count }})</p>
          <p class="text-[10px] text-white/70 uppercase tracking-widest font-black">Sharia Board Mediation in progress</p>
        </div>
        <div class="text-white/40">Ã¢Å¾Â¡Ã¯Â¸Â</div>
      </div>

      <!-- Shura Voting Banner -->
      <div v-if="appStatusStore.features['shura-voting-active']"
           class="mt-4 p-4 rounded-3xl bg-indigo-600 text-white flex items-center gap-3 shadow-lg shadow-indigo-200 cursor-pointer"
           @click="$router.push('/agm')">
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl animate-bounce">Ã°Å¸â€”Â³Ã¯Â¸Â</div>
        <div class="flex-1">
          <p class="text-sm font-bold">AGM Voting Live</p>
          <p class="text-[10px] text-white/70 uppercase tracking-widest font-black">Cast your vote for the Shura Council</p>
        </div>
        <div class="text-white/40">Ã¢Å¾Â¡Ã¯Â¸Â</div>
      </div>

      <!-- Migration Discrepancy Banner -->
      <div v-if="dashboardData.migration?.discrepancy_reported_at && !dashboardData.migration?.verified_at"
           class="mt-4 p-4 rounded-3xl bg-blue-50 border border-blue-200 flex items-center gap-3">
        <div class="text-2xl">Ã¢ÂÂ³</div>
        <div class="flex-1">
          <p class="text-sm font-bold text-blue-900">Balance Under Review</p>
          <p class="text-xs text-blue-700">You reported a discrepancy. Our officers are currently reconciling your records.</p>
        </div>
      </div>

      <!-- Next Due Installment Banner -->
      <div v-if="kpis.next_due_date"
           class="mt-4 p-4 rounded-3xl bg-white border border-slate-100 flex items-center gap-3 shadow-sm cursor-pointer"
           @click="$router.push('/loans')">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shrink-0"
             :class="kpis.total_due_amount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'">
          {{ kpis.total_due_amount > 0 ? 'Ã°Å¸Å¡Â¨' : 'Ã°Å¸â€â€' }}
        </div>
        <div class="flex-1">
          <div class="flex justify-between items-start">
            <p class="text-[10px] font-bold uppercase tracking-widest mb-0.5"
               :class="kpis.total_due_amount > 0 ? 'text-rose-600' : 'text-amber-600'">
              Next Due Installment
            </p>
            <span v-if="kpis.total_due_amount > 0" class="text-[9px] font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full uppercase tracking-tighter">Action Required</span>
          </div>
          <p class="text-sm font-black text-slate-800">
            {{ formatDate(kpis.next_due_date) }} Ã¢â‚¬Â¢ {{ currency }} {{ hideBalances ? '***,***.**' : formatMoney(kpis.next_due_amount) }}
          </p>
          <p v-if="kpis.total_due_amount > 0" class="text-[10px] text-rose-500 font-bold mt-1 uppercase tracking-tighter">
            Overdue Amount: {{ currency }} {{ hideBalances ? '***,***.**' : formatMoney(kpis.total_due_amount) }}
          </p>
          <p v-if="kpis.expected_amount_to_pay > 0" class="text-[10px] text-blue-500 font-bold mt-1 uppercase tracking-tighter">
            Expected to Pay: {{ currency }} {{ hideBalances ? '***,***.**' : formatMoney(kpis.expected_amount_to_pay) }}
          </p>
        </div>
        <div class="text-slate-300">Ã¢Å¾Â¡Ã¯Â¸Â</div>
      </div>
    </div> <!-- end left col -->

    <!-- Right Column: Status & Performance -->
    <div class="lg:col-span-5 space-y-6 mt-6 lg:mt-0">
      <!-- Qard Hasan Status & Savings Section -->
      <div class="bg-white rounded-[2.5rem] p-7 shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-slate-800 font-bold text-lg">Qard Hasan Status</h3>
          <div class="flex items-center gap-3">
            <router-link v-if="appStatusStore.features['apply-for-loan']" to="/loans" class="text-xs font-bold text-blue-600 hover:text-blue-700">Apply for Qard Hasan</router-link>
          </div>
          <div class="w-10 h-10 bg-blue-50 rounded-2xl flex items-center justify-center text-xl">Ã°Å¸â€™Å½</div>
        </div>
        
        <div class="flex items-end gap-1 mb-8" v-if="kpis.has_active_loan || kpis.total_due_amount > 0">
          <template v-if="kpis.total_due_amount > 0">
            <span class="text-3xl font-black text-rose-600">Ã¢â€šÂ¦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.total_due_amount) }}</span>
            <span class="text-[10px] text-rose-500 font-bold uppercase mb-2 ml-1 tracking-wider">Overdue Amount</span>
          </template>
          <template v-else-if="kpis.is_defaulted">
            <span class="text-3xl font-black text-rose-600">Ã¢â€šÂ¦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.total_due_amount) }}</span>
            <span class="text-[10px] text-rose-500 font-bold uppercase mb-2 ml-1 tracking-wider">Defaulted Amount</span>
          </template>
          <template v-else-if="kpis.has_active_loan">
            <span class="text-3xl font-black text-amber-600">Ã¢â€šÂ¦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.loans) }}</span>
            <span class="text-[10px] text-amber-500 font-bold uppercase mb-2 ml-1 tracking-wider">Outstanding Balance</span>
          </template>
        </div>

        <div v-if="kpis.expected_amount_to_pay > 0" class="mb-6">
           <p class="text-[10px] text-slate-400 uppercase font-black mb-1">Expected Amount to Pay (To Date)</p>
           <p class="text-lg font-black text-blue-600">Ã¢â€šÂ¦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.expected_amount_to_pay) }}</p>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <StatPill label="Savings" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.savings_balance))" icon="Ã°Å¸â€™Â°" />
          <StatPill label="Shares" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.shares_balance))" icon="Ã°Å¸â€œË†" />
        </div>
        
        <div v-if="kpis.is_defaulted" class="mt-6 flex items-center gap-3 bg-rose-50 p-4 rounded-3xl border border-rose-100">
          <div class="text-lg">Ã°Å¸â€ºâ€˜</div>
          <p class="text-[10px] text-rose-700 leading-tight font-medium">
            Your account is currently <span class="font-bold">in default</span> due to an unpaid Qard Hasan repayment. You must clear your outstanding balance before you can access further credit.
          </p>
        </div>
      </div>

      <!-- KPI row -->
      <div class="mt-4 grid grid-cols-2 gap-3">
        <StatPill label="Contributions" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.contributions))" hint="Total" intent="success" icon="Ã°Å¸â€™Â°" />
        <StatPill v-if="kpis.total_due_amount > 0" label="Overdue Amount" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.total_due_amount))" hint="Pay Now" intent="danger" icon="Ã¢Å¡Â Ã¯Â¸Â" @click="$router.push('/loans')" class="cursor-pointer" />
        <StatPill v-else-if="kpis.expected_amount_to_pay > 0" label="Expected to Pay" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.expected_amount_to_pay))" hint="Cumulative" intent="info" icon="Ã°Å¸â€œâ€¦" @click="$router.push('/loans')" class="cursor-pointer" />
        <StatPill v-else-if="appStatusStore.features['gold-savings-beta']" label="Gold Balance" :value="(hideBalances ? '***.**' : kpis.gold_balance?.toFixed(4)) + ' g'" :hint="hideBalances ? 'Ã¢â€°Ë† Ã¢â€šÂ¦ ***' : (kpis.gold_value_naira ? 'Ã¢â€°Ë† Ã¢â€šÂ¦ ' + formatMoney(kpis.gold_value_naira) : 'Digital Gold')" intent="warning" icon="Ã°Å¸Âªâ„¢" @click="$router.push('/gold')" class="cursor-pointer" />
        <StatPill label="Qard Hasan" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.loans))" hint="Outstanding" intent="danger" icon="Ã°Å¸â€œÅ " />
        <StatPill label="Assalaam Score" :value="String(kpis.assalaam_score || 0)" hint="Credit Rating" intent="info" icon="Ã¢Â­Â" @click="$router.push('/profile')" class="cursor-pointer" />
      </div>
    </div> <!-- end right col -->
  </div> <!-- end grid -->

      <!-- Trend chart -->
      <FinCard class="mt-6" :padded="true" :elevated="true">
        <template #title>
          Activity Trend
        </template>
        <TrendChart :series="chart.series" :categories="chart.categories" :currency="currency" />
      </FinCard>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mt-8">
      <button @click="$router.push('/pay')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€™Â³</div>
        <span class="text-sm font-bold text-slate-700">Allocate Fund</span>
      </button>
      <button v-if="appStatusStore.features['chat-help-enabled']" @click="$router.push('/chat')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€™Â¬</div>
        <span class="text-sm font-bold text-slate-700">Chat & Help</span>
      </button>
      <button v-if="appStatusStore.features['projects-enabled']" @click="$router.push('/projects')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œÂ¦</div>
        <span class="text-sm font-bold text-slate-700">Projects</span>
      </button>
      <button @click="$router.push('/sadaqah')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-rose-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸Å’â„¢</div>
        <span class="text-sm font-bold text-slate-700">Sadaqah</span>
      </button>
      <button @click="$router.push('/attendance')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œÂ</div>
        <span class="text-sm font-bold text-slate-700">Attendance</span>
      </button>
      <button v-if="appStatusStore.features['group-savings-enabled']" @click="$router.push('/savings-groups')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸Â¤Â</div>
        <span class="text-sm font-bold text-slate-700">Group Savings</span>
      </button>
      <button @click="$router.push('/vtu')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œÂ¶</div>
        <span class="text-sm font-bold text-slate-700">Airtime/Data</span>
      </button>
      <button id="loan-btn" @click="$router.push('/loans')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œÅ </div>
        <span class="text-sm font-bold text-slate-700">Qard Hasan Records</span>
      </button>
      <button @click="$router.push('/reports')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œË†</div>
        <span class="text-sm font-bold text-slate-700">Reports</span>
      </button>
      <button v-if="appStatusStore.features['takaful-enabled']" @click="$router.push('/takaful')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-cyan-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€ºÂ¡Ã¯Â¸Â</div>
        <span class="text-sm font-bold text-slate-700">Takaful</span>
      </button>
      <button @click="$router.push('/transparency')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-lime-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸Â§Â¾</div>
        <span class="text-sm font-bold text-slate-700">Transparency</span>
      </button>
      <button @click="$router.push('/store')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€ºâ€™</div>
        <span class="text-sm font-bold text-slate-700">Store</span>
      </button>
      <button v-if="appStatusStore.features['gold-savings-enabled'] || appStatusStore.features['gold-savings-beta']" @click="$router.push('/gold')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-yellow-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸Âªâ„¢</div>
        <span class="text-sm font-bold text-slate-700">Gold Savings</span>
      </button>
      <button v-if="appStatusStore.features['merchant-pay-enabled']" @click="$router.push('/merchant/pay')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œÂ¸</div>
        <span class="text-sm font-bold text-slate-700">Pay Merchant</span>
      </button>
      <button v-if="appStatusStore.features['receive-qr-enabled']" @click="$router.push('/merchant/receive')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€Â²</div>
        <span class="text-sm font-bold text-slate-700">Receive QR</span>
      </button>
      <button @click="$router.push('/agm')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-fuchsia-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€”Â³Ã¯Â¸Â</div>
        <span class="text-sm font-bold text-slate-700">AGM & Voting</span>
      </button>
      <button v-if="appStatusStore.features['zakat-enabled']" @click="checkZakat" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€¢Å’</div>
        <span class="text-sm font-bold text-slate-700">Zakat</span>
      </button>
      <button v-if="dashboardData.is_ramadan && appStatusStore.features['zakat-enabled']" @click="payZakatFitr" class="bg-blue-50 p-5 rounded-3xl shadow-sm border border-blue-100 flex flex-col items-center gap-2 active:bg-blue-100 transition-all">
        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸Â¥Â£</div>
        <span class="text-sm font-bold text-blue-800">Zakat Al-Fitr</span>
      </button>
      <button @click="$router.push('/goals')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€¢â€¹</div>
        <span class="text-sm font-bold text-slate-700">Hajj & Umrah</span>
      </button>
      <button v-if="appStatusStore.features['junior-coop-enabled']" @click="$router.push('/junior-cooperative')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€˜Â¶</div>
        <span class="text-sm font-bold text-slate-700">Junior Coop</span>
      </button>
      <button @click="$router.push('/wasiyyah')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€œâ€¹</div>
        <span class="text-sm font-bold text-slate-700">Wasiyyah</span>
      </button>
      <button v-if="kpis.vendor && kpis.vendor.is_vendor" @click="$router.push('/vendor/dashboard')" class="bg-blue-50 p-5 rounded-3xl shadow-sm border border-blue-100 flex flex-col items-center gap-2 active:bg-blue-100 transition-all">
        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸ÂÂª</div>
        <span class="text-sm font-bold text-blue-800">Vendor Portal</span>
      </button>
      <button v-else @click="$router.push('/vendor/apply')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸ÂÂª</div>
        <span class="text-sm font-bold text-slate-700">Become a Vendor</span>
      </button>
      <button @click="$router.push('/sharia-board')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">Ã¢Å¡â€“Ã¯Â¸Â</div>
        <span class="text-sm font-bold text-slate-700">Sharia Board</span>
      </button>
      <button v-if="dashboardData.is_admin" @click="$router.push('/admin/vendors')" class="bg-rose-50 p-5 rounded-3xl shadow-sm border border-rose-100 flex flex-col items-center gap-2 active:bg-rose-100 transition-all">
        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl">Ã°Å¸â€˜Â®</div>
        <span class="text-sm font-bold text-rose-800">Admin Portal</span>
      </button>
    </div>

    <!-- Quick guide links -->
    <div class="mt-6 text-[12px] text-slate-600">
      <p>
        New here? Learn about
        <button class="text-blue-700 font-semibold underline" @click="showPassbookInfo">Passbook</button>,
        <template v-if="appStatusStore.features['zakat-enabled']">
          <button class="text-blue-700 font-semibold underline" @click="showZakatInfo">Zakat</button>,
        </template>
        and
        <button class="text-blue-700 font-semibold underline" @click="showHajjInfo">Hajj & Umrah</button>.
      </p>
    </div>

    <!-- Tabs Navigation -->
    <div class="mt-12">
      <div class="flex p-1.5 bg-slate-200/50 rounded-[1.5rem] gap-1 shadow-inner mb-6">
        <button 
          v-for="tab in ['transactions', 'passbook', 'vtu']" 
          :key="tab"
          @click="switchTab(tab)"
          :class="activeTab === tab ? 'bg-white text-blue-700 shadow-md scale-[1.02]' : 'text-slate-500 hover:bg-white/30'"
          class="flex-1 py-3 rounded-2xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 ease-out"
        >
          {{ tab }}
        </button>
      </div>

      <!-- Search Bar -->
      <div v-if="activeTab !== 'passbook'" class="relative group mb-6">
        <input 
          v-model="searchQuery" 
          type="text" 
          :placeholder="activeTab === 'transactions' ? 'Search transactions...' : 'Search airtime/data...'"
          class="w-full pl-12 pr-4 py-4 bg-white border border-slate-100 rounded-2xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all shadow-sm"
        >
        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Transactions Tab -->
      <div v-if="activeTab === 'transactions'" class="space-y-6">
        <!-- Live Activity Feed -->
        <div v-if="liveActions.length" class="animate-in fade-in slide-in-from-bottom duration-500">
          <h3 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
            Live Activity
          </h3>
          <div class="space-y-3">
            <div v-for="action in liveActions" :key="action.id" 
                 class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 border-2 border-blue-100 shadow-sm animate-bounce-in">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-lg shrink-0">
                  Ã°Å¸â€â€
                </div>
                <div>
                  <p class="font-bold text-slate-800 text-sm">{{ action.message }}</p>
                  <p class="text-[10px] text-blue-600 font-mono uppercase tracking-widest font-black">{{ action.time }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center">
          <h3 class="font-bold text-slate-800 text-lg">Recent Transactions</h3>
          <button class="text-blue-700 text-sm font-bold" @click="$router.push('/passbook')">See All</button>
        </div>

        <div v-if="filteredTransactions.length" class="space-y-3">
          <div v-for="tx in filteredTransactions" :key="tx.id"
               class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 overflow-hidden border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <div :class="tx.type === 'credit' ? 'bg-blue-100 text-blue-600' : 'bg-rose-100 text-rose-600'"
                   class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
                {{ tx.type === 'credit' ? '+' : 'Ã¢Ë†â€™' }}
              </div>
              <div class="min-w-0 overflow-hidden">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="font-bold text-slate-800 text-sm truncate max-w-[160px] sm:max-w-none">{{ txTitle(tx) }}</p>
                  <span v-if="isFine(tx)" class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black uppercase">Fine</span>
                </div>
                <p class="text-[10px] text-gray-500 uppercase font-medium">{{ formatDate(tx.created_at) }}</p>
                <p class="text-[10px] text-slate-400 font-mono truncate">{{ txPrefix(tx) }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold text-slate-800">Ã¢â€šÂ¦ {{ hideBalances ? '***,***.**' : formatMoney(tx.amount) }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-10 text-gray-400 bg-white rounded-3xl border border-dashed border-slate-200">
          <p>No transactions found.</p>
        </div>
      </div>

      <!-- Passbook Tab -->
      <div v-if="activeTab === 'passbook'" class="space-y-6">
        <div v-if="isLoadingPassbook" class="space-y-4">
          <div class="h-32 bg-slate-200 rounded-[2rem] animate-pulse"></div>
          <div class="h-20 bg-slate-100 rounded-3xl animate-pulse"></div>
        </div>
        <div v-else-if="passbookSummary" class="space-y-6 animate-in fade-in slide-in-from-bottom duration-500">
          <!-- Yearly Summary Card -->
          <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-[2rem] p-7 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full"></div>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Yearly Cumulative</p>
            <h2 class="text-4xl font-black tracking-tight">Ã¢â€šÂ¦ {{ formatMoney(passbookSummary.grand_total) }}</h2>
            
            <div class="mt-8 pt-6 border-t border-white/10 flex justify-between items-center">
              <div>
                <p class="text-slate-400 text-[10px] uppercase font-bold">Current Year</p>
                <p class="text-sm font-bold">{{ new Date().getFullYear() }}</p>
              </div>
              <button @click="$router.push('/passbook')" class="bg-blue-600 hover:bg-blue-500 px-6 py-3 rounded-2xl text-xs font-bold transition-all shadow-lg shadow-blue-900/20">
                View Full Passbook
              </button>
            </div>
          </div>

          <!-- Quick Stats Grid -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm">
              <p class="text-[10px] text-slate-400 uppercase font-black mb-1">Schemes</p>
              <p class="text-xl font-black text-slate-800">{{ passbookSummary.matrix?.length || 0 }}</p>
            </div>
            <div v-if="passbookSummary.agm_fee_amount" class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm">
              <p class="text-[10px] text-slate-400 uppercase font-black mb-1">AGM Fee</p>
              <div class="flex items-center gap-2">
                <p class="text-xl font-black text-slate-800">Ã¢â€šÂ¦{{ formatMoney(passbookSummary.agm_fee_amount) }}</p>
                <span :class="passbookSummary.agm_fee_paid ? 'text-blue-500' : 'text-amber-500'" class="text-xs">
                  {{ passbookSummary.agm_fee_paid ? 'Ã¢Å“â€œ' : 'Ã¢Å’â€º' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-10 text-gray-400 bg-white rounded-3xl border border-dashed border-slate-200">
          <p>Could not load passbook summary.</p>
          <button @click="fetchPassbookSummary" class="mt-4 text-blue-700 font-bold underline">Retry</button>
        </div>
      </div>

      <!-- VTU Tab -->
      <div v-if="activeTab === 'vtu'" class="space-y-6">
        <div class="flex justify-between items-center">
          <h3 class="font-bold text-slate-800 text-lg">Recent Airtime/Data</h3>
          <button class="text-blue-700 text-sm font-bold" @click="$router.push('/vtu/history')">See All</button>
        </div>

        <div v-if="filteredUtilityTransactions.length" class="space-y-3">
          <div v-for="ux in filteredUtilityTransactions" :key="ux.id"
               class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 overflow-hidden border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <div :class="ux.status === 'success' ? 'bg-blue-100 text-blue-600' : (ux.status === 'failed' ? 'bg-rose-100 text-rose-600' : 'bg-yellow-100 text-yellow-600')"
                   class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
                {{ ux.status === 'success' ? 'Ã¢Å“â€œ' : (ux.status === 'failed' ? 'Ã¢Å“â€¢' : 'Ã¢Å’â€º') }}
              </div>
              <div class="min-w-0 overflow-hidden">
                <p class="font-bold text-slate-800 text-sm capitalize truncate max-w-[180px] sm:max-w-none">{{ utilLabel(ux) }}</p>
                <p class="text-[10px] text-gray-500 uppercase font-medium">{{ formatDate(ux.created_at) }}</p>
                <p class="text-[10px] text-slate-400 font-mono truncate">{{ ux.reference }}</p>
              </div>
            </div>
            <div class="text-right shrink-0">
              <p class="font-bold text-slate-800">Ã¢â€šÂ¦ {{ formatMoney(ux.amount) }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-10 text-gray-400 bg-white rounded-3xl border border-dashed border-slate-200">
          <p>No VTU activity found.</p>
        </div>
      </div>
    </div> <!-- end tabs container -->
  </div> <!-- end max-w container -->

    <!-- Reusable Custom Notice Modal for Zakat/info alerts -->
    <CustomNotice
      v-model="notice.visible"
      :type="notice.type"
      :title="notice.title"
      :message="notice.message"
      @close="closeNotice"
    />

    <!-- Force Gender Update Modal -->
    <div v-if="showGenderModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md flex items-center justify-center z-[100] p-6">
      <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in duration-300 border border-slate-100">
        <div class="p-8">
           <div class="w-20 h-20 bg-blue-50 rounded-3xl flex items-center justify-center text-4xl mx-auto mb-6 shadow-sm border border-blue-100">Ã°Å¸â€˜Â¤</div>
           
           <h3 class="text-2xl font-black text-slate-800 text-center mb-2 uppercase tracking-tight">Update Gender</h3>
           <p class="text-slate-500 text-center text-xs mb-8 leading-relaxed font-medium">To provide you with tailored services and accurate records, please select your gender.</p>
           
           <div class="space-y-3">
             <button 
               @click="selectedGender = 'male'"
               :class="selectedGender === 'male' ? 'bg-blue-600 text-white border-blue-600 scale-[1.02] shadow-lg shadow-blue-100' : 'bg-slate-50 text-slate-600 border-slate-100 hover:bg-slate-100'"
               class="w-full p-5 rounded-2xl border-2 font-black uppercase tracking-widest text-xs transition-all flex items-center justify-between"
             >
               <span>Male</span>
               <span v-if="selectedGender === 'male'" class="text-lg">Ã¢Å“â€œ</span>
             </button>
             
             <button 
               @click="selectedGender = 'female'"
               :class="selectedGender === 'female' ? 'bg-blue-600 text-white border-blue-600 scale-[1.02] shadow-lg shadow-blue-100' : 'bg-slate-50 text-slate-600 border-slate-100 hover:bg-slate-100'"
               class="w-full p-5 rounded-2xl border-2 font-black uppercase tracking-widest text-xs transition-all flex items-center justify-between"
             >
               <span>Female</span>
               <span v-if="selectedGender === 'female'" class="text-lg">Ã¢Å“â€œ</span>
             </button>
           </div>
        </div>
        
        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button 
            @click="updateGender" 
            :disabled="!selectedGender || updatingGender"
            class="w-full bg-slate-800 text-white font-black py-5 rounded-2xl shadow-xl shadow-slate-200 flex items-center justify-center gap-3 uppercase tracking-[0.2em] text-[10px] disabled:opacity-50 active:scale-95 transition-all"
          >
            <span v-if="updatingGender" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
            <span v-else>Confirm Profile Update</span>
          </button>
          
          <p class="text-[9px] text-slate-400 text-center mt-4 font-bold uppercase tracking-widest opacity-60">This is required to proceed to your dashboard</p>
        </div>
      </div>
    </div>

    <!-- Force Email Update Modal -->
    <div v-if="showEmailModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md flex items-center justify-center z-[101] p-6">
      <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in duration-300 border border-slate-100">
        <div class="p-8">
           <div class="w-20 h-20 bg-blue-50 rounded-3xl flex items-center justify-center text-4xl mx-auto mb-6 shadow-sm border border-blue-100">Ã°Å¸â€œÂ§</div>
           
           <h3 class="text-2xl font-black text-slate-800 text-center mb-2 uppercase tracking-tight">Update Email</h3>
           <p class="text-slate-500 text-center text-xs mb-8 leading-relaxed font-medium">Your current email address is invalid. Please provide a valid email to receive notifications and secure your account.</p>
           
           <div class="space-y-4">
             <div>
               <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">New Email Address</label>
               <input 
                 v-model="emailForm.email" 
                 type="email" 
                 placeholder="yourname@example.com"
                 class="w-full p-4 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-700"
               />
               <p v-if="emailErrors.email" class="text-[10px] text-rose-500 mt-1 ml-1 font-bold">{{ emailErrors.email[0] }}</p>
             </div>

             <div>
               <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm Password</label>
               <input 
                 v-model="emailForm.password" 
                 type="password" 
                 placeholder="Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢"
                 class="w-full p-4 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-blue-500 focus:bg-white outline-none transition-all font-bold text-slate-700"
               />
               <p v-if="emailErrors.password" class="text-[10px] text-rose-500 mt-1 ml-1 font-bold">{{ emailErrors.password[0] }}</p>
             </div>
           </div>
        </div>
        
        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button 
            @click="updateEmail" 
            :disabled="emailSaving"
            class="w-full bg-slate-800 text-white font-black py-5 rounded-2xl shadow-xl shadow-slate-200 flex items-center justify-center gap-3 uppercase tracking-[0.2em] text-[10px] disabled:opacity-50 active:scale-95 transition-all"
          >
            <span v-if="emailSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
            <span v-else>Update Email Address</span>
          </button>
          
          <p class="text-[9px] text-slate-400 text-center mt-4 font-bold uppercase tracking-widest opacity-60">This is required to proceed to your dashboard</p>
        </div>
      </div>
    </div>

    <!-- Force PIN Setup Modal -->
    <div v-if="showPinModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md flex items-center justify-center z-[101] p-6">
      <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in duration-300 border border-slate-100">
        <div class="p-8">
           <div class="w-20 h-20 bg-amber-50 rounded-3xl flex items-center justify-center text-4xl mx-auto mb-6 shadow-sm border border-amber-100">Ã°Å¸â€Â</div>
           
           <h3 class="text-2xl font-black text-slate-800 text-center mb-2 uppercase tracking-tight">Set Security PIN</h3>
           <p class="text-slate-500 text-center text-xs mb-8 leading-relaxed font-medium">Please set a 4-digit transaction PIN to secure your withdrawals and transfers.</p>
           
           <div class="space-y-4">
             <div>
               <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">New 4-Digit PIN</label>
               <input 
                 v-model="pinForm.new_pin" 
                 type="password" 
                 inputmode="numeric"
                 maxlength="4"
                 placeholder="Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢"
                 class="w-full p-4 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-amber-500 focus:bg-white outline-none transition-all font-bold text-slate-700 text-center text-2xl tracking-[0.5em]"
               />
               <p v-if="pinErrors.new_pin" class="text-[10px] text-rose-500 mt-1 ml-1 font-bold">{{ Array.isArray(pinErrors.new_pin) ? pinErrors.new_pin[0] : pinErrors.new_pin }}</p>
             </div>

             <div>
               <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm PIN</label>
               <input 
                 v-model="pinForm.confirm_pin" 
                 type="password" 
                 inputmode="numeric"
                 maxlength="4"
                 placeholder="Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢"
                 class="w-full p-4 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-amber-500 focus:bg-white outline-none transition-all font-bold text-slate-700 text-center text-2xl tracking-[0.5em]"
               />
               <p v-if="pinErrors.confirm_pin" class="text-[10px] text-rose-500 mt-1 ml-1 font-bold">{{ Array.isArray(pinErrors.confirm_pin) ? pinErrors.confirm_pin[0] : pinErrors.confirm_pin }}</p>
             </div>

             <div>
               <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Account Password</label>
               <input 
                 v-model="pinForm.current_password" 
                 type="password" 
                 placeholder="Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢Ã¢â‚¬Â¢"
                 class="w-full p-4 rounded-2xl bg-slate-50 border-2 border-slate-100 focus:border-amber-500 focus:bg-white outline-none transition-all font-bold text-slate-700"
               />
               <p v-if="pinErrors.current_password" class="text-[10px] text-rose-500 mt-1 ml-1 font-bold">{{ Array.isArray(pinErrors.current_password) ? pinErrors.current_password[0] : pinErrors.current_password }}</p>
             </div>
           </div>
        </div>
        
        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button 
            @click="updatePin" 
            :disabled="pinSaving"
            class="w-full bg-slate-800 text-white font-black py-5 rounded-2xl shadow-xl shadow-slate-200 flex items-center justify-center gap-3 uppercase tracking-[0.2em] text-[10px] disabled:opacity-50 active:scale-95 transition-all"
          >
            <span v-if="pinSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
            <span v-else>Set Transaction PIN</span>
          </button>
          
          <p class="text-[9px] text-slate-400 text-center mt-4 font-bold uppercase tracking-widest opacity-60">This is required for account security</p>
        </div>
      </div>
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import { ref, onMounted, computed } from 'vue'
import { isValidEmail } from '../utils/validation'
import { getEcho } from '../realtime/echo'
import { useAppStatusStore } from '../stores/appStatus'
import axios from '../http'
import getImageUrl from '../utils/image'
import { useModal } from '../composables/useModal'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'
import FinCard from '../components/FinCard.vue'
import StatPill from '../components/StatPill.vue'
import TrendChart from '../components/TrendChart.vue'
import { startDashboardTour } from '../utils/tour'
import { useBalanceVisibility } from '../composables/useBalanceVisibility'

const modal = useModal()
const { notice, showNotice, closeNotice } = useNotice()
const appStatusStore = useAppStatusStore()

const currency = 'Ã¢â€šÂ¦'
const dashboardData = ref({})
const liveActions = ref([])
const activeTab = ref('transactions')
const searchQuery = ref('')
const passbookSummary = ref(null)
const isLoadingPassbook = ref(false)
const showGenderModal = ref(false)
const selectedGender = ref('')
const updatingGender = ref(false)

const showEmailModal = ref(false)
const emailForm = ref({ email: '', password: '' })
const emailSaving = ref(false)
const emailErrors = ref({})

const showPinModal = ref(false)
const pinForm = ref({ current_password: '', new_pin: '', confirm_pin: '' })
const pinSaving = ref(false)
const pinErrors = ref({})

const { hideBalances, toggleBalances } = useBalanceVisibility()

const filteredTransactions = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  const txs = dashboardData.value.transactions || []
  if (!query) return txs
  return txs.filter(tx => 
    txTitle(tx).toLowerCase().includes(query) ||
    txPrefix(tx).toLowerCase().includes(query) ||
    formatMoney(tx.amount).includes(query)
  )
})

const filteredUtilityTransactions = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  const utils = dashboardData.value.utility_transactions || []
  if (!query) return utils
  return utils.filter(ux => 
    utilLabel(ux).toLowerCase().includes(query) ||
    (ux.reference || '').toLowerCase().includes(query) ||
    formatMoney(ux.amount).includes(query) ||
    (ux.phone_number || '').includes(query)
  )
})

const fetchPassbookSummary = async () => {
  if (passbookSummary.value) return
  isLoadingPassbook.value = true
  try {
    const year = new Date().getFullYear()
    const { data } = await axios.get(`/api/passbook/${year}`)
    passbookSummary.value = data
  } catch (e) {
    console.error('Failed to fetch passbook summary', e)
  } finally {
    isLoadingPassbook.value = false
  }
}

const switchTab = (tab) => {
  activeTab.value = tab
  searchQuery.value = ''
  if (tab === 'passbook') {
    fetchPassbookSummary()
  }
}

const formatMoney = (val) => Number(val ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
const copy = async (text) => {
  try {
    await navigator.clipboard.writeText(String(text || ''))
    await modal.alert('Copied to clipboard')
  } catch (_) {}
}

const kpis = computed(() => {
  const d = dashboardData.value || {}
  if (d.kpis) return d.kpis

  const txs = Array.isArray(d.transactions) ? d.transactions : []
  const utils = Array.isArray(d.utility_transactions) ? d.utility_transactions : []
  const totalContrib = txs.reduce((sum, t) => sum + Number(t.amount || 0), 0)
  const outstandingLoans = txs.filter(t => (t.type === 'loan' || String(t.scheme?.name || '').toLowerCase().includes('loan')))
    .reduce((sum, t) => sum + Number(t.balance || 0), 0)
  const utilSpent = utils.reduce((sum, u) => sum + Number(u.amount || 0), 0)
  return { contributions: totalContrib, loans: outstandingLoans, utilities: utilSpent, assalaam_score: d.assalaam_score || 0, is_defaulted: false, defaulted_amount: 0, total_due_amount: 0, has_active_loan: false, loan_limit: 0, savings_balance: 0, shares_balance: 0, next_due_date: null, next_due_amount: 0 }
})

const chart = computed(() => {
  const d = dashboardData.value || {}
  const txs = Array.isArray(d.transactions) ? d.transactions.slice().sort((a,b) => new Date(a.created_at) - new Date(b.created_at)) : []
  // build simple last-10 points
  const points = txs.slice(-10)
  const categories = points.map(p => new Date(p.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }))
  const series = [{ name: 'Balance', data: points.map(p => Number(p.balance_after || p.running_balance || 0)) }]
  return { categories, series }
})

const txTitle = (tx) => {
  const src = tx?.source
  if (src === 'wallet_allocation') return 'Allocation to Schemes'
  if (src === 'paystack_dva') return 'Bank Transfer (DVA)'
  if (src === 'vtu_airtime') return 'Airtime Purchase'
  if (src === 'vtu_data') return 'Data Purchase'
  if (src === 'p2p_transfer') {
    if (tx.type === 'debit') {
      const name = tx?.meta?.to_name || tx?.meta?.to_membership
      return name ? `Transfer to ${name}` : 'Transfer Sent'
    } else {
      const name = tx?.meta?.from_name || tx?.meta?.from_membership
      return name ? `Transfer from ${name}` : 'Transfer Received'
    }
  }
  if (src === 'contribution' || (tx.meta && tx.meta.scheme_name)) return tx.meta.scheme_name || 'Contribution'
  return 'Wallet Transaction'
}
const txPrefix = (tx) => {
  return tx.reference || tx.tx_ref || `tx_${tx.id}`
}
const isFine = (tx) => {
  const src = (tx.source || '').toLowerCase()
  const meta = tx.meta || {}
  const schemeName = (meta.scheme_name || '').toLowerCase()
  return src.includes('fine') || schemeName.includes('fine') || schemeName.includes('lateness') || schemeName.includes('apology')
}

const utilLabel = (ux) => {
  const type = (ux.type || '').toLowerCase()
  const net = (ux.network || '').toUpperCase()
  const phone = ux.phone_number || ''
  if (type === 'airtime') return `Airtime Ã¢â‚¬â€ ${net} (${phone})`
  if (type === 'data') return `Data Ã¢â‚¬â€ ${net} (${phone})`
  return `${type || 'utility'} Ã¢â‚¬â€ ${net} (${phone})`
}

const checkMigration = async () => {
  const m = dashboardData.value.migration
  if (!m || !m.migrated_at) return
  if (m.discrepancy_reported_at || m.verified_at) return

  // Show verification modal
  const total = formatMoney(m.total_balance)
  const breakdownLines = Object.entries(m.breakdown || {})
    .filter(([_, val]) => Number(val) > 0)
    .map(([key, val]) => `Ã¢â‚¬Â¢ ${key}: ${currency} ${formatMoney(val)}`)
    .join('\n')

  const ok = await modal.prompt(
    'Verify Opening Balance',
    `Assalamu Alaikum! Welcome to As-Salaam Mobile App. Based on our system migration from paper/Excel records, here is your opening balance breakdown:\n\n${breakdownLines}\n\nTotal: ${currency} ${total}\n\nIs this correct?`,
    [
      { label: 'Yes, it is correct', value: 'verify', primary: true },
      { label: 'No, report discrepancy', value: 'report', danger: true },
      { label: 'Ask me later', value: 'cancel' }
    ]
  )

  const token = localStorage.getItem('token')
  if (ok === 'verify') {
    try {
      await axios.post('/api/profile/verify-migration', {}, { headers: { Authorization: `Bearer ${token}` } })
      showNotice('Success', 'Mashallah! Thank you! Your account is now fully verified.', 'success')
      dashboardData.value.migration.verified_at = new Date().toISOString()
    } catch (e) {
      showNotice('Error', 'Failed to verify balance. Please try again.', 'error')
    }
  } else if (ok === 'report') {
    const details = await modal.promptText(
      'Report Discrepancy',
      'Please describe the difference between your records and the amount shown above. Our officers will investigate and update your account.',
      { placeholder: 'e.g. My savings should be N50,000 not N45,000...' }
    )
    if (details) {
      try {
        await axios.post('/api/profile/report-migration-error', { details }, { headers: { Authorization: `Bearer ${token}` } })
        showNotice('Reported', 'Your report has been submitted. We will review it shortly.', 'info')
        dashboardData.value.migration.discrepancy_reported_at = new Date().toISOString()
      } catch (e) {
        showNotice('Error', 'Failed to submit report. Please try again.', 'error')
      }
    }
  }
}

const load = async () => {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('/api/dashboard', { headers: { Authorization: `Bearer ${token}` } })
  dashboardData.value = data
  localStorage.setItem('is_admin', data.is_admin ? 'true' : 'false')
  
  if (data.features) {
    appStatusStore.setFeatures(data.features)
  }
  
  // Check Migration status
  checkMigration()
  
  // Check Gender
  if (!data.gender) {
    showGenderModal.value = true
  } else if (!isValidEmail(data.email)) {
    // Check Email
    showEmailModal.value = true
    // If the email is clearly invalid (like a membership number or nonsense), clear it for them to type fresh
    emailForm.value.email = '' 
  } else if (!data.kpis.has_pin) {
    // Check PIN
    showPinModal.value = true
  }

  // Show Zakat alert if reached nisab but not yet paid (or simply reached nisab)
  if (appStatusStore.features['zakat-enabled'] && data.zakat_status?.reached_nisab) {
    const due = formatMoney(data.zakat_status.zakat_due)
    const nisab = formatMoney(data.zakat_status.nisab)
    
    if (data.zakat_status.eligible) {
      showNotice('Zakat Alert', `Your savings have reached the Nisab. Your Zakat due is ${currency} ${due}.`, 'info')
    } else {
      showNotice('Zakat Update', `Your savings have reached the Nisab. Keep tracking your savings to know when your Zakat becomes due!`, 'info')
    }
  }
}

const updateGender = async () => {
  if (!selectedGender.value) return
  updatingGender.value = true
  try {
    const token = localStorage.getItem('token')
    await axios.post('/api/profile/gender', { gender: selectedGender.value }, { headers: { Authorization: `Bearer ${token}` } })
    showGenderModal.value = false
    dashboardData.value.gender = selectedGender.value
    showNotice('Success', 'Mashallah! Profile updated. Jazakallah Khair!', 'success')
  } catch (e) {
    showNotice('Error', 'Failed to update gender. Please try again.', 'error')
  } finally {
    updatingGender.value = false
  }
}

const updateEmail = async () => {
  if (!emailForm.value.email || !emailForm.value.password) {
     emailErrors.value = { 
       email: !emailForm.value.email ? ['Email is required'] : [],
       password: !emailForm.value.password ? ['Password is required to confirm change'] : []
     }
     return
  }
  
  if (!isValidEmail(emailForm.value.email)) {
    emailErrors.value = { email: ['Please provide a valid email address.'] }
    return
  }
  
  emailSaving.value = true
  emailErrors.value = {}
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.post('/api/profile/email', emailForm.value, { headers: { Authorization: `Bearer ${token}` } })
    showEmailModal.value = false
    dashboardData.value.email = data.email
    showNotice('Success', 'Mashallah! Email updated successfully!', 'success')
  } catch (e) {
    if (e.response?.data?.errors) {
      emailErrors.value = e.response.data.errors
    } else {
      showNotice('Error', e.response?.data?.message || 'Failed to update email. Please try again.', 'error')
    }
  } finally {
    emailSaving.value = false
  }
}

const updatePin = async () => {
  pinErrors.value = {}
  if (!pinForm.value.current_password) {
    pinErrors.value.current_password = ['Current password is required.']
  }
  if (!pinForm.value.new_pin) {
    pinErrors.value.new_pin = ['PIN is required.']
  } else if (!/^\d{4}$/.test(String(pinForm.value.new_pin))) {
    pinErrors.value.new_pin = ['PIN must be exactly 4 digits.']
  }
  if (String(pinForm.value.confirm_pin) !== String(pinForm.value.new_pin)) {
    pinErrors.value.confirm_pin = ['PIN confirmation does not match.']
  }

  if (Object.keys(pinErrors.value).length > 0) return

  pinSaving.value = true
  try {
    await axios.post('/api/security/pin/set', {
      current_password: pinForm.value.current_password,
      new_pin: String(pinForm.value.new_pin),
      confirm_pin: String(pinForm.value.confirm_pin),
    })
    showPinModal.value = false
    dashboardData.value.kpis.has_pin = true
    showNotice('Success', 'Mashallah! Transaction PIN set successfully!', 'success')
  } catch (err) {
    const e = err?.response?.data
    if (e?.errors) {
      pinErrors.value = e.errors
    } else {
      showNotice('Error', e?.message || 'Failed to save PIN. Please try again.', 'error')
    }
  } finally {
    pinSaving.value = false
  }
}

const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (_) {}
  localStorage.removeItem('token')
  localStorage.removeItem('is_admin')
  const base = import.meta?.env?.BASE_URL || '/'
  const basePath = (base && base.endsWith('/')) ? base : `${base}/`
  window.location.assign(`${basePath}login`)
}

const checkZakat = async () => {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/zakat/estimate', { headers: { Authorization: `Bearer ${token}` } })

    if (!data || !data.base) {
      showNotice('Zakat', 'Could not compute your Zakat at this time. Please try again later.', 'error')
      return
    }

    if (!data.eligible) {
      const msg = data.base < data.nisab
        ? `You are currently below the Nisab (${currency} ${formatMoney(data.nisab)}).`
        : `You will be eligible on ${new Date(data.eligible_on).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}.`
      showNotice('Zakat', `Zakat not yet due.\n${msg}`, 'info')
      return
    }

    const due = formatMoney(data.zakat_due)
    const ok = await modal.confirm(`Your Zakat for this year is ${currency} ${due}. Would you like to pay now?`, { confirmText: 'Pay Now' })
    if (!ok) return

    const gateway = appStatusStore.paymentGateways?.primary || 'paystack'
    const callback_url = `${window.location.origin}${basePath}payment-callback?gateway=${gateway}`
    const payResp = await axios.post('/api/zakat/pay', { gateway, callback_url }, { headers: { Authorization: `Bearer ${token}` } })
    const url = payResp.data?.checkout_url || payResp.data?.authorization_url
    if (url) {
      window.location.assign(url)
    } else {
      showNotice('Zakat', 'Failed to start payment. Please try again.', 'error')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'An error occurred while checking Zakat.'
    showNotice('Zakat', msg, 'error')
  }
}

const payZakatFitr = async () => {
  try {
    const amount = formatMoney(dashboardData.value.fitr_amount)
    const ok = await modal.confirm(`Quick-pay Zakat Al-Fitr for this year: ${currency} ${amount}. Proceed to payment?`, {
      confirmText: 'Pay Now',
      title: 'Zakat Al-Fitr'
    })
    if (!ok) return

    const token = localStorage.getItem('token')
    const gateway = appStatusStore.paymentGateways?.primary || 'paystack'
    const callback_url = `${window.location.origin}${basePath}payment-callback?gateway=${gateway}`
    const { data } = await axios.post('/api/zakat/pay-fitr', { gateway, callback_url }, { headers: { Authorization: `Bearer ${token}` } })
    const url = data?.checkout_url
    if (url) {
      window.location.assign(url)
    } else {
      showNotice('Zakat Al-Fitr', 'Failed to start payment. Please try again.', 'error')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || 'An error occurred while initiating Zakat Al-Fitr payment.'
    showNotice('Zakat Al-Fitr', msg, 'error')
  }
}

// Quick guide: inline explanations for key features
const showPassbookInfo = () => {
  const msg = [
    'Your digital ledger with the cooperative.',
    'Ã¢â‚¬Â¢ See every contribution, withdrawal, Qard Hasan disbursement/repayment, fines, and adjustments.',
    'Ã¢â‚¬Â¢ Tap a row to view full details and reference.',
    'Ã¢â‚¬Â¢ Use filters (date range, scheme/type) to find entries fast.'
  ].join('\n')
  showNotice('Passbook', msg, 'info')
}

const showZakatInfo = () => {
  const msg = [
    'We help you check if Zakat is due and estimate the amount.',
    'Ã¢â‚¬Â¢ Eligibility: compares your eligible wealth with the Nisab and timing (haul).',
    'Ã¢â‚¬Â¢ Rate: typically 2.5% on eligible holdings once due.',
    'Ã¢â‚¬Â¢ Data source: based on balances and assets recorded with the cooperative.',
    'You can run an estimate now and, if due, pay securely in-app.'
  ].join('\n')
  showNotice('Zakat', msg, 'info')
}

const showHajjInfo = () => {
  const msg = [
    'Plan and save towards your Hajj or Umrah journey.',
    'Ã¢â‚¬Â¢ Set a goal amount and target date on the Goals page.',
    'Ã¢â‚¬Â¢ Track progress with each deposit and stay on schedule.',
    'Ã¢â‚¬Â¢ Withdrawals are protected to keep your pilgrimage savings intact.'
  ].join('\n')
  showNotice('Hajj & Umrah', msg, 'info')
}

onMounted(async () => {
  try {
    await load()
  } catch (_) {}

  // Real-time listener for balance updates and notifications
  try {
    const echo = getEcho()
    const userId = dashboardData.value.id
    if (userId) {
      echo.private(`user.${userId}`)
        .listen('UserAccountUpdated', (e) => {
          console.log('Real-time update received:', e)
          
          // 1. Update balances smoothly
          if (e.balances) {
            dashboardData.value.balance = e.balances.wallet
            if (dashboardData.value.kpis) {
              dashboardData.value.kpis.savings_balance = e.balances.savings
              dashboardData.value.kpis.gold_balance = e.balances.gold
              dashboardData.value.kpis.special_savings_balance = e.balances.special_savings
              dashboardData.value.kpis.shares_balance = e.balances.shares
              dashboardData.value.kpis.takaful_balance = e.balances.takaful
              dashboardData.value.kpis.outstanding_fines = e.balances.outstanding_fines
              dashboardData.value.kpis.loan_limit = e.balances.loan_limit
              dashboardData.value.kpis.assalaam_score = e.balances.assalaam_score
            }
          }

          // 2. Show a real-time notification
          if (e.message) {
            showNotice('Real-time Update', e.message, 'success')
            
            // 3. Add to live actions feed
            liveActions.value.unshift({
              id: Date.now(),
              message: e.message,
              time: new Date(e.time || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            })
            // Keep only last 3 to avoid clutter
            if (liveActions.value.length > 3) liveActions.value.pop()

            // 4. Play a subtle sound (Optional)
            try { 
              const audio = new Audio('/sounds/notification.mp3')
              audio.volume = 0.5
              audio.play().catch(() => {}) // Handle browsers blocking autoplay
            } catch (_) {}
          }
        })
    }
  } catch (err) {
    console.error('Failed to initialize real-time listener:', err)
  }

  // Ensure DOM is fully painted and elements are visible before starting tour
  setTimeout(() => {
    try { startDashboardTour() } catch (_) {}
  }, 500)
})
</script>


