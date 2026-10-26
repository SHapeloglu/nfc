<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NfcCard> */
class NfcCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_id' => Business::factory(),
            'label' => 'NFC '.fake()->unique()->numerify('#####'),
            'status' => 'pending',
        ];
    }
}
