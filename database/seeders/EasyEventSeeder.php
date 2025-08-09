<?php

namespace Doyosi\EasyEvent\Database\Seeders;

use Illuminate\Database\Seeder;
use Doyosi\EasyEvent\Models\EasyEvent as Event;

/**
 * Seeds a realistic mix of past/today/upcoming events.
 *
 * Run with:
 *  php artisan db:seed --class="Doyosi\EasyEvent\Database\Seeders\EasyEventSeeder"
 */
class EasyEventSeeder extends Seeder
{
    public function run(): void
    {
        // Past published
        Event::factory()->count(8)->past()->published()->create();

        // Today (some all-day)
        Event::factory()->count(5)->today()->published()->create();
        Event::factory()->count(2)->today()->allDay()->type('holiday')->published()->create();

        // Upcoming this month + next
        Event::factory()->count(12)->upcoming()->published()->create();

        // Some drafts/archived for panel screens
        Event::factory()->count(3)->upcoming()->draft()->create();
        Event::factory()->count(2)->past()->archived()->create();
    }
}
