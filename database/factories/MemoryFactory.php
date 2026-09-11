<?php

namespace Database\Factories;

use App\Enums\MemoryStatus;
use App\Models\Memorial;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memory>
 */
class MemoryFactory extends Factory
{
    public function definition(): array
    {
        $paragraphs = fake()->paragraphs(fake()->numberBetween(1, 3));
        $plain = implode("\n", $paragraphs);

        return [
            'memorial_id' => Memorial::factory(),
            'author_name' => fake()->name(),
            'author_email' => fake()->optional()->safeEmail(),
            'body' => '<p>'.implode('</p><p>', $paragraphs).'</p>',
            'body_plain' => $plain,
            'status' => MemoryStatus::Approved,
            'approved_at' => now(),
            'submitted_via' => 'link',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => MemoryStatus::Pending, 'approved_at' => null]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => MemoryStatus::Rejected, 'approved_at' => null]);
    }
}
