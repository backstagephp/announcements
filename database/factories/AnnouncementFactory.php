<?php

namespace Backstage\Announcements\Database\Factories;

use Backstage\Announcements\Models\Announcement;
use Filament\Pages\Dashboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->sentence(),
            'scopes' => [Dashboard::class],
            'color' => 'info',
            'start_date' => null,
            'end_date' => null,
        ];
    }

    public function scopes(array $scopes): static
    {
        return $this->state(fn () => ['scopes' => $scopes]);
    }
}
