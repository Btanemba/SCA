<?php

namespace Tests\Feature;

use App\Models\ManagementMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_page_shows_published_members_in_display_order(): void
    {
        ManagementMember::create([
            'first_name' => 'Amina',
            'last_name' => 'Okafor',
            'title' => 'Chairman of the Board',
            'description' => 'Guides the board.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        ManagementMember::create([
            'first_name' => 'Bola',
            'last_name' => 'Adeyemi',
            'title' => 'Board Director',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        ManagementMember::create([
            'first_name' => 'Hidden',
            'last_name' => 'Member',
            'title' => 'Unpublished Director',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('Amina Okafor')
            ->assertSeeInOrder(['Chairman of the Board', 'Board Director'])
            ->assertDontSee('Unpublished Director');
    }
}
