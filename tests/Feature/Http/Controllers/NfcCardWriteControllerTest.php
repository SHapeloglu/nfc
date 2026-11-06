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

    public function test_readback_with_a_different_url_does_not_verify_the_card(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
            'project_id' => $project->id,
            'written_url' => $project->target_url,
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('nfc.cards.verified', $card), [
            'project_id' => $project->id,
            'verified_url' => 'https://example.org/other-destination',
        ])->assertUnprocessable()->assertJsonValidationErrors('project_id');

        $card->refresh();
        $this->assertSame('written', $card->status);
        $this->assertNull($card->verified_at);
    }

    public function test_verified_card_cannot_be_written_again(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
            'status' => 'verified',
            'written_url' => $project->target_url,
            'written_at' => now()->subMinute(),
            'verified_at' => now(),
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
            'project_id' => $project->id,
            'written_url' => $project->target_url,
        ])->assertUnprocessable()->assertJsonValidationErrors('card');

        $card->refresh();
        $this->assertSame('verified', $card->status);
        $this->assertNotNull($card->verified_at);
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

    public function test_browser_normalized_addresses_are_accepted_for_the_stored_target(): void
    {
        $admin = User::factory()->admin()->create();
        $cases = [
            'büyük harfli host' => ['https://Search.Google.com/local/writereview?placeid=ChIJabc', 'https://search.google.com/local/writereview?placeid=ChIJabc'],
            'varsayılan port' => ['https://Instagram.com:443/kullanici?xtok=abc', 'https://instagram.com/kullanici?xtok=abc'],
            'boş yol' => ['https://x.example.com', 'https://x.example.com/'],
            'yüzde kodlama' => ['https://x.example.com/a b', 'https://x.example.com/a%20b'],
        ];

        foreach ($cases as $label => [$stored, $sent]) {
            $project = LinkProject::factory()->for($admin)->create(['target_url' => $stored]);
            $card = NfcCard::factory()->create([
                'user_id' => $admin->id,
                'link_project_id' => $project->id,
            ]);

            $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
                'project_id' => $project->id,
                'written_url' => $sent,
            ])->assertOk();

            $this->actingAs($admin)->postJson(route('nfc.cards.verified', $card), [
                'project_id' => $project->id,
                'verified_url' => $sent,
            ])->assertOk()->assertJsonPath('status', 'verified');

            $this->assertSame('verified', $card->fresh()->status, $label);
        }
    }

    public function test_a_different_address_is_still_rejected_after_normalization(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create([
            'target_url' => 'https://Search.Google.com/local/writereview?placeid=ChIJabc',
        ]);
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $project->id,
        ]);

        foreach (['https://search.google.com/local/writereview?placeid=ChIJABC', 'https://search.google.com/local/writereview?placeid=ChIJabd', 'http://search.google.com/local/writereview?placeid=ChIJabc'] as $other) {
            $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
                'project_id' => $project->id,
                'written_url' => $other,
            ])->assertUnprocessable()->assertJsonValidationErrors('project_id');
        }

        $this->assertSame('pending', $card->fresh()->status);
    }

    public function test_locked_card_is_checked_against_the_project_inside_the_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $other = LinkProject::factory()->for($admin)->create();
        $card = NfcCard::factory()->create([
            'user_id' => $admin->id,
            'link_project_id' => $other->id,
        ]);

        $this->actingAs($admin)->postJson(route('nfc.cards.written', $card), [
            'project_id' => $project->id,
            'written_url' => $project->target_url,
        ])->assertUnprocessable()->assertJsonValidationErrors('project_id');

        $this->assertSame('pending', $card->fresh()->status);
        $this->assertNull($card->fresh()->written_url);
    }
}
