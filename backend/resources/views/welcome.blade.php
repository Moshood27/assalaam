<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }} | Shariah-Compliant Financial Excellence</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        h1, h2, h3, h4, .font-heading { font-family: 'Instrument Sans', sans-serif; }

        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(6, 95, 70, 0.05), transparent 40%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .text-gradient {
            background: linear-gradient(to right, #065f46, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased selection:bg-emerald-100 selection:text-emerald-900">

    <!-- Navigation -->
    <header x-data="{ mobileMenuOpen: false, scrolled: false }"
            @scroll.window="scrolled = (window.pageYOffset > 20)"
            :class="{ 'bg-white/90 backdrop-blur-md border-b border-slate-100 py-3': scrolled, 'bg-transparent py-5': !scrolled }"
            class="fixed top-0 w-full z-50 transition-all duration-300">
        <nav class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-9 w-auto transition-transform group-hover:scale-105">
                <span class="font-heading font-extrabold text-xl tracking-tight text-slate-900">{{ config('brand.name') }}</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-10">
                <div class="flex items-center gap-8 text-[13px] font-bold uppercase tracking-widest text-slate-500">
                    <a href="#about" class="hover:text-emerald-600 transition-colors">Principles</a>
                    <a href="#services" class="hover:text-emerald-600 transition-colors">Services</a>
                    <a href="#how-it-works" class="hover:text-emerald-600 transition-colors">How it works</a>
                    <a href="#faq" class="hover:text-emerald-600 transition-colors">FAQ</a>
                </div>
                <div class="h-6 w-[1px] bg-slate-200"></div>
                <div class="flex items-center gap-4">
                    <a href="{{ url('/app/login') }}" class="text-sm font-bold text-slate-700 hover:text-emerald-600 px-4 transition-colors">Log In</a>
                    <a href="{{ url('/app/register') }}" class="bg-slate-900 text-white text-sm font-bold px-6 py-2.5 rounded-full hover:bg-emerald-700 transition-all shadow-sm active:scale-95">
                        Join Now
                    </a>
                </div>
            </div>

            <!-- Mobile menu button -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 text-slate-900">
                <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </nav>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden bg-white border-b border-slate-100 px-6 pt-4 pb-8 shadow-2xl">
            <div class="flex flex-col gap-5">
                <a @click="mobileMenuOpen = false" href="#about" class="text-lg font-bold text-slate-900">Principles</a>
                <a @click="mobileMenuOpen = false" href="#services" class="text-lg font-bold text-slate-900">Services</a>
                <a @click="mobileMenuOpen = false" href="#how-it-works" class="text-lg font-bold text-slate-900">How it works</a>
                <a @click="mobileMenuOpen = false" href="#faq" class="text-lg font-bold text-slate-900">FAQ</a>
                <div class="pt-4 flex flex-col gap-3">
                    <a href="{{ url('/app/login') }}" class="text-center font-bold text-slate-900 py-3 rounded-2xl border border-slate-200">Log In</a>
                    <a href="{{ url('/app/register') }}" class="text-center font-bold text-white bg-emerald-600 py-3 rounded-2xl">Join Now</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="relative pt-40 pb-20 lg:pt-60 lg:pb-40 hero-gradient overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-[11px] font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-full mb-8 border border-emerald-100/50">
                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Shariah-Compliant Financial Co-operative
                </div>
                <h1 class="text-5xl lg:text-8xl font-extrabold text-slate-900 tracking-[-0.03em] mb-8 leading-[1.05]">
                    Ethical Finance for <br class="hidden md:block">
                    <span class="text-gradient">Every Generation.</span>
                </h1>
                <p class="text-lg md:text-xl text-slate-600 mb-12 max-w-2xl mx-auto leading-relaxed">
                    Join Nigeria's most trusted Islamic co-operative. No interest, no hidden fees—just pure, community-driven wealth building designed for your spiritual peace.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-5">
                    <a href="{{ url('/app/register') }}" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-base font-bold px-10 py-5 rounded-full shadow-2xl shadow-emerald-200/50 transition-all hover:-translate-y-1">
                        Get Started Today
                    </a>
                    <a href="#services" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-900 text-base font-bold px-10 py-5 rounded-full border border-slate-200 transition-all">
                        Explore Services
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="mt-24 pt-10 border-t border-slate-100 flex flex-wrap justify-center items-center gap-x-12 gap-y-8 opacity-60 grayscale hover:grayscale-0 transition-all duration-500">
                    <div class="flex items-center gap-2 font-heading font-bold text-sm tracking-tight">
                        <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        REGISTERED CO-OPERATIVE
                    </div>
                    <div class="flex items-center gap-2 font-heading font-bold text-sm tracking-tight">
                        <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                        10,000+ MEMBERS
                    </div>
                    <div class="flex items-center gap-2 font-heading font-bold text-sm tracking-tight">
                        <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                        PURE HALAL PROFIT
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section (Social Proof) -->
        <section class="py-10 bg-slate-50">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
                    <div>
                        <div class="font-heading text-4xl font-extrabold text-slate-900 mb-1">₦2.5B+</div>
                        <div class="text-slate-500 text-xs font-bold uppercase tracking-widest">Assets Managed</div>
                    </div>
                    <div>
                        <div class="font-heading text-4xl font-extrabold text-slate-900 mb-1">100%</div>
                        <div class="text-slate-500 text-xs font-bold uppercase tracking-widest">Interest-Free</div>
                    </div>
                    <div>
                        <div class="font-heading text-4xl font-extrabold text-slate-900 mb-1">24/7</div>
                        <div class="text-slate-500 text-xs font-bold uppercase tracking-widest">Online Banking</div>
                    </div>
                    <div>
                        <div class="font-heading text-4xl font-extrabold text-slate-900 mb-1">0.00%</div>
                        <div class="text-slate-500 text-xs font-bold uppercase tracking-widest">Hidden Fees</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Principles -->
        <section id="about" class="py-24 lg:py-40 bg-white">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid lg:grid-cols-2 gap-24 items-center">
                    <div>
                        <h2 class="font-heading text-4xl lg:text-6xl font-extrabold text-slate-900 mb-8 tracking-tight">Built on <span class="text-emerald-600">Trust</span> and Faith.</h2>
                        <p class="text-slate-600 text-lg mb-12 leading-relaxed">
                            We aren't just a financial platform; we're a community of like-minded individuals pooled together to support each other's growth without compromising our values.
                        </p>

                        <div class="space-y-8">
                            <div class="flex gap-6">
                                <div class="flex-shrink-0 w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="font-heading text-xl font-bold text-slate-900 mb-2 text-gradient">No Riba (Interest)</h4>
                                    <p class="text-slate-500 text-sm leading-relaxed">We strictly prohibit interest-based lending. Our wealth is grown through ethical investments and profit-sharing models.</p>
                                </div>
                            </div>
                            <div class="flex gap-6">
                                <div class="flex-shrink-0 w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="font-heading text-xl font-bold text-slate-900 mb-2">Member Ownership</h4>
                                    <p class="text-slate-500 text-sm leading-relaxed">Every member is an owner. We prioritize your financial well-being over corporate profits.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="bg-slate-100 rounded-[3rem] aspect-square flex items-center justify-center overflow-hidden">
                            <div class="p-12 text-center">
                                <div class="w-20 h-20 bg-emerald-600 rounded-3xl mx-auto mb-8 flex items-center justify-center text-white shadow-xl shadow-emerald-200">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.954 0 0112 2.944a11.955 11.954 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                                <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Shariah-Certified</h3>
                                <p class="text-slate-500 max-w-xs mx-auto text-sm">Our operations are strictly audited by our Advisory Board of Islamic Finance Experts.</p>
                            </div>
                        </div>
                        <div class="absolute -bottom-10 -left-10 glass-card p-6 rounded-3xl shadow-xl hidden md:block max-w-[240px]">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center font-bold">✓</div>
                                <div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Compliance</p>
                                    <p class="text-sm font-extrabold text-slate-900 leading-tight">100% Shariah Compliant Audit 2024</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services -->
        <section id="services" class="py-24 lg:py-40 bg-slate-50">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row md:items-end justify-between mb-20 gap-8">
                    <div class="max-w-2xl">
                        <h2 class="font-heading text-4xl lg:text-6xl font-extrabold text-slate-900 mb-6 tracking-tight tracking-tight">Solutions for <span class="text-emerald-600">Prosperity.</span></h2>
                        <p class="text-slate-600 text-lg">Modern financial tools built on ancient principles of fairness and equity.</p>
                    </div>
                    <a href="{{ url('/app/register') }}" class="inline-flex items-center gap-2 font-bold text-emerald-600 hover:text-emerald-700 transition-colors group">
                        See All Services
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Target Savings -->
                    <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 hover:border-emerald-200 hover:shadow-2xl hover:shadow-emerald-200/20 transition-all group">
                        <div class="w-16 h-16 bg-slate-50 text-slate-900 rounded-2xl flex items-center justify-center mb-8 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Multi-Scheme Savings</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Save for Hajj, Education, or Business. Your funds are protected and accessible whenever you need them.</p>
                    </div>

                    <!-- Qard Hasan -->
                    <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 hover:border-emerald-200 hover:shadow-2xl hover:shadow-emerald-200/20 transition-all group">
                        <div class="w-16 h-16 bg-slate-50 text-slate-900 rounded-2xl flex items-center justify-center mb-8 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Benevolent Loans</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Access Qard Hasan (Interest-Free Loans) for emergencies or personal growth. Stress-free repayment terms.</p>
                    </div>

                    <!-- Mudarabah -->
                    <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 hover:border-emerald-200 hover:shadow-2xl hover:shadow-emerald-200/20 transition-all group">
                        <div class="w-16 h-16 bg-slate-50 text-slate-900 rounded-2xl flex items-center justify-center mb-8 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-4">Halal Investments</h3>
                        <p class="text-slate-500 leading-relaxed text-sm">Earn pure profits from vetted, Shariah-compliant businesses. Transparent reporting and professional management.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="py-24 lg:py-40 bg-white">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-20">
                    <h2 class="font-heading text-4xl lg:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Our Members' Success.</h2>
                    <p class="text-slate-500 text-lg">The real impact of ethical co-operative banking.</p>
                </div>

                <div class="grid lg:grid-cols-3 gap-12">
                    <div class="relative p-10 bg-slate-50 rounded-[2.5rem]">
                        <p class="text-slate-700 italic mb-8 leading-relaxed">"Attaqwa is more than a bank to me. They helped me fund my business without the burden of interest. It's truly a community that cares."</p>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center font-bold text-emerald-700">AA</div>
                            <div>
                                <h5 class="font-bold text-slate-900">Abdullah Adamu</h5>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Business Owner</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative p-10 bg-emerald-900 text-white rounded-[2.5rem] lg:-translate-y-6 shadow-2xl shadow-emerald-900/40">
                        <p class="text-emerald-50 italic mb-8 leading-relaxed">"The Mudarabah pool has allowed me to grow my savings ethically. Transparent, professional, and reliable. Highly recommended."</p>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-800 rounded-full flex items-center justify-center font-bold text-emerald-100">ZM</div>
                            <div>
                                <h5 class="font-bold text-white">Zainab Musa</h5>
                                <p class="text-xs text-emerald-300 font-bold uppercase tracking-widest">Investor</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative p-10 bg-slate-50 rounded-[2.5rem]">
                        <p class="text-slate-700 italic mb-8 leading-relaxed">"I saved for my Hajj trip through the targeted savings scheme. The discipline and support from the co-operative made it possible."</p>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center font-bold text-emerald-700">YM</div>
                            <div>
                                <h5 class="font-bold text-slate-900">Yusuf Muhammed</h5>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Member since 2021</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-24 lg:py-40 bg-slate-50">
            <div class="max-w-3xl mx-auto px-6">
                <div class="text-center mb-20">
                    <h2 class="font-heading text-4xl lg:text-5xl font-extrabold text-slate-900 mb-6 tracking-tight">Got Questions?</h2>
                    <p class="text-slate-500 text-lg">Everything you need to know about Attaqwa Co-operative.</p>
                </div>

                <div class="space-y-4" x-data="{ active: 0 }">
                    <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm transition-all" :class="{ 'ring-2 ring-emerald-500/20 shadow-lg': active === 0 }">
                        <button @click="active = 0" class="w-full flex items-center justify-between p-8 text-left group">
                            <span class="font-bold text-slate-900 text-lg group-hover:text-emerald-600 transition-colors">Is Attaqwa a regular bank?</span>
                            <svg :class="active === 0 ? 'rotate-180 text-emerald-600' : 'text-slate-300'" class="w-6 h-6 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 0" x-cloak class="px-8 pb-8 text-slate-500 leading-relaxed">
                            No, we are a Shariah-compliant co-operative. Unlike regular banks, we are member-owned and operate on interest-free principles. We pool member funds to provide benevolent loans and invest in halal businesses, sharing the profits back with our members.
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm transition-all" :class="{ 'ring-2 ring-emerald-500/20 shadow-lg': active === 1 }">
                        <button @click="active = 1" class="w-full flex items-center justify-between p-8 text-left group">
                            <span class="font-bold text-slate-900 text-lg group-hover:text-emerald-600 transition-colors">Who can join the co-operative?</span>
                            <svg :class="active === 1 ? 'rotate-180 text-emerald-600' : 'text-slate-300'" class="w-6 h-6 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 1" x-cloak class="px-8 pb-8 text-slate-500 leading-relaxed">
                            Membership is open to all individuals who align with our ethical principles and are looking for Shariah-compliant financial solutions. You can join by creating an account on our platform and making your first contribution.
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm transition-all" :class="{ 'ring-2 ring-emerald-500/20 shadow-lg': active === 2 }">
                        <button @click="active = 2" class="w-full flex items-center justify-between p-8 text-left group">
                            <span class="font-bold text-slate-900 text-lg group-hover:text-emerald-600 transition-colors">How do you ensure Shariah compliance?</span>
                            <svg :class="active === 2 ? 'rotate-180 text-emerald-600' : 'text-slate-300'" class="w-6 h-6 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 2" x-cloak class="px-8 pb-8 text-slate-500 leading-relaxed">
                            We have a dedicated Shariah Advisory Board consisting of experts in Islamic Finance. They audit all our products, investment portfolios, and operational processes to ensure they remain 100% free of Riba, Gharar, and Maysir.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 lg:py-40 bg-white">
            <div class="max-w-7xl mx-auto px-6">
                <div class="bg-emerald-600 rounded-[3rem] p-12 lg:p-24 text-center text-white relative overflow-hidden shadow-2xl shadow-emerald-200">
                    <div class="relative z-10">
                        <h2 class="font-heading text-4xl lg:text-7xl font-extrabold mb-8 tracking-tight">Your Path to Ethical <br class="hidden md:block"> Prosperity Starts Here.</h2>
                        <p class="text-emerald-100 text-lg mb-12 max-w-2xl mx-auto leading-relaxed opacity-90">Join 10,000+ members building a brighter, interest-free future for themselves and their community.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ url('/app/register') }}" class="w-full sm:w-auto bg-white text-emerald-700 text-lg font-bold px-12 py-5 rounded-full hover:bg-emerald-50 transition-all shadow-xl active:scale-95">
                                Join the Co-operative
                            </a>
                            <a href="{{ url('/app/login') }}" class="w-full sm:w-auto text-white border-2 border-emerald-400 hover:bg-emerald-500/30 px-12 py-5 rounded-full font-bold transition-all active:scale-95">
                                Member Login
                            </a>
                        </div>
                    </div>
                    <div class="absolute -top-20 -right-20 w-80 h-80 bg-emerald-500 rounded-full opacity-20 blur-3xl"></div>
                    <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-emerald-500 rounded-full opacity-20 blur-3xl"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-50 border-t border-slate-100 pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 lg:grid-cols-12 gap-16 mb-24">
                <div class="col-span-2 lg:col-span-5">
                    <a href="/" class="flex items-center gap-3 mb-8">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-8 w-auto">
                        <span class="font-heading font-extrabold text-xl tracking-tight text-slate-900">{{ config('brand.name') }}</span>
                    </a>
                    <p class="text-slate-500 mb-8 max-w-sm leading-relaxed font-medium">
                        Empowering the community through Shariah-compliant financial solutions. We are a registered co-operative society dedicated to ethical wealth building and mutual support.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-all shadow-sm border border-slate-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-all shadow-sm border border-slate-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.84 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                    </div>
                </div>
                <div class="col-span-1 lg:col-span-2">
                    <h5 class="font-heading font-bold text-slate-900 mb-8 uppercase text-[11px] tracking-widest">Platform</h5>
                    <ul class="space-y-4 text-[14px] font-semibold text-slate-500">
                        <li><a href="#services" class="hover:text-emerald-600 transition-colors">Savings</a></li>
                        <li><a href="#services" class="hover:text-emerald-600 transition-colors">Loans</a></li>
                        <li><a href="#services" class="hover:text-emerald-600 transition-colors">Investments</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Mobile App</a></li>
                    </ul>
                </div>
                <div class="col-span-1 lg:col-span-2">
                    <h5 class="font-heading font-bold text-slate-900 mb-8 uppercase text-[11px] tracking-widest">Company</h5>
                    <ul class="space-y-4 text-[14px] font-semibold text-slate-500">
                        <li><a href="#about" class="hover:text-emerald-600 transition-colors">Principles</a></li>
                        <li><a href="#faq" class="hover:text-emerald-600 transition-colors">FAQ</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Support</a></li>
                    </ul>
                </div>
                <div class="col-span-1 lg:col-span-3">
                    <h5 class="font-heading font-bold text-slate-900 mb-8 uppercase text-[11px] tracking-widest">Contact</h5>
                    <ul class="space-y-4 text-[14px] font-semibold text-slate-500">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Lagos Office: 123 Business Way, Ikeja, Lagos, Nigeria</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>hello@attaqwacoop.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="pt-12 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6">
                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest">© {{ date('Y') }} {{ config('brand.name') }}. RC: 1234567. All Rights Reserved.</p>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-[11px] text-slate-400 font-bold uppercase tracking-widest hover:text-emerald-600">Privacy Policy</a>
                    <a href="#" class="text-[11px] text-slate-400 font-bold uppercase tracking-widest hover:text-emerald-600">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
