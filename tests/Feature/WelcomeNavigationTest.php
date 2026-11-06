<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WelcomeNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_sees_wifi_and_utm_links_but_not_nfc_studio_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('wifi.qr.index').'"', false)
            ->assertSee('href="'.route('utm.builder.index').'"', false)
            ->assertDontSee('href="'.route('nfc.studio').'"', false)
            ->assertSee('YÖNETİCİ GİRİŞİ GEREKLİ');
    }

    public function test_non_admin_cannot_open_nfc_studio_from_homepage(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertDontSee('href="'.route('nfc.studio').'"', false);
    }

    public function test_admin_sees_nfc_studio_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('href="'.route('nfc.studio').'"', false)
            ->assertDontSee('YÖNETİCİ GİRİŞİ GEREKLİ');
    }
}
