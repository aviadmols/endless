<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Models\Memorial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Memorial>
 */
class MemorialFactory extends Factory
{
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());
        $birth = fake()->dateTimeBetween('-90 years', '-40 years');
        $death = fake()->dateTimeBetween('-5 years', '-1 month');

        return [
            'user_id' => User::factory(),
            'slug' => 'memorial-'.Str::lower(Str::random(8)),
            'first_name' => fake()->firstName($gender->value),
            'last_name' => fake()->lastName(),
            'gender' => $gender,
            'subtitle' => $gender->defaultSubtitle(),
            'birth_date' => $birth,
            'death_date' => $death,
            'religion' => Religion::Jewish,
            'biography_title' => 'איש משפחה, חבר ואדם טוב',
            'biography' => '<p>'.fake()->paragraph(4).'</p><p>'.fake()->paragraph(3).'</p>',
            'quote' => fake()->sentence(12),
            'quote_name' => fake()->name(),
            'visibility' => 'private',
            'require_approval' => true,
            'notify_owner' => true,
        ];
    }
}
