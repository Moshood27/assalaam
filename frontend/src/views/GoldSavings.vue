<template>
  <div class="min-h-screen pb-20 bg-slate-50">
    <header class="bg-white border-b border-slate-100 p-4 sticky top-0 z-10 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="$router.back()" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <h1 class="text-xl font-bold text-slate-800">Gold Savings</h1>
      </div>
      <a :href="getExportUrl()" target="_blank" class="p-2 text-slate-500 hover:text-yellow-600 transition-colors" title="Export CSV">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
      </a>
    </header>

    <div class="p-4 max-w-lg mx-auto space-y-4">
      <!-- Feature Disabled Alert -->
      <div v-if="!appStatusStore.features['gold-savings-beta']" class="bg-amber-50 border border-amber-200 p-8 rounded-[2rem] text-center space-y-4 shadow-sm">
        <div class="w-20 h-20 bg-amber-100 rounded-[2.5rem] flex items-center justify-center mx-auto text-4xl shadow-inner">
          ✨
        </div>
        <div>
          <h3 class="text-xl font-black text-slate-800">Exclusive Feature</h3>
          <p class="text-sm text-slate-500 mt-2 leading-relaxed px-4">
            Digital Gold Savings is currently available to a limited number of members during our beta phase. 
            Keep using the app to unlock this feature soon!
          </p>
        </div>
        <button @click="$router.back()" class="w-full bg-slate-800 text-white p-4 rounded-2xl font-bold active:scale-95 transition-all">
          Back to Dashboard
        </button>
      </div>

      <template v-else>
        <!-- Gold Balance Card -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-700 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative z-10">
          <p class="text-yellow-100 text-sm font-medium mb-1">Total Gold Balance</p>
          <div class="flex items-baseline gap-2">
            <h2 class="text-4xl font-black">{{ goldData.gold_balance.toFixed(6) }}</h2>
            <span class="text-lg font-bold">grams</span>
          </div>
          <div class="mt-4 pt-4 border-t border-white/20">
            <p class="text-yellow-100 text-xs mb-1">Current Value</p>
            <p class="text-2xl font-bold">₦ {{ formatMoney(goldData.current_value) }}</p>
          </div>
        </div>
      </div>

      <!-- Price History Chart -->
      <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-2 px-2">
          <h3 class="text-xs font-bold text-slate-400 uppercase">Price Trend (7 Days)</h3>
          <div class="flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
            LIVE
          </div>
        </div>
        <div class="h-40">
          <apexchart 
            type="area" 
            height="160" 
            :options="chartOptions" 
            :series="chartSeries"
          ></apexchart>
        </div>
      </div>

      <!-- Live Price Card -->
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-100 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Market Price</p>
            <p class="text-xl font-black text-slate-800">₦ {{ formatMoney(goldData.base_price) }} <span class="text-sm font-normal text-slate-400">/ gram</span></p>
          </div>
          <button @click="fetchData" :disabled="loading" class="p-2 hover:bg-slate-50 rounded-full transition-colors text-yellow-600">
            <svg xmlns="http://www.w3.org/2000/svg" :class="['h-6 w-6', loading ? 'animate-spin' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Buying At</p>
            <p class="font-bold text-emerald-600">₦ {{ formatMoney(goldData.buy_price) }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Selling At</p>
            <p class="font-bold text-amber-600">₦ {{ formatMoney(goldData.sell_price) }}</p>
          </div>
        </div>
      </div>

      <!-- Performance & Zakat Stats -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Performance Card -->
        <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 flex flex-col justify-between">
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Profit/Loss</p>
            <p :class="['text-lg font-black', goldData.performance.total_profit_loss >= 0 ? 'text-emerald-600' : 'text-rose-600']">
              ₦ {{ formatMoney(goldData.performance.total_profit_loss) }}
            </p>
          </div>
          <div class="mt-2 pt-2 border-t border-slate-50 flex items-center justify-between">
            <span class="text-[10px] text-slate-400 font-bold">ROI</span>
            <span :class="['text-[10px] font-black px-1.5 py-0.5 rounded-md', goldData.performance.roi_percent >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600']">
              {{ goldData.performance.roi_percent > 0 ? '+' : '' }}{{ goldData.performance.roi_percent }}%
            </span>
          </div>
        </div>

        <!-- Zakat Tracker Card (Now with Automated Report) -->
        <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 cursor-pointer hover:bg-slate-50 transition-colors" @click="showZakatReport = true">
          <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Zakat Tracker</p>
          <div class="flex items-end justify-between mb-1">
            <p class="text-lg font-black text-slate-800">{{ goldData.zakat.progress_percent }}%</p>
            <p class="text-[10px] text-slate-400 font-bold">{{ goldData.zakat.nisab_grams }}g</p>
          </div>
          <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
            <div 
              :class="[goldData.zakat.is_eligible ? 'bg-emerald-500' : 'bg-amber-400', 'h-full rounded-full transition-all duration-500']"
              :style="{ width: goldData.zakat.progress_percent + '%' }"
            ></div>
          </div>
          <p class="text-[8px] text-slate-400 mt-2 leading-tight uppercase font-bold flex items-center justify-between">
            <span>{{ goldData.zakat.is_eligible ? 'Nisab Reached (Hawl Active)' : `${goldData.zakat.grams_to_nisab.toFixed(2)}g to Nisab` }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </p>
        </div>
      </div>

      <!-- Zakat Report Modal -->
      <div v-if="showZakatReport" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
          <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-emerald-50/50">
            <h3 class="text-lg font-black text-slate-800">Zakat Al-Maal Report</h3>
            <button @click="showZakatReport = false" class="p-2 hover:bg-white rounded-full transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
            <!-- Eligibility Status -->
            <div :class="['p-4 rounded-2xl border flex items-start gap-3', goldData.zakat.is_eligible ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100']">
              <div :class="['mt-1 w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0', goldData.zakat.is_eligible ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400']">
                <svg v-if="goldData.zakat.is_eligible" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <p class="font-bold text-slate-800 text-sm">
                  {{ goldData.zakat.is_eligible ? 'You are eligible to pay Zakat' : 'Zakat is not yet due' }}
                </p>
                <p class="text-xs text-slate-500 leading-relaxed mt-1">
                  {{ goldData.zakat.is_eligible 
                    ? 'Your wealth has stayed above the Nisab for a full lunar year (Hawl).' 
                    : `Your assets must stay above Nisab (85g Gold) for 354 days. ${goldData.zakat.report.days_since_crossed} days tracked so far.` }}
                </p>
              </div>
            </div>

            <!-- Wealth Breakdown -->
            <div class="space-y-3">
              <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Wealth Breakdown</h4>
              <div class="bg-slate-50 rounded-2xl p-4 space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Savings & Shares</span>
                  <span class="font-bold text-slate-800">₦{{ formatMoney(goldData.zakat.report.savings + goldData.zakat.report.shares) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Gold Value</span>
                  <span class="font-bold text-slate-800">₦{{ formatMoney(goldData.zakat.report.gold_value) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Wallet Balance</span>
                  <span class="font-bold text-slate-800">₦{{ formatMoney(goldData.zakat.report.wallet_balance) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex justify-between">
                  <span class="font-bold text-slate-600">Total Zakatable Assets</span>
                  <span class="font-black text-emerald-600">₦{{ formatMoney(goldData.zakat.report.base) }}</span>
                </div>
              </div>
            </div>

            <!-- Comparison -->
            <div class="flex items-center gap-4 bg-slate-50 rounded-2xl p-4">
              <div class="flex-1 text-center border-r border-slate-200">
                <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Current Assets</p>
                <p class="text-sm font-black text-slate-800">₦{{ formatMoney(goldData.zakat.report.base) }}</p>
              </div>
              <div class="flex-1 text-center">
                <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Current Nisab (85g)</p>
                <p class="text-sm font-black text-amber-600">₦{{ formatMoney(goldData.zakat.report.nisab) }}</p>
              </div>
            </div>

            <!-- Zakat Due -->
            <div class="bg-emerald-600 rounded-2xl p-6 text-white text-center shadow-lg shadow-emerald-100">
              <p class="text-xs font-bold text-emerald-100 uppercase mb-2">Estimated Zakat (2.5%)</p>
              <h2 class="text-3xl font-black mb-1">₦ {{ formatMoney(goldData.zakat.report.zakat_due) }}</h2>
              <p v-if="goldData.zakat.report.last_paid_at" class="text-[10px] text-emerald-200">
                Last paid: {{ formatDateShort(goldData.zakat.report.last_paid_at) }}
              </p>
            </div>

            <!-- Zakat Al-Fitr (Only in Ramadan) -->
            <div v-if="goldData.zakat.report.is_ramadan" class="bg-amber-50 rounded-2xl p-4 border border-amber-100 flex items-center justify-between">
              <div>
                <p class="text-[10px] font-bold text-amber-600 uppercase">Zakat Al-Fitr</p>
                <p class="text-sm font-black text-slate-800">₦ {{ formatMoney(goldData.zakat.report.fitr_amount) }}</p>
              </div>
              <button @click="handlePayFitr" :disabled="loading" class="bg-amber-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-all">
                Pay Fitr
              </button>
            </div>

            <!-- Payment Gateway Selection -->
            <div class="space-y-3">
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Payment Method</label>
              <div class="grid grid-cols-2 gap-2">
                <button 
                  @click="zakatForm.gateway = 'wallet'"
                  :class="['py-2 rounded-xl text-[10px] font-bold border transition-all', zakatForm.gateway === 'wallet' ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-200 text-slate-600']"
                >
                  WALLET
                </button>
                <button 
                  v-for="gw in enabledGateways" 
                  :key="gw"
                  @click="zakatForm.gateway = gw"
                  :class="['py-2 rounded-xl text-[10px] font-bold border transition-all', zakatForm.gateway === gw ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-200 text-slate-600']"
                >
                  {{ gw.toUpperCase() }}
                </button>
              </div>
            </div>

            <!-- Transaction PIN -->
            <div v-if="zakatForm.gateway === 'wallet'">
              <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Transaction PIN</label>
              <input v-model="zakatForm.pin" type="password" maxlength="4" placeholder="••••" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-center text-2xl tracking-[1em] font-bold focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>

          <div class="p-6 bg-slate-50 border-t border-slate-100">
            <button 
              @click="handlePayZakat" 
              :disabled="loading || !goldData.zakat.is_eligible"
              class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-emerald-100 disabled:opacity-50 disabled:grayscale transition-all active:scale-[0.98]"
            >
              {{ loading ? 'Processing...' : 'Pay Zakat Now' }}
            </button>
            <a 
              :href="getZakatReportUrl()" 
              target="_blank"
              class="w-full bg-white text-emerald-600 border border-emerald-200 py-3 rounded-2xl font-bold transition-all active:scale-[0.98] flex items-center justify-center gap-2 no-underline"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Download Report PDF
            </a>
            <p class="text-[10px] text-slate-400 text-center mt-3 px-4">
              Deducts directly from your wallet and moves to the General Zakat Fund.
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-2 gap-4">
        <button @click="activeTab = 'buy'" :class="['py-3 rounded-2xl font-bold transition-all', activeTab === 'buy' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200']">
          Buy Gold
        </button>
        <button @click="activeTab = 'sell'" :class="['py-3 rounded-2xl font-bold transition-all', activeTab === 'sell' ? 'bg-amber-600 text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200']">
          Sell Gold
        </button>
      </div>

      <!-- Forms and History sections remain same... but I'll include them for completeness -->
       <!-- Buy Form -->
      <div v-if="activeTab === 'buy'" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4">
        <h3 class="font-bold text-slate-800">Buy Digital Gold</h3>
        <p class="text-xs text-slate-500">Convert your wallet balance into gold. Minimum ₦1,000.</p>
        
        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Amount in Naira (₦)</label>
          <div class="relative">
            <input v-model="form.amount" type="number" placeholder="e.g. 5000" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-lg font-bold focus:ring-2 focus:ring-emerald-500" />
            <p v-if="form.amount" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-medium">
              ≈ {{ ((form.amount * 0.995) / goldData.buy_price).toFixed(6) }} g
            </p>
          </div>
          <p class="text-[10px] text-slate-400 mt-2 px-1">Includes 0.5% fee: ₦{{ (form.amount * 0.005).toFixed(2) }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Transaction PIN</label>
          <input v-model="form.pin" type="password" maxlength="4" placeholder="••••" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-center text-2xl tracking-[1em] font-bold focus:ring-2 focus:ring-emerald-500" />
        </div>

        <button @click="handleBuy" :disabled="loading || !form.amount || !form.pin" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-emerald-100 disabled:opacity-50 disabled:shadow-none transition-all active:scale-[0.98]">
          {{ loading ? 'Processing...' : `Confirm Purchase` }}
        </button>
      </div>

      <!-- Sell Form -->
      <div v-if="activeTab === 'sell'" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4">
        <h3 class="font-bold text-slate-800">Sell Digital Gold</h3>
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-500">Convert your gold back to Naira.</p>
          <button @click="form.grams = goldData.gold_balance" class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg hover:bg-amber-100 transition-colors">SELL MAX</button>
        </div>
        
        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Amount in Grams (g)</label>
          <div class="relative">
            <input v-model="form.grams" type="number" step="0.000001" placeholder="e.g. 0.05" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-lg font-bold focus:ring-2 focus:ring-amber-500" />
            <p v-if="form.grams" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 font-medium">
              ≈ ₦ {{ formatMoney(form.grams * goldData.sell_price * 0.995) }}
            </p>
          </div>
          <p class="text-[10px] text-slate-400 mt-2 px-1">Est. Credit: ₦{{ formatMoney(form.grams * goldData.sell_price * 0.995) }} (after 0.5% fee)</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Transaction PIN</label>
          <input v-model="form.pin" type="password" maxlength="4" placeholder="••••" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-center text-2xl tracking-[1em] font-bold focus:ring-2 focus:ring-amber-500" />
        </div>

        <button @click="handleSell" :disabled="loading || !form.grams || !form.pin" class="w-full bg-amber-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-amber-100 disabled:opacity-50 disabled:shadow-none transition-all active:scale-[0.98]">
          {{ loading ? 'Processing...' : `Confirm Sale` }}
        </button>
      </div>

      <!-- History -->
      <div class="space-y-6">
        <!-- Gold Transactions -->
        <div class="space-y-3">
          <h3 class="font-bold text-slate-800 px-1">Gold Transactions</h3>
          <div v-if="history.length === 0 && !loading" class="bg-white rounded-3xl p-8 text-center border border-slate-100">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <p class="text-slate-400 text-sm">No gold transactions yet.</p>
          </div>
          
          <div v-for="tx in history" :key="tx.id" class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', tx.amount > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600']">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path v-if="tx.amount > 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                </svg>
              </div>
              <div>
                <p class="font-bold text-slate-800 text-sm">{{ tx.amount > 0 ? 'Bought' : 'Sold' }} Gold</p>
                <p class="text-[10px] text-slate-400">{{ formatDate(tx.created_at) }}</p>
              </div>
            </div>
            <div class="text-right">
              <p :class="['font-bold text-sm', tx.amount > 0 ? 'text-emerald-600' : 'text-amber-600']">
                {{ tx.amount > 0 ? '+' : '' }}{{ Number(tx.units || 0).toFixed(6) }} g
              </p>
              <p class="text-[10px] text-slate-400">₦ {{ formatMoney(Math.abs(tx.amount)) }}</p>
            </div>
          </div>
        </div>

        <!-- Zakat History -->
        <div v-if="zakatHistory.length > 0" class="space-y-3">
          <h3 class="font-bold text-slate-800 px-1">Zakat Purification History</h3>
          <div v-for="zh in zakatHistory" :key="zh.id" class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="font-bold text-slate-800 text-sm">Zakat Paid</p>
                <p class="text-[10px] text-slate-400">{{ formatDate(zh.created_at) }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold text-sm text-emerald-600">₦ {{ formatMoney(zh.amount) }}</p>
              <p class="text-[10px] text-slate-400 uppercase font-medium">Purified</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sunnah Info -->
      <div class="bg-emerald-50 rounded-3xl p-6 border border-emerald-100">
        <h3 class="font-bold text-emerald-800 mb-2 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Why Gold?
        </h3>
        <p class="text-xs text-emerald-700 leading-relaxed italic">
          "The Messenger of Allah (ﷺ) said: A time will come over the people when nothing will be of use except a Dinar and a Dirham (gold and silver)." 
          <span class="block mt-1 font-bold">— Musnad Ahmad</span>
        </p>
        <p class="text-[10px] text-emerald-600/80 mt-3 leading-relaxed">
          Gold is a stable store of value and protects your wealth against inflation. In Islamic tradition, it is considered the most reliable form of currency.
        </p>
      </div>
    </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../http.js'
import { useAppStatusStore } from '../stores/appStatus'

const appStatusStore = useAppStatusStore()
const loading = ref(false)
const activeTab = ref('buy')
const showZakatReport = ref(false)
const zakatHistory = ref([])
const enabledGateways = computed(() => {
  const gws = appStatusStore.paymentGateways || {}
  return Object.keys(gws).filter(k => k !== 'primary' && gws[k])
})
const zakatForm = ref({ pin: '', gateway: appStatusStore.paymentGateways?.primary || 'wallet' })
const goldData = ref({
  gold_balance: 0,
  base_price: 0,
  buy_price: 0,
  sell_price: 0,
  current_value: 0,
  price_history: [],
  performance: {
    total_profit_loss: 0,
    roi_percent: 0
  },
  zakat: {
    progress_percent: 0,
    nisab_grams: 85,
    is_eligible: false,
    grams_to_nisab: 85,
    report: {
      base: 0,
      savings: 0,
      shares: 0,
      gold_value: 0,
      wallet_balance: 0,
      nisab: 0,
      rate: 0.025,
      zakat_due: 0,
      days_since_crossed: 0,
      last_paid_at: null
    }
  }
})
const history = ref([])
const form = ref({
  amount: null,
  grams: null,
  pin: ''
})

// Chart Configuration
const chartSeries = computed(() => [{
  name: 'Price (NGN)',
  data: goldData.value.price_history.map(h => h.price)
}])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    sparkline: { enabled: false },
    zoom: { enabled: false }
  },
  colors: ['#eab308'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.05,
      stops: [20, 100]
    }
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  xaxis: {
    categories: goldData.value.price_history.map(h => {
      const d = new Date(h.date)
      return d.toLocaleDateString('en-US', { weekday: 'short' })
    }),
    labels: { show: true, style: { fontSize: '10px', colors: '#94a3b8' } },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    show: false
  },
  grid: {
    show: false,
    padding: { left: 0, right: 0 }
  },
  dataLabels: { enabled: false },
  tooltip: {
    theme: 'light',
    x: { show: true },
    y: {
      formatter: (val) => `₦${val.toLocaleString()}`
    }
  }
}))

const fetchData = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/gold/price')
    goldData.value = res.data
    
    if (res.data.features) {
      appStatusStore.setFeatures(res.data.features)
    }
    
    const histRes = await axios.get('/api/gold/history')
    history.value = histRes.data.data

    const zakatHistRes = await axios.get('/api/zakat/history')
    zakatHistory.value = zakatHistRes.data
  } catch (err) {
    console.error(err)
    alert('Failed to fetch gold data')
  } finally {
    loading.value = false
  }
}

const handleBuy = async () => {
  if (!confirm(`Confirm buying gold for ₦${form.value.amount}?`)) return
  loading.value = true
  try {
    const res = await axios.post('/api/gold/buy', {
      amount_naira: form.value.amount,
      pin: form.value.pin
    })
    alert(res.data.message)
    form.value = { amount: null, grams: null, pin: '' }
    await fetchData()
  } catch (err) {
    alert(err.response?.data?.message || 'Purchase failed')
  } finally {
    loading.value = false
  }
}

const handleSell = async () => {
  if (!confirm(`Confirm selling ${form.value.grams}g of gold?`)) return
  loading.value = true
  try {
    const res = await axios.post('/api/gold/sell', {
      grams: form.value.grams,
      pin: form.value.pin
    })
    alert(res.data.message)
    form.value = { amount: null, grams: null, pin: '' }
    await fetchData()
  } catch (err) {
    alert(err.response?.data?.message || 'Sale failed')
  } finally {
    loading.value = false
  }
}

const getExportUrl = () => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/gold/export?token=${encodeURIComponent(token)}`
}

const handlePayZakat = async () => {
  if (zakatForm.value.gateway === 'wallet' && !zakatForm.value.pin) {
    alert('Please enter your transaction PIN')
    return
  }
  if (!confirm(`Pay ₦${formatMoney(goldData.value.zakat.report.zakat_due)} Zakat?`)) return
  loading.value = true
  const gateway = zakatForm.value.gateway
  const callback_url = `${window.location.origin}/payment-callback?gateway=${gateway}`
  try {
    const res = await axios.post('/api/zakat/pay', {
      gateway,
      callback_url,
      pin: zakatForm.value.pin
    })

    if (res.data.checkout_url) {
      window.location.href = res.data.checkout_url
      return
    }

    alert(res.data.message)
    showZakatReport.value = false
    zakatForm.value.pin = ''
    await fetchData()
  } catch (err) {
    alert(err.response?.data?.message || 'Zakat payment failed')
  } finally {
    loading.value = false
  }
}

const handlePayFitr = async () => {
  if (zakatForm.value.gateway === 'wallet' && !zakatForm.value.pin) {
    alert('Please enter your transaction PIN')
    return
  }
  if (!confirm(`Pay ₦${formatMoney(goldData.value.zakat.report.fitr_amount)} Zakat Al-Fitr?`)) return
  loading.value = true
  const gateway = zakatForm.value.gateway
  const callback_url = `${window.location.origin}/payment-callback?gateway=${gateway}`
  try {
    const res = await axios.post('/api/zakat/pay-fitr', {
      gateway,
      callback_url,
      pin: zakatForm.value.pin
    })

    if (res.data.checkout_url) {
      window.location.href = res.data.checkout_url
      return
    }

    alert(res.data.message)
    showZakatReport.value = false
    zakatForm.value.pin = ''
    await fetchData()
  } catch (err) {
    alert(err.response?.data?.message || 'Fitr payment failed')
  } finally {
    loading.value = false
  }
}

const getZakatReportUrl = () => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  return `${baseUrl}/api/download-zakat-report?token=${encodeURIComponent(token)}`
}

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateStr) => {
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const formatDateShort = (dateStr) => {
  if (!dateStr) return 'Never'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchData)
</script>