<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Terms & Conditions - {{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }}</title>

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
    <body class="bg-gray-50 dark:bg-[#0a0a0a] text-gray-900 dark:text-gray-100 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col font-sans">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6">
            <nav class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group font-semibold">
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-emerald-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="text-gray-500 group-hover:text-emerald-600 transition-colors">Home</span>
                </a>
            </nav>
        </header>

        <main class="w-full lg:max-w-4xl max-w-[335px] bg-white dark:bg-[#161615] shadow-xl shadow-gray-200/50 dark:shadow-none rounded-[2rem] overflow-hidden border border-gray-100 dark:border-gray-800">
            <div class="p-8 lg:p-16">
                <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Terms & Conditions</h1>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-10">Effective Date: May 2024</p>

                <div class="prose dark:prose-invert max-w-none space-y-10 text-gray-600 dark:text-gray-400 leading-relaxed">
                    <p>
                        Welcome to {{ config('brand.name', 'AT-TAQWA') }}. By accessing our services, you agree to be bound by the following terms and conditions. Please read them carefully.
                    </p>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">1. Membership Eligibility</h2>
                        <p>
                            To use our services, you must be a registered member of AT-TAQWA OSOGBO ISLAMIC CICU LTD. Membership is subject to approval and compliance with our internal bylaws and Sharia guidelines.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">2. Sharia Compliance</h2>
                        <p>
                            You acknowledge that our services operate strictly on Islamic financial principles. This includes the prohibition of Riba (interest), Maisir (gambling), and Gharar (excessive uncertainty). By using our platform, you agree to these ethical constraints.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">3. Account Security</h2>
                        <p>
                            You are responsible for maintaining the confidentiality of your account credentials. Any activity performed through your account is deemed to be your own. Notify us immediately if you suspect any unauthorized access.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">4. Ethical Conduct</h2>
                        <p>
                            Members are expected to maintain the highest standards of integrity. Any fraudulent activity, misrepresentation, or attempt to circumvent our security systems will result in immediate termination of membership and potential legal action.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">5. Service Availability</h2>
                        <p>
                            While we strive for 100% uptime, we do not guarantee that our digital services will always be available or uninterrupted. We reserve the right to perform maintenance or update features as needed.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">6. Limitation of Liability</h2>
                        <p>
                            {{ config('brand.name', 'AT-TAQWA') }} shall not be liable for any indirect, incidental, or consequential damages arising out of the use or inability to use our services, except as required by law.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">7. Amendments</h2>
                        <p>
                            We may update these terms from time to time. Your continued use of the platform after such changes constitutes acceptance of the new terms.
                        </p>
                    </section>
                </div>

                <div class="mt-16 pt-8 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-400">
                        © {{ date('Y') }} AT-TAQWA OSOGBO
                    </div>
                    <div class="flex gap-8 text-xs font-bold uppercase tracking-widest text-gray-400">
                        <a href="{{ url('/about-us') }}" class="hover:text-emerald-600 transition-colors">About</a>
                        <a href="{{ url('/privacy-policy') }}" class="hover:text-emerald-600 transition-colors">Privacy</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
