<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\MemberImportTemplate;
use App\Exports\MemberBalanceImportTemplate;
use App\Exports\LoanImportTemplate;
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
}
