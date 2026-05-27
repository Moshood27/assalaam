@extends('layouts.public')

@section('title', 'About Us')

@section('content')
    <h1 class="text-3xl font-bold mb-8">About Us</h1>

    <div class="prose dark:prose-invert max-w-none space-y-8 text-slate-600 dark:text-slate-400 leading-relaxed">
        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Our Identity</h2>
            <p>
                <span class="font-bold text-primary-600 uppercase tracking-tight">AT-TAQWA OSOGBO ISLAMIC CICU LTD</span> (RC Number: 9518505) is a premier Islamic Cooperative Investment and Credit Union. We operate on the principles of Sharia, providing a viable alternative to conventional interest-based financial systems.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Our Mission</h2>
            <p>
                To empower our members through ethical financial solutions that promote economic growth, social welfare, and spiritual fulfillment. We strive to be the most trusted Islamic financial cooperative in the region.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">What We Offer</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 not-prose">
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2">Interest-Free Savings</h3>
                    <p class="text-sm">Safe and accessible accounts managed according to Islamic principles.</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2">Halal Investments</h3>
                    <p class="text-sm">Opportunities to participate in profitable ventures through Mudarabah.</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2">Ethical Credit</h3>
                    <p class="text-sm">Qard Hasan (benevolent loans) for personal and business needs.</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2">Community Support</h3>
                    <p class="text-sm">Takaful and welfare programs designed to assist members.</p>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Sharia Governance</h2>
            <p>
                All our operations, products, and services are supervised by a dedicated Sharia Board to ensure strict compliance with Islamic jurisprudence. We are committed to transparency, justice, and the prohibition of Riba (interest).
            </p>
        </section>
    </div>
@endsection
