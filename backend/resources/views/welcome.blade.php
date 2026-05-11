<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('brand.name', config('app.name', 'ATTAQWA')) }} - Ethical Islamic Fintech</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'media',
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                50: '#f0f9f6',
                                100: '#d1fae5',
                                500: '#10b981',
                                600: '#059669',
                                700: '#047857',
                            },
                            emerald: {
                                50: '#ecfdf5',
                                100: '#d1fae5',
                                200: '#a7f3d0',
                                300: '#6ee7b7',
                                400: '#34d399',
                                500: '#10b981',
                                600: '#059669',
                                700: '#047857',
                                800: '#065f46',
                                900: '#064e3b',
                                950: '#022c22',
                            },
                        },
                        fontFamily: {
                            sans: ['Instrument Sans', 'sans-serif'],
                        },
                    }
                }
            }
        </script>
    @endif
</head>
<body class="bg-white dark:bg-[#0a0a0a] text-gray-900 dark:text-gray-100 antialiased font-sans">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white/90 dark:bg-[#0a0a0a]/90 backdrop-blur-sm border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-brand-600 rounded-lg flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-brand-700 dark:text-brand-500 uppercase">{{ config('brand.name', 'ATTAQWA') }}</span>
                </div>
                <div class="hidden md:flex items-center gap-8 text-sm font-medium">
                    <a href="#offerings" class="hover:text-brand-600 transition-colors">Offerings</a>
                    <a href="#about" class="hover:text-brand-600 transition-colors">About</a>
                    <a href="{{ url('/about-us') }}" class="hover:text-brand-600 transition-colors">Company</a>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/admin') }}" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition-all shadow-sm">Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="text-sm font-semibold hover:text-brand-600 transition-colors">Sign In</a>
                        <a href="{{ url('/admin/register') }}" class="px-5 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold rounded-lg hover:opacity-90 transition-all shadow-sm">Join Now</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="relative pt-20 pb-24 lg:pt-32 lg:pb-40 bg-gradient-to-b from-brand-50/50 to-transparent dark:from-brand-900/10 dark:to-transparent overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                <div class="flex-1 text-center lg:text-left">
                    <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-8">
                        Ethical Finance, <br class="hidden sm:block" />
                        <span class="text-brand-600">Purely Islamic.</span>
                    </h1>
                    <p class="max-w-2xl mx-auto lg:mx-0 text-lg lg:text-xl text-gray-600 dark:text-gray-400 leading-relaxed mb-12">
                        Empowering the Ummah with Sharia-compliant financial solutions. AT-TAQWA OSOGBO ISLAMIC CICU LTD offers interest-free growth through cooperation and integrity.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ url('/admin/register') }}" class="w-full sm:w-auto px-10 py-4 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-all shadow-xl shadow-brand-600/20 text-center">
                            Get Started
                        </a>
                        <a href="#offerings" class="w-full sm:w-auto px-10 py-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 font-bold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all text-center">
                            See How It Works
                        </a>
                    </div>
                </div>

                <div class="w-full lg:w-auto flex flex-col gap-10 lg:border-l lg:border-gray-100 lg:dark:border-gray-800 lg:pl-16">
                    <div class="flex flex-col items-center lg:items-start group">
                        <div class="text-4xl font-bold text-brand-600">3.4M+</div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mt-1">RC: 3449303</div>
                    </div>
                    <div class="flex flex-col items-center lg:items-start group">
                        <div class="text-4xl font-bold text-gray-900 dark:text-white group-hover:text-brand-600 transition-colors">100%</div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mt-1">Sharia Compliant</div>
                    </div>
                    <div class="flex flex-col items-center lg:items-start group">
                        <div class="text-4xl font-bold text-gray-900 dark:text-white group-hover:text-brand-600 transition-colors">Pure</div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mt-1">Interest Free</div>
                    </div>
                    <div class="flex flex-col items-center lg:items-start group">
                        <div class="text-4xl font-bold text-gray-900 dark:text-white group-hover:text-brand-600 transition-colors">Secure</div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mt-1">Asset Backed</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Core Offerings -->
    <section id="offerings" class="py-24 lg:py-32 bg-gray-50/30 dark:bg-gray-900/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">Core Offerings</h2>
                <div class="w-16 h-1 bg-brand-600 mx-auto rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <!-- Mudarabah -->
                <div class="bg-white dark:bg-gray-800 p-10 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-10 h-10 bg-brand-100 dark:bg-brand-900/30 text-brand-600 rounded-xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Mudarabah</h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-sm">Profit-sharing investment where we partner for growth. Your capital is managed in Sharia-compliant ventures, sharing risks and rewards fairly.</p>
                </div>
                <!-- Qard Hasan -->
                <div class="bg-white dark:bg-gray-800 p-10 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-10 h-10 bg-brand-100 dark:bg-brand-900/30 text-brand-600 rounded-xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Qard Hasan</h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-sm">Benevolent, interest-free loans designed to support you in times of need. A pillar of mutual assistance within our cooperative community.</p>
                </div>
                <!-- Takaful -->
                <div class="bg-white dark:bg-gray-800 p-10 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-10 h-10 bg-brand-100 dark:bg-brand-900/30 text-brand-600 rounded-xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Takaful Pool</h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-sm">A collective protection scheme based on brotherhood. Members contribute to help one another face life's unforeseen challenges.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Us -->
    <section id="about" class="py-24 lg:py-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
                <div>
                    <h2 class="text-3xl lg:text-4xl font-bold mb-8 text-gray-900 dark:text-white">Why Choose Us?</h2>
                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="shrink-0 w-8 h-8 bg-brand-600 text-white rounded-full flex items-center justify-center font-bold text-xs">1</div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Digital Excellence</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Manage your finances seamlessly with our modern fintech portal. Real-time tracking and instant applications.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="shrink-0 w-8 h-8 bg-brand-600 text-white rounded-full flex items-center justify-center font-bold text-xs">2</div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Sharia Governance</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Strictly monitored by our Sharia Board to ensure every transaction is Halal and free from Riba.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="shrink-0 w-8 h-8 bg-brand-600 text-white rounded-full flex items-center justify-center font-bold text-xs">3</div>
                            <div>
                                <h4 class="text-lg font-bold mb-1">Community Owned</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Built for the members, by the members. Your success is our collective success.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-[3rem] border border-gray-100 dark:border-gray-700">
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-10 shadow-inner">
                         <div class="text-center">
                            <div class="w-12 h-12 bg-brand-100 dark:bg-brand-900/30 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-2">Registered & Secure</h3>
                            <p class="text-gray-400 uppercase tracking-widest text-[10px] font-bold mb-6">RC Number: 3449303</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">AT-TAQWA OSOGBO ISLAMIC CICU LTD is a legally recognized cooperative dedicated to ethical financial empowerment for all members.</p>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-brand-600 rounded-[3rem] p-12 lg:p-20 text-center text-white shadow-2xl shadow-brand-600/30">
                <h2 class="text-4xl lg:text-5xl font-extrabold mb-8">Join the Ethical Revolution</h2>
                <p class="text-brand-50 text-xl max-w-2xl mx-auto mb-12">Take control of your financial future with a community that shares your values. No interest, no hidden fees, just growth.</p>
                <a href="{{ url('/admin/register') }}" class="inline-block px-12 py-5 bg-white text-brand-600 font-bold rounded-2xl hover:bg-brand-50 transition-all shadow-lg transform hover:scale-105">
                    Start Your Journey
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-50 dark:bg-[#050505] border-t border-gray-100 dark:border-gray-800 pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="flex flex-col items-center mb-16">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <span class="font-bold text-xl uppercase tracking-tight text-gray-900 dark:text-white">{{ config('brand.name', 'ATTAQWA') }}</span>
                </div>
                <p class="max-w-xl text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-8">
                    AT-TAQWA OSOGBO ISLAMIC CICU LTD. Empowering members through Sharia-compliant financial solutions and mutual cooperation.
                </p>
                <div class="flex flex-wrap justify-center gap-x-8 gap-y-3 text-sm font-bold text-gray-900 dark:text-white">
                    <span>RC: 3449303</span>
                    <a href="mailto:attaqwaosogbo@gmail.com" class="text-gray-500 hover:text-brand-600 transition-colors font-medium">attaqwaosogbo@gmail.com</a>
                    <a href="tel:08037282495" class="text-gray-500 hover:text-brand-600 transition-colors font-medium">08037282495</a>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-12 mb-20 max-w-3xl mx-auto border-t border-gray-100 dark:border-gray-800 pt-16">
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-gray-900 dark:text-white mb-6">Services</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="#offerings" class="hover:text-brand-600">Mudarabah Savings</a></li>
                        <li><a href="#offerings" class="hover:text-brand-600">Qard Hasan Loans</a></li>
                        <li><a href="#offerings" class="hover:text-brand-600">Takaful Protection</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-gray-900 dark:text-white mb-6">Company</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="{{ url('/about-us') }}" class="hover:text-brand-600">About Us</a></li>
                        <li><a href="{{ url('/admin/login') }}" class="hover:text-brand-600">Member Login</a></li>
                        <li><a href="{{ url('/admin/register') }}" class="hover:text-brand-600">Join Now</a></li>
                    </ul>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-gray-900 dark:text-white mb-6">Legal</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="{{ url('/privacy-policy') }}" class="hover:text-brand-600">Privacy Policy</a></li>
                        <li><a href="{{ url('/terms') }}" class="hover:text-brand-600">Terms & Conditions</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-12 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-6 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('brand.name', 'ATTAQWA') }}. All rights reserved.</p>
                <div class="flex gap-8">
                    <span>Sharia Compliant</span>
                    <span>Interest Free</span>
                    <span>Secure & Transparent</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
