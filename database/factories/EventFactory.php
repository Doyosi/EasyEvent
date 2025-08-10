<?php

namespace Doyosi\EasyEvent\Database\Factories;

use Doyosi\EasyEvent\Models\EasyEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<EasyEvent>
 */
class EasyEventFactory extends Factory
{
    protected $model = EasyEvent::class;

    public function definition(): array
    {
        $types = (array) config('easy-event.types', ['meeting','holiday','webinar','workshop','custom']);
        $statuses = (array) config('easy-event.status', ['draft','published','archived']);

        $start = Carbon::instance($this->faker->dateTimeBetween('-30 days', '+45 days'))->minute(0)->second(0);
        $end   = (clone $start)->addHours($this->faker->numberBetween(1, 3));

        $allDay = $this->faker->boolean(10);
        if ($allDay) {
            $start = $start->copy()->startOfDay();
            $end   = $start->copy()->endOfDay();
        }

        $fallbackLang = config('app.fallback_locale', 'en');
        $title = $this->faker->sentence(3, true);
        $description = $this->faker->boolean(70) ? $this->faker->paragraph() : null;
        $location = $this->faker->boolean(60) ? $this->faker->city() : null;

        $arrayTitle = [$fallbackLang => $title];
        $arrayDescription = $description ? [$fallbackLang => $description] : null;
        $arrayLocation = $location ? [$fallbackLang => $location] : null;

        return [
            'event_id'    => $this->faker->boolean(40) ? (string) Str::uuid() : null,
            'type'        => $this->faker->randomElement($types),
            'title'       => $arrayTitle,
            'description' => $arrayDescription,
            'starts_at'   => $start,
            'ends_at'     => $end,
            'all_day'     => $allDay,
            'location'    => $arrayLocation,
            'status'      => $this->faker->randomElement($statuses),
            'meta'        => [
                'speaker'  => $this->faker->name(),
                'capacity' => $this->faker->numberBetween(20, 200),
            ],
        ];
    }

    // ---- States ----
    public function published(): static { return $this->state(fn () => ['status' => 'published']); }
    public function draft(): static     { return $this->state(fn () => ['status' => 'draft']); }
    public function archived(): static  { return $this->state(fn () => ['status' => 'archived']); }

    public function today(): static
    {
        return $this->state(function () {
            $start = Carbon::today()->addHours(fake()->numberBetween(9, 16))->minute(0)->second(0);
            return ['starts_at' => $start, 'ends_at' => (clone $start)->addHours(fake()->numberBetween(1, 3))];
        });
    }

    public function upcoming(): static
    {
        return $this->state(function () {
            $start = Carbon::instance(fake()->dateTimeBetween('+1 day', '+45 days'))->minute(0)->second(0);
            return ['starts_at' => $start, 'ends_at' => (clone $start)->addHours(fake()->numberBetween(1, 3))];
        });
    }

    public function past(): static
    {
        return $this->state(function () {
            $start = Carbon::instance(fake()->dateTimeBetween('-30 days', 'yesterday'))->minute(0)->second(0);
            return ['starts_at' => $start, 'ends_at' => (clone $start)->addHours(fake()->numberBetween(1, 3))];
        });
    }

    public function allDay(): static
    {
        return $this->state(function () {
            $d = Carbon::instance(fake()->dateTimeBetween('-10 days', '+30 days'))->startOfDay();
            return ['all_day' => true, 'starts_at' => $d, 'ends_at' => $d->copy()->endOfDay()];
        });
    }

    /** Set a specific type */
    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    /** Optional: generic status() helper */
    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
