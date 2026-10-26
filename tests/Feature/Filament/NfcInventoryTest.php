<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\NfcCards\NfcCardResource;
use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Models\Business;
use App\Models\LinkProject;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_written_verified_and_delivered_cards_cannot_be_reassigned_in_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $this->actingAs($admin);

        foreach (['written', 'verified', 'delivered'] as $status) {
            $card = NfcCard::factory()->for($admin)->for($business)->create(['status' => $status]);

            $this->assertFalse(NfcCardResource::canEdit($card), $status.' card must be immutable');
        }

        $this->assertFalse(NfcCardResource::canEdit(NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'failed',
            'written_at' => now(),
        ])));
    }

    public function test_link_project_assigned_to_a_card_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $assignedProject = LinkProject::factory()->for($admin)->create();
        $unusedProject = LinkProject::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create([
            'link_project_id' => $assignedProject->id,
            'status' => 'written',
        ]);

        $this->actingAs($admin);

        $this->assertFalse(LinkProjectResource::canDelete($assignedProject));
        $this->assertTrue(LinkProjectResource::canDelete($unusedProject));
    }

    public function test_only_owner_can_reset_written_verified_or_failed_cards(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();

        $this->actingAs($admin);

        foreach (['written', 'verified', 'failed'] as $status) {
            $own = NfcCard::factory()->for($admin)->for($business)->create(['status' => $status]);
            $other = NfcCard::factory()->for($otherAdmin)->for($otherBusiness)->create(['status' => $status]);

            $this->assertTrue(NfcCardResource::canReset($own), $status.' owned card must be resettable');
            $this->assertFalse(NfcCardResource::canReset($other), $status.' foreign card must not be resettable');
        }
    }

    public function test_pending_and_delivered_cards_cannot_be_reset(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $this->actingAs($admin);

        $pending = NfcCard::factory()->for($admin)->for($business)->create();
        $delivered = NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'delivered',
            'written_at' => now(),
            'verified_at' => now(),
            'delivered_at' => now(),
        ]);

        $this->assertFalse(NfcCardResource::canReset($pending));
        $this->assertFalse(NfcCardResource::canReset($delivered));
    }

    public function test_delivery_and_failure_transitions_respect_status_and_ownership(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();
        $this->actingAs($admin);

        $verified = NfcCard::factory()->for($admin)->for($business)->create(['status' => 'verified']);
        $written = NfcCard::factory()->for($admin)->for($business)->create(['status' => 'written']);
        $pending = NfcCard::factory()->for($admin)->for($business)->create();
        $foreignVerified = NfcCard::factory()->for($otherAdmin)->for($otherBusiness)->create(['status' => 'verified']);

        $this->assertTrue(NfcCardResource::canMarkDelivered($verified));
        $this->assertFalse(NfcCardResource::canMarkDelivered($written));
        $this->assertFalse(NfcCardResource::canMarkDelivered($pending));
        $this->assertFalse(NfcCardResource::canMarkDelivered($foreignVerified));

        $this->assertTrue(NfcCardResource::canMarkFailed($pending));
        $this->assertTrue(NfcCardResource::canMarkFailed($written));
        $this->assertFalse(NfcCardResource::canMarkFailed($verified));
        $this->assertFalse(NfcCardResource::canMarkFailed($foreignVerified));
    }

    public function test_business_with_cards_cannot_be_deleted_but_empty_one_can(): void
    {
        $admin = User::factory()->admin()->create();
        $usedBusiness = Business::factory()->for($admin)->create();
        $emptyBusiness = Business::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($usedBusiness)->create();
        $this->actingAs($admin);

        $this->assertFalse(BusinessResource::canDelete($usedBusiness));
        $this->assertTrue(BusinessResource::canDelete($emptyBusiness));
    }

    public function test_delete_checks_use_preloaded_flag_without_extra_queries(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $withCards = LinkProject::factory()->for($admin)->create();
        $withoutCards = LinkProject::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create(['link_project_id' => $withCards->id]);
        $this->actingAs($admin);

        $projects = LinkProjectResource::getEloquentQuery()->orderBy('id')->get();
        DB::enableQueryLog();
        $results = $projects->mapWithKeys(fn (LinkProject $project): array => [
            $project->id => LinkProjectResource::canDelete($project),
        ])->all();
        $this->assertCount(0, DB::getQueryLog(), 'canDelete must not query when has_cards is preloaded');
        DB::disableQueryLog();

        $this->assertFalse($results[$withCards->id]);
        $this->assertTrue($results[$withoutCards->id]);

        $businesses = BusinessResource::getEloquentQuery()->get();
        DB::enableQueryLog();
        foreach ($businesses as $record) {
            BusinessResource::canDelete($record);
        }
        $this->assertCount(0, DB::getQueryLog(), 'business canDelete must not query when has_cards is preloaded');
        DB::disableQueryLog();
    }

    public function test_delete_checks_fall_back_to_query_when_flag_is_missing(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $project = LinkProject::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create(['link_project_id' => $project->id]);
        $this->actingAs($admin);

        $fresh = LinkProject::query()->findOrFail($project->id);
        $this->assertFalse(LinkProjectResource::canDelete($fresh));
    }
}
