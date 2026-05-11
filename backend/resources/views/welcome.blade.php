<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('brand.name', config('app.name', 'ATTAQWA CO-OPERATIVE')) }} - Islamic Fintech Solutions</title>

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
        <style>
            .islamic-pattern {
                background-color: transparent;
                background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0);
                background-size: 24px 24px;
            }
            .glass {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
        </style>
    </head>
    <body class="bg-white dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <!-- Navigation -->
        <nav class="fixed top-0 w-full z-50 border-b border-[#19140015] dark:border-[#ffffff15] bg-white/80 dark:bg-[#0a0a0a]/80 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 21h18M3 10h18M5 10V7a3 3 0 013-3h8a3 3 0 013 3v3M9 21v-4a2 2 0 012-2h2a2 2 0 012 2v4" />
                            </svg>
                        </div>
                        <span class="font-bold text-lg tracking-tight uppercase">{{ config('brand.name', 'ATTAQWA') }}</span>
                    </div>
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#services" class="text-sm font-medium hover:text-emerald-600 transition-colors">Services</a>
                        <a href="#about" class="text-sm font-medium hover:text-emerald-600 transition-colors">About</a>
                        <a href="{{ url('/about-us') }}" class="text-sm font-medium hover:text-emerald-600 transition-colors">Company</a>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/admin') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-all">Dashboard</a>
                        @else
                            <a href="{{ url('/admin/login') }}" class="text-sm font-medium hover:text-emerald-600 transition-colors">Log in</a>
                            <a href="{{ url('/admin/register') }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1b1b18] text-sm font-medium rounded-lg hover:opacity-90 transition-all">Join Us</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
            <div class="absolute inset-0 -z-10 opacity-[0.03] dark:opacity-[0.05] islamic-pattern text-emerald-900 dark:text-emerald-100"></div>
            <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl md:text-6xl font-bold tracking-tight mb-6">
                        Ethical Finance for a <br/>
                        <span class="text-emerald-600">Thriving Community</span>
                    </h1>
                    <p class="text-lg md:text-xl text-[#706f6c] dark:text-[#A1A09A] max-w-2xl mx-auto mb-10">
                        Join AT-TAQWA OSOGBO ISLAMIC CICU LTD. Empowering members through Sharia-compliant financial solutions, mutual cooperation, and sustainable growth.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ url('/admin/register') }}" class="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-emerald-600/20">
                            Become a Member
                        </a>
                        <a href="#services" class="w-full sm:w-auto px-8 py-4 bg-white dark:bg-[#161615] border border-[#19140015] dark:border-[#ffffff15] font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-[#1c1c1a] transition-all">
                            Explore Services
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="py-24 bg-gray-50 dark:bg-[#0d0d0d]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2 class="text-3xl font-bold mb-4">Our Core Offerings</h2>
                    <p class="text-[#706f6c] dark:text-[#A1A09A]">Transparent, interest-free, and community-driven financial tools.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Mudarabah -->
                    <div class="p-8 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#19140005] dark:border-[#ffffff05] hover:border-emerald-500/30 transition-all group">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Mudarabah</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] leading-relaxed">
                            Invest in profitable, pooled projects managed by the cooperative. Share in the success and growth through ethical profit-sharing models.
                        </p>
                    </div>

                    <!-- Qard Hasan -->
                    <div class="p-8 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#19140005] dark:border-[#ffffff05] hover:border-emerald-500/30 transition-all group">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Qard Hasan</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] leading-relaxed">
                            Access interest-free benevolent loans designed to support members during times of need without the burden of usury (Riba).
                        </p>
                    </div>

                    <!-- Takaful -->
                    <div class="p-8 bg-white dark:bg-[#161615] rounded-2xl shadow-sm border border-[#19140005] dark:border-[#ffffff05] hover:border-emerald-500/30 transition-all group">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Takaful Pool</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] leading-relaxed">
                            A mutual protection scheme where members contribute to a shared pool to help settle liabilities in cases of unforeseen hardships.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About / Why Us Section -->
        <section id="about" class="py-24 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-center gap-16">
                    <div class="flex-1">
                        <h2 class="text-3xl font-bold mb-6">Why AT-TAQWA OSOGBO?</h2>
                        <div class="space-y-6">
                            <div class="flex gap-4">
                                <div class="shrink-0 w-10 h-10 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-full flex items-center justify-center font-bold">1</div>
                                <div>
                                    <h4 class="font-bold mb-1">Sharia Compliance</h4>
                                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">All our operations are vetted to ensure they are free from Riba, Gharar, and Maysir.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="shrink-0 w-10 h-10 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-full flex items-center justify-center font-bold">2</div>
                                <div>
                                    <h4 class="font-bold mb-1">Digital Convenience</h4>
                                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Manage your contributions, track your passbook, and apply for loans right from your mobile device.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="shrink-0 w-10 h-10 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-full flex items-center justify-center font-bold">3</div>
                                <div>
                                    <h4 class="font-bold mb-1">Community Driven</h4>
                                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">We are owned by our members. Every contribution helps strengthen our collective financial stability.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 relative">
                        <div class="relative z-10 p-2 bg-white dark:bg-[#161615] rounded-3xl shadow-2xl border border-[#19140015] dark:border-[#ffffff15]">
                            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-2xl p-8 lg:p-12">
                                <div class="flex flex-col items-center text-center">
                                    <svg class="w-24 h-24 text-emerald-600 mb-6" viewBox="0 0 256 180" xmlns="http://www.w3.org/2000/svg">
                                        <g fill="currentColor">
                                            <path d="M110 40c0-27 22-49 49-49 9 0 18 2 25 7-23 1-41 20-41 43s18 42 41 43c-7 5-16 7-25 7-27 0-49-22-49-51z"/>
                                            <path d="M30 150h196c-10-18-28-32-50-37v-12c0-6-5-11-11-11h-4v-6c0-4-3-7-7-7h-2v-9l-10-8-10 8v9h-2c-4 0-7 3-7 7v6h-4c-6 0-11 5-11 11v12c-22 5-40 19-50 37z"/>
                                        </g>
                                    </svg>
                                    <h3 class="text-xl font-bold mb-2 uppercase tracking-widest text-emerald-900 dark:text-emerald-100">{{ config('brand.name', 'ATTAQWA') }}</h3>
                                    <p class="text-sm text-emerald-800/60 dark:text-emerald-200/40">Established for Mutual Success</p>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-200/50 dark:bg-emerald-600/20 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-emerald-200/50 dark:bg-emerald-600/20 rounded-full blur-3xl"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 bg-emerald-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to start your ethical financial journey?</h2>
                <p class="text-emerald-50 text-lg mb-10 max-w-2xl mx-auto">Join thousands of members today and experience a transparent, community-focused cooperative.</p>
                <a href="{{ url('/admin/register') }}" class="inline-block px-10 py-4 bg-white text-emerald-600 font-bold rounded-xl hover:bg-emerald-50 transition-colors shadow-xl">
                    Get Started Now
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-white dark:bg-[#0a0a0a] pt-20 pb-10 border-t border-[#19140015] dark:border-[#ffffff15]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                    <div class="col-span-1 md:col-span-2">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-6 h-6 bg-emerald-600 rounded flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 21h18M3 10h18M5 10V7a3 3 0 013-3h8a3 3 0 013 3v3M9 21v-4a2 2 0 012-2h2a2 2 0 012 2v4" /></svg>
                            </div>
                            <span class="font-bold uppercase tracking-tight">{{ config('brand.name', 'ATTAQWA') }}</span>
                        </div>
                        <p class="text-[#706f6c] dark:text-[#A1A09A] text-sm max-w-sm mb-6">
                            AT-TAQWA OSOGBO ISLAMIC CICU LTD.
                            Empowering members through Sharia-compliant financial solutions and mutual cooperation.
                        </p>
                        <div class="flex flex-col gap-2 text-sm">
                            <span class="font-medium">RC Number: 3449303</span>
                            <a href="mailto:attaqwaosogbo@gmail.com" class="hover:text-emerald-600 transition-colors">attaqwaosogbo@gmail.com</a>
                            <a href="tel:08037282495" class="hover:text-emerald-600 transition-colors">08037282495</a>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold mb-6 text-sm uppercase tracking-wider">Quick Links</h4>
                        <ul class="space-y-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <li><a href="#services" class="hover:text-emerald-600 transition-colors">Services</a></li>
                            <li><a href="#about" class="hover:text-emerald-600 transition-colors">About Us</a></li>
                            <li><a href="{{ url('/admin/login') }}" class="hover:text-emerald-600 transition-colors">Login</a></li>
                            <li><a href="{{ url('/admin/register') }}" class="hover:text-emerald-600 transition-colors">Register</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold mb-6 text-sm uppercase tracking-wider">Legal</h4>
                        <ul class="space-y-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <li><a href="{{ url('/privacy-policy') }}" class="hover:text-emerald-600 transition-colors">Privacy Policy</a></li>
                            <li><a href="{{ url('/terms') }}" class="hover:text-emerald-600 transition-colors">Terms & Conditions</a></li>
                            <li><a href="{{ url('/about-us') }}" class="hover:text-emerald-600 transition-colors">About Us</a></li>
                        </ul>
                    </div>
                </div>
                <div class="pt-10 border-t border-[#19140008] dark:border-[#ffffff08] flex flex-col md:row items-center justify-between gap-4 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    <p>&copy; {{ date('Y') }} {{ config('brand.name', 'ATTAQWA') }}. All rights reserved.</p>
                    <div class="flex gap-6">
                        <span>Sharia Compliant</span>
                        <span>Secure Payments</span>
                        <span>Community Owned</span>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
