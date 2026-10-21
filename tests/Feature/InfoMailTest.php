<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoMailTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    private User $founder;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.info_mail.email' => 'info@example.test',
            'services.info_mail.password' => 'test-mailbox-password',
            'services.finance_mail.email' => 'finance@example.test',
            'services.finance_mail.password' => 'test-finance-password',
        ]);

        $this->administrator = $this->createUserForRole(Person::ROLE_ADMIN);
        $this->founder = $this->createUserForRole(Person::ROLE_FOUNDER);
        $this->accountant = $this->createUserForRole(Person::ROLE_ACCOUNTANT);
    }

    private function createUserForRole(string $roleCode): User
    {
        $role = SacRole::firstOrCreate(['code' => $roleCode], ['name' => $roleCode, 'order' => 1]);
        $user = User::factory()->create();
        Person::create([
            'user_id' => $user->id,
            'sac_role_id' => $role->id,
            'first_name' => $roleCode,
            'last_name' => 'User',
        ]);

        return $user;
    }

    private function login(User $user): static
    {
        return $this->actingAs($user, config('backpack.base.guard'));
    }

    public function test_administrator_and_founder_can_view_both_mail_accounts_and_reveal_passwords_on_request(): void
    {
        foreach ([$this->administrator, $this->founder] as $user) {
            $this->login($user)
                ->get(route('mail-accounts.show', 'info'))
                ->assertOk()
                ->assertSee('info@example.test')
                ->assertDontSee('test-mailbox-password');

            $this->postJson(route('mail-accounts.password', 'info'))
                ->assertOk()
                ->assertExactJson(['password' => 'test-mailbox-password'])
                ->assertHeader('Cache-Control', 'no-store, private');

            $this->get(route('mail-accounts.show', 'finance'))
                ->assertOk()
                ->assertSee('finance@example.test')
                ->assertDontSee('test-finance-password');

            $this->postJson(route('mail-accounts.password', 'finance'))
                ->assertOk()
                ->assertExactJson(['password' => 'test-finance-password']);
        }
    }

    public function test_accountant_can_access_finance_mail_but_not_info_mail(): void
    {
        $this->login($this->accountant)
            ->get(route('mail-accounts.show', 'info'))
            ->assertForbidden();

        $this->postJson(route('mail-accounts.password', 'info'))
            ->assertForbidden();

        $this->get(route('mail-accounts.show', 'finance'))
            ->assertOk()
            ->assertSee('finance@example.test')
            ->assertDontSee('test-finance-password');

        $this->postJson(route('mail-accounts.password', 'finance'))
            ->assertOk()
            ->assertExactJson(['password' => 'test-finance-password']);
    }

    public function test_mailbox_password_cannot_be_revealed_when_it_is_not_configured(): void
    {
        config(['services.finance_mail.password' => null]);
        $this->login($this->accountant)
            ->postJson(route('mail-accounts.password', 'finance'))
            ->assertStatus(503);
    }
}
