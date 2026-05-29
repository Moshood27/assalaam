<template>
  <div class="min-h-screen bg-slate-50 pb-20 font-sans">
    <AppHeader title="Member Terms" :showBack="true" />

    <main class="p-4 space-y-6 max-w-3xl mx-auto">
      <div class="flex items-center justify-between px-2">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Effective: 2026-03-21</p>
        <span class="badge bg-blue-100 text-blue-700">v1.2</span>
      </div>

      <section class="card card-elevated p-6 bg-gradient-to-br from-white to-slate-50">
        <p class="text-slate-700 leading-relaxed">
          These Terms govern your use of the <span class="font-bold">{{ brand.name }}</span> member portal and mobile app. By accessing or using
          these services, you agree to be bound by the Terms below.
        </p>
      </section>

      <div class="space-y-4">
        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">01</span>
            Eligibility and Account
          </h2>
          <ul class="space-y-3">
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              You must be a registered member of {{ brand.name }} to access member features.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              You are responsible for maintaining the confidentiality of your login credentials.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Notify Support immediately if you suspect unauthorized access to your account.
            </li>
          </ul>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">02</span>
            Services
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            Available services may include wallet funding, payments, VTU purchases, savings goals, loans, and AGM voting.
            Features may change from time to time and may be subject to additional policies or guidelines.
          </p>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">03</span>
            Acceptable Use
          </h2>
          <ul class="space-y-3">
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Use the services only for lawful purposes and in accordance with co‐operative rules.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Do not attempt to interfere with the security or integrity of the platform.
            </li>
          </ul>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">04</span>
            Fees and Transactions
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            Certain transactions may attract fees as displayed within the app or communicated prior to completion. You
            authorize us and our partners to process transactions you initiate.
          </p>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">05</span>
            Privacy
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            Our handling of personal data is described in our
            <router-link to="/privacy" class="text-blue-700 font-bold hover:underline">Privacy Policy</router-link>.
          </p>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">06</span>
            Changes
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            We may update these Terms from time to time. Material changes will be communicated within the app or via
            registered contact channels.
          </p>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">07</span>
            Support
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            For questions or concerns, visit the <router-link to="/support" class="text-blue-700 font-bold hover:underline">Support</router-link>
            page or email <a href="mailto:assalaamosogbo@gmail.com" class="text-blue-700 font-bold hover:underline">assalaamosogbo@gmail.com</a>.
          </p>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue'
import brand from '../brand'
</script>


