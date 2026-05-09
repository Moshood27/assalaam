<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name', 'ATTAQWA CO-OPERATIVE') }} | The Future of Ethical Finance</title>
    <meta name="description" content="Shariah-compliant digital finance. Save, borrow, and invest without interest. Trusted by 10,000+ Nigerians.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jetbrains-mono:400,500,600|space-grotesk:400,500,600,700" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --brand-50:  #ecfdf5;
            --brand-100: #d1fae5;
            --brand-400: #34d399;
            --brand-500: #10b981;
            --brand-600: #059669;
            --brand-700: #047857;
            --brand-900: #064e3b;
            --ink-950:   #050B14;
            --ink-900:   #0A1220;
            --ink-800:   #131C2E;
            --grid:      rgba(255,255,255,0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            font-feature-settings: "ss01", "cv11";
        }
        h1, h2, h3, h4, .font-display {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.025em;
        }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .tabular { font-variant-numeric: tabular-nums; }

        /* === Hero Aurora === */
        .aurora {
            background:
                radial-gradient(60% 50% at 50% 0%, rgba(16,185,129,0.18), transparent 60%),
                radial-gradient(40% 35% at 15% 25%, rgba(52,211,153,0.10), transparent 60%),
                radial-gradient(40% 35% at 85% 30%, rgba(6,95,70,0.12), transparent 60%);
        }

        /* === Subtle Grid Background === */
        .bg-grid {
            background-image:
                linear-gradient(to right, rgba(15,23,42,0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(15,23,42,0.05) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 100%);
        }
        .bg-grid-dark {
            background-image:
                linear-gradient(to right, var(--grid) 1px, transparent 1px),
                linear-gradient(to bottom, var(--grid) 1px, transparent 1px);
            background-size: 56px 56px;
        }

        /* === Gradient Text === */
        .text-gradient {
            background: linear-gradient(120deg, #0f172a 0%, #047857 50%, #10b981 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-gradient-light {
            background: linear-gradient(120deg, #ffffff 0%, #a7f3d0 60%, #34d399 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* === Glass === */
        .glass {
            background: rgba(255,255,255,0.65);
            backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255,255,255,0.5);
        }
        .glass-dark {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.08);
        }

        /* === Marquee === */
        @keyframes marquee {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track {
            animation: marquee 40s linear infinite;
        }

        /* === Float === */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%      { transform: translateY(-12px); }
        }
        .float { animation: float 6s ease-in-out infinite; }
        .float-slow { animation: float 9s ease-in-out infinite; }

        /* === Pulse Dot === */
        @keyframes pulseDot {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0.6); }
            50%      { box-shadow: 0 0 0 8px rgba(16,185,129,0); }
        }
        .pulse-dot { animation: pulseDot 2s ease-out infinite; }

        /* === Ticker Number === */
        @keyframes tickFade {
            0% { opacity: 0; transform: translateY(4px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .tick { animation: tickFade 0.4s ease; }

        /* === Glow Border === */
        .glow-border {
            position: relative;
        }
        .glow-border::before {
            content: '';
            position: absolute;
            inset: -1px;
            background: linear-gradient(135deg, rgba(16,185,129,0.4), rgba(16,185,129,0));
            border-radius: inherit;
            z-index: -1;
            filter: blur(8px);
            opacity: 0;
            transition: opacity 0.4s;
        }
        .glow-border:hover::before { opacity: 1; }

        /* === Spark line === */
        .spark path { stroke-dasharray: 200; stroke-dashoffset: 200; animation: drawSpark 2s ease forwards; }
        @keyframes drawSpark {
            to { stroke-dashoffset: 0; }
        }

        /* === Button shine === */
        .shine {
            position: relative;
            overflow: hidden;
        }
        .shine::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.6s ease;
        }
        .shine:hover::after { left: 100%; }

        /* === Noise === */
        .noise::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.4'/%3E%3C/svg%3E");
            opacity: 0.04;
            pointer-events: none;
            mix-blend-mode: overlay;
        }

        /* === Rotating ring === */
        @keyframes rotate { to { transform: rotate(360deg); } }
        .ring-rotate { animation: rotate 24s linear infinite; }

        /* === Hide scrollbar === */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased selection:bg-emerald-200 selection:text-emerald-900">

<!-- ============ TOP STRIP ============ -->
<div class="hidden md:block bg-slate-950 text-slate-300 text-[11px] font-mono">
    <div class="max-w-7xl mx-auto px-6 py-2 flex items-center justify-between">
        <div class="flex items-center gap-6">
                <span class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                    <span>SYSTEM ONLINE</span>
                </span>
            <span class="text-slate-500">SHARIAH BOARD: <span class="text-emerald-400">CERTIFIED 2026</span></span>
            <span class="text-slate-500">UPTIME: <span class="text-white">99.998%</span></span>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-slate-500">NGN/USD: <span class="text-white tabular">1,548.20</span> <span class="text-emerald-400">▲ 0.42%</span></span>
            <span class="text-slate-500">PROFIT POOL: <span class="text-white tabular">₦180.4M</span></span>
        </div>
    </div>
</div>

<!-- ============ NAV ============ -->
<header x-data="{ mobileMenuOpen: false, scrolled: false }"
        @scroll.window="scrolled = (window.pageYOffset > 20)"
        :class="scrolled ? 'bg-white/85 backdrop-blur-xl border-b border-slate-200/70 py-3' : 'bg-transparent py-5'"
        class="sticky top-0 w-full z-50 transition-all duration-300">
    <nav class="max-w-7xl mx-auto px-6 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="relative">
                <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-9 w-auto transition-transform group-hover:scale-105 relative z-10">
            </div>
            <div class="flex flex-col leading-none">
                <span class="font-display font-bold text-[17px] tracking-tight text-slate-900">{{ config('brand.name') }}</span>
                <span class="font-mono text-[9px] text-slate-400 tracking-[0.2em] mt-0.5">CO-OPERATIVE · v3.0</span>
            </div>
        </a>

        <div class="hidden lg:flex items-center gap-8">
            <div class="flex items-center gap-7 text-[13.5px] font-medium text-slate-600">
                <a href="#products" class="hover:text-slate-900 transition-colors">Products</a>
                <a href="#how-it-works" class="hover:text-slate-900 transition-colors">How it works</a>
                <a href="#about" class="hover:text-slate-900 transition-colors">Compliance</a>
                <a href="#faq" class="hover:text-slate-900 transition-colors">FAQ</a>
            </div>
            <div class="h-5 w-[1px] bg-slate-200"></div>
            <div class="flex items-center gap-2">
                <a href="{{ url('/app/login') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900 px-4 py-2 transition-colors">Sign in</a>
                <a href="{{ url('/app/register') }}" class="shine bg-slate-900 text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-emerald-600 transition-all flex items-center gap-2 active:scale-95">
                    Open account
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>

        <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 text-slate-900">
            <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </nav>

    <div x-show="mobileMenuOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="lg:hidden bg-white border-b border-slate-100 px-6 pt-4 pb-8 shadow-2xl">
        <div class="flex flex-col gap-4">
            <a @click="mobileMenuOpen = false" href="#products" class="text-base font-semibold text-slate-900 py-2">Products</a>
            <a @click="mobileMenuOpen = false" href="#how-it-works" class="text-base font-semibold text-slate-900 py-2">How it works</a>
            <a @click="mobileMenuOpen = false" href="#about" class="text-base font-semibold text-slate-900 py-2">Compliance</a>
            <a @click="mobileMenuOpen = false" href="#faq" class="text-base font-semibold text-slate-900 py-2">FAQ</a>
            <div class="pt-4 flex flex-col gap-2.5 border-t border-slate-100">
                <a href="{{ url('/app/login') }}" class="text-center font-semibold text-slate-900 py-3 rounded-xl border border-slate-200">Sign in</a>
                <a href="{{ url('/app/register') }}" class="text-center font-semibold text-white bg-slate-900 py-3 rounded-xl">Open account</a>
            </div>
        </div>
    </div>
</header>

<main>
    <!-- ============ HERO ============ -->
    <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-40 aurora overflow-hidden">
        <div class="absolute inset-0 bg-grid"></div>

        <div class="relative max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-12 gap-16 items-center">
                <!-- Left: copy -->
                <div class="lg:col-span-6 relative z-10">
                    <a href="#products" class="inline-flex items-center gap-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-medium pl-1.5 pr-4 py-1.5 rounded-full mb-8 hover:border-emerald-300 transition-colors group shadow-sm">
                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">New</span>
                        Mudarabah Pool 2026 — apply now
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <h1 class="font-display text-[44px] sm:text-6xl lg:text-7xl xl:text-[80px] font-bold text-slate-900 leading-[0.98] mb-6">
                        Banking, but <br>
                        <span class="text-gradient">interest-free.</span>
                    </h1>

                    <p class="text-lg text-slate-600 mb-10 max-w-xl leading-relaxed">
                        A digital co-operative built on Shariah principles. Save, access benevolent loans, and earn pure profits — all from one Nigerian-built app trusted by <span class="text-slate-900 font-semibold">10,000+ members.</span>
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 mb-12">
                        <a href="{{ url('/app/register') }}" class="shine bg-slate-900 hover:bg-emerald-600 text-white text-[15px] font-semibold px-7 py-4 rounded-2xl shadow-xl shadow-slate-900/20 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2">
                            Get started — free
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="#products" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-900 text-[15px] font-semibold px-7 py-4 rounded-2xl transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Watch overview
                        </a>
                    </div>

                    <!-- Stats strip -->
                    <div class="grid grid-cols-3 gap-2 max-w-lg pt-8 border-t border-slate-200">
                        <div>
                            <div class="font-display text-2xl font-bold text-slate-900 tabular">₦2.5B+</div>
                            <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider mt-1">Assets pooled</div>
                        </div>
                        <div>
                            <div class="font-display text-2xl font-bold text-slate-900 tabular">10,247</div>
                            <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider mt-1">Active members</div>
                        </div>
                        <div>
                            <div class="font-display text-2xl font-bold text-emerald-600 tabular">0%</div>
                            <div class="text-[11px] font-medium text-slate-500 uppercase tracking-wider mt-1">Riba, ever</div>
                        </div>
                    </div>
                </div>

                <!-- Right: product mockup -->
                <div class="lg:col-span-6 relative">
                    <!-- Decorative floats -->
                    <div class="absolute -top-6 left-8 z-30 float">
                        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-900/10 border border-slate-100 px-4 py-3 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <div class="text-[10px] font-mono text-slate-400">PROFIT SHARE</div>
                                <div class="text-sm font-semibold text-slate-900 tabular">+₦12,400 credited</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-4 -left-4 z-30 float-slow">
                        <div class="bg-slate-900 text-white rounded-2xl shadow-2xl px-4 py-3 flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></div>
                            <div>
                                <div class="text-[10px] font-mono text-emerald-400">SHARIAH BOARD</div>
                                <div class="text-sm font-semibold tabular">Monthly audit · passed</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card stack -->
                    <div class="relative">
                        <!-- Back card -->
                        <div class="absolute inset-0 translate-x-4 translate-y-4 bg-emerald-100 rounded-[2rem] blur-2xl opacity-60"></div>
                        <div class="absolute inset-0 translate-x-3 translate-y-3 bg-white rounded-[2rem] border border-slate-200/60"></div>

                        <!-- Main dashboard card -->
                        <div class="relative bg-slate-950 rounded-[2rem] p-7 text-white overflow-hidden border border-slate-800 shadow-2xl noise">
                            <!-- bg glow -->
                            <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-500/20 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
                            <div class="absolute inset-0 bg-grid-dark opacity-50"></div>

                            <!-- Header -->
                            <div class="relative flex items-center justify-between mb-7">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-slate-950 font-bold text-sm">A</div>
                                    <div>
                                        <div class="text-[10px] font-mono text-slate-500">MEMBER</div>
                                        <div class="text-xs font-semibold">Aisha Bello</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                                    <span class="text-[9px] font-mono text-emerald-400 uppercase tracking-widest">Halal</span>
                                </div>
                            </div>

                            <!-- Balance -->
                            <div class="relative mb-6">
                                <div class="text-[10px] font-mono text-slate-400 uppercase tracking-widest mb-1.5">Available balance</div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-slate-400 text-2xl font-light">₦</span>
                                    <span class="font-display text-4xl lg:text-[44px] font-bold tabular">2,450,000</span>
                                    <span class="text-slate-500 text-2xl font-light">.00</span>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-emerald-400 text-xs font-mono">▲ ₦62,400 (2.6%)</span>
                                    <span class="text-slate-500 text-xs">this month</span>
                                </div>
                            </div>

                            <!-- Mini chart -->
                            <div class="relative h-20 mb-6">
                                <svg class="w-full h-full spark" viewBox="0 0 300 80" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="grad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.3"/>
                                            <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    <path d="M0,60 L20,55 L40,58 L60,45 L80,48 L100,38 L120,42 L140,30 L160,32 L180,22 L200,28 L220,18 L240,22 L260,10 L280,15 L300,5 L300,80 L0,80 Z" fill="url(#grad)"/>
                                    <path d="M0,60 L20,55 L40,58 L60,45 L80,48 L100,38 L120,42 L140,30 L160,32 L180,22 L200,28 L220,18 L240,22 L260,10 L280,15 L300,5" stroke="#10b981" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>

                            <!-- Quick stats grid -->
                            <div class="relative grid grid-cols-3 gap-2.5 mb-6">
                                <div class="bg-white/5 backdrop-blur rounded-xl p-3 border border-white/5">
                                    <div class="text-[9px] font-mono text-slate-500 uppercase mb-1">Savings</div>
                                    <div class="text-sm font-semibold tabular">₦1.2M</div>
                                </div>
                                <div class="bg-white/5 backdrop-blur rounded-xl p-3 border border-white/5">
                                    <div class="text-[9px] font-mono text-slate-500 uppercase mb-1">Hajj fund</div>
                                    <div class="text-sm font-semibold tabular">₦820K</div>
                                </div>
                                <div class="bg-emerald-500/10 backdrop-blur rounded-xl p-3 border border-emerald-500/20">
                                    <div class="text-[9px] font-mono text-emerald-400 uppercase mb-1">Profit YTD</div>
                                    <div class="text-sm font-semibold text-emerald-400 tabular">+12.4%</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="relative grid grid-cols-2 gap-2.5">
                                <button class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs py-3 rounded-xl flex items-center justify-center gap-2 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    Add savings
                                </button>
                                <button class="bg-white/5 hover:bg-white/10 border border-white/10 font-semibold text-xs py-3 rounded-xl flex items-center justify-center gap-2 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    Transfer
                                </button>
                            </div>
                        </div>

                        <!-- Side notification (overlapping) -->
                        <div class="hidden md:block absolute top-1/2 -right-6 z-30 -translate-y-1/2 float">
                            <div class="bg-white rounded-2xl shadow-2xl shadow-slate-900/10 border border-slate-100 px-4 py-3 w-56">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-[10px] font-mono text-slate-400 uppercase">Hajj Goal</div>
                                    <div class="text-[10px] font-bold text-emerald-600">85%</div>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 85%"></div>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-2 tabular">₦820,000 / ₦965,000</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trust marquee -->
            <div class="mt-24 lg:mt-32">
                <div class="text-center text-[11px] font-mono text-slate-400 uppercase tracking-[0.25em] mb-8">
                    Trusted &amp; certified by
                </div>
                <div class="relative overflow-hidden mask-fade">
                    <div class="flex marquee-track w-max gap-16 items-center text-slate-400">
                        @php $badges = ['REGISTERED CO-OPERATIVE', '· ', 'AAOIFI ALIGNED', '· ', 'SHARIAH BOARD CERTIFIED', '· ', 'ISO 27001 SECURITY', '· ', 'PCI-DSS COMPLIANT', '· ', '256-BIT TLS', '· ', 'NDPR REGISTERED']; @endphp
                        @for($i=0; $i<2; $i++)
                            @foreach($badges as $b)
                                <span class="font-display font-semibold text-base whitespace-nowrap {{ $b === '· ' ? 'text-emerald-500' : '' }}">{{ $b }}</span>
                            @endforeach
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ LIVE METRICS BAR ============ -->
    <section class="relative bg-slate-950 text-white overflow-hidden">
        <div class="absolute inset-0 bg-grid-dark opacity-60"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-px bg-gradient-to-r from-transparent via-emerald-400/60 to-transparent"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-12 lg:py-16">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-slate-800">
                <div class="px-6 first:pl-0">
                    <div class="text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-3">Profit Distributed</div>
                    <div class="font-display text-3xl lg:text-4xl font-bold tabular tick">₦180.4M</div>
                    <div class="text-xs text-slate-400 font-mono mt-1.5">FY 2025 · audited</div>
                </div>
                <div class="px-6">
                    <div class="text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-3">Qard Hasan Issued</div>
                    <div class="font-display text-3xl lg:text-4xl font-bold tabular tick">₦450M+</div>
                    <div class="text-xs text-slate-400 font-mono mt-1.5">0% interest · always</div>
                </div>
                <div class="px-6">
                    <div class="text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-3">Member Growth</div>
                    <div class="font-display text-3xl lg:text-4xl font-bold tabular tick">+24% YoY</div>
                    <div class="text-xs text-slate-400 font-mono mt-1.5">Compounded growth</div>
                </div>
                <div class="px-6">
                    <div class="text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-3">Net Promoter</div>
                    <div class="font-display text-3xl lg:text-4xl font-bold tabular tick">68 NPS</div>
                    <div class="text-xs text-slate-400 font-mono mt-1.5">vs. 31 industry avg.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ PRODUCTS / BENTO ============ -->
    <section id="products" class="py-24 lg:py-36 bg-slate-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="max-w-3xl mb-16 lg:mb-20">
                <div class="inline-flex items-center gap-2 text-emerald-700 text-[11px] font-mono uppercase tracking-[0.25em] mb-5">
                    <span class="w-6 h-px bg-emerald-500"></span>
                    Products
                </div>
                <h2 class="font-display text-4xl lg:text-6xl font-bold text-slate-900 mb-5 leading-[1.05]">
                    One platform, <br>
                    every halal financial primitive.
                </h2>
                <p class="text-slate-600 text-lg max-w-xl">
                    From multi-goal savings to benevolent loans, every product is engineered alongside a Shariah board — so you ship plans, not compromises.
                </p>
            </div>

            <div class="grid grid-cols-12 gap-4 lg:gap-5">
                <!-- Savings: large -->
                <div class="col-span-12 lg:col-span-7 group bg-white rounded-3xl border border-slate-200 p-8 lg:p-12 relative overflow-hidden hover:border-emerald-300 hover:shadow-2xl hover:shadow-emerald-500/5 transition-all">
                    <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-100/40 rounded-full blur-3xl -translate-y-1/4 translate-x-1/4"></div>

                    <div class="relative">
                        <div class="flex items-center gap-2 text-[10px] font-mono text-emerald-700 uppercase tracking-[0.2em] mb-5">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                            01 / Savings
                        </div>
                        <h3 class="font-display text-3xl lg:text-4xl font-bold text-slate-900 mb-4 max-w-md">Multi-goal savings, on autopilot.</h3>
                        <p class="text-slate-600 mb-8 max-w-md leading-relaxed">Set goals for Hajj, education or business. Lock funds, automate contributions, and watch your discipline compound.</p>

                        <!-- Goal cards -->
                        <div class="grid sm:grid-cols-2 gap-3 mb-8 max-w-xl">
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2L3 7v11h4v-6h6v6h4V7l-7-5z"/></svg>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-900">Hajj 2027</span>
                                    </div>
                                    <span class="text-[10px] font-mono text-emerald-600 font-bold">85%</span>
                                </div>
                                <div class="h-1.5 bg-white rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width:85%"></div>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-2 tabular font-mono">₦820K / ₦965K</div>
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-900">School fees</span>
                                    </div>
                                    <span class="text-[10px] font-mono text-emerald-600 font-bold">42%</span>
                                </div>
                                <div class="h-1.5 bg-white rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-400 rounded-full" style="width:42%"></div>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-2 tabular font-mono">₦210K / ₦500K</div>
                            </div>
                        </div>

                        <a href="{{ url('/app/register') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900 hover:text-emerald-600 transition-colors">
                            Open a savings goal
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Qard Hasan: dark vertical -->
                <div class="col-span-12 lg:col-span-5 group bg-slate-950 text-white rounded-3xl p-8 lg:p-10 relative overflow-hidden border border-slate-900">
                    <div class="absolute inset-0 bg-grid-dark opacity-50"></div>
                    <div class="absolute -top-20 -right-20 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl"></div>

                    <div class="relative h-full flex flex-col">
                        <div class="flex items-center gap-2 text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-5">
                            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full pulse-dot"></span>
                            02 / Qard Hasan
                        </div>
                        <h3 class="font-display text-3xl lg:text-4xl font-bold mb-4">Loans without a catch.</h3>
                        <p class="text-slate-300 mb-6 leading-relaxed">Members access benevolent loans for emergencies and growth. No interest. No hidden fees. Repay at your pace.</p>

                        <!-- Mock loan terms -->
                        <div class="glass-dark rounded-2xl p-5 mb-8 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Principal</span>
                                <span class="font-semibold tabular">₦500,000</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Interest</span>
                                <span class="font-semibold text-emerald-400 tabular">₦0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Service fee</span>
                                <span class="font-semibold tabular">₦2,500 (flat)</span>
                            </div>
                            <div class="border-t border-white/10 pt-3 flex justify-between text-sm">
                                <span class="text-slate-300 font-semibold">Total payable</span>
                                <span class="font-bold text-emerald-400 tabular">₦502,500</span>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <a href="#" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-400 hover:text-white transition-colors">
                                See loan terms
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Halal Investing -->
                <div class="col-span-12 sm:col-span-6 lg:col-span-4 group bg-white rounded-3xl border border-slate-200 p-8 hover:border-emerald-300 hover:shadow-xl transition-all">
                    <div class="flex items-center gap-2 text-[10px] font-mono text-emerald-700 uppercase tracking-[0.2em] mb-5">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                        03 / Mudarabah
                    </div>
                    <h3 class="font-display text-2xl font-bold text-slate-900 mb-3">Halal profits, vetted assets.</h3>
                    <p class="text-slate-600 text-sm mb-6 leading-relaxed">Earn from professionally managed Shariah-compliant business pools. Quarterly distributions.</p>

                    <!-- Mock yield card -->
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-mono text-slate-500 uppercase">Pool yield (TTM)</span>
                            <span class="font-display text-2xl font-bold text-emerald-600 tabular">12.4%</span>
                        </div>
                        <div class="flex items-end gap-1 h-10">
                            @php $bars = [40, 55, 38, 70, 60, 80, 72, 90]; @endphp
                            @foreach($bars as $h)
                                <div class="flex-1 bg-emerald-500 rounded-t" style="height: {{ $h }}%; opacity: {{ 0.3 + ($loop->index * 0.08) }}"></div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Takaful -->
                <div class="col-span-12 sm:col-span-6 lg:col-span-4 group bg-white rounded-3xl border border-slate-200 p-8 hover:border-emerald-300 hover:shadow-xl transition-all">
                    <div class="flex items-center gap-2 text-[10px] font-mono text-emerald-700 uppercase tracking-[0.2em] mb-5">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                        04 / Takaful
                    </div>
                    <h3 class="font-display text-2xl font-bold text-slate-900 mb-3">Mutual support fund.</h3>
                    <p class="text-slate-600 text-sm mb-6 leading-relaxed">A community-funded safety net. When a member faces hardship, the fund responds — that's true Islamic insurance.</p>

                    <!-- Member avatars -->
                    <div class="flex items-center gap-3">
                        <div class="flex -space-x-2">
                            @foreach(['#10b981','#34d399','#059669','#047857'] as $c)
                                <div class="w-8 h-8 rounded-full border-2 border-white" style="background: {{ $c }}"></div>
                            @endforeach
                            <div class="w-8 h-8 rounded-full border-2 border-white bg-slate-100 text-slate-600 text-[10px] font-bold flex items-center justify-center">10k+</div>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">contributing members</span>
                    </div>
                </div>

                <!-- Mobile App -->
                <div class="col-span-12 lg:col-span-4 group bg-gradient-to-br from-slate-900 via-emerald-900 to-slate-900 rounded-3xl p-8 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-grid-dark opacity-40"></div>

                    <div class="relative">
                        <div class="flex items-center gap-2 text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-5">
                            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                            05 / Mobile
                        </div>
                        <h3 class="font-display text-2xl font-bold mb-3">Pocket co-operative.</h3>
                        <p class="text-slate-200 text-sm mb-6 leading-relaxed">iOS &amp; Android apps. Biometric login, instant transfers, profit alerts.</p>

                        <div class="flex flex-col gap-2">
                            <a href="#" class="flex items-center gap-3 bg-white/10 hover:bg-white/15 backdrop-blur border border-white/10 rounded-xl px-4 py-2.5 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                                <div class="text-left">
                                    <div class="text-[9px] font-mono text-slate-300 uppercase">Download on</div>
                                    <div class="text-sm font-semibold">App Store</div>
                                </div>
                            </a>
                            <a href="#" class="flex items-center gap-3 bg-white/10 hover:bg-white/15 backdrop-blur border border-white/10 rounded-xl px-4 py-2.5 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 20.5V3.5c0-.6.4-1 .9-1.2L13.7 12 3.9 21.7c-.5-.2-.9-.6-.9-1.2zM14.7 13l2.9 2.9-11.5 6.6L14.7 13zm5.6-3.2c.5.4.7.9.7 1.5s-.2 1.1-.7 1.5l-2.5 1.4-3-3 3-3 2.5 1.6zM6.1 1.7l11.5 6.6L14.7 11 6.1 1.7z"/></svg>
                                <div class="text-left">
                                    <div class="text-[9px] font-mono text-slate-300 uppercase">Get it on</div>
                                    <div class="text-sm font-semibold">Google Play</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ HOW IT WORKS ============ -->
    <section id="how-it-works" class="py-24 lg:py-36 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-12 gap-16 mb-20">
                <div class="lg:col-span-5">
                    <div class="inline-flex items-center gap-2 text-emerald-700 text-[11px] font-mono uppercase tracking-[0.25em] mb-5">
                        <span class="w-6 h-px bg-emerald-500"></span>
                        How it works
                    </div>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-slate-900 leading-[1.05]">
                        Onboard in <span class="text-emerald-600">under 4 minutes.</span>
                    </h2>
                </div>
                <p class="lg:col-span-6 lg:col-start-7 text-lg text-slate-600 leading-relaxed self-end">
                    We've reduced co-operative onboarding from days of paperwork to a single conversation. BVN-verified, Shariah-vetted, end-to-end encrypted.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-5">
                <!-- Step 1 -->
                <div class="relative bg-slate-50 rounded-3xl p-8 border border-slate-100 group hover:bg-white hover:shadow-2xl hover:shadow-slate-900/5 hover:border-emerald-200 transition-all">
                    <div class="flex items-center justify-between mb-12">
                        <span class="font-mono text-[10px] tracking-widest text-slate-400">STEP 01</span>
                        <span class="font-display text-5xl font-bold text-slate-200 group-hover:text-emerald-200 transition-colors">01</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h4 class="font-display text-xl font-bold text-slate-900 mb-2">Open your account</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">Phone number + BVN. We verify your identity in seconds with NIBSS.</p>
                </div>
                <!-- Step 2 -->
                <div class="relative bg-slate-50 rounded-3xl p-8 border border-slate-100 group hover:bg-white hover:shadow-2xl hover:shadow-slate-900/5 hover:border-emerald-200 transition-all">
                    <div class="flex items-center justify-between mb-12">
                        <span class="font-mono text-[10px] tracking-widest text-slate-400">STEP 02</span>
                        <span class="font-display text-5xl font-bold text-slate-200 group-hover:text-emerald-200 transition-colors">02</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8m6 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="font-display text-xl font-bold text-slate-900 mb-2">Fund &amp; pick a goal</h4>
                    <p class="text-slate-600 text-sm leading-relaxed">Bank transfer, card, or USSD. Pick a savings goal or join a Mudarabah pool.</p>
                </div>
                <!-- Step 3 -->
                <div class="relative bg-slate-950 text-white rounded-3xl p-8 group overflow-hidden">
                    <div class="absolute inset-0 bg-grid-dark opacity-40"></div>
                    <div class="absolute -bottom-10 -right-10 w-56 h-56 bg-emerald-500/20 rounded-full blur-3xl"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-12">
                            <span class="font-mono text-[10px] tracking-widest text-emerald-400">STEP 03</span>
                            <span class="font-display text-5xl font-bold text-white/10 group-hover:text-emerald-400/30 transition-colors">03</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500 flex items-center justify-center mb-6">
                            <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <h4 class="font-display text-xl font-bold mb-2">Watch it grow — halal.</h4>
                        <p class="text-slate-300 text-sm leading-relaxed">Track contributions, profit shares, and goal progress live. Withdraw anytime.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ COMPLIANCE / WHY ============ -->
    <section id="about" class="py-24 lg:py-36 bg-slate-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-20 items-center">
                <!-- Left: visual stack -->
                <div class="lg:col-span-6 order-2 lg:order-1 relative">
                    <!-- Center compliance card -->
                    <div class="relative max-w-md mx-auto">
                        <div class="absolute -inset-4 bg-emerald-200/30 rounded-[2.5rem] blur-2xl"></div>
                        <div class="relative bg-white rounded-3xl border border-slate-200 p-8 shadow-2xl shadow-slate-900/5">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-display font-bold text-slate-900">Shariah Audit</div>
                                        <div class="text-[10px] font-mono text-slate-500 uppercase tracking-widest">Q1 · 2026</div>
                                    </div>
                                </div>
                                <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">Passed</span>
                            </div>

                            <div class="space-y-3">
                                @php $checks = [
                                        ['Riba (interest) screening', 'Clean'],
                                        ['Gharar (uncertainty) review', 'Clean'],
                                        ['Maysir (gambling) review', 'Clean'],
                                        ['Asset-backed verification', '100%'],
                                        ['Zakat allocation', 'Allocated'],
                                    ]; @endphp
                                @foreach($checks as $c)
                                    <div class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0">
                                        <div class="flex items-center gap-3">
                                            <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <span class="text-sm text-slate-700">{{ $c[0] }}</span>
                                        </div>
                                        <span class="text-xs font-semibold text-emerald-700 font-mono">{{ $c[1] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-xs text-slate-500">Audited by</span>
                                <span class="text-xs font-semibold text-slate-900">Independent Shariah Board</span>
                            </div>
                        </div>

                        <!-- Floating badges -->
                        <div class="absolute -top-6 -right-6 bg-slate-950 text-white rounded-2xl px-4 py-3 shadow-xl float">
                            <div class="text-[9px] font-mono text-emerald-400 uppercase tracking-widest">Encryption</div>
                            <div class="text-sm font-bold">AES-256</div>
                        </div>
                        <div class="absolute -bottom-6 -left-6 bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-xl float-slow">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 pulse-dot"></div>
                                <div>
                                    <div class="text-[9px] font-mono text-slate-500 uppercase tracking-widest">Real-time</div>
                                    <div class="text-sm font-bold text-slate-900">Compliance</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: copy -->
                <div class="lg:col-span-6 order-1 lg:order-2">
                    <div class="inline-flex items-center gap-2 text-emerald-700 text-[11px] font-mono uppercase tracking-[0.25em] mb-5">
                        <span class="w-6 h-px bg-emerald-500"></span>
                        Compliance &amp; Trust
                    </div>
                    <h2 class="font-display text-4xl lg:text-6xl font-bold text-slate-900 mb-6 leading-[1.05]">
                        Built like a bank. <br>
                        <span class="text-emerald-600">Audited like a mosque.</span>
                    </h2>
                    <p class="text-slate-600 text-lg mb-10 leading-relaxed">
                        Every product passes a continuous Shariah audit and a continuous security audit — because being halal isn't a one-time stamp.
                    </p>

                    <div class="grid sm:grid-cols-2 gap-6">
                        <div class="border-l-2 border-emerald-500 pl-5">
                            <div class="font-mono text-[10px] text-emerald-700 uppercase tracking-widest mb-1.5">Riba-free, period.</div>
                            <p class="text-slate-700 text-sm leading-relaxed">No interest, ever. Profits come from real assets and shared trade.</p>
                        </div>
                        <div class="border-l-2 border-slate-300 pl-5">
                            <div class="font-mono text-[10px] text-slate-700 uppercase tracking-widest mb-1.5">Bank-grade security</div>
                            <p class="text-slate-700 text-sm leading-relaxed">256-bit TLS, biometric auth, hardware-key admin access.</p>
                        </div>
                        <div class="border-l-2 border-slate-300 pl-5">
                            <div class="font-mono text-[10px] text-slate-700 uppercase tracking-widest mb-1.5">Member-owned</div>
                            <p class="text-slate-700 text-sm leading-relaxed">A registered co-operative. The members are the shareholders.</p>
                        </div>
                        <div class="border-l-2 border-slate-300 pl-5">
                            <div class="font-mono text-[10px] text-slate-700 uppercase tracking-widest mb-1.5">Transparent ledger</div>
                            <p class="text-slate-700 text-sm leading-relaxed">Every kobo accounted for, audited annually, accessible 24/7.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ TESTIMONIALS ============ -->
    <section class="py-24 lg:py-36 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between mb-16 gap-8">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 text-emerald-700 text-[11px] font-mono uppercase tracking-[0.25em] mb-5">
                        <span class="w-6 h-px bg-emerald-500"></span>
                        Member voices
                    </div>
                    <h2 class="font-display text-4xl lg:text-6xl font-bold text-slate-900 leading-[1.05]">10,000 stories. <br> One community.</h2>
                </div>
                <div class="flex items-center gap-4 bg-slate-50 rounded-2xl px-5 py-4 border border-slate-100">
                    <div class="flex -space-x-2">
                        @for($i=0; $i<5; $i++)
                            <div class="w-10 h-10 rounded-full border-2 border-white bg-emerald-100 flex items-center justify-center text-[10px] font-bold text-emerald-700">
                                {{ ['AA', 'ZM', 'YM', 'IB', 'SK'][$i] }}
                            </div>
                        @endfor
                    </div>
                    <div>
                        <div class="flex items-center gap-1 mb-0.5">
                            @for($i=0; $i<5; $i++)
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <div class="text-xs font-semibold text-slate-900">4.9 from 10,000+ members</div>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-5">
                <!-- T1 -->
                <figure class="group bg-slate-50 rounded-3xl p-8 border border-slate-100 hover:border-emerald-200 hover:bg-white hover:shadow-xl transition-all">
                    <svg class="w-7 h-7 text-emerald-500 mb-6" fill="currentColor" viewBox="0 0 24 24"><path d="M9.13 8c-1.51.45-2.79 1.36-3.84 2.74-1.05 1.38-1.6 2.96-1.65 4.74-.05 1.78.49 3.05 1.62 3.81C6.4 20.05 7.7 20.31 9.16 20c1.46-.31 2.59-1.07 3.39-2.27.8-1.21 1.13-2.55.99-4.04-.14-1.49-.79-2.66-1.95-3.51-1.16-.85-2.32-1.21-3.49-1.08L9.13 8zm10 0c-1.51.45-2.79 1.36-3.84 2.74-1.05 1.38-1.6 2.96-1.65 4.74-.05 1.78.49 3.05 1.62 3.81C16.4 20.05 17.7 20.31 19.16 20c1.46-.31 2.59-1.07 3.39-2.27.8-1.21 1.13-2.55.99-4.04-.14-1.49-.79-2.66-1.95-3.51-1.16-.85-2.32-1.21-3.49-1.08L19.13 8z" transform="rotate(180 12 14)"/></svg>
                    <blockquote class="text-slate-800 text-lg leading-relaxed mb-8">
                        "Funded my entire grocery business with a Qard Hasan in 2024. No interest, no stress. The repayment terms moved with my cashflow — that's a co-operative, not a bank."
                    </blockquote>
                    <figcaption class="flex items-center gap-3 pt-6 border-t border-slate-200">
                        <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center font-bold text-emerald-700">AA</div>
                        <div>
                            <div class="font-semibold text-slate-900">Abdullah Adamu</div>
                            <div class="text-[11px] text-slate-500 font-mono">Lagos · Member since 2023</div>
                        </div>
                    </figcaption>
                </figure>

                <!-- T2 (featured) -->
                <figure class="group bg-slate-950 text-white rounded-3xl p-8 relative overflow-hidden lg:-translate-y-4 shadow-2xl shadow-slate-900/20">
                    <div class="absolute inset-0 bg-grid-dark opacity-50"></div>
                    <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl"></div>
                    <div class="relative">
                        <svg class="w-7 h-7 text-emerald-400 mb-6" fill="currentColor" viewBox="0 0 24 24"><path d="M9.13 8c-1.51.45-2.79 1.36-3.84 2.74-1.05 1.38-1.6 2.96-1.65 4.74-.05 1.78.49 3.05 1.62 3.81C6.4 20.05 7.7 20.31 9.16 20c1.46-.31 2.59-1.07 3.39-2.27.8-1.21 1.13-2.55.99-4.04-.14-1.49-.79-2.66-1.95-3.51-1.16-.85-2.32-1.21-3.49-1.08L9.13 8zm10 0c-1.51.45-2.79 1.36-3.84 2.74-1.05 1.38-1.6 2.96-1.65 4.74-.05 1.78.49 3.05 1.62 3.81C16.4 20.05 17.7 20.31 19.16 20c1.46-.31 2.59-1.07 3.39-2.27.8-1.21 1.13-2.55.99-4.04-.14-1.49-.79-2.66-1.95-3.51-1.16-.85-2.32-1.21-3.49-1.08L19.13 8z" transform="rotate(180 12 14)"/></svg>
                        <blockquote class="text-white text-lg leading-relaxed mb-8">
                            "The Mudarabah pool returned 12.4% YTD — fully halal, fully transparent. I get a quarterly statement, an asset list, and a Shariah review. Nobody else does this in Nigeria."
                        </blockquote>
                        <figcaption class="flex items-center gap-3 pt-6 border-t border-white/10">
                            <div class="w-11 h-11 bg-emerald-400 text-slate-950 rounded-xl flex items-center justify-center font-bold">ZM</div>
                            <div>
                                <div class="font-semibold">Zainab Musa</div>
                                <div class="text-[11px] text-emerald-400 font-mono">Abuja · Investment Member</div>
                            </div>
                        </figcaption>
                    </div>
                </figure>

                <!-- T3 -->
                <figure class="group bg-slate-50 rounded-3xl p-8 border border-slate-100 hover:border-emerald-200 hover:bg-white hover:shadow-xl transition-all">
                    <svg class="w-7 h-7 text-emerald-500 mb-6" fill="currentColor" viewBox="0 0 24 24"><path d="M9.13 8c-1.51.45-2.79 1.36-3.84 2.74-1.05 1.38-1.6 2.96-1.65 4.74-.05 1.78.49 3.05 1.62 3.81C6.4 20.05 7.7 20.31 9.16 20c1.46-.31 2.59-1.07 3.39-2.27.8-1.21 1.13-2.55.99-4.04-.14-1.49-.79-2.66-1.95-3.51-1.16-.85-2.32-1.21-3.49-1.08L9.13 8zm10 0c-1.51.45-2.79 1.36-3.84 2.74-1.05 1.38-1.6 2.96-1.65 4.74-.05 1.78.49 3.05 1.62 3.81C16.4 20.05 17.7 20.31 19.16 20c1.46-.31 2.59-1.07 3.39-2.27.8-1.21 1.13-2.55.99-4.04-.14-1.49-.79-2.66-1.95-3.51-1.16-.85-2.32-1.21-3.49-1.08L19.13 8z" transform="rotate(180 12 14)"/></svg>
                    <blockquote class="text-slate-800 text-lg leading-relaxed mb-8">
                        "Saved for two Hajjs through the goal-based savings. The auto-debit, the lock, the weekly progress nudges — built for discipline. Subhanallah, finally on the plane."
                    </blockquote>
                    <figcaption class="flex items-center gap-3 pt-6 border-t border-slate-200">
                        <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center font-bold text-emerald-700">YM</div>
                        <div>
                            <div class="font-semibold text-slate-900">Yusuf Muhammed</div>
                            <div class="text-[11px] text-slate-500 font-mono">Kano · Member since 2021</div>
                        </div>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    <!-- ============ FAQ ============ -->
    <section id="faq" class="py-24 lg:py-36 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-12 gap-16">
                <div class="lg:col-span-4">
                    <div class="inline-flex items-center gap-2 text-emerald-700 text-[11px] font-mono uppercase tracking-[0.25em] mb-5">
                        <span class="w-6 h-px bg-emerald-500"></span>
                        FAQ
                    </div>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-slate-900 mb-6 leading-[1.05]">Questions, answered.</h2>
                    <p class="text-slate-600 mb-8">Everything you need to know about banking with us. Can't find what you're looking for?</p>
                    <a href="#" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900 group">
                        Talk to a member success manager
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>

                <div class="lg:col-span-8 space-y-3" x-data="{ active: 0 }">
                    @php
                        $faqs = [
                            ['Is Attaqwa a regular bank?', 'No — we are a registered Shariah-compliant co-operative society. Unlike banks, our members are owners. We pool funds to issue benevolent loans (Qard Hasan) and invest in halal businesses, with profits shared back to members. There is no interest, in or out.'],
                            ['How is my money protected?', 'Funds are held in segregated accounts at regulated commercial banks, secured with bank-grade 256-bit encryption, and audited annually. Members are independently insured by our Takaful mutual support fund.'],
                            ['Who can join the co-operative?', 'Membership is open to any Nigerian (or Nigerian resident) who aligns with our ethical principles. Open an account in 4 minutes with your phone number and BVN — no paperwork.'],
                            ['How do you make profits without interest?', 'Through real-asset trade and Mudarabah (profit-sharing) partnerships with vetted halal businesses. We earn from genuine economic activity, not from lending money against money.'],
                            ['How do you ensure Shariah compliance?', 'A standing Shariah Advisory Board of qualified scholars reviews every product, asset, and process — continuously, not once. We publish quarterly compliance attestations. All operations are screened for Riba, Gharar, and Maysir.'],
                        ];
                    @endphp

                    @foreach($faqs as $i => $faq)
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden transition-all" :class="active === {{ $i }} ? 'border-emerald-300 shadow-lg shadow-emerald-500/5' : 'hover:border-slate-300'">
                            <button @click="active = (active === {{ $i }} ? null : {{ $i }})" class="w-full flex items-center justify-between p-6 text-left group">
                                <span class="font-display font-semibold text-slate-900 text-base lg:text-lg pr-6">{{ $faq[0] }}</span>
                                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all" :class="active === {{ $i }} ? 'bg-emerald-500 text-white rotate-180' : 'bg-slate-100 text-slate-500'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>
                            <div x-show="active === {{ $i }}" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 class="px-6 pb-6 text-slate-600 leading-relaxed">
                                {{ $faq[1] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="py-24 lg:py-32 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="relative bg-slate-950 rounded-[2.5rem] p-10 lg:p-20 overflow-hidden">
                <div class="absolute inset-0 bg-grid-dark opacity-60"></div>
                <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-emerald-500/20 rounded-full blur-[120px]"></div>
                <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-emerald-700/20 rounded-full blur-[120px]"></div>

                <!-- Rotating decorative ring -->
                <div class="hidden lg:block absolute top-1/2 right-12 -translate-y-1/2 w-72 h-72 ring-rotate opacity-30">
                    <svg viewBox="0 0 200 200" class="w-full h-full">
                        <circle cx="100" cy="100" r="80" fill="none" stroke="rgba(16,185,129,0.3)" stroke-width="0.5" stroke-dasharray="2 4"/>
                        <circle cx="100" cy="100" r="60" fill="none" stroke="rgba(16,185,129,0.4)" stroke-width="0.5" stroke-dasharray="3 6"/>
                        <circle cx="100" cy="100" r="40" fill="none" stroke="rgba(16,185,129,0.5)" stroke-width="0.5" stroke-dasharray="4 8"/>
                    </svg>
                </div>

                <div class="relative max-w-3xl">
                    <div class="inline-flex items-center gap-2 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-[11px] font-mono uppercase tracking-[0.2em] px-3 py-1.5 rounded-full mb-8">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                        Open enrollment · 2026
                    </div>

                    <h2 class="font-display text-4xl lg:text-7xl font-bold text-white mb-6 leading-[1] tracking-tight">
                        Your money, <br>
                        <span class="text-gradient-light">your values, aligned.</span>
                    </h2>
                    <p class="text-slate-300 text-lg lg:text-xl mb-10 leading-relaxed max-w-2xl">
                        Join 10,000+ members building wealth without compromise. Open your account in 4 minutes — no paperwork, no hidden fees.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ url('/app/register') }}" class="shine bg-emerald-500 text-slate-950 text-base font-semibold px-7 py-4 rounded-2xl hover:bg-emerald-400 transition-all active:scale-95 flex items-center justify-center gap-2 shadow-xl shadow-emerald-500/20">
                            Open my account
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="{{ url('/app/login') }}" class="bg-white/5 hover:bg-white/10 border border-white/10 backdrop-blur text-white text-base font-semibold px-7 py-4 rounded-2xl transition-all active:scale-95 flex items-center justify-center gap-2">
                            Member sign in
                        </a>
                    </div>

                    <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 text-xs text-slate-400 font-mono">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                4-MIN ONBOARDING
                            </span>
                        <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                ZERO MAINTENANCE FEES
                            </span>
                        <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                100% SHARIAH-AUDITED
                            </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ============ FOOTER ============ -->
<footer class="bg-slate-950 text-slate-400 pt-24 pb-12 relative overflow-hidden">
    <div class="absolute inset-0 bg-grid-dark opacity-40"></div>
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-px bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"></div>

    <div class="relative max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-2 lg:grid-cols-12 gap-12 mb-20">
            <div class="col-span-2 lg:col-span-5">
                <a href="/" class="flex items-center gap-3 mb-8">
                    <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="{{ config('brand.name') }}" class="h-9 w-auto brightness-0 invert">
                    <div class="flex flex-col leading-none">
                        <span class="font-display font-bold text-lg tracking-tight text-white">{{ config('brand.name') }}</span>
                        <span class="font-mono text-[9px] text-slate-500 tracking-[0.2em] mt-0.5">CO-OPERATIVE · v3.0</span>
                    </div>
                </a>
                <p class="text-slate-400 mb-8 max-w-sm leading-relaxed text-sm">
                    Nigeria's modern Shariah-compliant co-operative. We help members save, borrow and invest — without compromising their values or returns.
                </p>

                <!-- Newsletter -->
                <form class="max-w-sm">
                    <label class="text-[10px] font-mono text-emerald-400 uppercase tracking-[0.2em] mb-2 block">Member newsletter</label>
                    <div class="flex bg-white/5 border border-white/10 rounded-xl overflow-hidden focus-within:border-emerald-500/50 transition-colors">
                        <input type="email" placeholder="you@email.com" class="flex-1 bg-transparent px-4 py-3 text-sm text-white placeholder-slate-500 outline-none">
                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-4 font-semibold text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-span-1 lg:col-span-2">
                <h5 class="font-display font-semibold text-white mb-6 text-xs uppercase tracking-[0.2em]">Products</h5>
                <ul class="space-y-3 text-sm">
                    <li><a href="#products" class="hover:text-white transition-colors">Savings</a></li>
                    <li><a href="#products" class="hover:text-white transition-colors">Qard Hasan</a></li>
                    <li><a href="#products" class="hover:text-white transition-colors">Mudarabah Pool</a></li>
                    <li><a href="#products" class="hover:text-white transition-colors">Takaful</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Mobile App</a></li>
                </ul>
            </div>
            <div class="col-span-1 lg:col-span-2">
                <h5 class="font-display font-semibold text-white mb-6 text-xs uppercase tracking-[0.2em]">Company</h5>
                <ul class="space-y-3 text-sm">
                    <li><a href="#about" class="hover:text-white transition-colors">Compliance</a></li>
                    <li><a href="#faq" class="hover:text-white transition-colors">FAQ</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Press</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                </ul>
            </div>
            <div class="col-span-2 lg:col-span-3">
                <h5 class="font-display font-semibold text-white mb-6 text-xs uppercase tracking-[0.2em]">Get in touch</h5>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>123 Business Way, Ikeja, Lagos</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:hello@attaqwacoop.com" class="hover:text-white transition-colors">hello@attaqwacoop.com</a>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <a href="tel:+2348001234567" class="hover:text-white transition-colors tabular">+234 800 123 4567</a>
                    </li>
                </ul>

                <!-- Socials -->
                <div class="flex gap-2 mt-6">
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-slate-950 border border-white/10 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-slate-950 border border-white/10 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.84 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-slate-950 border border-white/10 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="pt-10 border-t border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6">
            <p class="text-[11px] text-slate-500 font-mono tracking-wide">© {{ date('Y') }} {{ config('brand.name') }} · RC: 1234567 · All rights reserved.</p>
            <div class="flex items-center gap-6">
                <a href="#" class="text-[11px] text-slate-500 hover:text-white font-mono tracking-wide transition-colors">Privacy</a>
                <a href="#" class="text-[11px] text-slate-500 hover:text-white font-mono tracking-wide transition-colors">Terms</a>
                <a href="#" class="text-[11px] text-slate-500 hover:text-white font-mono tracking-wide transition-colors">Cookies</a>
                <a href="#" class="text-[11px] text-slate-500 hover:text-white font-mono tracking-wide transition-colors">Shariah Compliance</a>
            </div>
        </div>
    </div>
</footer>

<style>
    .mask-fade {
        mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent);
        -webkit-mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent);
    }
</style>
</body>
</html>
