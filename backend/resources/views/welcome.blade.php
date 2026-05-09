<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }} | Ethical & Halal Financial Services</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800|plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }
        .islamic-pattern {
            background-color: #f8fafc;
            background-image:  radial-gradient(#10b981 0.5px, transparent 0.5px), radial-gradient(#10b981 0.5px, #f8fafc 0.5px);
            background-size: 20px 20px;
            background-position: 0 0,10px 10px;
            opacity: 0.05;
        }
        .gradient-text {
            background: linear-gradient(135deg, #065f46 0%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-jakarta antialiased">
    <!-- Navigation -->
    <header x-data="{ mobileMenuOpen: false, scrolled: false }"
            @scroll.window="scrolled = (window.pageYOffset > 20)"
            :class="{ 'bg-white/80 backdrop-blur-md shadow-sm border-b border-slate-200': scrolled, 'bg-transparent': !scrolled }"
            class="fixed top-0 w-full z-50 transition-all duration-300">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-10 w-auto">
                <span class="font-extrabold text-xl tracking-tight text-emerald-900">{{ config('brand.name') }}</span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#about" class="hover:text-emerald-600 transition-colors">About</a>
                <a href="#services" class="hover:text-emerald-600 transition-colors">Services</a>
                <a href="#how-it-works" class="hover:text-emerald-600 transition-colors">Process</a>
                <a href="#faq" class="hover:text-emerald-600 transition-colors">FAQ</a>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-3">
                    <a href="{{ url('/app/login') }}" class="text-sm font-semibold text-slate-700 hover:text-emerald-700 px-4 py-2 transition-colors">Login</a>
                    <a href="{{ url('/app/register') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-emerald-200 transition-all active:scale-95">Open Account</a>
                </div>

                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-600">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="md:hidden bg-white border-b border-slate-200 px-4 pt-2 pb-6 shadow-xl">
            <div class="flex flex-col gap-4">
                <a @click="mobileMenuOpen = false" href="#about" class="text-lg font-semibold text-slate-700 px-4 py-2">About</a>
                <a @click="mobileMenuOpen = false" href="#services" class="text-lg font-semibold text-slate-700 px-4 py-2">Services</a>
                <a @click="mobileMenuOpen = false" href="#how-it-works" class="text-lg font-semibold text-slate-700 px-4 py-2">Process</a>
                <a @click="mobileMenuOpen = false" href="#faq" class="text-lg font-semibold text-slate-700 px-4 py-2">FAQ</a>
                <hr class="border-slate-100">
                <div class="flex flex-col gap-3 px-4">
                    <a href="{{ url('/app/login') }}" class="text-center font-bold text-slate-700 py-3 rounded-xl border border-slate-200">Login</a>
                    <a href="{{ url('/app/register') }}" class="text-center font-bold text-white bg-emerald-600 py-3 rounded-xl">Open Account</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="relative pt-32 pb-16 lg:pt-52 lg:pb-32 overflow-hidden">
            <div class="absolute inset-0 islamic-pattern"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="text-left max-w-2xl">
                        <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-lg mb-6 uppercase tracking-wider">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            Shariah Compliant & Trusted
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight mb-6 leading-[1.1]">
                            Your Path to <span class="gradient-text">Ethical Wealth</span> & Growth
                        </h1>
                        <p class="text-lg sm:text-xl text-slate-600 mb-10 leading-relaxed">
                            Join Nigeria's fastest growing Islamic cooperative. Experience interest-free savings, ethical investments, and benevolent loans designed for your spiritual peace of mind.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <a href="{{ url('/app/register') }}" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-lg font-bold px-8 py-4 rounded-xl shadow-xl shadow-emerald-200 transition-all hover:-translate-y-1 text-center">
                                Start Your Journey
                            </a>
                            <a href="#services" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 text-lg font-bold px-8 py-4 rounded-xl border border-slate-200 transition-all text-center">
                                Our Services
                            </a>
                        </div>
                        <div class="mt-10 flex items-center gap-4 text-sm text-slate-500">
                            <div class="flex -space-x-2">
                                <div class="w-8 h-8 rounded-full bg-slate-200 border-2 border-white"></div>
                                <div class="w-8 h-8 rounded-full bg-slate-300 border-2 border-white"></div>
                                <div class="w-8 h-8 rounded-full bg-emerald-200 border-2 border-white"></div>
                            </div>
                            <span>Joined by <span class="text-emerald-700 font-bold">10,000+</span> members nationwide</span>
                        </div>
                    </div>

                    <div class="hidden lg:block relative">
                        <div class="bg-gradient-to-tr from-emerald-500 to-emerald-700 rounded-[2.5rem] p-4 shadow-2xl rotate-2">
                            <div class="bg-white rounded-[2rem] p-8 aspect-[4/3] flex flex-col justify-between">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase font-bold tracking-widest mb-1">Account Balance</p>
                                        <p class="text-3xl font-black text-slate-900 tracking-tight">₦4,250,000.00</p>
                                    </div>
                                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="h-2 bg-slate-100 rounded-full w-3/4"></div>
                                    <div class="h-2 bg-slate-100 rounded-full w-1/2"></div>
                                </div>
                                <div class="pt-6 border-t border-slate-100 flex gap-4">
                                    <div class="flex-1 bg-emerald-50 rounded-xl p-3">
                                        <p class="text-[10px] text-emerald-600 font-bold uppercase">Savings</p>
                                        <p class="text-sm font-bold text-slate-900">+12.5%</p>
                                    </div>
                                    <div class="flex-1 bg-blue-50 rounded-xl p-3">
                                        <p class="text-[10px] text-blue-600 font-bold uppercase">Mudarabah</p>
                                        <p class="text-sm font-bold text-slate-900">Active</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -bottom-6 -left-6 bg-white shadow-xl rounded-2xl p-4 flex items-center gap-4 border border-slate-100 animate-bounce duration-[3000ms]">
                            <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-medium">Loan Approved</p>
                                <p class="text-sm font-bold text-slate-900">Interest-Free Qard Hasan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-12 bg-emerald-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-4xl font-extrabold mb-1">5,000+</div>
                        <div class="text-emerald-300 text-sm font-medium">Active Members</div>
                    </div>
                    <div>
                        <div class="text-4xl font-extrabold mb-1">100%</div>
                        <div class="text-emerald-300 text-sm font-medium">Interest-Free</div>
                    </div>
                    <div>
                        <div class="text-4xl font-extrabold mb-1">₦1B+</div>
                        <div class="text-emerald-300 text-sm font-medium">Total Assets</div>
                    </div>
                    <div>
                        <div class="text-4xl font-extrabold mb-1">24/7</div>
                        <div class="text-emerald-300 text-sm font-medium">Digital Access</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="py-24 bg-white relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4 tracking-tight">Our Financial Solutions</h2>
                    <p class="text-slate-500 text-lg max-w-2xl mx-auto">Ethical, interest-free, and community-driven services tailored for your prosperity.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Service 1: Multi-Scheme Savings -->
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-xl group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Multi-Scheme Savings</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Targeted savings for Hajj, education, or business projects. Fully Shariah-compliant with absolute security.</p>
                    </div>

                    <!-- Service 2: Qard Hasan -->
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-xl group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Qard Hasan Loans</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Interest-free benevolent loans for personal development and emergencies, repaid on stress-free terms.</p>
                    </div>

                    <!-- Service 3: Mutual Takaful -->
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-xl group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Mutual Takaful</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">A cooperative insurance pool where members share risks and support each other during trials and loss.</p>
                    </div>

                    <!-- Service 4: Mudarabah -->
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-xl group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.5 4.5L21.75 7.5M21.75 7.5V12m0-4.5H17.25" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Halal Investments</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Mudarabah-based investments in vetted businesses, allowing you to earn pure profits from ethical growth.</p>
                    </div>

                    <!-- Service 5: E-Commerce -->
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-xl group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Islamic E-Commerce</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Purchase essential commodities at competitive prices with interest-free credit options for active members.</p>
                    </div>

                    <!-- Service 6: Zakat -->
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-xl group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Zakat & Sadaqah</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Professional management and distribution of your religious taxes to the most vulnerable in our community.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section id="about" class="py-24 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-center gap-16">
                    <div class="lg:w-1/2">
                        <div class="relative">
                            <div class="rounded-[3rem] overflow-hidden shadow-2xl bg-emerald-600 p-12 text-center aspect-square flex flex-col justify-center">
                                <svg class="w-32 h-32 text-emerald-100/20 absolute -top-8 -left-8" fill="currentColor" viewBox="0 0 256 180">
                                    <path d="M110 40c0-27 22-49 49-49 9 0 18 2 25 7-23 1-41 20-41 43s18 42 41 43c-7 5-16 7-25 7-27 0-49-22-49-51z"/>
                                </svg>
                                <h3 class="text-4xl font-black text-white mb-4">Integrity in every transaction.</h3>
                                <p class="text-emerald-100 text-lg">We are committed to the highest standards of Shariah compliance and ethical banking.</p>
                            </div>
                            <!-- Floating badges -->
                            <div class="absolute -bottom-6 -right-6 bg-white p-6 rounded-2xl shadow-xl border border-slate-100 hidden sm:block">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 leading-none">100% Halal</p>
                                        <p class="text-xs text-slate-500 mt-1">Certified Shariah Compliance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:w-1/2">
                        <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-8 tracking-tight">Built for the <span class="text-emerald-600">Community</span></h2>
                        <div class="space-y-6">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-6 h-6 text-emerald-600 mt-1">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-slate-900 mb-1">Riba-Free Principles</h4>
                                    <p class="text-slate-500 text-sm">We strictly avoid interest (riba) in all operations, ensuring your wealth remains pure and grows ethically.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-6 h-6 text-emerald-600 mt-1">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-slate-900 mb-1">Mutual Prosperity</h4>
                                    <p class="text-slate-500 text-sm">Our profit-sharing models ensure that when the cooperative grows, every member benefits directly.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-6 h-6 text-emerald-600 mt-1">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-slate-900 mb-1">Modern Digital Experience</h4>
                                    <p class="text-slate-500 text-sm">Access your accounts, apply for loans, and manage investments seamlessly via our web and mobile apps.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-10">
                            <a href="{{ url('/app/register') }}" class="text-emerald-600 font-bold flex items-center gap-2 group">
                                Learn more about our principles
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="py-24 bg-slate-50 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4 tracking-tight">Voices of Our Community</h2>
                    <p class="text-slate-500 text-lg max-w-2xl mx-auto">Real stories from members who have transformed their financial lives with us.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                        <div class="flex gap-1 text-amber-400 mb-4">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <p class="text-slate-600 italic mb-6">"Attaqwa has been a blessing. I was able to secure an interest-free loan to expand my tailoring business without the stress of riba. Truly a cooperative for the people."</p>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700">FA</div>
                            <div>
                                <p class="font-bold text-slate-900 text-sm">Fatima Ahmed</p>
                                <p class="text-xs text-slate-500">Business Member</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                        <div class="flex gap-1 text-amber-400 mb-4">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <p class="text-slate-600 italic mb-6">"Finally, a financial system that aligns with my values. The Mudarabah investment platform is transparent and the returns are halal. Highly recommended for every Muslim."</p>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700">IU</div>
                            <div>
                                <p class="font-bold text-slate-900 text-sm">Ibrahim Usman</p>
                                <p class="text-xs text-slate-500">Investor</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                        <div class="flex gap-1 text-amber-400 mb-4">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </div>
                        <p class="text-slate-600 italic mb-6">"Saving for Hajj seemed impossible until I joined Attaqwa. Their targeted savings scheme helped me stay disciplined and reach my goal in 3 years. Alhamdulillah."</p>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-700">YM</div>
                            <div>
                                <p class="font-bold text-slate-900 text-sm">Yusuf Musa</p>
                                <p class="text-xs text-slate-500">Savings Member</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-24 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4 tracking-tight">Frequently Asked Questions</h2>
                    <p class="text-slate-500 text-lg">Everything you need to know about joining our cooperative.</p>
                </div>

                <div class="space-y-4" x-data="{ active: null }">
                    <div class="border border-slate-100 rounded-2xl overflow-hidden">
                        <button @click="active = active === 0 ? null : 0" class="w-full flex items-center justify-between p-6 text-left hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-900">What makes Attaqwa different from a regular bank?</span>
                            <svg :class="active === 0 ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 0" x-cloak class="p-6 pt-0 text-slate-500 text-sm leading-relaxed bg-slate-50">
                            Unlike commercial banks, we operate strictly on Shariah principles. This means we don't charge interest (riba) on loans and our investments are limited to halal businesses. We are a member-owned cooperative, meaning our goal is mutual prosperity rather than maximizing profit for shareholders.
                        </div>
                    </div>

                    <div class="border border-slate-100 rounded-2xl overflow-hidden">
                        <button @click="active = active === 1 ? null : 1" class="w-full flex items-center justify-between p-6 text-left hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-900">Is my money safe with Attaqwa?</span>
                            <svg :class="active === 1 ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 1" x-cloak class="p-6 pt-0 text-slate-500 text-sm leading-relaxed bg-slate-50">
                            Yes, absolutely. We are a registered cooperative society with strict internal audits and regulatory oversight. We use bank-grade security for our digital platforms and maintain healthy reserve ratios to ensure member funds are always protected.
                        </div>
                    </div>

                    <div class="border border-slate-100 rounded-2xl overflow-hidden">
                        <button @click="active = active === 2 ? null : 2" class="w-full flex items-center justify-between p-6 text-left hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-900">How do I qualify for a Qard Hasan (interest-free) loan?</span>
                            <svg :class="active === 2 ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 2" x-cloak class="p-6 pt-0 text-slate-500 text-sm leading-relaxed bg-slate-50">
                            To qualify for a loan, you must be an active member for at least 6 months and have a consistent record of contributions. Loans are typically granted up to a multiple of your total savings, subject to approval by the credit committee.
                        </div>
                    </div>

                    <div class="border border-slate-100 rounded-2xl overflow-hidden">
                        <button @click="active = active === 3 ? null : 3" class="w-full flex items-center justify-between p-6 text-left hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-900">Can I withdraw my savings at any time?</span>
                            <svg :class="active === 3 ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 3" x-cloak class="p-6 pt-0 text-slate-500 text-sm leading-relaxed bg-slate-50">
                            Regular savings can be withdrawn based on our cooperative's bylaws (usually with a short notice period). Specific targeted savings schemes (like Hajj or Education) may have different withdrawal terms to help you meet your goals.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-emerald-900 rounded-[3rem] p-8 lg:p-24 text-center relative overflow-hidden shadow-2xl">
                    <div class="relative z-10">
                        <h2 class="text-4xl lg:text-6xl font-extrabold text-white mb-8 tracking-tight">Start Your Ethical <br class="hidden lg:block">Financial Journey Today</h2>
                        <p class="text-emerald-100 text-lg mb-12 max-w-2xl mx-auto">Join thousands of members building a better future together through interest-free cooperation.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ url('/app/register') }}" class="w-full sm:w-auto bg-white text-emerald-900 text-lg font-bold px-12 py-5 rounded-2xl hover:bg-emerald-50 transition-all shadow-xl">
                                Create Free Account
                            </a>
                            <a href="{{ url('/app/login') }}" class="w-full sm:w-auto text-white border-2 border-emerald-700 hover:border-emerald-600 px-12 py-5 rounded-2xl font-bold transition-all">
                                Member Login
                            </a>
                        </div>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-emerald-800 rounded-full opacity-30"></div>
                    <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-64 h-64 bg-emerald-800 rounded-full opacity-30"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-100 pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-12 gap-12 mb-16">
                <div class="col-span-2 lg:col-span-4">
                    <div class="flex items-center gap-2 mb-8">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-10 w-auto">
                        <span class="font-extrabold text-2xl tracking-tight text-emerald-900">{{ config('brand.name') }}</span>
                    </div>
                    <p class="text-slate-500 mb-8 max-w-xs leading-relaxed text-sm font-medium">
                        Nigeria's premier Shariah-compliant cooperative platform. Empowering the community through interest-free financial solutions.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-colors border border-slate-100">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-colors border border-slate-100">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.84 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-colors border border-slate-100">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.066 2.633.344 3.608 1.319.975.975 1.253 2.242 1.319 3.608.058 1.266.069 1.646.069 4.85s-.011 3.584-.069 4.85c-.066 1.366-.344 2.633-1.319 3.608-.975.975-2.242 1.253-3.608 1.319-1.266.058-1.646.069-4.85.069s-3.584-.011-4.85-.069c-1.366-.066-2.633-.344-3.608-1.319-.975-.975-1.253-2.242-1.319-3.608-.058-1.266-.069-1.646-.069-4.85s.011-3.584.069-4.85c.066-1.366.344-2.633 1.319-3.608.975-.975 2.242-1.253 3.608-1.319 1.266-.058 1.646-.069 4.85-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-1.727.079-3.515.446-4.788 1.719-1.273 1.273-1.64 3.061-1.719 4.788-.058 1.28-.072 1.688-.072 4.947s.014 3.667.072 4.947c.079 1.727.446 3.515 1.719 4.788 1.273 1.273 3.061 1.64 4.788 1.719 1.28.058 1.688.072 4.947.072s3.667-.014 4.947-.072c1.727-.079 3.515-.446 4.788-1.719 1.273-1.273 1.64-3.061 1.719-4.788.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.079-1.727-.446-3.515-1.719-4.788-1.273-1.273-3.061-1.64-4.788-1.719-1.28-.058-1.688-.072-4.947-.072zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.162 6.162 6.162 6.162-2.759 6.162-6.162-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.791-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.209-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    </div>
                </div>
                <div class="col-span-1 lg:col-span-2">
                    <h5 class="font-bold text-slate-900 mb-8 uppercase text-xs tracking-widest">Solutions</h5>
                    <ul class="space-y-4 text-sm font-semibold text-slate-500">
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Savings Plans</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Halal Loans</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Investment Pool</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Takaful Pool</a></li>
                    </ul>
                </div>
                <div class="col-span-1 lg:col-span-2">
                    <h5 class="font-bold text-slate-900 mb-8 uppercase text-xs tracking-widest">Resources</h5>
                    <ul class="space-y-4 text-sm font-semibold text-slate-500">
                        <li><a href="#about" class="hover:text-emerald-600 transition-colors">About Us</a></li>
                        <li><a href="#faq" class="hover:text-emerald-600 transition-colors">Common FAQ</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Branch Locator</a></li>
                        <li><a href="{{ url('/support') }}" class="hover:text-emerald-600 transition-colors">Get Support</a></li>
                    </ul>
                </div>
                <div class="col-span-1 lg:col-span-2">
                    <h5 class="font-bold text-slate-900 mb-8 uppercase text-xs tracking-widest">Legal</h5>
                    <ul class="space-y-4 text-sm font-semibold text-slate-500">
                        <li><a href="{{ url('/privacy') }}" class="hover:text-emerald-600 transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ url('/policy') }}" class="hover:text-emerald-600 transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Shariah Board</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-12 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">© {{ date('Y') }} {{ config('brand.name') }}. Registered Cooperative Society.</p>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">All systems operational</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
