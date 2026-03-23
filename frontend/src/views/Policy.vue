<template>
  <div class="min-h-screen bg-slate-50 pb-20">
    <header class="p-4 bg-white border-b flex items-center justify-between">
      <h1 class="text-xl font-bold">Member Terms & Policy</h1>
      <button @click="$router.back()" class="text-slate-600">Close</button>
    </header>

    <main class="p-4 space-y-5 max-w-3xl mx-auto">
      <p class="text-xs text-slate-500">Effective date: 2026-03-21</p>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5 space-y-3">
        <p class="text-slate-700">
          These Terms govern your use of the Assalam Co‑operative member portal and mobile app. By accessing or using
          these services, you agree to be bound by the Terms below.
        </p>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">1. Eligibility and Account</h2>
        <ul class="list-disc pl-5 text-slate-700 space-y-1">
          <li>You must be a registered member of Assalam Co‑operative to access member features.</li>
          <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
          <li>Notify Support immediately if you suspect unauthorized access to your account.</li>
        </ul>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">2. Services</h2>
        <p class="text-slate-700">
          Available services may include wallet funding, payments, VTU purchases, savings goals, loans, and AGM voting.
          Features may change from time to time and may be subject to additional policies or guidelines.
        </p>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">3. Acceptable Use</h2>
        <ul class="list-disc pl-5 text-slate-700 space-y-1">
          <li>Use the services only for lawful purposes and in accordance with co‑operative rules.</li>
          <li>Do not attempt to interfere with the security or integrity of the platform.</li>
          <li>Provide accurate information when requested and keep your profile up to date.</li>
        </ul>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">4. Fees and Transactions</h2>
        <p class="text-slate-700">
          Certain transactions may attract fees as displayed within the app or communicated prior to completion. You
          authorize us and our partners to process transactions you initiate.
        </p>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">5. Privacy</h2>
        <p class="text-slate-700">
          Our handling of personal data is described in our
          <router-link to="/privacy" class="text-emerald-700 font-semibold hover:underline">Privacy Policy</router-link>.
        </p>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">6. Changes</h2>
        <p class="text-slate-700">
          We may update these Terms from time to time. Material changes will be communicated within the app or via
          registered contact channels.
        </p>
      </section>

      <section class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
        <h2 class="font-bold text-slate-900 mb-2">7. Support</h2>
        <p class="text-slate-700">
          For questions or concerns, visit the <router-link to="/support" class="text-emerald-700 font-semibold hover:underline">Support</router-link>
          page or email <a href="mailto:support@example.com" class="text-emerald-700 font-semibold hover:underline">support@example.com</a>.
        </p>
      </section>
    </main>
  </div>
</template>

<script setup>
</script>
