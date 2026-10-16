<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPageTest extends TestCase
{
    use RefreshDatabase;

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
