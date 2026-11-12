<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\LinkProject;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NfcCardApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  list<string>  $abilities
     */
    private function tokenFor(User $user, array $abilities): string
    {
        return $user->createToken('test', $abilities)->plainTextToken;
    }

    public function test_returns_401_without_a_token(): void
    {
        $card = NfcCard::factory()->create();

        $this->postJson('/api/nfc/cards/'.$card->id.'/written', [])->assertUnauthorized();
    }

    public function test_forbids_a_token_without_the_nfc_write_ability(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create(['user_id' => $admin->id, 'link_project_id' => $project->id]);

        $this->withToken($this->tokenFor($admin, ['nfc:read']))
            ->postJson('/api/nfc/cards/'.$card->id.'/written', [
                'project_id' => $project->id,
                'written_url' => $project->target_url,
            ])
            ->assertForbidden();
    }

    public function test_forbids_a_non_admin_token_even_with_the_nfc_write_ability(): void
    {
        $user = User::factory()->create();
        $project = LinkProject::factory()->for($user)->create();
        $card = NfcCard::factory()->create(['user_id' => $user->id, 'link_project_id' => $project->id]);

        $this->withToken($this->tokenFor($user, ['nfc:write']))
            ->postJson('/api/nfc/cards/'.$card->id.'/written', [
                'project_id' => $project->id,
                'written_url' => $project->target_url,
            ])
            ->assertForbidden();
    }

    public function test_records_write_and_readback_with_an_nfc_write_token(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create(['target_url' => 'https://ornek.com/kampanya']);
        $card = NfcCard::factory()->create(['user_id' => $admin->id, 'link_project_id' => $project->id]);
        $token = $this->tokenFor($admin, ['nfc:write']);

        $this->withToken($token)
            ->postJson('/api/nfc/cards/'.$card->id.'/written', [
                'project_id' => $project->id,
                'written_url' => 'https://ornek.com/kampanya',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'written');

        $this->withToken($token)
            ->postJson('/api/nfc/cards/'.$card->id.'/verified', [
                'project_id' => $project->id,
                'verified_url' => 'https://ornek.com/kampanya',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'verified');

        $this->assertDatabaseHas('nfc_cards', ['id' => $card->id, 'status' => 'verified']);
    }

    public function test_rejects_a_readback_that_does_not_match_the_written_url(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create(['target_url' => 'https://ornek.com/kampanya']);
        $card = NfcCard::factory()->create(['user_id' => $admin->id, 'link_project_id' => $project->id]);
        $token = $this->tokenFor($admin, ['nfc:write']);

        $this->withToken($token)
            ->postJson('/api/nfc/cards/'.$card->id.'/written', [
                'project_id' => $project->id,
                'written_url' => 'https://ornek.com/kampanya',
            ])
            ->assertOk();

        $this->withToken($token)
            ->postJson('/api/nfc/cards/'.$card->id.'/verified', [
                'project_id' => $project->id,
                'verified_url' => 'https://baska.com/',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('nfc_cards', ['id' => $card->id, 'status' => 'written']);
    }

    public function test_lists_only_own_available_cards_of_the_project_with_nfc_read(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $other = LinkProject::factory()->for($admin)->create();
        $available = NfcCard::factory()->create(['user_id' => $admin->id, 'link_project_id' => $project->id, 'label' => 'Hazır']);
        NfcCard::factory()->create(['user_id' => $admin->id, 'link_project_id' => $project->id, 'label' => 'Doğrulanmış', 'status' => 'verified']);
        NfcCard::factory()->create(['user_id' => $admin->id, 'link_project_id' => $other->id, 'label' => 'Başka proje']);
        NfcCard::factory()->create(['link_project_id' => $project->id, 'label' => 'Başka kullanıcı']);

        $response = $this->withToken($this->tokenFor($admin, ['nfc:read']))
            ->getJson('/api/nfc/cards?proje='.$project->id);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('cards.0.id', $available->id);
        $this->assertSame(['id', 'label', 'status', 'link_project_id'], array_keys($response->json('cards.0')));
    }

    public function test_card_list_forbids_a_token_without_nfc_read(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();

        $this->withToken($this->tokenFor($admin, ['nfc:write']))
            ->getJson('/api/nfc/cards?proje='.$project->id)
            ->assertForbidden();
    }
}
