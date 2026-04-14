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

            <form wire:submit.prevent="importLoans">
                <div class="mb-4">
                    <input type="file" wire:model="loansFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-300">
                </div>
                <x-filament::button type="submit" color="primary" class="w-full">
                    Import Loans
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
                            <li><strong>Members:</strong> <code>name, membership_no, phone, email, branch, address</code></li>
                            <li><strong>Balances:</strong> <code>member_no, savings_balance, shares_balance, takaful_balance, development_fund_balance, wallet_balance, building_balance, agm_balance, loan_repayment_balance, fine_balance, welfare_balance, lateness_balance, stationery_balance, loan_form_balance, others_balance, id_card_balance, emergency_balance, entrance_balance, h_savings_balance, investment_balance, digital_gold_balance, group_savings_balance, outstanding_fines</code></li>
                            <li><strong>Loans:</strong> <code>member_no, original_loan_amount, total_repaid_to_date, remaining_principal, next_installment_amount</code></li>
                        </ul>
                    </li>
                    <li><strong>Step 3: Reconciliation:</strong> After each import, run the reconciliation report to verify totals match your paper records.</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
