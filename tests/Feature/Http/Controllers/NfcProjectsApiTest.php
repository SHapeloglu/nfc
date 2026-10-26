<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\LinkProject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NfcProjectsApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  list<string>  $abilities
     */
    private function tokenFor(User $user, array $abilities): string
    {
        return $user->createToken('test', $abilities)->plainTextToken;
    }

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $this->getJson('/api/nfc/projects')->assertUnauthorized();
    }

    public function test_returns_401_for_an_invalid_token(): void
    {
        $this->withToken('999|gecersiz-token')->getJson('/api/nfc/projects')->assertUnauthorized();
    }

    public function test_forbids_an_admin_token_without_the_nfc_read_ability(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withToken($this->tokenFor($admin, ['baska:yetki']))
            ->getJson('/api/nfc/projects')
            ->assertForbidden();
    }

    public function test_forbids_a_non_admin_token_even_with_the_nfc_read_ability(): void
    {
        $user = User::factory()->create();
        LinkProject::factory()->for($user)->create();

        $this->withToken($this->tokenFor($user, ['nfc:read']))
            ->getJson('/api/nfc/projects')
            ->assertForbidden();
    }

    public function test_returns_only_the_token_owners_projects_with_the_documented_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $mine = LinkProject::factory()->for($admin)->create(['name' => 'Benim', 'created_at' => now()->subDay()]);
        $newest = LinkProject::factory()->for($admin)->instagramFollow()->create(['name' => 'En yeni', 'created_at' => now()]);
        LinkProject::factory()->for(User::factory()->admin())->create(['name' => 'Başkasının']);

        $response = $this->withToken($this->tokenFor($admin, ['nfc:read']))->getJson('/api/nfc/projects');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2)
            ->assertJsonMissing(['name' => 'Başkasının']);
        $this->assertSame([$newest->id, $mine->id], array_column($response->json('projects'), 'id'));
        $this->assertSame(['id', 'name', 'type', 'target_url'], array_keys($response->json('projects.0')));
    }

    public function test_a_wildcard_token_can_read_the_projects(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withToken($this->tokenFor($admin, ['*']))
            ->getJson('/api/nfc/projects')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_user_endpoint_returns_the_token_owner(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user, ['*']))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonMissing(['password' => $user->password]);
    }

    public function test_user_endpoint_requires_a_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_browser_style_request_without_a_token_gets_401_instead_of_a_redirect(): void
    {
        $this->get('/api/nfc/projects')->assertUnauthorized();
    }
}
