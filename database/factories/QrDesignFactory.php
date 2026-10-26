<?php

// Test için sahte QR tasarımı üretir. Sahip: ->for($kullanici) ile verilir (user_id elle atanır, $fillable dışındadır).
// Ayarlar, QrDesignController'ın doğruladığı tam ayar dizisidir.

namespace Database\Factories;

use App\Models\QrDesign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrDesign>
 */
class QrDesignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'settings' => [
                'content' => 'https://example.com',
                'dots' => 'square',
                'corners' => 'square',
                'fg' => '#000000',
                'bg' => '#ffffff',
                'cornerColor' => '#000000',
                'gradient' => 'none',
                'gradientEnd' => '#000000',
                'size' => '500',
                'margin' => '20',
            ],
        ];
    }
}
