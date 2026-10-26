<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\LinkProjects\Pages\EditLinkProject;
use App\Filament\Resources\NfcCards\Pages\CreateNfcCard;
use App\Filament\Resources\NfcCards\Pages\EditNfcCard;
use App\Filament\Resources\NfcCards\NfcCardResource;
use App\Filament\Resources\NfcCards\Pages\ListNfcCards;
use App\Models\Business;
use App\Models\LinkProject;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NfcInventoryFormsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_business_from_filament_form(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateBusiness::class)
            ->fillForm(['name' => 'İzmir Kadın Kuaförü'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('businesses', [
            'user_id' => $admin->id,
            'name' => 'İzmir Kadın Kuaförü',
        ]);
    }

    public function test_admin_can_create_card_for_own_business_and_link(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $link = LinkProject::factory()->for($admin)->create();
        $this->actingAs($admin);

        Livewire::test(CreateNfcCard::class)
            ->fillForm([
                'label' => 'Test NFC 01',
                'business_id' => $business->id,
                'link_project_id' => $link->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('nfc_cards', [
            'user_id' => $admin->id,
            'business_id' => $business->id,
            'link_project_id' => $link->id,
            'label' => 'Test NFC 01',
            'status' => 'pending',
        ]);
    }

    public function test_card_creation_rejects_business_owned_by_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();
        $this->actingAs($admin);

        Livewire::test(CreateNfcCard::class)
            ->fillForm([
                'label' => 'Başkasının İşletmesi',
                'business_id' => $otherBusiness->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['business_id']);

        $this->assertDatabaseMissing('nfc_cards', [
            'user_id' => $admin->id,
            'business_id' => $otherBusiness->id,
        ]);
    }

    public function test_card_creation_rejects_link_owned_by_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $otherLink = LinkProject::factory()->for($otherAdmin)->create();
        $this->actingAs($admin);

        Livewire::test(CreateNfcCard::class)
            ->fillForm([
                'label' => 'Başkasının Bağlantısı',
                'business_id' => $business->id,
                'link_project_id' => $otherLink->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['link_project_id']);

        $this->assertDatabaseMissing('nfc_cards', [
            'user_id' => $admin->id,
            'link_project_id' => $otherLink->id,
        ]);
    }

    public function test_another_admin_cannot_open_business_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();

        $this->actingAs($admin)
            ->get('/admin/businesses/'.$otherBusiness->id.'/edit')
            ->assertNotFound();
    }

    public function test_another_admin_cannot_open_card_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();
        $otherCard = NfcCard::factory()->for($otherAdmin)->for($otherBusiness)->create();

        $this->actingAs($admin)
            ->get('/admin/nfc-cards/'.$otherCard->id.'/edit')
            ->assertNotFound();
    }

    public function test_cannot_change_destination_of_a_link_already_written_to_a_card(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $link = LinkProject::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create([
            'link_project_id' => $link->id,
            'status' => 'written',
            'written_at' => now(),
            'written_url' => $link->target_url,
        ]);
        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $link->id])
            ->fillForm([
                'name' => 'Değişen hedef',
                'type' => 'google_review',
                'target_url' => 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4',
            ])
            ->call('save')
            ->assertHasFormErrors(['target_url']);

        $this->assertSame($link->target_url, $link->fresh()->target_url);
    }

    public function test_owner_reset_returns_written_card_to_pending_and_clears_write_state(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $card = NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'verified',
            'tag_uid' => 'ABCD1234',
            'written_url' => 'https://example.test/a',
            'written_at' => now(),
            'verified_at' => now(),
        ]);
        $this->actingAs($admin);

        Livewire::test(ListNfcCards::class)
            ->callTableAction('reset', $card);

        $card->refresh();
        $this->assertSame('pending', $card->status);
        $this->assertNull($card->written_url);
        $this->assertNull($card->written_at);
        $this->assertNull($card->verified_at);
        $this->assertNull($card->delivered_at);
        $this->assertNull($card->tag_uid);
    }

    public function test_reset_action_is_hidden_for_delivered_and_pending_cards(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $delivered = NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $pending = NfcCard::factory()->for($admin)->for($business)->create();
        $this->actingAs($admin);

        Livewire::test(ListNfcCards::class)
            ->assertTableActionHidden('reset', $delivered)
            ->assertTableActionHidden('reset', $pending);
    }

    public function test_other_admin_cards_are_not_listed_and_their_actions_are_not_available(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherBusiness = Business::factory()->for($otherAdmin)->create();
        $otherCard = NfcCard::factory()->for($otherAdmin)->for($otherBusiness)->create(['status' => 'written']);
        $this->actingAs($admin);

        Livewire::test(ListNfcCards::class)
            ->assertCanNotSeeTableRecords([$otherCard]);

        $this->assertFalse(NfcCardResource::canReset($otherCard));
        $this->assertFalse(NfcCardResource::canMarkFailed($otherCard));
    }

    public function test_verified_card_can_be_marked_delivered_and_written_card_can_be_marked_failed(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $verified = NfcCard::factory()->for($admin)->for($business)->create(['status' => 'verified', 'verified_at' => now()]);
        $written = NfcCard::factory()->for($admin)->for($business)->create(['status' => 'written', 'written_at' => now()]);
        $this->actingAs($admin);

        Livewire::test(ListNfcCards::class)
            ->assertTableActionVisible('markDelivered', $verified)
            ->assertTableActionHidden('markDelivered', $written)
            ->callTableAction('markDelivered', $verified)
            ->assertTableActionHidden('markFailed', $verified)
            ->callTableAction('markFailed', $written);

        $this->assertSame('delivered', $verified->fresh()->status);
        $this->assertNotNull($verified->fresh()->delivered_at);
        $this->assertSame('failed', $written->fresh()->status);
    }

    public function test_failed_card_can_be_reset_by_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $card = NfcCard::factory()->for($admin)->for($business)->create([
            'status' => 'written',
            'written_at' => now(),
        ]);
        $this->actingAs($admin);

        Livewire::test(ListNfcCards::class)->callTableAction('markFailed', $card);
        $this->assertSame('failed', $card->fresh()->status);

        Livewire::test(ListNfcCards::class)->callTableAction('reset', $card);
        $this->assertSame('pending', $card->fresh()->status);
        $this->assertNull($card->fresh()->written_at);
    }

    public function test_tag_uid_must_be_unique_per_user_but_blank_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $own = NfcCard::factory()->for($admin)->for($business)->create(['tag_uid' => 'UID-1']);
        NfcCard::factory()->for($otherAdmin)->for(Business::factory()->for($otherAdmin))->create(['tag_uid' => 'UID-2']);
        $this->actingAs($admin);

        Livewire::test(CreateNfcCard::class)
            ->fillForm(['label' => 'Yinelenen', 'business_id' => $business->id, 'tag_uid' => 'UID-1'])
            ->call('create')
            ->assertHasFormErrors(['tag_uid']);

        // Başka kullanıcının UID'si aynı kullanıcı için çakışma sayılmaz.
        Livewire::test(CreateNfcCard::class)
            ->fillForm(['label' => 'Başkasının UID', 'business_id' => $business->id, 'tag_uid' => 'UID-2'])
            ->call('create')
            ->assertHasNoFormErrors();

        // Kendi kaydını aynı UID ile güncellemek çakışma değildir.
        Livewire::test(EditNfcCard::class, ['record' => $own->id])
            ->fillForm(['label' => $own->label, 'business_id' => $business->id, 'tag_uid' => 'UID-1'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Boş UID birden fazla kartta serbesttir.
        Livewire::test(CreateNfcCard::class)
            ->fillForm(['label' => 'Boş UID 1', 'business_id' => $business->id, 'tag_uid' => ''])
            ->call('create')
            ->assertHasNoFormErrors();
        Livewire::test(CreateNfcCard::class)
            ->fillForm(['label' => 'Boş UID 2', 'business_id' => $business->id, 'tag_uid' => ''])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_written_link_error_points_to_reset_action_and_hint_is_shown_when_delete_blocked(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $link = LinkProject::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create([
            'link_project_id' => $link->id,
            'status' => 'written',
            'written_at' => now(),
            'written_url' => $link->target_url,
        ]);
        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $link->id])
            ->assertActionVisible('silmeIpucu')
            ->assertActionHidden('delete')
            ->fillForm([
                'name' => $link->name,
                'type' => 'google_review',
                'target_url' => 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4',
            ])
            ->call('save')
            ->assertHasFormErrors(['target_url'])
            ->tap(function ($component): void {
                $this->assertStringContainsString('Yeniden hazırla', (string) $component->errors()->first('data.target_url'));
            });
    }

    public function test_unused_link_project_and_business_show_delete_action_without_hint(): void
    {
        $admin = User::factory()->admin()->create();
        $link = LinkProject::factory()->for($admin)->create();
        $business = Business::factory()->for($admin)->create();
        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $link->id])
            ->assertActionVisible('delete')
            ->assertActionHidden('silmeIpucu');

        Livewire::test(EditBusiness::class, ['record' => $business->id])
            ->assertActionVisible('delete')
            ->assertActionHidden('silmeIpucu');
    }

    public function test_business_with_card_shows_hint_instead_of_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create();
        $this->actingAs($admin);

        Livewire::test(EditBusiness::class, ['record' => $business->id])
            ->assertActionDisabled('delete')
            ->assertActionVisible('silmeIpucu');
    }

    public function test_linked_link_project_and_business_cannot_be_deleted_from_edit_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $business = Business::factory()->for($admin)->create();
        $link = LinkProject::factory()->for($admin)->create();
        NfcCard::factory()->for($admin)->for($business)->create(['link_project_id' => $link->id]);
        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $link->id])
            ->assertActionHidden('delete');
        Livewire::test(EditBusiness::class, ['record' => $business->id])
            ->assertActionHidden('delete');

        $this->assertDatabaseHas('link_projects', ['id' => $link->id]);
        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_unlinked_link_project_can_be_deleted_from_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $link = LinkProject::factory()->for($admin)->create();
        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $link->id])
            ->assertActionVisible('delete')
            ->callAction('delete');

        $this->assertDatabaseMissing('link_projects', ['id' => $link->id]);
    }
}
