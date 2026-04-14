<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Migration Configuration
        </x-slot>
        <div class="max-w-xs">
            <x-filament::input.wrapper label="Migration Cut-off Date">
                <x-filament::input
                    type="date"
                    wire:model="migrationDate"
                    required
                />
            </x-filament::input.wrapper>
            <p class="text-xs text-gray-500 mt-1">Transactions and records will be backdated to this date.</p>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <!-- Members Master -->
        <x-filament::section>
            <x-slot name="heading">
                1. Member Master
            </x-slot>
            <p class="text-sm text-gray-500 mb-4">Import Name, Membership No, Phone, Email, Branch, Address.</p>

            <div class="mb-4">
                <a href="/admin/templates/migration-users.xlsx" class="text-primary-600 hover:underline flex items-center gap-1 text-sm font-medium">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                    Download User Template (Excel)
                </a>
            </div>

            <form wire:submit.prevent="importMembers">
                <div class="mb-4">
                    <input type="file" wire:model="membersFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                </div>
                <x-filament::button type="submit" color="primary" class="w-full">
                    Import Members
                </x-filament::button>
            </form>
        </x-filament::section>

        <!-- Balances Master -->
        <x-filament::section>
            <x-slot name="heading">
                2. Balances Master
            </x-slot>
            <p class="text-sm text-gray-500 mb-4">Import Savings, Shares, Takaful, Building, Development, AGM, Welfare, H Savings, Gold, and all other cooperative funds.</p>

            <div class="mb-4">
                <a href="/admin/templates/migration-balances.xlsx" class="text-primary-600 hover:underline flex items-center gap-1 text-sm font-medium">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                    Download Balance Template (Excel)
                </a>
            </div>

            <form wire:submit.prevent="importBalances">
                <div class="mb-4">
                    <input type="file" wire:model="balancesFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                </div>
                <x-filament::button type="submit" color="primary" class="w-full">
                    Import Balances
                </x-filament::button>
            </form>
        </x-filament::section>

        <!-- Loan Master -->
        <x-filament::section>
            <x-slot name="heading">
                3. Loan Master
            </x-slot>
            <p class="text-sm text-gray-500 mb-4">Import Remaining Principal and Next Installment Amounts.</p>

            <div class="mb-4">
                <a href="/admin/templates/migration-loans.xlsx" class="text-primary-600 hover:underline flex items-center gap-1 text-sm font-medium">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                    Download Loan Template (Excel)
                </a>
            </div>

            <form wire:submit.prevent="importLoans">
                <div class="mb-4">
                    <input type="file" wire:model="loansFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                </div>
                <x-filament::button type="submit" color="primary" class="w-full">
                    Import Loans
                </x-filament::button>
            </form>
        </x-filament::section>

        <!-- Passbook Master -->
        <x-filament::section>
            <x-slot name="heading">
                4. Passbook Master (Optional)
            </x-slot>
            <p class="text-sm text-gray-500 mb-4">Import monthly historical breakdown (Jan-Dec) for specific years/schemes.</p>

            <div class="mb-4">
                <a href="/admin/templates/migration-passbook.xlsx" class="text-primary-600 hover:underline flex items-center gap-1 text-sm font-medium">
                    <x-heroicon-o-document-arrow-down class="w-4 h-4" />
                    Download Passbook Template (Excel)
                </a>
            </div>

            <form wire:submit.prevent="importPassbook">
                <div class="mb-4">
                    <input type="file" wire:model="passbookFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                </div>
                <x-filament::button type="submit" color="primary" class="w-full">
                    Import Passbook
                </x-filament::button>
            </form>
        </x-filament::section>
    </div>

    <div class="mt-8">
        <x-filament::section>
            <x-slot name="heading">
                Migration Guidelines (Amanah)
            </x-slot>
            <div class="prose dark:prose-invert max-w-none">
                <ul>
                    <li><strong>Step 1: Cleanup:</strong> Ensure Excel data matches the physical paper passbooks.</li>
                    <li><strong>Step 2: Template:</strong> Format Excel to match headings:
                        <ul>
                            <li><strong>Members:</strong> <code>name, surname, other_names, membership_no, phone, secondary_phone, email, gender, native_place, dob, marital_status, occupation, address, residential_address, permanent_address, branch, bvn, nature_of_business, business_address, has_other_cooperatives, other_cooperative_details, nok_name, nok_phone, nok_relationship, nok_address, guarantor_name, guarantor_phone, guarantor_occupation, guarantor_address, religious_society_name, imam_name, imam_phone, mosque_address, spouse_father_name, spouse_father_phone, spouse_father_address, spouse_father_business_address, admission_form_number, admission_date, admission_officer_name, approval_status, date_joined</code></li>
                            <li><strong>Balances:</strong> <code>membership_no, savings_balance, shares_balance, takaful_balance, development_fund_balance, outstanding_fines, wallet_balance, building_balance, agm_balance, loan_repayment_balance, fine_balance, welfare_balance, lateness_balance, stationery_balance, loan_form_balance, others_balance, id_card_balance, emergency_balance, entrance_balance, h_savings_balance, investment_balance, digital_gold_balance, group_savings_balance</code></li>
                            <li><strong>Loans:</strong> <code>membership_no, original_loan_amount, total_repaid_to_date, remaining_principal, next_installment_amount, interval, total_installments</code></li>
                            <li><strong>Passbook:</strong> <code>membership_no, scheme_name, year, january, february, march, april, may, june, july, august, september, october, november, december</code></li>
                        </ul>
                    </li>
                    <li><strong>Step 3: Reconciliation:</strong> After each import, run the reconciliation report to verify totals match your paper records.</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
