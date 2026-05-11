<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>About Us - {{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }}</title>

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
                <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">About Us</h1>

                <div class="prose dark:prose-invert max-w-none space-y-10 text-gray-600 dark:text-gray-400 leading-relaxed">
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Our Identity</h2>
                        <p>
                            <span class="font-bold text-emerald-600">AT-TAQWA OSOGBO ISLAMIC CICU LTD</span> (RC Number: 3449303) is a premier Islamic Cooperative Investment and Credit Union. We operate on the principles of Sharia, providing a viable alternative to conventional interest-based financial systems.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Our Mission</h2>
                        <p>
                            To empower our members through ethical financial solutions that promote economic growth, social welfare, and spiritual fulfillment. We strive to be the most trusted Islamic financial cooperative in the region.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">What We Offer</h2>
                        <ul class="list-disc pl-5 space-y-4">
                            <li><strong>Interest-Free Savings:</strong> Safe and accessible accounts managed according to Islamic principles.</li>
                            <li><strong>Halal Investments:</strong> Opportunities to participate in profitable ventures through Mudarabah and Musharakah.</li>
                            <li><strong>Ethical Credit:</strong> Qard Hasan (benevolent loans) and Murabahah (cost-plus financing) for personal and business needs.</li>
                            <li><strong>Community Support:</strong> Takaful and welfare programs designed to assist members in times of need.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Sharia Governance</h2>
                        <p>
                            All our operations, products, and services are supervised by a dedicated Sharia Board to ensure strict compliance with Islamic jurisprudence. We are committed to transparency, justice, and the prohibition of Riba (interest).
                        </p>
                    </section>
                </div>

                <div class="mt-16 pt-8 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-400">
                        © {{ date('Y') }} AT-TAQWA OSOGBO
                    </div>
                    <div class="flex gap-8 text-xs font-bold uppercase tracking-widest text-gray-400">
                        <a href="{{ url('/privacy-policy') }}" class="hover:text-emerald-600 transition-colors">Privacy</a>
                        <a href="{{ url('/terms') }}" class="hover:text-emerald-600 transition-colors">Terms</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
