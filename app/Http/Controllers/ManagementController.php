<?php

namespace App\Http\Controllers;

use App\Models\ManagementMember;
use Illuminate\View\View;

class ManagementController extends Controller
{
    public function index(): View
    {
        $managementMembers = ManagementMember::published()
            ->orderBy('sort_order')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('management.index', compact('managementMembers'));
    }
}
