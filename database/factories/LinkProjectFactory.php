<?php

// Test için sahte bağlantı projesi üretir (varsayılan: Google yorum bağlantısı). Sahip: ->for($kullanici).
// Türler için adlandırılmış durumlar: googleReview(), instagramFollow().

namespace Database\Factories;

use App\Models\LinkProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkProject>
 */
class LinkProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'type' => 'google_review',
            'target_url' => 'https://search.google.com/local/writereview?placeid='.fake()->regexify('[A-Za-z0-9_-]{27}'),
            'metadata' => ['place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4'],
        ];
    }

    /**
     * Google yorum bağlantısı.
     */
    public function googleReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'google_review',
        ]);
    }

    /**
     * Instagram takip bağlantısı.
     */
    public function instagramFollow(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'instagram_follow',
            'target_url' => 'https://www.instagram.com/ornek.hesap?xtok=abcdEFGH1234',
            'metadata' => ['username' => 'ornek.hesap'],
        ]);
    }
}
