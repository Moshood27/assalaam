<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShariaBoardMember;
use Illuminate\Http\Request;

class ShariaBoardController extends Controller
{
    public function index()
    {
        $members = ShariaBoardMember::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($members);
    }
}
