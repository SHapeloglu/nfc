<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\NfcCards\NfcCardResource;
use App\Models\Business;
use App\Models\LinkProject;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NfcInventoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_cannot_open_business_or_card_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/businesses')->assertForbidden();
        $this->actingAs($user)->get('/admin/nfc-cards')->assertForbidden();
    }

    public function test_admin_can_open_business_and_card_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/businesses')->assertOk();
        $this->actingAs($admin)->get('/admin/nfc-cards')->assertOk();
    }

    public function test_business_resource_is_scoped_to_authenticated_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $own = Business::factory()->for($admin)->create();
        $other = Business::factory()->for(User::factory()->admin())->create();

        $this->actingAs($admin);

        $this->assertSame([$own->id], BusinessResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(BusinessResource::canEdit($own));
        $this->assertFalse(BusinessResource::canEdit($other));
    }

    public function test_nfc_card_resource_is_scoped_to_authenticated_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $ownBusiness = Business::factory()->for($admin)->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();
        $own = NfcCard::factory()->for($admin)->for($ownBusiness)->create();
        $other = NfcCard::factory()->for($otherAdmin)->for($otherBusiness)->create();

        $this->actingAs($admin);

        $this->assertSame([$own->id], NfcCardResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(NfcCardResource::canEdit($own));
        $this->assertFalse(NfcCardResource::canEdit($other));
    }

    public function test_card_tracks_business_link_and_verification_timestamps(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $project = LinkProject::factory()->for($admin)->create();
        $verifiedAt = now()->startOfSecond();

        $card = NfcCard::factory()->for($admin)->for($business)->create([
            'link_project_id' => $project->id,
            'status' => 'verified',
            'written_url' => $project->target_url,
            'verified_at' => $verifiedAt,
        ]);

        $this->assertSame($business->id, $card->business->id);
        $this->assertSame($project->id, $card->linkProject->id);
        $this->assertSame('verified', $card->status);
        $this->assertSame($verifiedAt->toDateTimeString(), $card->verified_at->toDateTimeString());
    }

    public function test_default_card_factory_uses_the_same_owner_for_card_and_business(): void
    {
        $card = NfcCard::factory()->create();

        $this->assertSame($card->user_id, $card->business->user_id);
    }

    public function test_written_or_delivered_cards_cannot_be_deleted_from_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();

        $this->actingAs($admin);

        $pendingCard = NfcCard::factory()->for($admin)->for($business)->create();
        $writtenCard = NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'written',
            'written_at' => now(),
        ]);
        $deliveredCard = NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->assertTrue(NfcCardResource::canDelete($pendingCard));
        $this->assertFalse(NfcCardResource::canDelete($writtenCard));
        $this->assertFalse(NfcCardResource::canDelete($deliveredCard));
    }
}
