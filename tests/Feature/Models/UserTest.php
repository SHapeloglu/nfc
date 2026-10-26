<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_an_admin_can_access_the_admin_panel(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_a_non_admin_cannot_access_the_admin_panel(): void
    {
        $this->assertFalse(User::factory()->create()->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_an_admin_cannot_access_a_different_panel(): void
    {
        $this->assertFalse(User::factory()->admin()->create()->canAccessPanel(Panel::make()->id('baska')));
    }

    public function test_users_are_not_admins_by_default(): void
    {
        $this->assertFalse((bool) User::factory()->create()->fresh()->is_admin);
    }

    public function test_is_admin_cannot_be_mass_assigned(): void
    {
        $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'gizli-sifre', 'is_admin' => true]);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }

    public function test_password_is_hashed_and_hidden_from_serialization(): void
    {
        $user = User::factory()->create(['password' => 'gizli-sifre']);

        $this->assertNotSame('gizli-sifre', $user->password);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}
