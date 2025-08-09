<?php

namespace Doyosi\EasyEvent\Support;

use Doyosi\EasyEvent\Models\EasyEvent as Event;
use Illuminate\Support\Collection;

class EasyEvent
{
    /** Get today's published events. */
    public function today(int $limit = null): Collection
    {
        $query = Event::query()->published()->today();
        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    /** Get this month's published events. */
    public function thisMonth(int $limit = null): Collection
    {
        $query = Event::query()->published()->thisMonth()->orderBy('starts_at');
        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    /** Get recent past events (published). */
    public function recent(int $limit = 5): Collection
    {
        return Event::query()->published()->past()->limit($limit)->get();
    }

    /** Get upcoming published events. */
    public function upcoming(int $limit = 10): Collection
    {
        return Event::query()->published()->upcoming()->limit($limit)->get();
    }
}
