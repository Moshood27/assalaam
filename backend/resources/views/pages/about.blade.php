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
                <h1 class="text-2xl font-semibold mb-6">About Us</h1>

                <div class="prose dark:prose-invert max-w-none space-y-6 text-[#706f6c] dark:text-[#A1A09A] leading-relaxed">
                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Our Identity</h2>
                        <p>
                            <span class="font-semibold text-emerald-600">AT-TAQWA OSOGBO ISLAMIC CICU LTD</span> (RC Number: 3449303) is a premier Islamic Cooperative Investment and Credit Union. We operate on the principles of Sharia, providing a viable alternative to conventional interest-based financial systems.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Our Mission</h2>
                        <p>
                            To empower our members through ethical financial solutions that promote economic growth, social welfare, and spiritual fulfillment. We strive to be the most trusted Islamic financial cooperative in the region.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">What We Offer</h2>
                        <ul class="list-disc pl-5 space-y-2">
                            <li><strong>Interest-Free Savings:</strong> Safe and accessible accounts managed according to Islamic principles.</li>
                            <li><strong>Halal Investments:</strong> Opportunities to participate in profitable ventures through Mudarabah and Musharakah.</li>
                            <li><strong>Ethical Credit:</strong> Qard Hasan (benevolent loans) and Murabahah (cost-plus financing) for personal and business needs.</li>
                            <li><strong>Community Support:</strong> Takaful and welfare programs designed to assist members in times of need.</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Sharia Governance</h2>
                        <p>
                            All our operations, products, and services are supervised by a dedicated Sharia Board to ensure strict compliance with Islamic jurisprudence. We are committed to transparency, justice, and the prohibition of Riba (interest).
                        </p>
                    </section>
                </div>

                <div class="mt-12 pt-8 border-t border-[#19140015] dark:border-[#ffffff15] flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        © {{ date('Y') }} AT-TAQWA OSOGBO ISLAMIC CICU LTD.
                    </div>
                    <div class="flex gap-6 text-sm">
                        <a href="{{ url('/privacy-policy') }}" class="hover:text-black dark:hover:text-white transition-colors">Privacy Policy</a>
                        <a href="{{ url('/terms') }}" class="hover:text-black dark:hover:text-white transition-colors">Terms & Conditions</a>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
