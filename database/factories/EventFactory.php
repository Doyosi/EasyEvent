<?php

namespace Doyosi\EasyEvent\Database\Factories;

use Doyosi\EasyEvent\Models\EasyEvent as Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $types = (array) config('easy-event.types', ['meeting','holiday','webinar','workshop','custom']);
        $statuses = (array) config('easy-event.status', ['draft','published','archived']);

        // Random base date between -30d and +45d
        $start = Carbon::instance($this->faker->dateTimeBetween('-30 days', '+45 days'))
            ->minute(0)->second(0);
        $durationH = $this->faker->numberBetween(1, 3);
        $end = (clone $start)->addHours($durationH);

        $allDay = $this->faker->boolean(10);
        if ($allDay) {
            $start = $start->copy()->startOfDay();
            $end = $start->copy()->endOfDay();
        }

        return [
            'event_id'    => $this->faker->boolean(40) ? (string) Str::uuid() : null,
            'type'        => $this->faker->randomElement($types),
            'title'       => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->boolean(70) ? $this->faker->paragraph() : null,
            'starts_at'   => $start,
            'ends_at'     => $end,
            'all_day'     => $allDay,
            'location'    => $this->faker->boolean(60) ? $this->faker->city() : null,
            'status'      => $this->faker->randomElement($statuses),
            'meta'        => [
                'speaker'  => $this->faker->name(),
                'capacity' => $this->faker->numberBetween(20, 200),
            ],
        ];
    }

    /** States */
    public function published(): static { return $this->state(fn () => ['status' => 'published']); }
    public function draft(): static     { return $this->state(fn () => ['status' => 'draft']); }
    public function archived(): static  { return $this->state(fn () => ['status' => 'archived']); }
    public function type(string $t): static { return $this->state(fn () => ['type' => $t]); }

    public function today(): static
    {
        return $this->state(function () {
            $start = Carbon::today()->addHours(fake()->numberBetween(9, 16))->minute(0)->second(0);
            $end = (clone $start)->addHours(fake()->numberBetween(1, 3));
            return ['starts_at' => $start, 'ends_at' => $end];
        });
    }

    public function upcoming(): static
    {
        return $this->state(function () {
            $start = Carbon::instance(fake()->dateTimeBetween('+1 day', '+45 days'))->minute(0)->second(0);
            $end = (clone $start)->addHours(fake()->numberBetween(1, 3));
            return ['starts_at' => $start, 'ends_at' => $end];
        });
    }

    public function past(): static
    {
        return $this->state(function () {
            $start = Carbon::instance(fake()->dateTimeBetween('-30 days', 'yesterday'))->minute(0)->second(0);
            $end = (clone $start)->addHours(fake()->numberBetween(1, 3));
            return ['starts_at' => $start, 'ends_at' => $end];
        });
    }

    public function allDay(): static
    {
        return $this->state(function () {
            $d = Carbon::instance(fake()->dateTimeBetween('-10 days', '+30 days'))->startOfDay();
            return ['all_day' => true, 'starts_at' => $d, 'ends_at' => $d->copy()->endOfDay()];
        });
    }
}
