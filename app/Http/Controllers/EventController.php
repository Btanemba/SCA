<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function home(): View
    {
        $now = now();
        $featuredEvent = Event::query()
            ->where('is_published', true)
            ->where(function ($query) use ($now) {
                $query->where('ends_at', '>=', $now)
                    ->orWhere(fn ($query) => $query->whereNull('ends_at')->where('starts_at', '>=', $now));
            })
            ->orderBy('starts_at')
            ->first();

        return view('home', compact('featuredEvent'));
    }

    public function index(): View
    {
        $now = now();
        $publishedEvents = Event::query()->where('is_published', true);

        $upcomingEvents = (clone $publishedEvents)
            ->where(function ($query) use ($now) {
                $query->where('ends_at', '>=', $now)
                    ->orWhere(fn ($query) => $query->whereNull('ends_at')->where('starts_at', '>=', $now));
            })
            ->orderBy('starts_at')
            ->get();

        $pastEvents = (clone $publishedEvents)
            ->where(function ($query) use ($now) {
                $query->where('ends_at', '<', $now)
                    ->orWhere(fn ($query) => $query->whereNull('ends_at')->where('starts_at', '<', $now));
            })
            ->orderByDesc('starts_at')
            ->get();

        return view('events.index', compact('upcomingEvents', 'pastEvents'));
    }
}
