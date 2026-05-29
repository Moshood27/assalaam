<template>
  <div class="min-h-screen bg-slate-50 pb-20 font-sans">
    <AppHeader title="Privacy Policy" :showBack="true" />

    <main class="p-4 space-y-6 max-w-3xl mx-auto">
      <div class="flex items-center justify-between px-2">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Last updated: 2026-04-15</p>
        <span class="badge bg-blue-100 text-blue-700">v1.2</span>
      </div>

      <section class="card card-elevated p-6 bg-gradient-to-br from-white to-slate-50">
        <p class="text-slate-700 leading-relaxed">
          We value your privacy. This Privacy Policy explains what information we collect, how we use it, and the
          choices you have about your information when using the <span class="font-bold">{{ brand.name }}</span> member portal and mobile app.
        </p>
      </section>

      <div class="space-y-4">
        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">01</span>
            Information we collect
          </h2>
          <ul class="space-y-3">
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Account details such as membership number, branch, and profile information you provide.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Transaction and wallet activity necessary to provide services (payments, VTU, loans, etc.).
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Camera data: We request access to your device's camera strictly for scanning QR codes during merchant payments. Images are processed locally on your device and are not uploaded or stored on our servers.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Location data: We request access to your device's GPS location strictly to verify your presence at scheduled branch meetings and mark attendance. Your location is only processed when you actively mark attendance and is not tracked in the background.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Device and usage data such as IP address, browser type, and access times for security and analytics.
            </li>
          </ul>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">02</span>
            How we use information
          </h2>
          <ul class="space-y-3">
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              To operate, maintain, and improve member services and features.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              To process payments, VTU purchases, loans, and related requests you initiate.
            </li>
          </ul>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">03</span>
            Sharing
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            We do not sell your data. We may share limited information with trusted service providers (e.g., payment and
            VTU partners) strictly to deliver the services you request, subject to confidentiality and legal requirements.
          </p>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">04</span>
            Security
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            We implement reasonable technical and organizational measures to protect your data. No method of transmission
            or storage is 100% secure; please keep your password confidential and enable available security features.
          </p>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">05</span>
            Your choices
          </h2>
          <ul class="space-y-3">
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Update your profile information from the Profile page.
            </li>
            <li class="flex gap-3 items-start text-sm text-slate-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Contact Support to request assistance or data-related inquiries.
            </li>
          </ul>
        </section>

        <section class="card p-6">
          <h2 class="font-black text-slate-900 mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">06</span>
            Contact
          </h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            Questions about this Privacy Policy? Reach us via the Support page or email
            <a href="mailto:assalaamosogbo@gmail.com" class="text-blue-700 font-bold hover:underline">assalaamosogbo@gmail.com
            </a>
          </p>
        </section>

        <div class="pt-4 flex justify-center">
          <router-link to="/policy" class="text-blue-700 font-bold hover:underline flex items-center gap-2">
            View Terms / Member Policy
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import AppHeader from '../components/AppHeader.vue'
import brand from '../brand'
</script>


