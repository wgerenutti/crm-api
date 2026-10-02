<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->cellphoneNumber(),
            'company' => $this->faker->company(),
            'source' => $this->faker->randomElement(['WEBSITE', 'INSTAGRAM', 'FACEBOOK', 'WHATSAPP', 'REFERRAL', 'OTHER']),
            'status' => $this->faker->randomElement(Lead::STATUSES),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
