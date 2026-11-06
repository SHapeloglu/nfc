<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\LinkProject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NfcStudioRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_the_admin_login(): void
    {
        $this->get('/nfc-studio')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get('/nfc-studio')->assertForbidden();
    }

    public function test_admin_sees_only_their_own_projects(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for($admin)->create(['name' => 'Benim Kafem']);
        LinkProject::factory()->for(User::factory()->admin())->create(['name' => 'Rakip Kafe']);

        $this->actingAs($admin)->get('/nfc-studio')
            ->assertOk()
            ->assertSee('Benim Kafem')
            ->assertDontSee('Rakip Kafe');
    }

    public function test_escapes_project_names_in_the_page(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for($admin)->create(['name' => '<script>alert("xss")</script>']);

        $response = $this->actingAs($admin)->get('/nfc-studio');

        $response->assertOk();
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $response->getContent());
    }

    public function test_admin_receives_nfc_card_controls_with_an_owned_link(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for($admin)->create([
            'name' => 'Test işletmesi',
            'target_url' => 'https://search.google.com/local/writereview?placeid=ChIJ1234567890123',
        ]);

        $response = $this->actingAs($admin)->get('/nfc-studio');

        $response->assertOk()
            ->assertSee('Test işletmesi')
            ->assertSee('id="readButton"', false)
            ->assertSee('id="writeButton"', false)
            ->assertSee('id="cancelButton"', false)
            ->assertSee('id="operationStatus"', false);
    }
}
