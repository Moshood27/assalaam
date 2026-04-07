<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage_admins',
            'delete_records',
            'create_branches',
            'approve_loans',
            'view_all_members',
            'view_own_branch_members',
            'record_contributions',
        ];

        // Resource permissions (Shield style)
        $resources = [
            'user',
            'contribution',
            'qard_hasan',
            'branch',
            'withdrawal_request',
            'savings_goal',
            'takaful_contribution',
            'wallet_transaction',
            'goal_booking',
            'member_application',
            'agm_session',
            'support_message',
            'charity_entry',
            'store_order',
            'product', // Store Product
            'category', // Store Category
            'activity', // ActivityLog
            'agm_candidate',
            'expense_entry',
            'income_entry',
            'project_investment',
            'project_profit',
            'project',
            'scheme',
            'shariah_audit_log',
            'takaful_pool_entry',
            'utility_transaction',
            'whitelisted_ip',
        ];

        $actions = ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}_{$resource}";
            }
        }

        foreach (array_unique($permissions) as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign permissions

        // Super Admin
        $superAdmin = Role::findOrCreate('super_admin');
        // Shield's super_admin usually doesn't need explicit permissions if intercept_gate is before,
        // but it's good to have them.
        $superAdmin->givePermissionTo(Permission::all());

        // Branch Manager
        $branchManager = Role::findOrCreate('Branch Manager');
        $branchManager->givePermissionTo([
            'view_any_user', 'view_user', 'update_user',
            'view_any_contribution', 'view_contribution', 'create_contribution', 'update_contribution',
            'view_any_qard_hasan', 'view_qard_hasan', 'create_qard_hasan', 'update_qard_hasan',
            'view_any_withdrawal_request', 'view_withdrawal_request', 'update_withdrawal_request',
            'view_any_savings_goal', 'view_savings_goal',
            'view_any_takaful_contribution', 'view_takaful_contribution',
            'view_any_wallet_transaction', 'view_wallet_transaction',
            'view_any_goal_booking', 'view_goal_booking',
            'view_any_member_application', 'view_member_application', 'update_member_application',
            'view_any_store_order', 'view_store_order', 'update_store_order',
            'view_any_support_message', 'view_support_message', 'create_support_message', 'update_support_message',
            'view_any_charity_entry', 'view_charity_entry', 'create_charity_entry',
            'view_any_expense_entry', 'view_expense_entry', 'create_expense_entry',
            'view_any_income_entry', 'view_income_entry', 'create_income_entry',
            'view_any_project_investment', 'view_project_investment',
            'view_any_project_profit', 'view_project_profit',
            'view_any_utility_transaction', 'view_utility_transaction',
            'view_any_takaful_pool_entry', 'view_takaful_pool_entry', 'create_takaful_pool_entry',
            'view_any_shariah_audit_log', 'view_shariah_audit_log',
            'view_any_category', 'view_category',
            'view_any_product', 'view_product',
            'view_any_project', 'view_project',
            'view_any_scheme', 'view_scheme',
            'approve_loans', // Specific custom permission
            'view_own_branch_members', // Legacy/custom
            'record_contributions', // Legacy/custom
        ]);

        // Clerk
        $clerk = Role::findOrCreate('Clerk');
        $clerk->givePermissionTo([
            'view_any_user', 'view_user', // Need to see users to record contributions for them
            'view_any_contribution', 'view_contribution', 'create_contribution',
            'view_any_support_message', 'view_support_message', 'create_support_message',
            'view_any_category', 'view_category',
            'view_any_product', 'view_product',
            'record_contributions', // Legacy/custom
        ]);

        // Assign Super Admin role to existing admins if any
        User::where('is_admin', true)->get()->each(function (User $user) {
            $user->assignRole('super_admin');
        });
    }
}
