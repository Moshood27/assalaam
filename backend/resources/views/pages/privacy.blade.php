<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Privacy Policy - {{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6">
            <nav class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <svg class="w-5 h-5 text-[#706f6c] group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="text-[#706f6c] group-hover:text-black transition-colors">Back to Home</span>
                </a>
            </nav>
        </header>

        <main class="w-full lg:max-w-4xl max-w-[335px] bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg overflow-hidden">
            <div class="p-8 lg:p-12">
                <h1 class="text-2xl font-semibold mb-6">Privacy Policy</h1>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#706f6c] dark:text-[#A1A09A] mb-8">Last updated: May 2024</p>

                <div class="prose dark:prose-invert max-w-none space-y-6 text-[#706f6c] dark:text-[#A1A09A] leading-relaxed">
                    <p>
                        We value your privacy. This Privacy Policy explains what information we collect, how we use it, and the choices you have about your information when using the {{ config('brand.name', 'AT-TAQWA') }} website, portal and services.
                    </p>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">1. Information We Collect</h2>
                        <ul class="list-disc pl-5 space-y-2">
                            <li><strong>Account details:</strong> such as name, membership number, branch, and contact information you provide.</li>
                            <li><strong>Financial and Transactional Data:</strong> wallet activity, payments, loans, and investment records necessary to provide our services.</li>
                            <li><strong>Security and Access Data:</strong> such as IP addresses, device identifiers, and logs to ensure the integrity of our platform.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">2. How We Use Your Information</h2>
                        <ul class="list-disc pl-5 space-y-2">
                            <li>To operate, maintain, and improve our services and features.</li>
                            <li>To process payments, investments, and loan requests.</li>
                            <li>To comply with legal obligations and prevent fraud.</li>
                            <li>To communicate important updates or requested information.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">3. Data Sharing</h2>
                        <p>
                            We do not sell your personal data. We may share limited information with trusted service providers (e.g., payment gateways or Sharia auditors) strictly to deliver the services you request, subject to strict confidentiality agreements.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">4. Data Security</h2>
                        <p>
                            We implement industry-standard technical and organizational measures to protect your data. However, no method of transmission over the internet or electronic storage is 100% secure, so we cannot guarantee absolute security.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">5. Your Rights</h2>
                        <p>
                            You have the right to access, update, or correct your personal information. If you have questions about your data, please contact our support team.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">6. Contact Us</h2>
                        <p>
                            If you have any questions about this Privacy Policy, please reach us via email at
                            <a href="mailto:attaqwaosogbo@gmail.com" class="text-emerald-600 hover:underline">attaqwaosogbo@gmail.com</a>.
                        </p>
                    </section>
                </div>

                <div class="mt-12 pt-8 border-t border-[#19140015] dark:border-[#ffffff15] flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        © {{ date('Y') }} AT-TAQWA OSOGBO ISLAMIC CICU LTD.
                    </div>
                    <div class="flex gap-6 text-sm">
                        <a href="{{ url('/about-us') }}" class="hover:text-black dark:hover:text-white transition-colors">About Us</a>
                        <a href="{{ url('/terms') }}" class="hover:text-black dark:hover:text-white transition-colors">Terms & Conditions</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
