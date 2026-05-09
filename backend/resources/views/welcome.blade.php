<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }} | Ethical & Halal Financial Services</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        .islamic-pattern {
            background-image: radial-gradient(ellipse at 10% 0%, rgba(16, 185, 129, .15) 0, transparent 40%),
                              radial-gradient(ellipse at 90% 0%, rgba(16, 185, 129, .1) 0, transparent 45%),
                              radial-gradient(circle at 50% 100%, rgba(5, 150, 105, .15) 0, transparent 50%);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased">
    <!-- Navigation -->
    <header class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-10 w-auto">
                <span class="hidden sm:block font-extrabold text-xl tracking-tighter text-emerald-900">{{ config('brand.name') }}</span>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#about" class="hover:text-emerald-600 transition-colors">About Us</a>
                <a href="#services" class="hover:text-emerald-600 transition-colors">Services</a>
                <a href="#how-it-works" class="hover:text-emerald-600 transition-colors">How it Works</a>
                <a href="{{ url('/support') }}" class="hover:text-emerald-600 transition-colors">Support</a>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ url('/app/login') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800 px-4 py-2">Login</a>
                <a href="{{ url('/app/register') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-6 py-2.5 rounded-full shadow-lg shadow-emerald-200 transition-all active:scale-95">Join Now</a>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden islamic-pattern">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-4xl mx-auto">
                    <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full mb-6 uppercase tracking-wider">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Shariah Compliant & Ethical
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-extrabold text-slate-900 tracking-tight mb-8 leading-[1.1]">
                        Empowering the Muslim Community Through <span class="text-emerald-600">Ethical Finance</span>
                    </h1>
                    <p class="text-xl text-slate-600 mb-10 leading-relaxed max-w-2xl mx-auto">
                        Experience a new way of financial co-operation. Interest-free loans, halal investments, and communal savings built on the principles of Attaqwa.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="{{ url('/app/register') }}" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-lg font-bold px-10 py-4 rounded-full shadow-xl shadow-emerald-200 transition-all hover:-translate-y-1">
                            Start Saving Today
                        </a>
                        <a href="#services" class="w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 text-lg font-bold px-10 py-4 rounded-full border border-slate-200 transition-all">
                            Explore Services
                        </a>
                    </div>
                </div>
            </div>

            <!-- Background motif -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full pointer-events-none opacity-[0.03] select-none">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                    <rect width="100" height="100" fill="url(#grid)" />
                </svg>
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
        <section id="services" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4 tracking-tight">Our Core Services</h2>
                    <p class="text-slate-500 text-lg max-w-2xl mx-auto">Comprehensive financial solutions tailored to your spiritual and material needs.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Service 1 -->
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-2xl hover:shadow-emerald-100 group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Multi-Scheme Savings</h3>
                        <p class="text-slate-500 leading-relaxed">Save for Hajj, education, or business projects through our flexible, interest-free schemes with guaranteed safety.</p>
                    </div>

                    <!-- Service 2 -->
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-2xl hover:shadow-emerald-100 group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Qard Hasan Loans</h3>
                        <p class="text-slate-500 leading-relaxed">Access benevolent interest-free loans for emergencies or personal development, repaid on terms you can afford.</p>
                    </div>

                    <!-- Service 3 -->
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-2xl hover:shadow-emerald-100 group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Mutual Takaful</h3>
                        <p class="text-slate-500 leading-relaxed">Join our cooperative insurance pool. We share risks and support each other during trials like illness or loss.</p>
                    </div>

                    <!-- Service 4 -->
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-2xl hover:shadow-emerald-100 group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.5 4.5L21.75 7.5M21.75 7.5V12m0-4.5H17.25" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Mudarabah Investment</h3>
                        <p class="text-slate-500 leading-relaxed">Invest in vetted halal businesses and share in the profits. Let your wealth grow ethically and sustainably.</p>
                    </div>

                    <!-- Service 5 -->
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-2xl hover:shadow-emerald-100 group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Islamic E-Commerce</h3>
                        <p class="text-slate-500 leading-relaxed">Shop for essentials and household items at fair prices with the option for interest-free credit for members.</p>
                    </div>

                    <!-- Service 6 -->
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all hover:shadow-2xl hover:shadow-emerald-100 group">
                        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Zakat & Sadaqah</h3>
                        <p class="text-slate-500 leading-relaxed">Easily fulfill your religious obligations. We ensure your charity reaches the most deserving in our community.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section id="about" class="py-24 bg-slate-50 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-center gap-16">
                    <div class="lg:w-1/2 relative">
                        <div class="relative z-10 rounded-[2rem] overflow-hidden shadow-2xl">
                             <div class="aspect-square bg-emerald-600 flex items-center justify-center p-12">
                                <svg class="w-full h-full text-emerald-100/20 absolute inset-0" fill="currentColor" viewBox="0 0 256 180">
                                    <path d="M110 40c0-27 22-49 49-49 9 0 18 2 25 7-23 1-41 20-41 43s18 42 41 43c-7 5-16 7-25 7-27 0-49-22-49-51z"/>
                                    <path d="M30 150h196c-10-18-28-32-50-37v-12c0-6-5-11-11-11h-4v-6c0-4-3-7-7-7h-2v-9l-10-8-10 8v9h-2c-4 0-7 3-7 7v6h-4c-6 0-11 5-11 11v12c-22 5-40 19-50 37z"/>
                                </svg>
                                <div class="relative z-20 text-center">
                                    <div class="text-6xl font-black text-white mb-4">Ethical</div>
                                    <div class="text-2xl text-emerald-100 font-medium tracking-wide">Community-Focused</div>
                                </div>
                             </div>
                        </div>
                        <div class="absolute -bottom-8 -right-8 w-64 h-64 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
                        <div class="absolute -top-8 -left-8 w-64 h-64 bg-emerald-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse delay-500"></div>
                    </div>

                    <div class="lg:w-1/2">
                        <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-8 tracking-tight">Financial services built on <span class="text-emerald-600">Islamic Values</span></h2>
                        <div class="space-y-8">
                            <div class="flex gap-6">
                                <div class="flex-shrink-0 w-12 h-12 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold mb-2">Interest-Free (Riba-Free)</h4>
                                    <p class="text-slate-500">We operate strictly on interest-free principles, ensuring your wealth remains pure and barakah-filled.</p>
                                </div>
                            </div>
                            <div class="flex gap-6">
                                <div class="flex-shrink-0 w-12 h-12 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold mb-2">Transparency & Justice</h4>
                                    <p class="text-slate-500">Every transaction is recorded and transparent. We ensure fairness for both the cooperative and its members.</p>
                                </div>
                            </div>
                            <div class="flex gap-6">
                                <div class="flex-shrink-0 w-12 h-12 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold mb-2">Digital Accessibility</h4>
                                    <p class="text-slate-500">Manage your finances anytime, anywhere via our modern web and mobile applications.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it Works -->
        <section id="how-it-works" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-16 tracking-tight">Become a Member in 3 Steps</h2>

                <div class="grid md:grid-cols-3 gap-12">
                    <div class="relative">
                        <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-3xl font-black mx-auto mb-8 border-4 border-white shadow-xl">1</div>
                        <h3 class="text-2xl font-bold mb-4">Register Online</h3>
                        <p class="text-slate-500">Create your account in minutes through our secure portal or mobile app.</p>
                        <div class="hidden md:block absolute top-10 left-[60%] w-full border-t-2 border-dashed border-emerald-100"></div>
                    </div>
                    <div class="relative">
                        <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-3xl font-black mx-auto mb-8 border-4 border-white shadow-xl">2</div>
                        <h3 class="text-2xl font-bold mb-4">Get Verified</h3>
                        <p class="text-slate-500">Complete your KYC and join your local branch to activate your membership.</p>
                        <div class="hidden md:block absolute top-10 left-[60%] w-full border-t-2 border-dashed border-emerald-100"></div>
                    </div>
                    <div>
                        <div class="w-20 h-20 bg-emerald-600 text-white rounded-full flex items-center justify-center text-3xl font-black mx-auto mb-8 border-4 border-white shadow-xl">3</div>
                        <h3 class="text-2xl font-bold mb-4">Start Contributing</h3>
                        <p class="text-slate-500">Begin your savings journey and access all cooperative benefits immediately.</p>
                    </div>
                </div>

                <div class="mt-20">
                    <a href="{{ url('/app/register') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-lg font-bold px-12 py-5 rounded-full shadow-2xl shadow-emerald-200 transition-all hover:scale-105 active:scale-95 inline-block">
                        Create Your Account Now
                    </a>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-emerald-900 rounded-[3rem] p-8 lg:p-20 text-center relative overflow-hidden">
                    <div class="relative z-10">
                        <h2 class="text-4xl lg:text-6xl font-extrabold text-white mb-8 tracking-tight">Ready to join the <br class="hidden lg:block">Attaqwa Community?</h2>
                        <p class="text-emerald-100 text-lg mb-12 max-w-2xl mx-auto">Join thousands of members already benefiting from a system that puts community and ethics first.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                            <a href="{{ url('/app/register') }}" class="w-full sm:w-auto bg-white text-emerald-900 text-lg font-bold px-12 py-5 rounded-full hover:bg-emerald-50 transition-all shadow-xl">
                                Sign Up for Free
                            </a>
                            <a href="{{ url('/support') }}" class="w-full sm:w-auto text-white border-2 border-emerald-700 hover:border-emerald-600 px-12 py-5 rounded-full font-bold transition-all">
                                Contact Sales
                            </a>
                        </div>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-emerald-800 rounded-full opacity-50"></div>
                    <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-64 h-64 bg-emerald-800 rounded-full opacity-50"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-12 mb-16">
                <div class="col-span-2">
                    <div class="flex items-center gap-2 mb-6">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-8 w-auto">
                        <span class="font-extrabold text-xl tracking-tighter text-emerald-900">{{ config('brand.name') }}</span>
                    </div>
                    <p class="text-slate-500 mb-8 max-w-xs leading-relaxed">
                        The leading Shariah-compliant cooperative platform providing interest-free financial solutions for the community.
                    </p>
                </div>
                <div>
                    <h5 class="font-bold text-slate-900 mb-6 uppercase text-xs tracking-widest">Company</h5>
                    <ul class="space-y-4 text-sm font-medium text-slate-500">
                        <li><a href="#about" class="hover:text-emerald-600 transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Press</a></li>
                        <li><a href="{{ url('/support') }}" class="hover:text-emerald-600 transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-slate-900 mb-6 uppercase text-xs tracking-widest">Services</h5>
                    <ul class="space-y-4 text-sm font-medium text-slate-500">
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Savings</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Loans</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Takaful</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Investments</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-slate-900 mb-6 uppercase text-xs tracking-widest">Legal</h5>
                    <ul class="space-y-4 text-sm font-medium text-slate-500">
                        <li><a href="{{ url('/privacy') }}" class="hover:text-emerald-600 transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ url('/policy') }}" class="hover:text-emerald-600 transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-emerald-600 transition-colors">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6">
                <p class="text-sm text-slate-400">© {{ date('Y') }} {{ config('brand.name') }}. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="text-slate-400 hover:text-emerald-600 transition-colors">
                        <span class="sr-only">Facebook</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <a href="#" class="text-slate-400 hover:text-emerald-600 transition-colors">
                        <span class="sr-only">Twitter</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.84 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    <a href="#" class="text-slate-400 hover:text-emerald-600 transition-colors">
                        <span class="sr-only">Instagram</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.066 2.633.344 3.608 1.319.975.975 1.253 2.242 1.319 3.608.058 1.266.069 1.646.069 4.85s-.011 3.584-.069 4.85c-.066 1.366-.344 2.633-1.319 3.608-.975.975-2.242 1.253-3.608 1.319-1.266.058-1.646.069-4.85.069s-3.584-.011-4.85-.069c-1.366-.066-2.633-.344-3.608-1.319-.975-.975-1.253-2.242-1.319-3.608-.058-1.266-.069-1.646-.069-4.85s.011-3.584.069-4.85c.066-1.366.344-2.633 1.319-3.608.975-.975 2.242-1.253 3.608-1.319 1.266-.058 1.646-.069 4.85-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-1.727.079-3.515.446-4.788 1.719-1.273 1.273-1.64 3.061-1.719 4.788-.058 1.28-.072 1.688-.072 4.947s.014 3.667.072 4.947c.079 1.727.446 3.515 1.719 4.788 1.273 1.273 3.061 1.64 4.788 1.719 1.28.058 1.688.072 4.947.072s3.667-.014 4.947-.072c1.727-.079 3.515-.446 4.788-1.719 1.273-1.273 1.64-3.061 1.719-4.788.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.079-1.727-.446-3.515-1.719-4.788-1.273-1.273-3.061-1.64-4.788-1.719-1.28-.058-1.688-.072-4.947-.072zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.162 6.162 6.162 6.162-2.759 6.162-6.162-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.791-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.209-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
