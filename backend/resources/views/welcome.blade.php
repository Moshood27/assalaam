<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'AT-TAQWA') }} - Ethical Islamic Fintech</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                            950: '#052e16',
                        },
                    },
                    fontFamily: {
                        sans: ['Instrument Sans', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .dark .glass {
            background: rgba(10, 10, 10, 0.7);
        }
        .hero-pattern {
            background-color: transparent;
            background-image: radial-gradient(at 0% 0%, rgba(34, 197, 94, 0.1) 0, transparent 50%), radial-gradient(at 50% 0%, rgba(16, 185, 129, 0.1) 0, transparent 50%), radial-gradient(at 100% 0%, rgba(5, 150, 105, 0.1) 0, transparent 50%);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#0a0a0a] text-slate-900 dark:text-slate-100 antialiased selection:bg-primary-500 selection:text-white">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 glass border-b border-slate-200 dark:border-slate-800 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="Logo" class="h-8 w-auto dark:hidden">
                    <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo-dark.svg') }}" alt="Logo" class="h-8 w-auto hidden dark:block">
                    <span class="font-bold text-lg tracking-tight hidden sm:block uppercase">{{ config('brand.name', 'AT-TAQWA') }}</span>
                </div>

                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium hover:text-primary-600 transition-colors">Features</a>
                    <a href="{{ url('/about-us') }}" class="text-sm font-medium hover:text-primary-600 transition-colors">About</a>
                    <a href="#download" class="text-sm font-medium hover:text-primary-600 transition-colors">App</a>
                </div>

                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/admin') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-full text-sm font-semibold transition-all shadow-lg shadow-primary-500/25">Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="text-sm font-medium hover:text-primary-600 transition-colors">Login</a>
                        <a href="#download" class="bg-slate-900 dark:bg-white dark:text-slate-900 text-white px-4 py-2 rounded-full text-sm font-semibold hover:opacity-90 transition-all">Get the App</a>
                    @endauth
                </div>
            </div>
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

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white dark:bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Financial Freedom, The Halal Way</h2>
                <p class="text-slate-600 dark:text-slate-400">Discover a range of financial products designed to grow your wealth while staying true to your values.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Target Savings -->
                <div class="group p-8 rounded-3xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-primary-500 transition-all duration-300">
                    <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-2xl flex items-center justify-center text-primary-600 mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="target" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Target Savings</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-6">Plan and save for Hajj, Umrah, weddings, or education with automated goals and reminders.</p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-2 text-sm text-slate-500"><i data-lucide="check" class="w-4 h-4 text-primary-500"></i> Automated deposits</li>
                        <li class="flex items-center gap-2 text-sm text-slate-500"><i data-lucide="check" class="w-4 h-4 text-primary-500"></i> Multiple goals</li>
                    </ul>
                </div>

                <!-- Halal Investment -->
                <div class="group p-8 rounded-3xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-primary-500 transition-all duration-300">
                    <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="trending-up" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Halal Investment</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-6">Put your money to work in vetted projects. Share profits based on the Mudarabah principle.</p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-2 text-sm text-slate-500"><i data-lucide="check" class="w-4 h-4 text-primary-500"></i> Ethical ventures</li>
                        <li class="flex items-center gap-2 text-sm text-slate-500"><i data-lucide="check" class="w-4 h-4 text-primary-500"></i> Transparent sharing</li>
                    </ul>
                </div>

                <!-- Qard Hasan -->
                <div class="group p-8 rounded-3xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-primary-500 transition-all duration-300">
                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center text-blue-600 mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="heart" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Qard Hasan</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-6">Need a hand? Access benevolent interest-free loans for personal or business needs.</p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-2 text-sm text-slate-500"><i data-lucide="check" class="w-4 h-4 text-primary-500"></i> No interest fees</li>
                        <li class="flex items-center gap-2 text-sm text-slate-500"><i data-lucide="check" class="w-4 h-4 text-primary-500"></i> Flexible repayment</li>
                    </ul>
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
                            <i data-lucide="apple" class="w-8 h-8 fill-slate-900"></i>
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
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="Logo" class="h-6 w-auto dark:hidden">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo-dark.svg') }}" alt="Logo" class="h-6 w-auto hidden dark:block">
                        <span class="font-bold text-lg uppercase tracking-tight">{{ config('brand.name', 'AT-TAQWA') }}</span>
                    </div>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Ethical financial services empowered by community and Sharia principles. Join us today.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-primary-600 transition-colors border border-slate-100 dark:border-slate-800"><i data-lucide="facebook" class="w-5 h-5"></i></a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-primary-600 transition-colors border border-slate-100 dark:border-slate-800"><i data-lucide="twitter" class="w-5 h-5"></i></a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 hover:text-primary-600 transition-colors border border-slate-100 dark:border-slate-800"><i data-lucide="linkedin" class="w-5 h-5"></i></a>
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
