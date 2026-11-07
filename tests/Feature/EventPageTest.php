<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Person;
use App\Models\SacRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_portal_link_opens_dashboard_or_login_as_appropriate(): void
    {
        $dashboardUrl = backpack_url('dashboard');
        $this->get(route('events.index'))->assertOk()
            ->assertSee('href="'.$dashboardUrl.'"', false)
            ->assertDontSee('href="'.backpack_url('login').'"', false);
        $this->get($dashboardUrl)->assertRedirect(backpack_url('login'));

        $role = SacRole::firstOrCreate(['code' => Person::ROLE_PARENT], ['name' => 'Parent', 'order' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        Person::create(['user_id' => $user->id, 'sac_role_id' => $role->id, 'first_name' => 'Parent', 'last_name' => 'User']);
        $this->actingAs($user, config('backpack.base.guard'))
            ->get(route('events.index'))->assertOk()->assertSee('href="'.$dashboardUrl.'"', false);
        $this->get($dashboardUrl)->assertOk()->assertSee('My children');
    }

    public function test_events_are_listed_on_the_archive_instead_of_the_homepage(): void
    {
        Event::create([
            'title' => 'Upcoming family day',
            'starts_at' => now()->addDays(10),
            'is_published' => true,
        ]);
        Event::create([
            'title' => 'Later story time',
            'starts_at' => now()->addDays(20),
            'is_published' => true,
        ]);
        Event::create([
            'title' => 'Past community picnic',
            'starts_at' => now()->subDays(10),
            'is_published' => true,
        ]);
        Event::create([
            'title' => 'Unpublished event',
            'starts_at' => now()->addDays(20),
            'is_published' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('All events')
            ->assertSee('Upcoming family day')
            ->assertDontSee('Later story time')
            ->assertDontSee('Past community picnic');

        $this->get('/events')
            ->assertOk()
            ->assertSee('Upcoming events')
            ->assertSee('Past events')
            ->assertSee('Upcoming family day')
            ->assertSee('Past community picnic')
            ->assertDontSee('Unpublished event');
    }
}
