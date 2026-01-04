<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $start = now()->addDays(2)->setTime(10, 0);
        $end   = (clone $start)->addMinutes(30);

        return [
            'user_id'     => User::factory(),
            'provider_id' => User::factory(),
            'starts_at'   => $start,
            'ends_at'     => $end,
            'status'      => 'scheduled',
            'notes'       => $this->faker->sentence(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(['status' => 'scheduled']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
