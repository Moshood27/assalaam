<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'AT-TAQWA') }} - Ethical Islamic Fintech</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Base styles handled by Tailwind utilities in app.css */
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#0a0a0a] text-slate-900 dark:text-slate-100 antialiased selection:bg-primary-500 selection:text-white">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 glass border-b border-slate-200 dark:border-slate-800 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="Logo" class="h-8 w-auto dark:hidden transition-transform group-hover:scale-105">
                    <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo-dark.svg') }}" alt="Logo" class="h-8 w-auto hidden dark:block transition-transform group-hover:scale-105">
                    <span class="font-bold text-lg tracking-tight hidden sm:block uppercase">{{ config('brand.name', 'AT-TAQWA') }}</span>
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium hover:text-primary-600 transition-colors">Features</a>
                    <a href="#leadership" class="text-sm font-medium hover:text-primary-600 transition-colors">Leadership</a>
                    <a href="{{ url('/about-us') }}" class="text-sm font-medium hover:text-primary-600 transition-colors">About</a>
                    <a href="#download" class="text-sm font-medium hover:text-primary-600 transition-colors">App</a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-4">
                        <a href="https://attaqwacooposg.com/app/" class="text-sm font-medium hover:text-primary-600 transition-colors">Member Login</a>
                        <a href="#download" class="bg-slate-900 dark:bg-white dark:text-slate-900 text-white px-4 py-2 rounded-full text-sm font-semibold hover:opacity-90 transition-all">Get the App</a>
                    </div>
                    <button id="mobile-menu-button" class="md:hidden p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-4">
            <a href="#features" class="block text-base font-medium text-slate-600 dark:text-slate-400">Features</a>
            <a href="#leadership" class="block text-base font-medium text-slate-600 dark:text-slate-400">Leadership</a>
            <a href="{{ url('/about-us') }}" class="block text-base font-medium text-slate-600 dark:text-slate-400">About</a>
            <a href="#download" class="block text-base font-medium text-slate-600 dark:text-slate-400">App</a>
            <hr class="border-slate-100 dark:border-slate-800">
            <a href="https://attaqwacooposg.com/app" class="block text-base font-medium text-slate-600 dark:text-slate-400">Member Login</a>
            <a href="#download" class="block text-base font-bold text-primary-600">Get the App</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden hero-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-bold mb-6 border border-primary-200 dark:border-primary-800">
                    <i data-lucide="shield-check" class="w-3 h-3"></i>
                    SHARIA COMPLIANT & SECURE
                </div>
                <h1 class="text-4xl sm:text-7xl font-extrabold tracking-tight mb-8 leading-[1.1]">
                    Ethical Banking for <br class="hidden sm:block"> <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-emerald-500">Your Future</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                    Join AT-TAQWA Islamic Cooperative. Manage your savings, invest in Halal ventures, and access interest-free loans—all in one secure platform.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="#download" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-2xl font-bold text-lg transition-all shadow-xl shadow-primary-500/20 flex items-center justify-center gap-2">
                        Get Started
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </a>
                    <a href="#features" class="w-full sm:w-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-8 py-4 rounded-2xl font-bold text-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                        View Features
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8 max-w-5xl mx-auto">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-3xl font-bold text-primary-600 mb-1">1k+</p>
                    <p class="text-sm text-slate-500 font-medium">Active Members</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-3xl font-bold text-primary-600 mb-1">0%</p>
                    <p class="text-sm text-slate-500 font-medium">Interest (Riba)</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-3xl font-bold text-primary-600 mb-1">100%</p>
                    <p class="text-sm text-slate-500 font-medium">Sharia Compliant</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-3xl font-bold text-primary-600 mb-1">24/7</p>
                    <p class="text-sm text-slate-500 font-medium">Mobile Access</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="py-12 bg-slate-50 dark:bg-[#0d0d0d] border-y border-slate-200 dark:border-slate-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                <div class="flex items-center gap-2 font-bold text-xl uppercase tracking-widest"><i data-lucide="shield-check" class="w-6 h-6 text-emerald-500"></i> Licensed</div>
                <div class="flex items-center gap-2 font-bold text-xl uppercase tracking-widest"><i data-lucide="award" class="w-6 h-6 text-emerald-500"></i> Ethical</div>
                <div class="flex items-center gap-2 font-bold text-xl uppercase tracking-widest"><i data-lucide="users" class="w-6 h-6 text-emerald-500"></i> Community</div>
                <div class="flex items-center gap-2 font-bold text-xl uppercase tracking-widest"><i data-lucide="lock" class="w-6 h-6 text-emerald-500"></i> Secure</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white dark:bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-3xl sm:text-5xl font-extrabold mb-6">Financial Freedom, <br> The Halal Way</h2>
                <p class="text-lg text-slate-600 dark:text-slate-400">Discover a range of financial products designed to grow your wealth while staying true to your values.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Target Savings -->
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/50 hover:bg-white dark:hover:bg-slate-900 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-2xl flex items-center justify-center text-primary-600 mb-8 group-hover:scale-110 transition-transform duration-500">
                        <i data-lucide="target" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Target Savings</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8">Plan and save for Hajj, Umrah, weddings, or education with automated goals and reminders.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-primary-500"></div> Automated deposits</li>
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-primary-500"></div> Multiple saving goals</li>
                    </ul>
                </div>

                <!-- Halal Investment -->
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/50 hover:bg-white dark:hover:bg-slate-900 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500">
                    <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center text-emerald-600 mb-8 group-hover:scale-110 transition-transform duration-500">
                        <i data-lucide="trending-up" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Halal Investment</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8">Put your money to work in vetted projects. Share profits based on the Mudarabah principle.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Ethical ventures</li>
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Transparent sharing</li>
                    </ul>
                </div>

                <!-- Qard Hasan -->
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/50 hover:bg-white dark:hover:bg-slate-900 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center text-blue-600 mb-8 group-hover:scale-110 transition-transform duration-500">
                        <i data-lucide="heart" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Qard Hasan</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8">Need a hand? Access benevolent interest-free loans for personal or business needs.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div> No interest (Riba)</li>
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div> Flexible repayment</li>
                    </ul>
                </div>

                <!-- Takaful -->
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/50 hover:bg-white dark:hover:bg-slate-900 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500">
                    <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center text-amber-600 mb-8 group-hover:scale-110 transition-transform duration-500">
                        <i data-lucide="shield" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Welfare (Takaful)</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8">A cooperative pool to protect members and their families during difficult times.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> Mutual assistance</li>
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> Debt protection</li>
                    </ul>
                </div>

                <!-- Virtual Accounts -->
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/50 hover:bg-white dark:hover:bg-slate-900 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500">
                    <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center text-purple-600 mb-8 group-hover:scale-110 transition-transform duration-500">
                        <i data-lucide="credit-card" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Virtual Accounts</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8">Get your own dedicated account number to fund your wallet instantly via bank transfer.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div> Instant funding</li>
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div> Personal accounts</li>
                    </ul>
                </div>

                <!-- Merchant Payments -->
                <div class="group p-8 rounded-[2.5rem] bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/50 hover:bg-white dark:hover:bg-slate-900 hover:shadow-2xl hover:shadow-primary-500/10 transition-all duration-500">
                    <div class="w-16 h-16 bg-rose-100 dark:bg-rose-900/30 rounded-2xl flex items-center justify-center text-rose-600 mb-8 group-hover:scale-110 transition-transform duration-500">
                        <i data-lucide="qr-code" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Pay with QR</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-8">Fast and secure payments to merchants using our "Pay with Attaqwa" QR system.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div> Zero contact</li>
                        <li class="flex items-center gap-3 text-sm font-medium text-slate-500"><div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div> Trusted network</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="py-24 bg-slate-50 dark:bg-[#0d0d0d]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Start Your Ethical Journey</h2>
                <p class="text-slate-600 dark:text-slate-400">Joining AT-TAQWA is simple and transparent. Here's how you can get started today.</p>
            </div>

            <div class="relative">
                <!-- Line -->
                <div class="hidden lg:block absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 dark:bg-slate-800 -translate-y-1/2"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 relative z-10">
                    <div class="text-center group">
                        <div class="w-20 h-20 bg-white dark:bg-slate-900 rounded-[2rem] border-4 border-slate-50 dark:border-[#0d0d0d] shadow-xl flex items-center justify-center text-2xl font-bold text-primary-600 mx-auto mb-8 group-hover:bg-primary-600 group-hover:text-white transition-all duration-500">1</div>
                        <h4 class="text-xl font-bold mb-2">Create Account</h4>
                        <p class="text-sm text-slate-500">Download the app and complete your profile in minutes.</p>
                    </div>
                    <div class="text-center group">
                        <div class="w-20 h-20 bg-white dark:bg-slate-900 rounded-[2rem] border-4 border-slate-50 dark:border-[#0d0d0d] shadow-xl flex items-center justify-center text-2xl font-bold text-primary-600 mx-auto mb-8 group-hover:bg-primary-600 group-hover:text-white transition-all duration-500">2</div>
                        <h4 class="text-xl font-bold mb-2">Join Cooperative</h4>
                        <p class="text-sm text-slate-500">Apply for membership and get verified by our team.</p>
                    </div>
                    <div class="text-center group">
                        <div class="w-20 h-20 bg-white dark:bg-slate-900 rounded-[2rem] border-4 border-slate-50 dark:border-[#0d0d0d] shadow-xl flex items-center justify-center text-2xl font-bold text-primary-600 mx-auto mb-8 group-hover:bg-primary-600 group-hover:text-white transition-all duration-500">3</div>
                        <h4 class="text-xl font-bold mb-2">Fund Wallet</h4>
                        <p class="text-sm text-slate-500">Add funds via your dedicated virtual account or card.</p>
                    </div>
                    <div class="text-center group">
                        <div class="w-20 h-20 bg-white dark:bg-slate-900 rounded-[2rem] border-4 border-slate-50 dark:border-[#0d0d0d] shadow-xl flex items-center justify-center text-2xl font-bold text-primary-600 mx-auto mb-8 group-hover:bg-primary-600 group-hover:text-white transition-all duration-500">4</div>
                        <h4 class="text-xl font-bold mb-2">Grow Wealth</h4>
                        <p class="text-sm text-slate-500">Start saving, investing, or access interest-free loans.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sharia Governance -->
    <section class="py-24 bg-slate-50 dark:bg-[#0d0d0d]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-900 rounded-[3rem] p-8 sm:p-16 border border-slate-100 dark:border-slate-800 flex flex-col lg:flex-row items-center gap-16">
                <div class="lg:w-1/2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold mb-6 border border-emerald-200 dark:border-emerald-800">
                        <i data-lucide="shield-check" class="w-3 h-3"></i>
                        SHARIA GOVERNANCE
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold mb-6">Built on Foundation of <span class="text-emerald-600">Trust & Integrity</span></h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-8 leading-relaxed">
                        At AT-TAQWA, we are committed to the highest standards of Islamic finance. Our operations are strictly supervised to ensure zero interest (Riba), avoidance of uncertainty (Gharar), and promotion of social justice.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600 shrink-0"><i data-lucide="check" class="w-4 h-4"></i></div>
                            <div>
                                <h4 class="font-bold text-sm">Sharia Audit</h4>
                                <p class="text-xs text-slate-500">Regular compliance audits by experts.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600 shrink-0"><i data-lucide="check" class="w-4 h-4"></i></div>
                            <div>
                                <h4 class="font-bold text-sm">Profit Sharing</h4>
                                <p class="text-xs text-slate-500">Fair distribution via Mudarabah.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:w-1/2 grid grid-cols-2 gap-4">
                    <div class="aspect-square bg-slate-50 dark:bg-slate-800/50 rounded-3xl flex flex-col items-center justify-center p-6 text-center">
                        <i data-lucide="book-open" class="w-8 h-8 text-primary-600 mb-4"></i>
                        <h4 class="font-bold text-sm">Ethics First</h4>
                    </div>
                    <div class="aspect-square bg-slate-50 dark:bg-slate-800/50 rounded-3xl flex flex-col items-center justify-center p-6 text-center mt-8">
                        <i data-lucide="pie-chart" class="w-8 h-8 text-primary-600 mb-4"></i>
                        <h4 class="font-bold text-sm">Equity</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Leadership Section -->
    <section id="leadership" class="py-24 bg-white dark:bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-bold mb-6 border border-primary-200 dark:border-primary-800">
                    <i data-lucide="users" class="w-3 h-3"></i>
                    OUR LEADERSHIP
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold mb-6">Guided by <span class="text-primary-600">Visionary Leaders</span></h2>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Our team is composed of dedicated professionals committed to ethical financial practices and community growth.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- President -->
                <div class="group relative bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] overflow-hidden border border-slate-100 dark:border-slate-800 transition-all hover:-translate-y-2">
                    <div class="aspect-[4/5] overflow-hidden">
                        <img src="{{ asset('images/team/president.jpg') }}" alt="President" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    </div>
                    <div class="p-8 text-center">
                        <h4 class="text-xl font-bold mb-1">Kazeem Olabamiji</h4>
                        <p class="text-primary-600 font-semibold text-sm">President</p>
                    </div>
                </div>

                <!-- Treasurer -->
                <div class="group relative bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] overflow-hidden border border-slate-100 dark:border-slate-800 transition-all hover:-translate-y-2">
                    <div class="aspect-[4/5] overflow-hidden">
                        <img src="{{ asset('images/team/treasurer.jpg') }}" alt="Treasurer" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    </div>
                    <div class="p-8 text-center">
                        <h4 class="text-xl font-bold mb-1">Aliyu Akeem Olaniyi</h4>
                        <p class="text-primary-600 font-semibold text-sm">Treasurer</p>
                    </div>
                </div>

                <!-- Chief Financial Secretary -->
                <div class="group relative bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] overflow-hidden border border-slate-100 dark:border-slate-800 transition-all hover:-translate-y-2">
                    <div class="aspect-[4/5] overflow-hidden">
                        <img src="{{ asset('images/team/chief-fin-sec.jpg') }}" alt="Chief Financial Secretary" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    </div>
                    <div class="p-8 text-center">
                        <h4 class="text-xl font-bold mb-1">AbdulAzeez Kadr Oladimeji</h4>
                        <p class="text-primary-600 font-semibold text-sm">Chief Financial Secretary</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-24 bg-slate-50 dark:bg-[#0d0d0d]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-bold mb-8">Frequently Asked Questions</h2>
                    <div class="space-y-4">
                        <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                            <h4 class="text-lg font-bold mb-2">Is AT-TAQWA really interest-free?</h4>
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">Yes. All our financial products are designed based on Sharia principles, which strictly prohibit Riba (interest). We use Mudarabah (profit sharing) and Qard Hasan (benevolent loans).</p>
                        </div>
                        <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                            <h4 class="text-lg font-bold mb-2">How do you make profit if there's no interest?</h4>
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">We invest in Halal businesses and projects. The profits generated from these investments are shared between the cooperative and the members according to pre-agreed ratios.</p>
                        </div>
                        <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                            <h4 class="text-lg font-bold mb-2">Who can join the cooperative?</h4>
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">While we operate on Islamic principles, our ethical services are open to anyone who believes in fair, transparent, and interest-free financial systems.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-primary-600 rounded-[3rem] p-8 sm:p-12 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-6">Have more questions?</h3>
                        <p class="text-primary-100 mb-8">Our support team is ready to assist you with any inquiries about our services, membership, or Sharia compliance.</p>
                        <form class="space-y-4">
                            <input type="text" placeholder="Your Name" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 placeholder-primary-100 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all">
                            <input type="email" placeholder="Email Address" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 placeholder-primary-100 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all">
                            <textarea placeholder="How can we help?" rows="4" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 placeholder-primary-100 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"></textarea>
                            <button type="button" class="w-full bg-white text-primary-600 font-bold py-4 rounded-xl hover:bg-primary-50 transition-all">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- App Showcase -->
    <section id="download" class="py-24 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-slate-900 rounded-[3rem] p-8 sm:p-16 lg:p-24 relative flex flex-col lg:flex-row items-center gap-16">
                <!-- Glow -->
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary-500/20 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/2"></div>

                <div class="flex-1 text-center lg:text-left relative z-10">
                    <h2 class="text-4xl sm:text-5xl font-extrabold text-white mb-8 leading-tight">Your entire cooperative <br> in your pocket.</h2>
                    <p class="text-slate-400 text-lg mb-12 max-w-xl">Join over 1,000 members enjoying seamless ethical banking. Available now on all platforms.</p>

                    <div class="flex flex-wrap justify-center lg:justify-start gap-4">
                        <a href="#" class="group bg-white text-slate-900 px-6 py-4 rounded-2xl flex items-center gap-3 hover:bg-primary-50 transition-all">
                            <i data-lucide="play" class="w-8 h-8 fill-slate-900"></i>
                            <div class="text-left">
                                <p class="text-[10px] uppercase font-bold text-slate-500 leading-none mb-1">Get it on</p>
                                <p class="text-xl font-extrabold leading-none">Google Play</p>
                            </div>
                        </a>
                        <a href="#" class="group bg-white text-slate-900 px-6 py-4 rounded-2xl flex items-center gap-3 hover:bg-primary-50 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-apple fill-slate-900"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06Z"/><path d="M10 2c1 .5 2 2 2 5"/></svg>
                            <div class="text-left">
                                <p class="text-[10px] uppercase font-bold text-slate-500 leading-none mb-1">Download on the</p>
                                <p class="text-xl font-extrabold leading-none">App Store</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="w-full lg:w-[400px] relative z-10">
                    <div class="relative mx-auto border-slate-800 dark:border-slate-800 bg-slate-800 border-[14px] rounded-[2.5rem] h-[600px] w-[300px] shadow-2xl">
                        <div class="h-[32px] w-[3px] bg-slate-800 absolute -start-[17px] top-[72px] rounded-s-lg"></div>
                        <div class="h-[46px] w-[3px] bg-slate-800 absolute -start-[17px] top-[124px] rounded-s-lg"></div>
                        <div class="h-[46px] w-[3px] bg-slate-800 absolute -start-[17px] top-[178px] rounded-s-lg"></div>
                        <div class="h-[64px] w-[3px] bg-slate-800 absolute -end-[17px] top-[142px] rounded-e-lg"></div>
                        <div class="rounded-[2rem] overflow-hidden h-[572px] bg-white dark:bg-slate-900">
                            <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&q=80&w=2070" class="h-full w-full object-cover opacity-80" alt="App Screenshot">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white dark:bg-slate-950 pt-24 pb-12 border-t border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                <div class="space-y-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 group w-fit">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="Logo" class="h-6 w-auto dark:hidden transition-transform group-hover:scale-105">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo-dark.svg') }}" alt="Logo" class="h-6 w-auto hidden dark:block transition-transform group-hover:scale-105">
                        <span class="font-bold text-lg uppercase tracking-tight">{{ config('brand.name', 'AT-TAQWA') }}</span>
                    </a>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Ethical financial services empowered by community and Sharia principles. Join us today.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-primary-600 transition-colors border border-slate-100 dark:border-slate-800">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-primary-600 transition-colors border border-slate-100 dark:border-slate-800">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-twitter"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-primary-600 transition-colors border border-slate-100 dark:border-slate-800">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-linkedin"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold mb-6">Product</h4>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li><a href="#features" class="hover:text-primary-600">Savings</a></li>
                        <li><a href="#features" class="hover:text-primary-600">Investments</a></li>
                        <li><a href="#features" class="hover:text-primary-600">Qard Hasan</a></li>
                        <li><a href="#features" class="hover:text-primary-600">Takaful</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold mb-6">Company</h4>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li><a href="#leadership" class="hover:text-primary-600">Leadership</a></li>
                        <li><a href="{{ url('/about-us') }}" class="hover:text-primary-600">About Us</a></li>
                        <li><a href="{{ url('/privacy-policy') }}" class="hover:text-primary-600">Privacy Policy</a></li>
                        <li><a href="{{ url('/terms') }}" class="hover:text-primary-600">Terms of Service</a></li>
                        <li><a href="mailto:attaqwaosogbo@gmail.com" class="hover:text-primary-600">Contact Support</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold mb-6">Contact</h4>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li class="flex items-start gap-3">
                            <i data-lucide="map-pin" class="w-5 h-5 text-primary-500 shrink-0"></i>
                            <span>Osogbo, Osun State, Nigeria</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i data-lucide="phone" class="w-5 h-5 text-primary-500 shrink-0"></i>
                            <a href="tel:08037282495">08037282495</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="pt-12 border-t border-slate-100 dark:border-slate-900 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-sm text-slate-400">
                    &copy; {{ date('Y') }} {{ config('brand.name', 'AT-TAQWA') }}. RC: 3449303
                </p>
                <div class="flex items-center gap-4 text-xs font-bold uppercase tracking-widest text-slate-400">
                    <span class="flex items-center gap-1"><i data-lucide="shield" class="w-3 h-3 text-emerald-500"></i> Secured</span>
                    <span class="flex items-center gap-1"><i data-lucide="check-circle" class="w-3 h-3 text-emerald-500"></i> Sharia Compliant</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 20) {
                nav.classList.add('py-2', 'shadow-sm');
            } else {
                nav.classList.remove('py-2', 'shadow-sm');
            }
        });
    </script>
</body>
</html>
