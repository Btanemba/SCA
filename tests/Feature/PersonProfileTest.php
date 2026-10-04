<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $code): User
    {
        $role = SacRole::firstOrCreate(['code' => $code], ['name' => $code, 'order' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        Person::create([
            'user_id' => $user->id,
            'sac_role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Employee',
            'account_number' => '0012345678',
        ]);

        return $user;
    }

    public static function profileRoles(): array
    {
        return [[Person::ROLE_ACCOUNTANT], [Person::ROLE_ADMIN]];
    }

    #[DataProvider('profileRoles')]
    public function test_own_profile_returns_to_dashboard(string $code): void
    {
        $user = $this->employee($code);
        $person = $user->person;
        $this->actingAs($user, config('backpack.base.guard'));

        $response = $this->get(backpack_url('person/'.$person->id.'/edit'));
        $response->assertOk();
        $response->assertSee('href="'.backpack_url('dashboard').'" class="btn btn-secondary text-decoration-none"', false);

        $this->put(backpack_url('person/'.$person->id), [
            'id' => $person->id,
            'first_name' => 'Updated',
            'last_name' => $person->last_name,
            'gender' => $person->gender,
            'date_of_birth' => '1990-01-01',
            'bank_name' => $person->bank_name,
            'account_name' => $person->account_name,
            'account_number' => $person->account_number,
            '_save_action' => 'save_and_back',
            '_http_referrer' => backpack_url('person'),
        ])->assertRedirect(backpack_url('dashboard'));

        $this->assertSame('Updated', $person->fresh()->first_name);
    }

    public function test_accountant_cannot_add_people(): void
    {
        $user = $this->employee(Person::ROLE_ACCOUNTANT);
        $this->actingAs($user, config('backpack.base.guard'));

        $this->get(backpack_url('person?role='.Person::ROLE_STAFF))
            ->assertOk()
            ->assertDontSee('person-create-role-dropdown', false);
        $this->get(backpack_url('person/create'))->assertForbidden();
        $this->post(backpack_url('person'), [
            'first_name' => 'Forbidden',
            'last_name' => 'Person',
            'email' => 'forbidden@example.com',
            'sac_role_id' => $user->person->sac_role_id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'forbidden@example.com']);
        $this->assertDatabaseCount('persons', 1);
    }

    public function test_other_profile_keeps_people_return_url_and_admin_can_add_people(): void
    {
        $admin = $this->employee(Person::ROLE_ADMIN);
        $staff = $this->employee(Person::ROLE_STAFF);
        $this->actingAs($admin, config('backpack.base.guard'));

        $this->get(backpack_url('person/'.$staff->person->id.'/edit'))
            ->assertOk()
            ->assertSee('href="'.backpack_url('person').'" class="btn btn-secondary text-decoration-none"', false);
        $this->get(backpack_url('person'))
            ->assertOk()
            ->assertSee('person-create-role-dropdown', false);
        $this->get(backpack_url('person/create'))->assertOk();
    }
}
