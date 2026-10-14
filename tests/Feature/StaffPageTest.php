<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\SacRole;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staffs_page_lists_only_published_profiles(): void
    {
        $staffRole = SacRole::create(['name' => 'Staff', 'code' => Person::ROLE_STAFF]);
        $adminRole = SacRole::create(['name' => 'Administrator', 'code' => Person::ROLE_ADMIN]);
        $amina = Person::create([
            'sac_role_id' => $staffRole->id,
            'first_name' => 'Amina',
            'last_name' => 'Okafor',
            'Occupation' => 'Teacher',
            'image_path' => 'staff/amina.jpg',
        ]);
        $bola = Person::create([
            'sac_role_id' => $staffRole->id,
            'first_name' => 'Bola',
            'last_name' => 'Adeyemi',
            'Occupation' => 'Assistant Teacher',
        ]);
        $chidi = Person::create([
            'sac_role_id' => $adminRole->id,
            'first_name' => 'Chidi',
            'last_name' => 'Nwosu',
            'Occupation' => 'Administrator',
        ]);

        StaffMember::create([
            'person_id' => $amina->id,
            'is_published' => true,
        ]);
        StaffMember::create([
            'person_id' => $bola->id,
            'is_published' => false,
        ]);
        StaffMember::create([
            'person_id' => $chidi->id,
            'is_published' => true,
        ]);

        $this->get('/staffs')
            ->assertOk()
            ->assertSee('Amina Okafor')
            ->assertSee('Teacher')
            ->assertSee('storage/staff/amina.jpg')
            ->assertSee('Chidi Nwosu')
            ->assertSee('Administrator')
            ->assertDontSee('Bola Adeyemi');
    }
}
