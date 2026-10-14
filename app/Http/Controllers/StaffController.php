<?php

namespace App\Http\Controllers;

use App\Models\StaffMember;
use App\Models\Person;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $staffMembers = StaffMember::published()
            ->with('person')
            ->whereHas('person.sacRole', fn ($query) => $query->whereIn('code', [Person::ROLE_STAFF, Person::ROLE_ADMIN]))
            ->orderBy('sort_order')
            ->orderBy('person_id')
            ->get();

        return view('staffs.index', compact('staffMembers'));
    }
}
