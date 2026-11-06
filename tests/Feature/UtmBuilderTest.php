<?php

namespace Tests\Feature;

use Tests\TestCase;

class UtmBuilderTest extends TestCase
{
    public function test_utm_builder_page_is_available_to_guests(): void
    {
        $this->get('/utm-builder')
            ->assertOk()
            ->assertSee('UTM Link Builder')
            ->assertSee('utm_source')
            ->assertSee('utm_medium')
            ->assertSee('utm_campaign')
            ->assertSee('Özel kanal şablonları');
    }

    public function test_utm_builder_offers_ten_preset_channels(): void
    {
        $this->get('/utm-builder')
            ->assertOk()
            ->assertSee('Email Signature')
            ->assertSee('Newsletter')
            ->assertSee('WhatsApp')
            ->assertSee('Instagram')
            ->assertSee('LinkedIn')
            ->assertSee('QR / Print')
            ->assertSee('TikTok')
            ->assertSee('Facebook')
            ->assertSee('Telegram')
            ->assertSee('>Email<', false);
    }
}
