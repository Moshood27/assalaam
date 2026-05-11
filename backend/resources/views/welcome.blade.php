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
                darkMode: 'class',
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
                            gold: {
                                DEFAULT: '#D4AF37',
                                50: '#fbf7e7',
                                100: '#f5eca4',
                                500: '#D4AF37',
                                600: '#b6922b',
                            },
                            charcoal: {
                                DEFAULT: '#1F2937',
                                900: '#111827',
                            }
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
<body class="bg-[#F9FAFB] text-gray-900 antialiased font-sans">
    <!-- Navbar -->
    <nav class="flex justify-between items-center py-4 px-4 md:px-10 bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/attaqwa-logo.svg') }}" alt="{{ config('brand.name', 'ATTAQWA') }}" class="h-10 w-auto">
            <div class="text-xl font-bold text-emerald-600 tracking-tight uppercase">{{ config('brand.name', 'ATTAQWA') }}</div>
        </div>
        <div class="hidden md:flex items-center space-x-8 font-medium text-gray-600">
            <a href="#features" class="hover:text-emerald-600 transition-colors">Features</a>
            <a href="#about" class="hover:text-emerald-600 transition-colors">About</a>
            <a href="{{ url('/about-us') }}" class="hover:text-emerald-600 transition-colors">Company</a>
        </div>
        <div class="flex items-center space-x-4">
            @auth
                <a href="{{ url('/admin') }}" class="px-6 py-2 bg-emerald-600 text-white rounded-full shadow-lg hover:bg-emerald-700 transition-all">Dashboard</a>
            @else
                <a href="{{ url('/admin/login') }}" class="hidden sm:inline-block px-6 py-2 border border-emerald-600 text-emerald-600 rounded-full hover:bg-emerald-50 transition-all font-semibold text-sm">Sign In</a>
                <a href="{{ url('/admin/register') }}" class="px-6 py-2 bg-emerald-600 text-white rounded-full shadow-lg hover:bg-emerald-700 transition-all font-semibold text-sm">Join Now</a>
            @endauth
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="relative py-16 lg:py-24 px-4 md:px-10 text-center bg-[#F9FAFB] overflow-hidden">
        <!-- Subtle Islamic Pattern Watermark -->
        <div class="absolute inset-0 opacity-[0.02] pointer-events-none" style="background-image: url('https://www.transparenttextures.com/patterns/islamic-art.png');"></div>

        <div class="relative z-10 max-w-5xl mx-auto">
            <h1 class="text-5xl md:text-6xl font-extrabold text-[#1F2937] leading-tight">
                Building Wealth, <br class="hidden sm:block" /> <span class="text-emerald-600">The Halal Way.</span>
            </h1>
            <p class="mt-6 text-lg md:text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Interest-Free Financial Growth for the Ummah. Empowering the community through interest-free cooperatives, transparent investments, and ethical financing.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-4 px-4">
                <a href="{{ url('/admin/register') }}" class="w-full sm:w-auto px-8 py-4 bg-emerald-600 text-white font-bold rounded-xl shadow-xl shadow-emerald-600/20 hover:bg-emerald-700 transition-all">Get Started</a>
                <a href="#features" class="w-full sm:w-auto px-8 py-4 bg-white text-[#1F2937] font-bold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">Learn More</a>
            </div>
        </div>
    </header>

    <!-- Stats Bar -->
    <section class="py-12 bg-white border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 md:px-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-emerald-600">5,000+</div>
                    <div class="mt-2 text-xs font-bold text-gray-500 uppercase tracking-widest">Total Members</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-emerald-600">₦250M+</div>
                    <div class="mt-2 text-xs font-bold text-gray-500 uppercase tracking-widest">Interest-Free Loans</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-extrabold text-emerald-600">₦15M+</div>
                    <div class="mt-2 text-xs font-bold text-gray-500 uppercase tracking-widest">Zakat Distributed</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Grid -->
    <section id="features" class="py-20 px-4 md:px-10 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-[#1F2937] mb-4">Our Services</h2>
                <p class="text-gray-500 max-w-2xl mx-auto">Financial solutions designed to help you grow without compromising your faith.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Murabaha Store -->
                <div class="p-6 bg-[#F9FAFB] border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold mb-3 text-[#1F2937]">Murabaha Store</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Purchase household assets and business tools with 0% interest via our ethical marketplace.</p>
                </div>

                <!-- Qardh Hasan -->
                <div class="p-6 bg-[#F9FAFB] border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold mb-3 text-[#1F2937]">Qardh Hasan</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Access interest-free benevolent loans designed to support you during financial emergencies.</p>
                </div>

                <!-- Halal Investments -->
                <div class="p-6 bg-[#F9FAFB] border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold mb-3 text-[#1F2937]">Halal Investments</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Grow your wealth through Sharia-compliant Mudarabah and Musharakah investment pools.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section id="about" class="py-20 px-4 md:px-10 bg-[#1F2937] text-white">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-12">
            <div class="md:w-1/2">
                <h2 class="text-3xl font-bold mb-6">Supervised by our <span class="text-emerald-400">Sharia Board.</span></h2>
                <p class="text-lg text-gray-300 mb-8 leading-relaxed">
                    At ATTAQWA, integrity is our foundation. Every product and transaction is strictly vetted by our board of Islamic scholars to ensure 100% compliance with Sharia principles.
                </p>
                <div class="flex items-center gap-4 text-emerald-400 font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>Guaranteed Riba-Free</span>
                </div>
            </div>
            <div class="md:w-1/2 bg-white/5 p-8 md:p-10 rounded-2xl border border-white/10 backdrop-blur-sm">
                <div class="text-center">
                    <div class="text-xs uppercase tracking-widest text-emerald-400 font-bold mb-4">Registration Proof</div>
                    <div class="text-2xl font-bold mb-2">RC Number: 3449303</div>
                    <p class="text-gray-400 text-sm">Registered with the Ministry of Commerce and Cooperatives.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- App Download Section -->
    <section class="bg-emerald-600 py-12 px-4 md:px-10 rounded-2xl mx-4 md:mx-10 my-16 text-white flex flex-col lg:flex-row items-center justify-between shadow-xl shadow-emerald-600/10">
        <div class="lg:max-w-xl text-center lg:text-left">
            <h2 class="text-3xl font-bold mb-4">Attaqwa in your pocket.</h2>
            <p class="text-emerald-100 text-lg leading-relaxed">
                Manage your cooperative accounts, apply for loans, and track your investments anywhere, anytime with our mobile app.
            </p>
        </div>
        <div class="mt-8 lg:mt-0 flex flex-wrap justify-center gap-4">
            <a href="#" class="transition-transform hover:scale-105">
                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" class="h-10" alt="Play Store">
            </a>
            <a href="#" class="transition-transform hover:scale-105">
                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" class="h-10" alt="App Store">
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 md:px-10">
            <div class="flex flex-col items-center text-center mb-12">
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('images/attaqwa-logo.svg') }}" alt="{{ config('brand.name', 'ATTAQWA') }}" class="h-8 w-auto">
                    <span class="font-bold text-lg uppercase tracking-tight text-gray-900">{{ config('brand.name', 'ATTAQWA') }}</span>
                </div>
                <p class="max-w-2xl text-gray-500 text-base leading-relaxed mb-6">
                    Empowering members through Sharia-compliant financial solutions and mutual cooperation. Join our community and grow your wealth the Halal way.
                </p>
                <div class="flex flex-wrap justify-center gap-6 text-sm font-semibold">
                    <a href="mailto:attaqwaosogbo@gmail.com" class="text-gray-600 hover:text-emerald-600 transition-colors">attaqwaosogbo@gmail.com</a>
                    <a href="tel:08037282495" class="text-gray-600 hover:text-emerald-600 transition-colors">08037282495</a>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12 pt-12 border-t border-gray-100">
                <div class="col-span-2 md:col-span-1">
                    <h4 class="font-bold text-xs uppercase tracking-widest text-gray-900 mb-4">Services</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="#features" class="hover:text-emerald-600">Murabaha Store</a></li>
                        <li><a href="#features" class="hover:text-emerald-600">Qardh Hasan</a></li>
                        <li><a href="#features" class="hover:text-emerald-600">Halal Investments</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-widest text-gray-900 mb-4">Company</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="{{ url('/about-us') }}" class="hover:text-emerald-600">About Us</a></li>
                        <li><a href="{{ url('/admin/login') }}" class="hover:text-emerald-600">Member Login</a></li>
                        <li><a href="{{ url('/admin/register') }}" class="hover:text-emerald-600">Join Now</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-widest text-gray-900 mb-4">Legal</h4>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="{{ url('/privacy-policy') }}" class="hover:text-emerald-600">Privacy Policy</a></li>
                        <li><a href="{{ url('/terms') }}" class="hover:text-emerald-600">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <h4 class="font-bold text-xs uppercase tracking-widest text-gray-900 mb-4">Sharia Board</h4>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Strictly monitored to ensure all operations remain 100% Halal and Interest-free.
                    </p>
                </div>
            </div>

            <div class="pt-8 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('brand.name', 'ATTAQWA') }}. All rights reserved.</p>
                <div class="flex gap-6">
                    <span>RC: 3449303</span>
                    <span>Sharia Compliant</span>
                    <span>Interest Free</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
