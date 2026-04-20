<template>
  <div class="min-h-screen pb-28 overflow-x-hidden bg-slate-50">
    <AppHeader :user="dashboardData" :showSettings="true" />

    <div class="p-4">
      <div id="balance-card" class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-7 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full" />
        <div class="flex items-center gap-2 mb-2 relative z-10">
          <p class="text-emerald-100 text-sm font-medium">Available Balance</p>
          <button @click="toggleBalances()" class="text-lg opacity-80 p-1 rounded-lg hover:bg-white/10 transition-colors" title="Toggle visibility">
            <svg v-if="hideBalances" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
            <p class="text-xs text-emerald-100 font-mono tracking-widest">ID: {{ dashboardData.membership_id }}</p>
            <button @click="copy(dashboardData.membership_id)" class="text-xs text-white/80 underline">Copy</button>
          </div>
          <button @click="$router.push('/wallet')" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">
            + Add Money
          </button>
        </div>
      </div>

      <!-- PIN Warning -->
      <div v-if="dashboardData.kpis && !dashboardData.kpis.has_pin"
           class="mt-4 p-4 rounded-3xl bg-amber-50 border border-amber-200 flex items-center gap-3"
           @click="$router.push('/profile')">
        <div class="text-2xl">🔑</div>
        <div class="flex-1">
          <p class="text-sm font-bold text-amber-900">Transaction PIN not set</p>
          <p class="text-xs text-amber-700">You need a PIN to transfer or withdraw funds.</p>
        </div>
        <div class="text-amber-400">➡️</div>
      </div>

      <!-- Attendance Reminder -->
      <div v-if="dashboardData.kpis && dashboardData.kpis.has_ongoing_meeting"
           class="mt-4 p-4 rounded-3xl bg-emerald-900 text-white flex items-center gap-3 shadow-lg shadow-emerald-200 cursor-pointer"
           @click="$router.push('/attendance')">
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl animate-pulse">📍</div>
        <div class="flex-1">
          <p class="text-sm font-bold">Meeting Ongoing</p>
          <p class="text-[10px] text-white/70 uppercase tracking-widest font-black">Tap to mark attendance</p>
        </div>
        <div class="text-white/40">➡️</div>
      </div>

      <!-- Outstanding Fines Warning -->
      <div v-if="dashboardData.kpis && dashboardData.kpis.outstanding_fines > 0"
           class="mt-4 p-4 rounded-3xl bg-rose-50 border border-rose-200 flex items-center gap-3"
           @click="$router.push('/passbook')">
        <div class="text-2xl">⚠️</div>
        <div class="flex-1">
          <p class="text-sm font-bold text-rose-900">Outstanding Fines: ₦{{ formatMoney(dashboardData.kpis.outstanding_fines) }}</p>
          <p class="text-xs text-rose-700">These will be deducted from your next wallet funding.</p>
        </div>
        <div class="text-rose-400">➡️</div>
      </div>

      <!-- Tahkim Dispute Warning -->
      <div v-if="kpis.active_disputes_count > 0"
           class="mt-4 p-4 rounded-3xl bg-slate-900 text-white flex items-center gap-3 shadow-lg shadow-slate-200 cursor-pointer"
           @click="$router.push('/sharia-board/history')">
        <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center text-xl">⚖️</div>
        <div class="flex-1">
          <p class="text-sm font-bold">Active Tahkim ({{ kpis.active_disputes_count }})</p>
          <p class="text-[10px] text-white/70 uppercase tracking-widest font-black">Sharia Board Mediation in progress</p>
        </div>
        <div class="text-white/40">➡️</div>
      </div>

      <!-- Migration Discrepancy Banner -->
      <div v-if="dashboardData.migration?.discrepancy_reported_at && !dashboardData.migration?.verified_at"
           class="mt-4 p-4 rounded-3xl bg-blue-50 border border-blue-200 flex items-center gap-3">
        <div class="text-2xl">⏳</div>
        <div class="flex-1">
          <p class="text-sm font-bold text-blue-900">Balance Under Review</p>
          <p class="text-xs text-blue-700">You reported a discrepancy. Our officers are currently reconciling your records.</p>
        </div>
      </div>

      <!-- Loan Eligibility & Savings Section -->
      <div class="mt-6 bg-white rounded-[2.5rem] p-7 shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-slate-800 font-bold text-lg">Loan Eligibility</h3>
          <div class="w-10 h-10 bg-emerald-50 rounded-2xl flex items-center justify-center text-xl">💎</div>
        </div>
        
        <div class="flex items-end gap-1 mb-8">
          <span class="text-3xl font-black text-slate-900">₦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.loan_limit) }}</span>
          <span class="text-[10px] text-slate-400 font-bold uppercase mb-2 ml-1 tracking-wider">Max Limit</span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100">
            <p class="text-[10px] text-slate-400 uppercase font-black mb-1">Savings</p>
            <p class="text-sm font-black text-slate-700">₦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.savings_balance) }}</p>
          </div>
          <div class="bg-slate-50 p-4 rounded-3xl border border-slate-100">
            <p class="text-[10px] text-slate-400 uppercase font-black mb-1">Shares</p>
            <p class="text-sm font-black text-slate-700">₦ {{ hideBalances ? '***,***.**' : formatMoney(kpis.shares_balance) }}</p>
          </div>
        </div>
        
        <div class="mt-6 flex items-center gap-3 bg-blue-50/50 p-4 rounded-3xl border border-blue-100/50">
          <div class="text-lg">ℹ️</div>
          <p class="text-[10px] text-blue-700 leading-tight font-medium">
            Your loan limit is determined by your <span class="font-bold">Member Savings</span> and <span class="font-bold">Shares balance</span>, adjusted by your <span class="font-bold">Attaqwa Score</span>.
          </p>
        </div>
      </div>

      <!-- KPI row -->
      <div class="mt-4 grid grid-cols-2 gap-2">
        <StatPill label="Contributions" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.contributions))" hint="Total" intent="success" icon="💰" />
        <StatPill label="Gold Balance" :value="(hideBalances ? '***.**' : kpis.gold_balance?.toFixed(4)) + ' g'" :hint="hideBalances ? '≈ ₦ ***' : (kpis.gold_value_naira ? '≈ ₦ ' + formatMoney(kpis.gold_value_naira) : 'Digital Gold')" intent="warning" icon="🪙" @click="$router.push('/gold')" class="cursor-pointer" />
        <StatPill label="Loans" :value="currency + ' ' + (hideBalances ? '***,***.**' : formatMoney(kpis.loans))" hint="Outstanding" intent="danger" icon="📊" />
        <StatPill label="Attaqwa Score" :value="String(kpis.attaqwa_score || 0)" hint="Credit Rating" intent="info" icon="⭐" @click="$router.push('/profile')" class="cursor-pointer" />
      </div>

      <!-- Trend chart -->
      <FinCard class="mt-4" :padded="true" :elevated="true">
        <template #title>
          Activity Trend
        </template>
        <TrendChart :series="chart.series" :categories="chart.categories" :currency="currency" />
      </FinCard>
    </div>

    <div class="px-4 grid grid-cols-2 gap-4 mt-2">
      <button @click="$router.push('/pay')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">💳</div>
        <span class="text-sm font-bold text-slate-700">Make Payment</span>
      </button>
      <button @click="$router.push('/projects')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-2xl">📦</div>
        <span class="text-sm font-bold text-slate-700">Projects</span>
      </button>
      <button @click="$router.push('/sadaqah')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-rose-50 rounded-2xl flex items-center justify-center text-2xl">🌙</div>
        <span class="text-sm font-bold text-slate-700">Sadaqah</span>
      </button>
      <button @click="$router.push('/attendance')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">📍</div>
        <span class="text-sm font-bold text-slate-700">Attendance</span>
      </button>
      <button @click="$router.push('/savings-groups')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">🤝</div>
        <span class="text-sm font-bold text-slate-700">Group Savings</span>
      </button>
      <button @click="$router.push('/vtu')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">📶</div>
        <span class="text-sm font-bold text-slate-700">Airtime/Data</span>
      </button>
      <button id="loan-btn" @click="$router.push('/loans')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-2xl">📊</div>
        <span class="text-sm font-bold text-slate-700">Loan Records</span>
      </button>
      <button @click="$router.push('/reports')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">📈</div>
        <span class="text-sm font-bold text-slate-700">Reports</span>
      </button>
      <button @click="$router.push('/takaful')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-cyan-50 rounded-2xl flex items-center justify-center text-2xl">🛡️</div>
        <span class="text-sm font-bold text-slate-700">Takaful</span>
      </button>
      <button @click="$router.push('/transparency')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-lime-50 rounded-2xl flex items-center justify-center text-2xl">🧾</div>
        <span class="text-sm font-bold text-slate-700">Transparency</span>
      </button>
      <button @click="$router.push('/store')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center text-2xl">🛒</div>
        <span class="text-sm font-bold text-slate-700">Store</span>
      </button>
      <button @click="$router.push('/gold')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-yellow-50 rounded-2xl flex items-center justify-center text-2xl">🪙</div>
        <span class="text-sm font-bold text-slate-700">Gold Savings</span>
      </button>
      <button @click="$router.push('/merchant/pay')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">📸</div>
        <span class="text-sm font-bold text-slate-700">Pay Merchant</span>
      </button>
      <button @click="$router.push('/merchant/receive')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">🔲</div>
        <span class="text-sm font-bold text-slate-700">Receive QR</span>
      </button>
      <button @click="$router.push('/agm')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-fuchsia-50 rounded-2xl flex items-center justify-center text-2xl">🗳️</div>
        <span class="text-sm font-bold text-slate-700">AGM & Voting</span>
      </button>
      <button @click="checkZakat" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-2xl">🕌</div>
        <span class="text-sm font-bold text-slate-700">Zakat</span>
      </button>
      <button v-if="dashboardData.is_ramadan" @click="payZakatFitr" class="bg-emerald-50 p-5 rounded-3xl shadow-sm border border-emerald-100 flex flex-col items-center gap-2 active:bg-emerald-100 transition-all">
        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl">🥣</div>
        <span class="text-sm font-bold text-emerald-800">Zakat Al-Fitr</span>
      </button>
      <button @click="$router.push('/goals')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">🕋</div>
        <span class="text-sm font-bold text-slate-700">Hajj & Umrah</span>
      </button>
      <button @click="$router.push('/junior-cooperative')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">👶</div>
        <span class="text-sm font-bold text-slate-700">Junior Coop</span>
      </button>
      <button @click="$router.push('/wasiyyah')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">📋</div>
        <span class="text-sm font-bold text-slate-700">Wasiyyah</span>
      </button>
      <button v-if="kpis.vendor && kpis.vendor.is_vendor" @click="$router.push('/vendor/dashboard')" class="bg-emerald-50 p-5 rounded-3xl shadow-sm border border-emerald-100 flex flex-col items-center gap-2 active:bg-emerald-100 transition-all">
        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl">🏪</div>
        <span class="text-sm font-bold text-emerald-800">Vendor Portal</span>
      </button>
      <button v-else @click="$router.push('/vendor/apply')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">🏪</div>
        <span class="text-sm font-bold text-slate-700">Become a Vendor</span>
      </button>
      <button @click="$router.push('/sharia-board')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-2xl">⚖️</div>
        <span class="text-sm font-bold text-slate-700">Sharia Board</span>
      </button>
      <button v-if="dashboardData.is_admin" @click="$router.push('/admin/vendors')" class="bg-rose-50 p-5 rounded-3xl shadow-sm border border-rose-100 flex flex-col items-center gap-2 active:bg-rose-100 transition-all">
        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-2xl">👮</div>
        <span class="text-sm font-bold text-rose-800">Admin Portal</span>
      </button>
    </div>

    <!-- Quick guide links -->
    <div class="px-4 mt-3 text-[12px] text-slate-600">
      <p>
        New here? Learn about
        <button class="text-emerald-700 font-semibold underline" @click="showPassbookInfo">Passbook</button>,
        <button class="text-emerald-700 font-semibold underline" @click="showZakatInfo">Zakat</button>,
        and
        <button class="text-emerald-700 font-semibold underline" @click="showHajjInfo">Hajj & Umrah</button>.
      </p>
    </div>

    <div class="px-4 mt-8">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-slate-800 text-lg">Recent Transactions</h3>
        <button class="text-emerald-700 text-sm font-bold" @click="$router.push('/passbook')">Passbook</button>
      </div>

      <div v-if="dashboardData.transactions?.length" class="space-y-3">
        <div v-for="tx in dashboardData.transactions" :key="tx.id"
             class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 overflow-hidden border border-slate-100 shadow-sm">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div :class="tx.type === 'credit' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"
                 class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
              {{ tx.type === 'credit' ? '+' : '−' }}
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
            <p class="font-bold text-slate-800">₦ {{ hideBalances ? '***,***.**' : formatMoney(tx.amount) }}</p>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-10 text-gray-400">
        <p>No transactions yet.</p>
      </div>

      <!-- Recent Utility Transactions -->
      <div class="flex justify-between items-center mb-4 mt-10">
        <h3 class="font-bold text-slate-800 text-lg">Recent Airtime/Data</h3>
        <button class="text-emerald-700 text-sm font-bold" @click="$router.push('/vtu/history')">See all</button>
      </div>
      <div v-if="dashboardData.utility_transactions?.length" class="space-y-3">
        <div v-for="ux in dashboardData.utility_transactions" :key="ux.id"
             class="bg-white p-4 rounded-2xl flex items-center justify-between gap-3 overflow-hidden border border-slate-100 shadow-sm">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div :class="ux.status === 'success' ? 'bg-emerald-100 text-emerald-600' : (ux.status === 'failed' ? 'bg-rose-100 text-rose-600' : 'bg-yellow-100 text-yellow-600')"
                 class="w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0">
              {{ ux.status === 'success' ? '✓' : (ux.status === 'failed' ? '✕' : '⌛') }}
            </div>
            <div class="min-w-0 overflow-hidden">
              <p class="font-bold text-slate-800 text-sm capitalize truncate max-w-[180px] sm:max-w-none">{{ utilLabel(ux) }}</p>
              <p class="text-[10px] text-gray-500 uppercase font-medium">{{ formatDate(ux.created_at) }}</p>
              <p class="text-[10px] text-slate-400 font-mono truncate">{{ ux.reference }}</p>
            </div>
          </div>
          <div class="text-right shrink-0">
            <p class="font-bold text-slate-800">₦ {{ formatMoney(ux.amount) }}</p>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-6 text-gray-400">
        <p>No VTU activity yet.</p>
      </div>
    </div>

    <!-- Reusable Custom Notice Modal for Zakat/info alerts -->
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
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import { ref, onMounted, computed } from 'vue'
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

const currency = '₦'
const dashboardData = ref({})
const { hideBalances, toggleBalances } = useBalanceVisibility()

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
  return { contributions: totalContrib, loans: outstandingLoans, utilities: utilSpent, attaqwa_score: d.attaqwa_score || 0 }
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
  if (type === 'airtime') return `Airtime — ${net} (${phone})`
  if (type === 'data') return `Data — ${net} (${phone})`
  return `${type || 'utility'} — ${net} (${phone})`
}

const checkMigration = async () => {
  const m = dashboardData.value.migration
  if (!m || !m.migrated_at) return
  if (m.discrepancy_reported_at || m.verified_at) return

  // Show verification modal
  const total = formatMoney(m.total_balance)
  const breakdownLines = Object.entries(m.breakdown || {})
    .filter(([_, val]) => Number(val) > 0)
    .map(([key, val]) => `• ${key}: ${currency} ${formatMoney(val)}`)
    .join('\n')

  const ok = await modal.prompt(
    'Verify Opening Balance',
    `Welcome to Attaqwa Pay. Based on our system migration from paper/Excel records, here is your opening balance breakdown:\n\n${breakdownLines}\n\nTotal: ${currency} ${total}\n\nIs this correct?`,
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
      showNotice('Success', 'Thank you! Your account is now fully verified.', 'success')
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
  
  // Check Migration status
  checkMigration()

  // Show Zakat alert if reached nisab but not yet paid (or simply reached nisab)
  if (data.zakat_status?.reached_nisab) {
    const due = formatMoney(data.zakat_status.zakat_due)
    const nisab = formatMoney(data.zakat_status.nisab)
    
    if (data.zakat_status.eligible) {
      showNotice('Zakat Alert', `Your savings have reached the Nisab. Your Zakat due is ${currency} ${due}.`, 'info')
    } else {
      showNotice('Zakat Update', `Your savings have reached the Nisab. Keep tracking your savings to know when your Zakat becomes due!`, 'info')
    }
  }
}

const logout = async () => {
  try {
    await axios.post('/api/logout')
  } catch (_) {}
  localStorage.removeItem('token')
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

    const payResp = await axios.post('/api/zakat/pay', {}, { headers: { Authorization: `Bearer ${token}` } })
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
    const { data } = await axios.post('/api/zakat/pay-fitr', {}, { headers: { Authorization: `Bearer ${token}` } })
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
    '• See every contribution, withdrawal, loan disbursement/repayment, fines, and adjustments.',
    '• Tap a row to view full details and reference.',
    '• Use filters (date range, scheme/type) to find entries fast.'
  ].join('\n')
  showNotice('Passbook', msg, 'info')
}

const showZakatInfo = () => {
  const msg = [
    'We help you check if Zakat is due and estimate the amount.',
    '• Eligibility: compares your eligible wealth with the Nisab and timing (haul).',
    '• Rate: typically 2.5% on eligible holdings once due.',
    '• Data source: based on balances and assets recorded with the cooperative.',
    'You can run an estimate now and, if due, pay securely in-app.'
  ].join('\n')
  showNotice('Zakat', msg, 'info')
}

const showHajjInfo = () => {
  const msg = [
    'Plan and save towards your Hajj or Umrah journey.',
    '• Set a goal amount and target date on the Goals page.',
    '• Track progress with each deposit and stay on schedule.',
    '• Withdrawals are protected to keep your pilgrimage savings intact.'
  ].join('\n')
  showNotice('Hajj & Umrah', msg, 'info')
}

onMounted(async () => {
  try {
    await load()
  } catch (_) {}
  // Ensure DOM is fully painted and elements are visible before starting tour
  setTimeout(() => {
    try { startDashboardTour() } catch (_) {}
  }, 500)
})
</script>
