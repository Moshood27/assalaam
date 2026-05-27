@extends('layouts.public')

@section('title', 'Terms and Conditions')

@section('content')
    <h1 class="text-3xl font-bold mb-4">Terms and Conditions</h1>
    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-8">Last updated: May 2026</p>

    <div class="prose dark:prose-invert max-w-none space-y-6 text-slate-600 dark:text-slate-400 leading-relaxed">
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">1. Acceptance of Terms</h2>
            <p>
                By accessing and using the {{ config('brand.name', 'AT-TAQWA') }} website, mobile application, and services, you agree to comply with and be bound by these Terms and Conditions.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">2. Membership and Eligibility</h2>
            <p>
                Membership is subject to the bylaws of AT-TAQWA OSOGBO ISLAMIC CICU LTD. Users must provide accurate and complete information during registration and maintain the confidentiality of their account credentials.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">3. Sharia Compliance</h2>
            <p>
                All financial activities, including savings, investments, and loans, must adhere to Sharia principles as interpreted by our Sharia Board. Interest (Riba) is strictly prohibited in all transactions.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">4. Services and Fees</h2>
            <p>
                While we do not charge interest, we may apply transparent administrative fees or service charges for certain operations, as permitted by Sharia and disclosed to members in advance.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">5. Limitation of Liability</h2>
            <p>
                {{ config('brand.name', 'AT-TAQWA') }} shall not be liable for any indirect, incidental, or consequential damages arising out of your use of our services, except as required by law.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">6. Governing Law</h2>
            <p>
                These terms are governed by the laws of the Federal Republic of Nigeria and the cooperative bylaws of the society.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">7. Contact Information</h2>
            <p>
                For questions regarding these terms, please contact us at
                <a href="mailto:attaqwaosogbo@gmail.com" class="text-primary-600 hover:underline transition-colors font-medium">attaqwaosogbo@gmail.com</a>.
            </p>
        </section>
    </div>
@endsection
