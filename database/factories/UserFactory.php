<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        static $password;

        $firstName = $this->faker->firstName();
        $surname = $this->faker->lastName();
        $lastName = $this->faker->optional()->lastName();

        return [
            'surname' => $surname,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'contact_number' => '2547' . $this->faker->numerify('######'),
            'password' => $password ?: $password = Hash::make('secret'),
            'language' => 'en',
            'remember_token' => Str::random(10),
        ];
    }
}
