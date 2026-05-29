<x-filament::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Logged in as</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->name }}</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ $this->email }}</div>
                </div>
                <x-filament::badge color="success">Admin</x-filament::badge>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <div class="px-5 py-3 text-sm font-semibold text-gray-700 dark:text-gray-100 bg-gray-50 dark:bg-gray-800/60">Update Email</div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">New Email</label>
                        <input type="email" wire:model.defer="email" class="fi-input fi-input-base w-full text-sm" placeholder="you@example.com" />
                        @error('email')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Current Password</label>
                        <input type="password" wire:model.defer="currentPasswordForEmail" class="fi-input fi-input-base w-full text-sm" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" />
                        @error('currentPasswordForEmail')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="pt-2">
                        <x-filament::button wire:click="updateEmail">Save Email</x-filament::button>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <div class="px-5 py-3 text-sm font-semibold text-gray-700 dark:text-gray-100 bg-gray-50 dark:bg-gray-800/60">Update Password</div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Current Password</label>
                        <input type="password" wire:model.defer="current_password" class="fi-input fi-input-base w-full text-sm" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" />
                        @error('current_password')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">New Password</label>
                        <input type="password" wire:model.defer="new_password" class="fi-input fi-input-base w-full text-sm" placeholder="At least 6 characters" />
                        @error('new_password')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Confirm New Password</label>
                        <input type="password" wire:model.defer="confirm_password" class="fi-input fi-input-base w-full text-sm" placeholder="Repeat new password" />
                        @error('confirm_password')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="pt-2">
                        <x-filament::button wire:click="updatePassword">Save Password</x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
