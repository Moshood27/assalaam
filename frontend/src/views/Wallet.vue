<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <AppHeader title="Wallet" :showBack="true" />

    <div class="p-4 space-y-6">
      <!-- Balance Card -->
      <div class="bg-gradient-to-br from-emerald-700 to-emerald-900 rounded-[2rem] p-7 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full" />
        <div class="flex items-center gap-2 mb-2 relative z-10">
          <p class="text-emerald-100 text-sm font-medium">Available Balance</p>
          <button @click="hideBalances = !hideBalances" class="text-lg opacity-80 p-1 rounded-lg hover:bg-white/10 transition-colors">
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
        <h2 class="text-4xl font-bold mt-1 relative z-10">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet.balance) }}</h2>
        <div class="mt-2 text-emerald-100 text-xs flex justify-between gap-2 relative z-10">
          <span>Available for Withdrawal</span>
          <span class="font-bold">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet.available_for_withdrawal || 0) }}</span>
        </div>
        <div class="mt-6 flex gap-2 flex-wrap relative z-10">
          <button @click="goAllocate" class="bg-white/20 hover:bg-white/30 px-4 py-2.5 rounded-xl text-xs font-bold backdrop-blur-md transition-all border border-white/10">Allocate Funds</button>
          <button @click="showFund = !showFund" class="bg-white text-emerald-900 px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-transform active:scale-95">{{ showFund ? 'Hide' : 'Fund Wallet' }}</button>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <button @click="showTransfer = !showTransfer" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
          <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-xl">💸</div>
          <span class="text-xs font-bold text-slate-700">{{ showTransfer ? 'Hide' : 'Transfer' }}</span>
        </button>
        <button @click="showWithdraw = !showWithdraw" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
          <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-xl">🏦</div>
          <span class="text-xs font-bold text-slate-700">{{ showWithdraw ? 'Hide' : 'Withdraw' }}</span>
        </button>
      </div>

      <!-- Merchant Pay (QR) quick access -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden relative">
        <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-full -mr-12 -mt-12 opacity-50" />
        <div class="relative z-10">
          <div class="flex justify-between items-center">
            <h3 class="font-bold text-slate-800">Merchant Pay (QR)</h3>
            <div class="flex gap-2">
              <button @click="$router.push('/merchant/receive')" class="bg-emerald-700 text-white px-3 py-2 rounded-xl text-[10px] font-black uppercase">Receive</button>
              <button @click="$router.push('/merchant/pay')" class="bg-white text-emerald-700 border border-emerald-200 px-3 py-2 rounded-xl text-[10px] font-black uppercase">Pay</button>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Let local shops accept Attaqwa Pay. Generate a QR to receive or scan a merchant's QR to pay.</p>
        </div>
      </div>

      <!-- Virtual Account Info -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-bold text-slate-800">Bank Transfer Account</h3>
          <button v-if="!wallet.virtual_account?.account_number" @click="assignVirtualAccount" :disabled="assigning || (!!bvn && !bvnValid)"
                  class="text-[10px] font-black uppercase bg-emerald-700 text-white px-3 py-2 rounded-xl disabled:opacity-50">
            {{ assigning ? 'Creating…' : 'Generate' }}
          </button>
        </div>
        <div v-if="wallet.virtual_account?.account_number" class="space-y-4">
          <div class="bg-slate-50 p-4 rounded-2xl border border-dashed border-slate-200">
            <div class="flex justify-between items-center mb-2">
              <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Bank Name</span>
              <span class="font-bold text-slate-800 text-sm">{{ wallet.virtual_account.bank_name }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Account Name</span>
              <span class="font-bold text-slate-800 text-sm">{{ wallet.virtual_account.account_name }}</span>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
              <div>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Account Number</p>
                <p class="font-black text-emerald-700 text-xl tracking-wider">{{ wallet.virtual_account.account_number }}</p>
              </div>
              <button @click="copy(wallet.virtual_account.account_number)" class="bg-emerald-50 text-emerald-700 p-2 rounded-lg hover:bg-emerald-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
              </button>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 text-center px-4">Transfer funds to this account to top up your wallet instantly.</p>
          <p class="text-[10px] text-rose-500 font-bold text-center mt-2">Note: A maintenance charge of {{ wallet?.maintenance_charge_config?.percentage || 0.1 }}% (max ₦{{ wallet?.maintenance_charge_config?.max_amount || 500 }}) applies.</p>
        </div>

        <!-- Administrative Charges Section -->
        <div v-if="wallet.admin_charge_balance > 0" class="bg-rose-50 p-6 rounded-[2rem] border border-rose-100 shadow-sm relative overflow-hidden">
          <div class="absolute right-0 top-0 w-24 h-24 bg-rose-100 rounded-full -mr-12 -mt-12 opacity-50" />
          <div class="relative z-10 flex justify-between items-center">
            <div>
              <h3 class="font-bold text-rose-900">Administrative Charges</h3>
              <p class="text-2xl font-black text-rose-700 mt-1">₦ {{ formatMoney(wallet.admin_charge_balance) }}</p>
              <p class="text-[10px] text-rose-500 font-bold uppercase mt-1 tracking-wider">Accumulated Balance</p>
            </div>
            <button @click="payAdminCharge" :disabled="payingAdminCharge" 
                    class="bg-rose-600 text-white px-5 py-3 rounded-2xl text-xs font-black uppercase shadow-lg active:scale-95 disabled:opacity-50 transition-all">
              {{ payingAdminCharge ? 'Paying…' : 'Pay Now' }}
            </button>
          </div>
          <p class="text-[11px] text-rose-600 mt-3 leading-relaxed opacity-80 italic">A monthly administrative charge of ₦300 applies. You can enable auto-deduction in settings.</p>
        </div>
        <div v-else class="space-y-3">
          <p class="text-sm text-slate-500">No virtual account yet. Generate one to fund via bank transfer.</p>
          <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">BVN (optional)</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-600 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm5 3h-3a2 2 0 01-2-2V5" />
                </svg>
              </div>
              <input v-model="bvn" type="tel" inputmode="numeric" maxlength="11" placeholder="11-digit BVN"
                     class="w-full bg-slate-50 pl-11 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>
            <p v-if="bvn && !bvnValid" class="text-rose-600 text-[10px] font-bold">Please enter a valid 11-digit BVN.</p>
            <p class="text-[10px] text-slate-400 leading-tight">Providing your BVN helps us verify your dedicated account faster.</p>
          </div>
        </div>
      </div>

      <!-- Card Top-up Form -->
      <div v-if="showFund" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 transition-all">
        <h3 class="font-bold text-slate-800 mb-4">Fund Wallet (Card)</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Amount to Fund</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-600 text-slate-400 font-bold">₦</div>
              <input v-model.number="topupAmount" type="number" min="1" placeholder="0.00"
                     class="w-full bg-slate-50 pl-11 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
            </div>

            <!-- Maintenance Charge Display -->
            <div v-if="topupAmount > 0 && wallet?.maintenance_charge_config" class="mt-3 p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
              <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                <span class="text-slate-500">System Maintenance Charge</span>
                <span class="text-rose-600">₦ {{ formatMoney(calculatedCharge) }}</span>
              </div>
              <div class="flex justify-between text-xs font-black uppercase tracking-wider pt-2 border-t border-slate-200">
                <span class="text-slate-800">Net Credit to Wallet</span>
                <span class="text-emerald-700 font-black">₦ {{ formatMoney(Math.max(0, topupAmount - calculatedCharge)) }}</span>
              </div>
            </div>
          </div>
          <button @click="initTopup" :disabled="loading || !topupAmount"
                  class="w-full bg-emerald-700 text-white p-4 rounded-2xl font-bold shadow-lg shadow-emerald-700/20 disabled:opacity-50 transition-all active:scale-[0.98]">
            {{ loading ? 'Processing…' : 'Proceed to Payment' }}
          </button>
        </div>
        <p class="mt-3 text-[10px] text-slate-500 text-center">Powered by Paystack. Securely top up using your debit card.</p>
      </div>

      <!-- P2P Transfer Form -->
      <div v-if="showTransfer" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 transition-all">
        <h3 class="font-bold text-slate-800 mb-4">Transfer to Member</h3>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <button v-for="type in ['phone', 'membership']" :key="type"
                    @click="toType = type"
                    :class="toType === type ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-slate-50 text-slate-600 border-slate-100'"
                    class="p-3 rounded-xl border text-[10px] font-black uppercase tracking-wider transition-all">
              {{ type === 'phone' ? 'Phone' : 'Member ID' }}
            </button>
          </div>

          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
              {{ toType === 'phone' ? 'Phone Number' : 'Membership ID' }}
            </label>
            <div class="flex gap-2">
              <input v-model="toValue" type="text"
                     class="flex-1 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:border-emerald-500 transition-all"
                     :placeholder="toType === 'phone' ? 'e.g. 0803...' : 'e.g. MEM123'" />
              <button @click="checkRecipient" type="button" class="shrink-0 bg-emerald-50 text-emerald-700 px-5 rounded-2xl text-[10px] font-black uppercase tracking-wider hover:bg-emerald-100 transition-colors">
                Verify
              </button>
            </div>
          </div>

          <div v-if="toType === 'membership'" class="space-y-4">
            <div>
              <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Branch ID (Optional)</label>
              <input v-model.number="branchId" type="number" min="1"
                     class="w-full bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:border-emerald-500 transition-all"
                     placeholder="ID if known" />
            </div>

            <!-- Recipient preview / disambiguation -->
            <div v-if="recipient" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-emerald-700 text-white flex items-center justify-center font-bold">
                {{ recipient.name[0] }}
              </div>
              <div class="min-w-0">
                <p class="text-[10px] font-black text-emerald-800 uppercase tracking-widest leading-none mb-1">Recipient Found</p>
                <p class="text-sm font-bold text-slate-800 truncate">{{ recipient.name }}</p>
                <p class="text-[10px] text-emerald-600 font-medium">{{ recipient.membership_number }} • {{ recipient.branch_name }}</p>
              </div>
            </div>

            <div v-else-if="recipientError" class="p-4 rounded-2xl bg-amber-50 border border-amber-100 space-y-3">
              <p class="text-xs font-bold text-amber-800">{{ recipientError }}</p>
              <div v-if="branchesOptions.length" class="flex flex-wrap gap-2">
                <button v-for="b in branchesOptions" :key="b.id" type="button" @click="chooseBranch(b)"
                        class="px-3 py-1.5 rounded-lg bg-white border border-amber-200 text-amber-700 text-[10px] font-bold uppercase hover:bg-amber-100 transition-colors">
                  {{ b.name }}
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Amount</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold">₦</div>
              <input v-model.number="transferAmount" type="number" min="1" placeholder="0.00"
                     class="w-full bg-slate-50 pl-11 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:border-emerald-500 transition-all" />
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Note (Optional)</label>
            <input v-model="note" type="text" maxlength="120"
                   class="w-full bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:border-emerald-500 transition-all"
                   placeholder="Purpose of transfer" />
          </div>

          <button @click="startTransfer" :disabled="loading || !canSend"
                  class="w-full bg-emerald-700 text-white p-4 rounded-2xl font-bold shadow-lg shadow-emerald-700/20 disabled:opacity-50 transition-all active:scale-[0.98]">
            {{ loading ? 'Transferring…' : 'Send Funds' }}
          </button>
          <p class="text-[10px] text-slate-500 text-center">Confirmation with Transaction PIN or Biometrics required.</p>
        </div>
      </div>

      <!-- Withdraw to Bank Form -->
      <div v-if="showWithdraw" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 transition-all">
        <h3 class="font-bold text-slate-800 mb-2">Withdraw to Bank</h3>
        <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Withdrawals are sent to your verified bank account in Profile settings.</p>
        <div class="space-y-4">
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Amount</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold">₦</div>
              <input v-model.number="withdrawAmount" type="number" min="1" :max="Number(wallet?.available_for_withdrawal || 0)"
                     class="w-full bg-slate-50 pl-11 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:border-emerald-500 transition-all"
                     placeholder="0.00" />
            </div>
            <div class="mt-2 flex justify-between items-center px-1">
              <span class="text-[10px] text-slate-400 font-bold uppercase">Available</span>
              <span class="text-[10px] text-emerald-700 font-black">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.available_for_withdrawal || 0) }}</span>
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Note (Optional)</label>
            <input v-model="withdrawNote" type="text" maxlength="200"
                   class="w-full bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm outline-none focus:border-emerald-500 transition-all"
                   placeholder="Purpose of withdrawal" />
          </div>
          <button @click="startWithdraw" :disabled="loading || !canWithdraw"
                  class="w-full bg-emerald-700 text-white p-4 rounded-2xl font-bold shadow-lg shadow-emerald-700/20 disabled:opacity-50 transition-all active:scale-[0.98]">
            {{ loading ? 'Submitting…' : 'Request Cashout' }}
          </button>
          <p class="text-[10px] text-slate-500 text-center">Confirmation with Transaction PIN required.</p>
        </div>
      </div>

      <!-- Withdrawal Breakdown -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-slate-50 rounded-full -mr-16 -mt-16 opacity-50" />
        <h3 class="font-bold text-slate-800 mb-4 relative z-10">Withdrawal Breakdown</h3>
        <div class="space-y-3 relative z-10">
          <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50/50 border border-slate-100">
            <span class="text-slate-500 text-[10px] font-black uppercase tracking-wider">Credits (Withdrawable)</span>
            <span class="font-bold text-slate-800 text-sm">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.credits_withdrawable || 0) }}</span>
          </div>
          <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50/50 border border-slate-100">
            <span class="text-slate-500 text-[10px] font-black uppercase tracking-wider">Credits (Restricted)</span>
            <span class="font-bold text-slate-800 text-sm">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.credits_restricted || 0) }}</span>
          </div>
          <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50/50 border border-slate-100">
            <span class="text-slate-500 text-[10px] font-black uppercase tracking-wider">Total Debits</span>
            <span class="font-bold text-rose-600 text-sm">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.total_debits || 0) }}</span>
          </div>
          <div class="flex justify-between items-center p-4 rounded-xl bg-emerald-50 border border-emerald-100 mt-2">
            <span class="text-emerald-800 text-[10px] font-black uppercase tracking-wider">Net Withdrawable</span>
            <span class="font-black text-emerald-700 text-lg">₦ {{ hideBalances ? '***,***.**' : formatMoney(wallet?.breakdown?.remaining_withdrawable || 0) }}</span>
          </div>
        </div>
        <p class="text-[10px] text-slate-400 mt-4 leading-relaxed italic">Restricted funds (e.g. loan disbursements) can be spent on utilities/store but cannot be withdrawn to bank unless unlocked.</p>
      </div>

      <!-- Your Withdrawal Requests -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-5 gap-2 flex-wrap">
          <h3 class="font-bold text-slate-800">Withdrawal Requests</h3>
          <button @click="loadMoreWithdrawals" class="text-emerald-700 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition-colors">Load more</button>
        </div>
        <div v-if="withdrawals.length" class="space-y-4">
          <div v-for="wr in withdrawals" :key="wr.id" class="group border border-slate-100 rounded-2xl p-4 active:bg-slate-50 transition-colors">
            <div class="flex items-center justify-between gap-3 mb-2">
              <div class="min-w-0">
                <p class="text-base font-black text-slate-800">₦ {{ formatMoney(wr.amount) }}</p>
                <p class="text-[10px] uppercase font-mono text-slate-400 tracking-tighter">REF: {{ wr.reference }}</p>
              </div>
              <div class="shrink-0 flex items-center gap-2">
                <span :class="statusClass(wr.status)" class="text-[10px] font-black uppercase px-2 py-1 rounded-lg tracking-wider">{{ wr.status }}</span>
                <button v-if="wr.status === 'pending'" @click="cancelWithdrawal(wr)" class="text-rose-700 text-[10px] font-black uppercase px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 transition-colors">Cancel</button>
              </div>
            </div>
            <div class="flex items-center justify-between mt-1 pt-2 border-t border-slate-50">
              <p class="text-[10px] text-slate-400 font-medium">{{ new Date(wr.created_at).toLocaleDateString() }} • {{ new Date(wr.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
              <p v-if="wr.bank?.account_number || wr.account_number" class="text-[10px] text-slate-400 font-bold uppercase truncate max-w-[140px]">
                {{ wr.bank?.bank_name || wr.bank_name }}
              </p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-6">
          <p class="text-xs text-slate-400">No withdrawal requests found.</p>
        </div>
      </div>

      <!-- Recent Wallet Transactions -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-5 gap-2 flex-wrap">
          <h3 class="font-bold text-slate-800">Transaction History</h3>
          <button @click="loadMore" class="text-emerald-700 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition-colors">View All</button>
        </div>
        <div v-if="transactions.length" class="space-y-4">
          <div v-for="tx in transactions" :key="tx.id" class="flex items-center justify-between gap-3 group">
            <div class="flex items-center gap-3 min-w-0">
              <div :class="tx.type === 'credit' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"
                   class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform group-active:scale-90">
                <svg v-if="tx.type === 'credit'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate leading-none mb-1">{{ titleFor(tx) }}</p>
                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tighter leading-relaxed">
                  {{ new Date(tx.created_at).toLocaleDateString() }} • {{ tx.reference.slice(0, 12) }}...
                </p>
                <div v-if="tx.meta?.maintenance_charge" class="mt-1 flex gap-2 text-[9px] font-bold text-slate-500 uppercase tracking-tighter bg-slate-50 px-2 py-1 rounded-lg w-fit">
                  <span>Gross: ₦{{ formatMoney(tx.meta.gross_amount) }}</span>
                  <span class="text-rose-600">Fee: ₦{{ formatMoney(tx.meta.maintenance_charge) }}</span>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0">
              <p class="font-black text-sm" :class="tx.type === 'credit' ? 'text-emerald-700' : 'text-slate-800'">
                {{ tx.type === 'credit' ? '+' : '-' }}₦{{ formatMoney(tx.amount) }}
              </p>
              <a :href="getReceiptDownloadUrl(tx)" target="_blank" class="text-emerald-700 text-[9px] font-black uppercase tracking-widest hover:underline">Receipt</a>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <p class="text-xs text-slate-400 font-medium">No transactions recorded yet.</p>
        </div>
      </div>

      <!-- Reusable Notice Modal -->
      <CustomNotice
        v-model="notice.visible"
        :type="notice.type"
        :title="notice.title"
        :message="notice.message"
        @close="closeNotice"
      />

      <!-- PIN Prompt Modal for Transfers -->
      <CustomNotice
        v-model="pinPrompt.visible"
        :type="'info'"
        :title="pinPrompt.title || 'Confirm'"
        :message="pinPrompt.message || 'Enter your 4-digit Transaction PIN to proceed.'"
        :prompt="true"
        inputLabel="Transaction PIN (4 digits)"
        :confirmText="pinPrompt.confirmText || 'Confirm'"
        cancelText="Cancel"
        :busy="loading"
        @confirm="handlePinConfirm"
        @cancel="handlePinCancel"
      />
    </div>

    <AppBottomNav />
  </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
import { ref, onMounted, computed, watch } from 'vue'
import axios from '../http.js'
import { useRouter } from 'vue-router'
import { openBlob } from '../utils/download'
import { useBalanceVisibility } from '../composables/useBalanceVisibility'
import CustomNotice from '../components/CustomNotice.vue'
import { useNotice } from '../composables/useNotice'
import { verifyBiometricIdentity, isBiometricAvailable } from '../services/biometric'

const router = useRouter()
const baseRaw = import.meta?.env?.BASE_URL || '/'
const basePath = (baseRaw && baseRaw.startsWith('./')) ? '/' : (baseRaw.endsWith('/') ? baseRaw : `${baseRaw}/`)
const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))

// Notices
const { notice, showNotice, closeNotice } = useNotice()

// Balance visibility
const { hideBalances } = useBalanceVisibility()

const wallet = ref({ balance: 0, virtual_account: {}, admin_charge_balance: 0 })
const transactions = ref([])
const page = ref(1)
const perPage = 10

// Administrative Charges state
const payingAdminCharge = ref(false)
const payAdminCharge = async () => {
  if (wallet.value.admin_charge_balance <= 0) return
  if (wallet.value.balance < wallet.value.admin_charge_balance) {
    showNotice('Insufficient wallet balance to pay administrative charge.', 'error')
    return
  }
  
  if (!confirm(`Are you sure you want to pay ₦${formatMoney(wallet.value.admin_charge_balance)} for outstanding administrative charges?`)) return

  payingAdminCharge.value = true
  try {
    const resp = await axios.post('/api/wallet/admin-charge/pay')
    showNotice(resp.data.message || 'Paid successfully', 'success')
    await loadWallet()
  } catch (e) {
    console.error(e)
    showNotice(e.response?.data?.message || 'Failed to pay administrative charge', 'error')
  } finally {
    payingAdminCharge.value = false
  }
}

// Withdrawal requests listing
const withdrawals = ref([])
const withdrawalsPage = ref(1)
const withdrawalsPerPage = 10
const withdrawalsLastPage = ref(1)
const topupAmount = ref('')
const loading = ref(false)
const calculatedCharge = computed(() => {
  const amount = Number(topupAmount.value || 0)
  if (!amount || !wallet.value?.maintenance_charge_config) return 0
  const { percentage, max_amount } = wallet.value.maintenance_charge_config
  const charge = (amount * (percentage / 100))
  return Math.min(charge, max_amount)
})
const assigning = ref(false)
const showFund = ref(true)
const showTransfer = ref(false)
const showWithdraw = ref(false)

// Withdraw to bank form state
const withdrawAmount = ref('')
const withdrawNote = ref('')

// P2P transfer form state
const toType = ref('phone') // 'phone' | 'membership'
const toValue = ref('')
const branchId = ref('')
const transferAmount = ref('')
const note = ref('')

// Recipient resolution state
const recipient = ref(null)
const recipientError = ref('')
const branchesOptions = ref([])

// Notice modal (shared)

// PIN prompt modal state
const pinPrompt = ref({ visible: false, mode: 'transfer', title: '', message: '', confirmText: '' })

// Optional BVN input before generating a virtual account
const bvn = ref('')
const bvnDigits = computed(() => String(bvn.value || '').replace(/\D/g, ''))
const bvnValid = computed(() => bvnDigits.value.length === 11)

const formatMoney = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })
const canSend = computed(() => {
  const amtOk = Number(transferAmount.value || 0) > 0
  const hasTo = String(toValue.value || '').trim().length > 0
  if (!amtOk || !hasTo) return false
  if (toType.value === 'membership') {
    if (branchesOptions.value.length) return false // force disambiguation
    if (recipient.value) return true
    if (Number(branchId.value)) return true
    // allow if backend won’t detect ambiguity; safer to require Verify
    return false
  }
  return true
})
const canWithdraw = computed(() => {
  const amt = Number(withdrawAmount.value || 0)
  const available = Number(wallet.value?.available_for_withdrawal || 0)
  return amt > 0 && amt <= available
})
const statusClass = (status) => {
  if (status === 'paid') return 'bg-emerald-100 text-emerald-700'
  if (status === 'declined') return 'bg-rose-100 text-rose-700'
  return 'bg-amber-100 text-amber-700'
}
const titleFor = (tx) => {
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
  return 'Wallet Top-up'
}

const loadWallet = async () => {
  const { data } = await axios.get('/api/wallet')
  wallet.value = data
  // Prefer server-provided recent list
  transactions.value = data.recent_transactions || []
}

const resetWithdrawals = async () => {
  withdrawalsPage.value = 1
  withdrawals.value = []
  await loadWithdrawals()
}

const loadWithdrawals = async () => {
  const { data } = await axios.get(`/api/wallet/withdrawals?page=${withdrawalsPage.value}&per_page=${withdrawalsPerPage}`)
  const items = Array.isArray(data?.data) ? data.data : []
  withdrawalsLastPage.value = Number(data?.last_page || 1)
  if (withdrawalsPage.value === 1) withdrawals.value = items
  else withdrawals.value = withdrawals.value.concat(items)
}

const loadMoreWithdrawals = async () => {
  if (withdrawalsPage.value < withdrawalsLastPage.value) {
    withdrawalsPage.value += 1
    await loadWithdrawals()
  }
}

const loadMore = async () => {
  const { data } = await axios.get(`/api/wallet/transactions?page=${page.value + 1}&per_page=${perPage}`)
  if (data?.data?.length) {
    page.value += 1
    transactions.value = transactions.value.concat(data.data)
  }
}

const initTopup = async () => {
  try {
    loading.value = true
        // Build callback URL only for web; on native apps, omit to avoid invalid localhost redirects
    const cb = !isNative ? (new URL(router.resolve({ name: 'wallet.callback' }).href, window.location.origin).toString()) : null
    const payload = { amount: Number(topupAmount.value) }
    if (cb) payload.callback_url = cb
    const { data } = await axios.post('/api/wallet/topup/initiate', payload)
    window.location.href = data.checkout_url
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to start top-up')
  } finally {
    loading.value = false
  }
}

const assignVirtualAccount = async () => {
  try {
    assigning.value = true
    const payload = {}
    if (bvnDigits.value.length === 11) payload.bvn = bvnDigits.value
    const { data } = await axios.post('/api/virtual-account/assign', payload)
    const assigned = Boolean(data?.bvn_assigned ?? true)
    try { localStorage.setItem('bvn_assigned', JSON.stringify(assigned)) } catch (_) {}
    await loadWallet()
    bvn.value = ''
    alert('Virtual account generated!')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to generate virtual account')
  } finally {
    assigning.value = false
  }
}

const copy = async (text) => {
  try { await navigator.clipboard.writeText(String(text || '')); alert('Copied'); } catch (_) {}
}

const goAllocate = () => {
  // Send user to make payment page; they can toggle wallet allocation there
  router.push({ name: 'pay' })
}

// Start P2P transfer: biometric check then prompt for PIN
const startTransfer = async () => {
  if (!toValue.value || !transferAmount.value || Number(transferAmount.value) <= 0) {
    showNotice('Incomplete', 'Please enter a valid recipient and amount.', 'warning')
    return
  }
  if (toType.value === 'membership') {
    if (branchesOptions.value.length) {
      showNotice('Select Branch', 'Multiple members found. Please select the correct branch.', 'warning')
      return
    }
    if (!recipient.value && !Number(branchId.value)) {
      showNotice('Verify Recipient', 'Please tap Verify to confirm the recipient or provide a Branch ID.', 'warning')
      return
    }
  }
  try {
    const bioAvailable = await isBiometricAvailable()
    if (bioAvailable) {
      const ok = await verifyBiometricIdentity({
        reason: 'Authorize transfer',
        description: `Send ₦ ${Number(transferAmount.value).toLocaleString()} to ${toType.value === 'phone' ? 'phone' : 'member'} ${toValue.value}`,
      })
      if (!ok) {
        showNotice('Authentication required', 'Biometric verification was cancelled or failed. Unable to send transfer.', 'warning')
        return
      }
    }
  } catch (e) {
    // If biometric check throws, allow fallback to PIN only
  }
  pinPrompt.value.mode = 'transfer'
  pinPrompt.value.title = 'Confirm Transfer'
  pinPrompt.value.message = 'Enter your 4-digit Transaction PIN to authorize this transfer.'
  pinPrompt.value.confirmText = 'Send'
  pinPrompt.value.visible = true
}

// Start Withdraw: biometric check then prompt for PIN
const startWithdraw = async () => {
  const amt = Number(withdrawAmount.value || 0)
  const available = Number(wallet.value?.available_for_withdrawal || 0)
  if (!(amt > 0)) {
    showNotice('Enter amount', 'Please enter a valid withdrawal amount.', 'warning')
    return
  }
  if (amt > available) {
    showNotice('Too high', 'Amount exceeds your available-for-withdrawal balance.', 'error')
    return
  }
  try {
    const bioAvailable = await isBiometricAvailable()
    if (bioAvailable) {
      const ok = await verifyBiometricIdentity({
        reason: 'Authorize withdrawal',
        description: `Withdraw ₦ ${amt.toLocaleString()} to your saved bank account`,
      })
      if (!ok) {
        showNotice('Authentication required', 'Biometric verification was cancelled or failed. Unable to request withdrawal.', 'warning')
        return
      }
    }
  } catch (e) {
    // allow fallback to PIN
  }
  pinPrompt.value.mode = 'withdraw'
  pinPrompt.value.title = 'Confirm Withdrawal'
  pinPrompt.value.message = 'Enter your 4-digit Transaction PIN to request this withdrawal.'
  pinPrompt.value.confirmText = 'Request'
  pinPrompt.value.visible = true
}

const handlePinConfirm = async (val) => {
  const pin = String(val || '').trim()
  if (!/^\d{4}$/.test(pin)) {
    showNotice('Invalid PIN', 'Please enter a valid 4-digit Transaction PIN.', 'error')
    return
  }
  loading.value = true
  try {
    if (pinPrompt.value.mode === 'withdraw') {
      const payload = { amount: Number(withdrawAmount.value), pin }
      const n = String(withdrawNote.value || '').trim()
      if (n) payload.note = n
      const { data } = await axios.post('/api/wallet/withdraw', payload)
      pinPrompt.value.visible = false
      // Reset form
      withdrawAmount.value = ''
      withdrawNote.value = ''
      // Refresh wallet & withdrawals
      await loadWallet()
      await resetWithdrawals()
      showNotice('Success', 'Withdrawal request submitted.', 'success')
    } else {
      const payload = {
        to_type: toType.value,
        to: String(toValue.value || '').trim(),
        amount: Number(transferAmount.value),
        pin,
      }
      const n = String(note.value || '').trim()
      if (n) payload.note = n
      if (toType.value === 'membership' && Number(branchId.value)) payload.branch_id = Number(branchId.value)

      await axios.post('/api/wallet/transfer', payload)

      pinPrompt.value.visible = false
      // Reset form
      toValue.value = ''
      branchId.value = ''
      transferAmount.value = ''
      note.value = ''
      // Refresh wallet & transactions
      await loadWallet()
      showNotice('Success', 'Transfer sent successfully.', 'success')
    }
  } catch (e) {
    pinPrompt.value.visible = false
    const status = e?.response?.status
    const defaultMsg = pinPrompt.value.mode === 'withdraw' ? 'Withdrawal failed' : 'Transfer failed'
    const msg = e?.response?.data?.message || defaultMsg
    if (status === 409) {
      showNotice('Set PIN', 'You need to set your Transaction PIN first. Go to Profile > Transaction PIN.', 'warning')
    } else if (status === 403) {
      showNotice('Invalid PIN', 'Your Transaction PIN is incorrect. Please try again.', 'error')
    } else if (status === 422 && pinPrompt.value.mode === 'withdraw' && (String(msg).toLowerCase().includes('bank details'))) {
      showNotice('Bank details required', 'Please add and verify your bank details in Profile > Bank Settings to withdraw to bank.', 'warning')
      try {
        if (window.confirm('Open Bank Settings now?')) router.push('/profile')
      } catch (_) {}
    } else if (status === 404 && pinPrompt.value.mode !== 'withdraw') {
      showNotice('Recipient not found', 'We could not find a member matching those details.', 'error')
    } else {
      showNotice('Failed', msg, 'error')
    }
  } finally {
    loading.value = false
  }
}

const handlePinCancel = () => {
  pinPrompt.value.visible = false
}

// Resolve recipient preview
const checkRecipient = async () => {
  recipient.value = null
  recipientError.value = ''
  branchesOptions.value = []
  const v = String(toValue.value || '').trim()
  if (!v) return
  try {
    const params = { to_type: toType.value, to: v }
    if (toType.value === 'membership' && Number(branchId.value)) params.branch_id = Number(branchId.value)
    const { data } = await axios.get('/api/wallet/transfer/resolve', { params })
    recipient.value = data
  } catch (e) {
    const status = e?.response?.status
    if (status === 422 && e?.response?.data?.multiple) {
      recipientError.value = e?.response?.data?.message || 'Multiple members found. Please select a branch.'
      branchesOptions.value = Array.isArray(e?.response?.data?.branches) ? e.response.data.branches : []
    } else {
      recipientError.value = e?.response?.data?.message || 'Recipient not found'
    }
  }
}

const getReceiptDownloadUrl = (tx) => {
  const token = localStorage.getItem('token')
  const baseUrl = axios.defaults.baseURL || ''
  const id = tx?.id ?? tx
  return `${baseUrl}/api/wallet/transactions/${id}/receipt?token=${encodeURIComponent(token)}`
}

watch([toType, toValue, branchId], () => {
  recipient.value = null
  recipientError.value = ''
  branchesOptions.value = []
})

onMounted(async () => {
  await loadWallet()
  await resetWithdrawals()
})

const cancelWithdrawal = async (wr) => {
  if (!wr || !wr.id) return
  try {
    if (!window.confirm('Cancel this withdrawal request?')) return
    await axios.post(`/api/wallet/withdrawals/${wr.id}/cancel`)
    showNotice('Cancelled', 'Withdrawal request cancelled.', 'success')
    await resetWithdrawals()
  } catch (e) {
    const status = e?.response?.status
    if (status === 404) {
      showNotice('Not found', 'This withdrawal request could not be found.', 'error')
    } else if (status === 422) {
      showNotice('Unable to cancel', e?.response?.data?.message || 'Only pending requests can be cancelled.', 'warning')
    } else {
      showNotice('Failed', e?.response?.data?.message || 'Could not cancel request. Please try again later.', 'error')
    }
  }
}
</script>
