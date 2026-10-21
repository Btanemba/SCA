<?php

namespace Tests\Feature;

use App\Models\ChildPickupContact;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\StaffDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuditUserTest extends TestCase
{
    use RefreshDatabase;

    public static function auditedModels(): array
    {
        return [
            'people' => [Person::class, true],
            'roles' => [SacRole::class, true],
            'pickup links' => [ChildPickupContact::class, true],
            'staff documents' => [StaffDocument::class, true],
            'expenses' => [Expense::class, false],
            'payrolls' => [Payroll::class, false],
        ];
    }

    #[DataProvider('auditedModels')]
    public function test_audit_users_are_stamped_and_display_full_names(string $modelClass, bool $tracksUpdater): void
    {
        $role = SacRole::create(['name' => 'Administrator', 'code' => Person::ROLE_ADMIN, 'order' => 1]);
        $creator = User::factory()->create(['name' => 'Creator login']);
        $updater = User::factory()->create(['name' => 'Updater login']);
        $person = Person::create([
            'user_id' => $creator->id,
            'sac_role_id' => $role->id,
            'first_name' => 'Ada',
            'last_name' => 'Okafor',
        ]);
        Person::create([
            'user_id' => $updater->id,
            'sac_role_id' => $role->id,
            'first_name' => 'Bola',
            'last_name' => 'Adebayo',
        ]);

        $attributes = match ($modelClass) {
            Person::class => ['sac_role_id' => $role->id, 'first_name' => 'New', 'last_name' => 'Person'],
            SacRole::class => ['name' => 'Audited role', 'code' => 'AUDT', 'order' => 2],
            ChildPickupContact::class => ['child_id' => $person->id, 'slot' => 1, 'first_name' => 'Pickup', 'last_name' => 'Contact'],
            StaffDocument::class => ['person_id' => $person->id, 'title' => 'Contract', 'document_type' => 'contract', 'body' => 'Terms'],
            Expense::class => [
                'title' => 'Supplies', 'category' => 'Supplies', 'description' => 'Books',
                'expense_date' => '2026-10-21', 'payee' => 'Supplier', 'amount_kobo' => 10000,
            ],
            Payroll::class => ['year' => 2026, 'month' => 10],
        };

        $this->actingAs($creator, config('backpack.base.guard'));
        $model = new $modelClass;
        $model->forceFill($attributes + ['created_by' => $updater->id]);
        if ($tracksUpdater) {
            $model->updated_by = $updater->id;
        }
        $model->save();
        $model->refresh();

        $this->assertSame($creator->id, $model->created_by);
        $this->assertSame('Ada Okafor', $model->created_by_name);
        if ($tracksUpdater) {
            $this->assertSame($creator->id, $model->updated_by);
            $this->assertSame('Ada Okafor', $model->updated_by_name);
        } else {
            $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
        }

        $auditFields = view('partials.audit-fields', ['auditEntry' => $model, 'showUpdater' => $tracksUpdater])->render();
        $this->assertStringContainsString('Ada Okafor', $auditFields);
        $this->assertStringContainsString('readonly', $auditFields);
        if (! $tracksUpdater) {
            $this->assertStringNotContainsString('Updated by', $auditFields);
        }

        $this->actingAs($updater, config('backpack.base.guard'));
        $this->travel(1)->minutes();
        $model->created_by = $updater->id;
        $model->save();
        $model->refresh();

        $this->assertSame($creator->id, $model->created_by);
        $this->assertSame('Ada Okafor', $model->created_by_name);
        if ($tracksUpdater) {
            $this->assertSame($updater->id, $model->updated_by);
            $this->assertSame('Bola Adebayo', $model->updated_by_name);
        } else {
            $this->assertArrayNotHasKey('updated_by', $model->getAttributes());
        }
    }

    public function test_missing_person_uses_account_name_and_deleted_user_displays_no_name(): void
    {
        $user = User::factory()->create(['name' => 'Account Name']);
        $role = new SacRole;
        $role->forceFill([
            'name' => 'Audited role', 'code' => 'AUDT', 'order' => 1,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ])->save();

        $this->assertSame('Account Name', $role->created_by_name);
        $this->assertSame('Account Name', $role->updated_by_name);

        $user->delete();
        $role->refresh();
        $this->assertNull($role->created_by_name);
        $this->assertNull($role->updated_by_name);
    }

    public function test_unauthenticated_edits_preserve_audit_users(): void
    {
        $user = User::factory()->create();
        $role = new SacRole;
        $role->forceFill([
            'name' => 'Original', 'code' => 'AUDT', 'order' => 1,
            'created_by' => $user->id, 'updated_by' => $user->id,
        ])->save();

        $role->forceFill(['name' => 'Changed', 'created_by' => null, 'updated_by' => null])->save();
        $role->refresh();

        $this->assertSame($user->id, $role->created_by);
        $this->assertSame($user->id, $role->updated_by);
    }

    public function test_role_crud_displays_read_only_full_names_and_ignores_forged_users(): void
    {
        $role = SacRole::create(['name' => 'Administrator', 'code' => Person::ROLE_ADMIN, 'order' => 1]);
        $user = User::factory()->create();
        Person::create([
            'user_id' => $user->id, 'sac_role_id' => $role->id,
            'first_name' => 'Ada', 'last_name' => 'Okafor',
        ]);
        $this->actingAs($user, config('backpack.base.guard'));

        $this->post(backpack_url('sac-role'), [
            'name' => 'Audited role', 'code' => 'AUDT', 'order' => 2,
            'created_by' => 999999, 'updated_by' => 999999,
            'created_by_name' => 'Forged Name', 'updated_by_name' => 'Forged Name',
        ])->assertRedirect();

        $entry = SacRole::where('code', 'AUDT')->firstOrFail();
        $this->assertSame($user->id, $entry->created_by);
        $this->assertSame($user->id, $entry->updated_by);
        $this->get(backpack_url('sac-role/'.$entry->id.'/edit'))
            ->assertOk()->assertSee('Ada Okafor')->assertSee('readonly');
        $this->get(backpack_url('sac-role/'.$entry->id.'/show'))
            ->assertOk()->assertSee('Ada Okafor');

        $person = Person::create([
            'sac_role_id' => $role->id, 'first_name' => 'New', 'last_name' => 'Person',
        ]);
        $this->get(backpack_url('person/'.$person->id.'/edit'))
            ->assertOk()->assertSee('created_by_name')->assertSee('Ada Okafor');
        $this->get(backpack_url('person/'.$person->id.'/show'))
            ->assertOk()->assertSee('Ada Okafor');
    }
}
