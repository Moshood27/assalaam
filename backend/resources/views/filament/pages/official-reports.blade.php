<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label for="year" class="block text-sm font-medium text-gray-700">Select Year</label>
                <select wire:model.live="year" id="year" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm rounded-md">
                    @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex-1">
                <label for="projectId" class="block text-sm font-medium text-gray-700">Select Project (for Distribution Report)</label>
                <select wire:model.live="projectId" id="projectId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm rounded-md">
                    <option value="">-- Select Project --</option>
                    @foreach ($this->getProjects() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Statutory & Regulatory -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-building-library" class="h-5 w-5 text-amber-500" />
                        <span>Statutory & Regulatory</span>
                    </div>
                </x-slot>
                <div class="space-y-2">
                    <x-filament::button wire:click="downloadFinancials" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Financial Statements ({{ $year }})
                    </x-filament::button>
                    <x-filament::button wire:click="downloadAppropriation" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Appropriation Account ({{ $year }})
                    </x-filament::button>
                    <x-filament::button wire:click="downloadCashFlow" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Statement of Cash Flows ({{ $year }})
                    </x-filament::button>
                </div>
            </x-filament::section>

            <!-- Islamic Finance & Sharia -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-moon" class="h-5 w-5 text-emerald-500" />
                        <span>Islamic Finance & Sharia</span>
                    </div>
                </x-slot>
                <div class="space-y-2">
                    <x-filament::button wire:click="downloadCoopZakat" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Cooperative Zakat Report
                    </x-filament::button>
                    <x-filament::button wire:click="downloadMemberZakatPortfolio" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Member Zakat Portfolio ({{ $year }})
                    </x-filament::button>
                    <x-filament::button wire:click="downloadCharityReport" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Charity Fund Report ({{ $year }})
                    </x-filament::button>
                    <x-filament::button wire:click="downloadShariaAudit" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Sharia Audit Report ({{ $year }})
                    </x-filament::button>
                </div>
            </x-filament::section>

            <!-- Operational Reports -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-5 w-5 text-blue-500" />
                        <span>Operational & Member</span>
                    </div>
                </x-slot>
                <div class="space-y-2">
                    <x-filament::button wire:click="downloadLoanAging" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Loan & Murabahah Aging
                    </x-filament::button>
                    <x-filament::button wire:click="downloadTakafulReport" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Takaful Pool Report
                    </x-filament::button>
                    <x-filament::button wire:click="downloadGoldReport" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Gold Savings Valuation
                    </x-filament::button>
                </div>
            </x-filament::section>

            <!-- Project & Investment -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-5 w-5 text-indigo-500" />
                        <span>Project & Investment</span>
                    </div>
                </x-slot>
                <div class="space-y-2">
                    <x-filament::button wire:click="downloadProjectRoi" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Project ROI Report
                    </x-filament::button>
                    <x-filament::button wire:click="downloadProjectDistribution" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start" :disabled="!$projectId">
                        Profit Distribution Report
                    </x-filament::button>
                    <x-filament::button wire:click="downloadVendorSettlement" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Vendor Settlement Report
                    </x-filament::button>
                </div>
            </x-filament::section>

            <!-- Compliance & Security -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-rose-500" />
                        <span>Compliance & Security</span>
                    </div>
                </x-slot>
                <div class="space-y-2">
                    <x-filament::button wire:click="downloadAttendance" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Attendance & Fine Summary ({{ $year }})
                    </x-filament::button>
                    <x-filament::button wire:click="downloadAuditTrail" icon="heroicon-o-document-arrow-down" color="gray" class="w-full text-left justify-start">
                        Comprehensive Audit Trail
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
