<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\MemberImportTemplate;
use App\Exports\MemberBalanceImportTemplate;
use App\Exports\LoanImportTemplate;
use App\Exports\MigrationUserImportTemplate;
use App\Exports\MigrationBalanceImportTemplate;
use App\Exports\MigrationLoanImportTemplate;
use Maatwebsite\Excel\Facades\Excel;

class TemplateDownloadController extends Controller
{
    public function members()
    {
        return Excel::download(new MemberImportTemplate, 'members_import_template.xlsx');
    }

    public function loans()
    {
        return Excel::download(new LoanImportTemplate, 'loans_import_template.xlsx');
    }

    public function memberBalance()
    {
        return Excel::download(new MemberBalanceImportTemplate, 'member_balance_import_template.xlsx');
    }

    public function migrationUsers()
    {
        return Excel::download(new MigrationUserImportTemplate, 'migration_users_template.xlsx');
    }

    public function migrationBalances()
    {
        return Excel::download(new MigrationBalanceImportTemplate, 'migration_balances_template.xlsx');
    }

    public function migrationLoans()
    {
        return Excel::download(new MigrationLoanImportTemplate, 'migration_loans_template.xlsx');
    }
}
