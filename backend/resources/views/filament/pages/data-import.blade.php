<x-filament::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bulk Import</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Import Members, Schemes, and Loans from CSV files. You can download sample templates to prepare your data.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Members -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Import Members</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Columns: name, email, membership_number, branch_id, balance, is_defaulter</p>
                </div>
                <div class="p-5 space-y-3">
                    <input type="file" wire:model.live="membersFile" accept=".csv,text/csv" class="fi-input fi-input-base w-full" />
                    <div class="flex items-center justify-between text-xs">
                        <a href="{{ asset('templates/members-template.csv') }}" class="text-primary-600 hover:underline">Download template</a>
                        <x-filament::button size="sm" wire:click="importMembers" :disabled="!$this->membersFile">Upload</x-filament::button>
                    </div>

                    @if(!empty($this->membersResult))
                        <div class="mt-2 text-sm space-y-1">
                            @if(isset($this->membersResult['summary']))
                                <div class="text-gray-700 dark:text-gray-200">
                                    <div>Processed: <span class="font-semibold">{{ $this->membersResult['summary']['processed'] ?? 0 }}</span></div>
                                    <div>Created: <span class="font-semibold">{{ $this->membersResult['summary']['created'] ?? 0 }}</span></div>
                                    <div>Updated: <span class="font-semibold">{{ $this->membersResult['summary']['updated'] ?? 0 }}</span></div>
                                    <div>Failed: <span class="font-semibold @if(($this->membersResult['summary']['failed'] ?? 0) > 0) text-danger-600 @endif">{{ $this->membersResult['summary']['failed'] ?? 0 }}</span></div>
                                </div>
                            @endif
                            @if(!empty($this->membersResult['errors']))
                                <div class="mt-2 p-2 rounded border border-danger-200 bg-danger-50 text-danger-700 max-h-40 overflow-auto">
                                    <div class="font-semibold mb-1">Errors</div>
                                    <ul class="list-disc pl-5 space-y-0.5">
                                        @foreach($this->membersResult['errors'] as $err)
                                            <li>Row {{ $err['row'] ?? '?' }}: {{ $err['error'] ?? 'Unknown error' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Schemes -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Import Schemes</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Columns: name, min_amount, active</p>
                </div>
                <div class="p-5 space-y-3">
                    <input type="file" wire:model.live="schemesFile" accept=".csv,text/csv" class="fi-input fi-input-base w-full" />
                    <div class="flex items-center justify-between text-xs">
                        <a href="{{ asset('templates/schemes-template.csv') }}" class="text-primary-600 hover:underline">Download template</a>
                        <x-filament::button size="sm" wire:click="importSchemes" :disabled="!$this->schemesFile">Upload</x-filament::button>
                    </div>

                    @if(!empty($this->schemesResult))
                        <div class="mt-2 text-sm space-y-1">
                            @if(isset($this->schemesResult['summary']))
                                <div class="text-gray-700 dark:text-gray-200">
                                    <div>Processed: <span class="font-semibold">{{ $this->schemesResult['summary']['processed'] ?? 0 }}</span></div>
                                    <div>Created: <span class="font-semibold">{{ $this->schemesResult['summary']['created'] ?? 0 }}</span></div>
                                    <div>Updated: <span class="font-semibold">{{ $this->schemesResult['summary']['updated'] ?? 0 }}</span></div>
                                    <div>Failed: <span class="font-semibold @if(($this->schemesResult['summary']['failed'] ?? 0) > 0) text-danger-600 @endif">{{ $this->schemesResult['summary']['failed'] ?? 0 }}</span></div>
                                </div>
                            @endif
                            @if(!empty($this->schemesResult['errors']))
                                <div class="mt-2 p-2 rounded border border-danger-200 bg-danger-50 text-danger-700 max-h-40 overflow-auto">
                                    <div class="font-semibold mb-1">Errors</div>
                                    <ul class="list-disc pl-5 space-y-0.5">
                                        @foreach($this->schemesResult['errors'] as $err)
                                            <li>Row {{ $err['row'] ?? '?' }}: {{ $err['error'] ?? 'Unknown error' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Loans -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Import Loans</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Columns: qard_id_string (opt), user_id/email/membership_number, principal_amount, total_installments, interval, admin_fee_flat, admin_fee_pct, paid_amount, status, per_installment (opt)</p>
                </div>
                <div class="p-5 space-y-3">
                    <input type="file" wire:model.live="loansFile" accept=".csv,text/csv" class="fi-input fi-input-base w-full" />
                    <div class="flex items-center justify-between text-xs">
                        <a href="{{ asset('templates/loans-template.csv') }}" class="text-primary-600 hover:underline">Download template</a>
                        <x-filament::button size="sm" wire:click="importLoans" :disabled="!$this->loansFile">Upload</x-filament::button>
                    </div>

                    @if(!empty($this->loansResult))
                        <div class="mt-2 text-sm space-y-1">
                            @if(isset($this->loansResult['summary']))
                                <div class="text-gray-700 dark:text-gray-200">
                                    <div>Processed: <span class="font-semibold">{{ $this->loansResult['summary']['processed'] ?? 0 }}</span></div>
                                    <div>Created: <span class="font-semibold">{{ $this->loansResult['summary']['created'] ?? 0 }}</span></div>
                                    <div>Updated: <span class="font-semibold">{{ $this->loansResult['summary']['updated'] ?? 0 }}</span></div>
                                    <div>Failed: <span class="font-semibold @if(($this->loansResult['summary']['failed'] ?? 0) > 0) text-danger-600 @endif">{{ $this->loansResult['summary']['failed'] ?? 0 }}</span></div>
                                </div>
                            @endif
                            @if(!empty($this->loansResult['errors']))
                                <div class="mt-2 p-2 rounded border border-danger-200 bg-danger-50 text-danger-700 max-h-40 overflow-auto">
                                    <div class="font-semibold mb-1">Errors</div>
                                    <ul class="list-disc pl-5 space-y-0.5">
                                        @foreach($this->loansResult['errors'] as $err)
                                            <li>Row {{ $err['row'] ?? '?' }}: {{ $err['error'] ?? 'Unknown error' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
