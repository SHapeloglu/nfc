<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\LinkProject;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NfcCardWriteControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_studio_lists_only_available_cards_owned_by_the_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $available = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
            'label' => 'Hazır Kart A',
        ]);
        NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
            'label' => 'Teslim Edilen Kart',
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        NfcCard::factory()->create(['label' => 'Başka Kullanıcı Kartı']);

        $this->actingAs($admin)->get(route('nfc.studio'))
            ->assertOk()
            ->assertSee('Hazır Kart A')
            ->assertSee('value="'.$available->id.'"', false)
            ->assertDontSee('Teslim Edilen Kart')
            ->assertDontSee('Başka Kullanıcı Kartı');
    }

    public function test_guest_cannot_record_a_card_write(): void
    {
        $card = NfcCard::factory()->create();

        $this->postJson(route('nfc.cards.written', $card), [])->assertUnauthorized();
    }

    public function test_non_admin_cannot_record_a_card_write(): void
    {
        $user = User::factory()->create();
        $card = NfcCard::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('nfc.cards.written', $card), [])->assertForbidden();
    }

    public function test_an_admin_cannot_update_another_users_card(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $card = NfcCard::factory()->create(['user_id' => $other->id]);

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [])->assertNotFound();
    }

    public function test_browser_reported_write_and_readback_update_own_card(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
        ]);

        $payload = ['project_id' => $project->id, 'written_url' => $project->target_url];

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), $payload)
            ->assertOk()->assertJsonPath('status', 'written');

        $this->assertDatabaseHas('nfc_cards', [
            'id' => $card->id,
            'status' => 'written',
            'written_url' => $project->target_url,
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.verified', $card), [
            'project_id' => $project->id,
            'verified_url' => $project->target_url,
        ])->assertOk()->assertJsonPath('status', 'verified');

        $this->assertSame('verified', $card->fresh()->status);
        $this->assertNotNull($card->fresh()->verified_at);
    }

    public function test_cannot_report_verification_before_a_write(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.verified', $card), [
            'project_id' => $project->id,
            'verified_url' => $project->target_url,
        ])->assertUnprocessable()->assertJsonValidationErrors('card');
    }

    public function test_cannot_use_an_unrelated_project_for_a_card(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $unrelated = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
            'project_id' => $unrelated->id,
            'written_url' => $unrelated->target_url,
        ])->assertUnprocessable()->assertJsonValidationErrors('project_id');
    }

    public function test_delivered_card_cannot_be_overwritten(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
            'project_id' => $project->id,
            'written_url' => $project->target_url,
        ])->assertUnprocessable()->assertJsonValidationErrors('card');
    }
}
