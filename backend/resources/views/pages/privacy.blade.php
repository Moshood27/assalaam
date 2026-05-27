@extends('layouts.public')

@section('title', 'Privacy Policy')

@section('content')
    <h1 class="text-3xl font-bold mb-4">Privacy Policy</h1>
    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-8">Last updated: May 2026</p>

    <div class="prose dark:prose-invert max-w-none space-y-6 text-slate-600 dark:text-slate-400 leading-relaxed">
        <p>
            We value your privacy. This Privacy Policy explains what information we collect, how we use it, and the choices you have about your information when using the {{ config('brand.name', 'AT-TAQWA') }} website, portal and services.
        </p>
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">1. Information We Collect</h2>
            <ul class="list-disc pl-5 space-y-2">
                <li><strong>Account details:</strong> such as name, membership number, branch, and contact information you provide.</li>
                <li><strong>Financial and Transactional Data:</strong> wallet activity, payments, loans, and investment records necessary to provide our services.</li>
                <li><strong>Security and Access Data:</strong> such as IP addresses, device identifiers, and logs to ensure the integrity of our platform.</li>
            </ul>
        </section>
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">2. How We Use Your Information</h2>
            <ul class="list-disc pl-5 space-y-2">
                <li>To operate, maintain, and improve our services and features.</li>
                <li>To process payments, investments, and loan requests.</li>
                <li>To comply with legal obligations and prevent fraud.</li>
                <li>To communicate important updates or requested information.</li>
            </ul>
        </section>
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">3. Data Sharing</h2>
            <p>
                We do not sell your personal data. We may share limited information with trusted service providers (e.g., payment gateways or Sharia auditors) strictly to deliver the services you request, subject to strict confidentiality agreements.
            </p>
        </section>
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">4. Data Security</h2>
            <p>
                We implement industry-standard technical and organizational measures to protect your data. However, no method of transmission over the internet or electronic storage is 100% secure, so we cannot guarantee absolute security.
            </p>
        </section>
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">5. Your Rights</h2>
            <p>
                You have the right to access, update, or correct your personal information. If you have questions about your data, please contact our support team.
            </p>
        </section>
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">6. Contact Us</h2>
            <p>
                If you have any questions about this Privacy Policy, please reach us via email at
                <a href="mailto:attaqwaosogbo@gmail.com" class="text-primary-600 hover:underline transition-colors font-medium">attaqwaosogbo@gmail.com</a>.
            </p>
        </section>
    </div>
@endsection
