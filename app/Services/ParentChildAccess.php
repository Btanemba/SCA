<?php

namespace App\Services;

use App\Models\Person;

class ParentChildAccess
{
    public function child(int $childId): Person
    {
        $parent = backpack_user()?->person;
        abort_unless($parent?->sacRole?->code === Person::ROLE_PARENT, 403);

        return $parent->children()
            ->where('persons.id', $childId)
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->firstOrFail();
    }
}
