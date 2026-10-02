<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guardian_id' => Guardian::factory(),
            'name' => fake()->firstName().' '.fake()->lastName(),
            'grade' => 'Grade '.fake()->numberBetween(1, 6),
            'status' => StudentStatus::Active,
            'joined_on' => now()->subMonths(fake()->numberBetween(0, 12))->toDateString(),
        ];
    }
}
