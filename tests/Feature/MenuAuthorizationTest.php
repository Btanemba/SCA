<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function userForRole(string $code): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        Person::create([
            'user_id' => $user->id,
            'sac_role_id' => $role->id,
            'first_name' => $code,
            'last_name' => 'User',
        ]);

        return $user;
    }

    public function test_founder_only_academy_menu_routes_reject_other_roles(): void
    {
        $founder = $this->userForRole(Person::ROLE_FOUNDER);
        $administrator = $this->userForRole(Person::ROLE_ADMIN);
        $staff = $this->userForRole(Person::ROLE_STAFF);

        foreach (['management-member', 'staff-member'] as $path) {
            $this->actingAs($administrator, config('backpack.base.guard'))
                ->get(backpack_url($path))
                ->assertForbidden();
            $this->actingAs($staff, config('backpack.base.guard'))
                ->get(backpack_url($path))
                ->assertForbidden();
            $this->actingAs($founder, config('backpack.base.guard'))
                ->get(backpack_url($path))
                ->assertOk();
        }

        $this->actingAs($administrator, config('backpack.base.guard'))
            ->get(route('academy.settings'))
            ->assertForbidden();
        $this->actingAs($founder, config('backpack.base.guard'))
            ->get(route('academy.settings'))
            ->assertOk();
    }
}
