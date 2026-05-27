<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('brand.name', 'AT-TAQWA') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Base styles handled by Tailwind utilities in app.css */
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#0a0a0a] text-slate-900 dark:text-slate-100 antialiased selection:bg-primary-500 selection:text-white min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="sticky top-0 w-full z-50 glass border-b border-slate-200 dark:border-slate-800 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg') }}" alt="Logo" class="h-8 w-auto dark:hidden transition-transform group-hover:scale-105">
                        <img src="{{ asset('images/' . config('brand.slug', 'attaqwa') . '-logo-dark.svg') }}" alt="Logo" class="h-8 w-auto hidden dark:block transition-transform group-hover:scale-105">
                        <span class="font-bold text-lg tracking-tight hidden sm:block uppercase">{{ config('brand.name', 'AT-TAQWA') }}</span>
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ url('/') }}" class="text-sm font-medium hover:text-primary-600 transition-colors flex items-center gap-1">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow py-12 sm:py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="p-8 sm:p-12 lg:p-16">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (Simplified for auxiliary pages) -->
    <footer class="bg-white dark:bg-slate-950 py-12 border-t border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-sm text-slate-400">
                    &copy; {{ date('Y') }} {{ config('brand.name', 'AT-TAQWA') }}. RC: 9518505
                </p>
                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ url('/about-us') }}" class="text-slate-500 hover:text-primary-600">About Us</a>
                    <a href="{{ url('/privacy-policy') }}" class="text-slate-500 hover:text-primary-600">Privacy Policy</a>
                    <a href="{{ url('/terms') }}" class="text-slate-500 hover:text-primary-600">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
