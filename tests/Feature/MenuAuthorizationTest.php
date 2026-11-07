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

    public function test_founder_dashboard_counts_each_people_role_including_people_without_accounts(): void
    {
        $founder = $this->userForRole(Person::ROLE_FOUNDER);
        foreach ([Person::ROLE_STUDENT => 3, Person::ROLE_PARENT => 2, Person::ROLE_STAFF => 4, Person::ROLE_ADMIN => 1] as $code => $total) {
            $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
            for ($index = 0; $index < $total; $index++) {
                Person::create(['sac_role_id' => $role->id, 'first_name' => $code, 'last_name' => 'Person']);
            }
        }
        $this->userForRole(Person::ROLE_ACCOUNTANT);
        $this->userForRole(Person::ROLE_SECURITY);

        $response = $this->actingAs($founder, config('backpack.base.guard'))
            ->get(backpack_url('dashboard'))
            ->assertOk()
            ->assertSee('Academy overview')
            ->assertSee('aria-label="Students: 3"', false)
            ->assertSee('aria-label="Parents: 2"', false)
            ->assertSee('aria-label="Staff: 4"', false)
            ->assertSee('aria-label="Administrators: 1"', false)
            ->assertDontSee('Finance overview');

        foreach ([Person::ROLE_STUDENT, Person::ROLE_PARENT, Person::ROLE_STAFF, Person::ROLE_ADMIN] as $code) {
            $response->assertSee('href="'.backpack_url('person?role='.$code).'"', false);
        }
    }

    public function test_founder_dashboard_shows_zero_for_roles_without_people(): void
    {
        $founder = $this->userForRole(Person::ROLE_FOUNDER);

        $this->actingAs($founder, config('backpack.base.guard'))
            ->get(backpack_url('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Students: 0"', false)
            ->assertSee('aria-label="Parents: 0"', false)
            ->assertSee('aria-label="Staff: 0"', false)
            ->assertSee('aria-label="Administrators: 0"', false);
    }

    public function test_administrator_dashboard_shows_people_summary(): void
    {
        $administrator = $this->userForRole(Person::ROLE_ADMIN);
        $this->userForRole(Person::ROLE_STUDENT);
        $this->userForRole(Person::ROLE_PARENT);
        $this->userForRole(Person::ROLE_STAFF);
        $this->userForRole(Person::ROLE_FOUNDER);

        $response = $this->actingAs($administrator, config('backpack.base.guard'))
            ->get(backpack_url('dashboard'))
            ->assertOk()
            ->assertSee('Academy overview')
            ->assertSee('aria-label="Students: 1"', false)
            ->assertSee('aria-label="Parents: 1"', false)
            ->assertSee('aria-label="Staff: 1"', false)
            ->assertSee('aria-label="Administrators: 1"', false)
            ->assertSee('class="card people-placard', false);

        foreach ([Person::ROLE_STUDENT, Person::ROLE_PARENT, Person::ROLE_STAFF, Person::ROLE_ADMIN] as $code) {
            $response->assertSee('href="'.backpack_url('person?role='.$code).'"', false);
        }
    }

    public function test_parent_cannot_access_administrative_routes(): void
    {
        $parent = $this->userForRole(Person::ROLE_PARENT);
        $this->actingAs($parent, config('backpack.base.guard'))
            ->get(backpack_url('dashboard'))->assertOk();

        foreach (['person', 'sac-role', 'job-application', 'job-opening', 'attendance', 'student-fees', 'expenses', 'payroll'] as $path) {
            $this->get(backpack_url($path))->assertForbidden();
        }
        $this->post(backpack_url('person'), [])->assertForbidden();
        $this->post(route('student-fees.charges'), [])->assertForbidden();
    }

    public function test_attendance_routes_are_limited_to_staff_admin_and_founder(): void
    {
        foreach ([Person::ROLE_STAFF, Person::ROLE_ADMIN, Person::ROLE_FOUNDER] as $role) {
            $this->actingAs($this->userForRole($role), config('backpack.base.guard'))
                ->get(backpack_url('attendance'))->assertOk()->assertSee('Reported absences');
        }

        foreach ([Person::ROLE_STUDENT, Person::ROLE_PARENT, Person::ROLE_ACCOUNTANT, Person::ROLE_SECURITY, 'OTHER'] as $role) {
            $this->actingAs($this->userForRole($role), config('backpack.base.guard'));
            $this->get(backpack_url('attendance'))->assertForbidden();
            $this->post(backpack_url('attendance/search'), [])->assertForbidden();
            $this->get(backpack_url('attendance/999/details'))->assertForbidden();
            $this->get(backpack_url('attendance/999/show'))->assertForbidden();
            if ($role === Person::ROLE_SECURITY) {
                $this->get(route('security.attendance'))->assertOk();
            }
        }

        $this->actingAs(User::factory()->create(['email_verified_at' => now()]), config('backpack.base.guard'))
            ->get(backpack_url('attendance'))->assertForbidden();
    }

    public function test_people_summary_is_not_shown_to_other_roles(): void
    {
        foreach ([Person::ROLE_STAFF, Person::ROLE_PARENT, Person::ROLE_STUDENT, Person::ROLE_ACCOUNTANT] as $code) {
            $this->actingAs($this->userForRole($code), config('backpack.base.guard'))
                ->get(backpack_url('dashboard'))
                ->assertOk()
                ->assertDontSee('Academy overview')
                ->assertDontSee('people-placard');
        }
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
