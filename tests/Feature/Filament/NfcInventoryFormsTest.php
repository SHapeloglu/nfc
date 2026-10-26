<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\LinkProjects\Pages\EditLinkProject;
use App\Filament\Resources\NfcCards\Pages\CreateNfcCard;
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
}
