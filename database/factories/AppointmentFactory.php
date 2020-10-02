<?php

namespace Database\Factories;

use App\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $start_time = now()->addHours(rand(1, 100));
        return [
            'client_id' => \App\Client::inRandomOrder()->first()->id,
            'employee_id' => \App\Employee::inRandomOrder()->first()->id,
            'start_time' => $start_time->format('Y-m-d H') . ':00',
            'finish_time' => $start_time->addHours(rand(1, 2))->format('Y-m-d H') . ':00',
        ];
    }
}
