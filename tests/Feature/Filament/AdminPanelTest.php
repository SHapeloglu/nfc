<?php

namespace Tests\Feature\Filament;

use App\Models\LinkProject;
use App\Models\QrDesign;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_the_panel_to_the_login(): void
    {
        $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
        $this->get('/admin/qr-tasarimlari')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_non_admin_cannot_open_the_panel_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/qr-tasarimlari')->assertForbidden();
        $this->actingAs($user)->get('/admin/link-projects')->assertForbidden();
    }

    public function test_admin_sees_only_their_own_qr_designs(): void
    {
        $admin = User::factory()->admin()->create();
        QrDesign::factory()->for($admin)->create(['name' => 'Benim Tasarımım']);
        QrDesign::factory()->for(User::factory()->admin())->create(['name' => 'Yabancı Tasarım']);

        $this->actingAs($admin)->get('/admin/qr-tasarimlari')
            ->assertOk()
            ->assertSee('Benim Tasarımım')
            ->assertDontSee('Yabancı Tasarım');
    }

    public function test_admin_opens_the_link_projects_list(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for($admin)->create(['name' => 'Panel Projesi']);

        $this->actingAs($admin)->get('/admin/link-projects')->assertOk();
    }
}
