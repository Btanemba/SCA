<?php

namespace Tests\Feature;

use App\Models\AcademyDocument;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake('local');
        $role = SacRole::firstOrCreate(['code' => Person::ROLE_ADMIN], ['name' => 'Admin', 'order' => 1]);
        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        Person::create([
            'user_id' => $this->admin->id,
            'sac_role_id' => $role->id,
            'first_name' => 'School',
            'last_name' => 'Admin',
        ]);
    }

    public function test_admin_can_upload_multiple_documents_download_them_and_delete_them(): void
    {
        $this->actingAs($this->admin, config('backpack.base.guard'))
            ->post(route('academy.settings.documents.store'), [
                'title' => 'School Handbook',
                'document' => UploadedFile::fake()->create('handbook.pdf', 20, 'application/pdf'),
            ])->assertRedirect();

        $first = AcademyDocument::query()->firstOrFail();

        $this->post(route('academy.settings.documents.store'), [
            'title' => 'Admissions Policy',
            'document' => UploadedFile::fake()->create('admissions.pdf', 24, 'application/pdf'),
        ])->assertRedirect();

        $second = AcademyDocument::query()->where('title', 'Admissions Policy')->firstOrFail();
        $this->assertDatabaseCount('academy_documents', 2);
        $this->assertNotSame($first->file_path, $second->file_path);
        $this->disk->assertExists([$first->file_path, $second->file_path]);

        $this->get(route('academy.settings'))->assertOk()->assertSee('Documents')->assertSee('School Handbook')->assertSee('Admissions Policy');
        $this->get(route('academy.settings.documents.download', $first))->assertDownload('handbook.pdf');

        $this->delete(route('academy.settings.documents.delete', $first))->assertRedirect();
        $this->assertDatabaseMissing('academy_documents', ['id' => $first->id]);
        $this->disk->assertMissing($first->file_path);
        $this->disk->assertExists($second->file_path);
    }

    public function test_non_admin_cannot_manage_school_documents(): void
    {
        $role = SacRole::firstOrCreate(['code' => Person::ROLE_STAFF], ['name' => 'Staff', 'order' => 1]);
        $staff = User::factory()->create(['email_verified_at' => now()]);
        Person::create([
            'user_id' => $staff->id,
            'sac_role_id' => $role->id,
            'first_name' => 'School',
            'last_name' => 'Staff',
        ]);
        $document = AcademyDocument::create([
            'title' => 'School Handbook',
            'original_name' => 'handbook.pdf',
            'file_path' => UploadedFile::fake()->create('handbook.pdf', 20, 'application/pdf')->store('academy/documents', 'local'),
        ]);

        $this->actingAs($staff, config('backpack.base.guard'))
            ->post(route('academy.settings.documents.store'), [
                'title' => 'New document',
                'document' => UploadedFile::fake()->create('new.pdf', 20, 'application/pdf'),
            ])->assertForbidden();
        $this->get(route('academy.settings.documents.download', $document))->assertForbidden();
        $this->delete(route('academy.settings.documents.delete', $document))->assertForbidden();
    }
}
