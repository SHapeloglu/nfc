<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\QrDesigns;
use App\Models\QrDesign;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class QrDesignsPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_can_access_is_false_for_guest_and_non_admin_and_true_for_admin(): void
    {
        $this->assertFalse(QrDesigns::canAccess());

        $this->actingAs(User::factory()->create());
        $this->assertFalse(QrDesigns::canAccess());

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(QrDesigns::canAccess());
    }

    public function test_get_designs_returns_only_the_signed_in_admins_designs(): void
    {
        $admin = User::factory()->admin()->create();
        $own = QrDesign::factory()->for($admin)->create();
        QrDesign::factory()->for(User::factory()->admin())->create();

        $this->actingAs($admin);

        $designs = Livewire::test(QrDesigns::class)->instance()->getDesigns();

        $this->assertSame([$own->id], $designs->pluck('id')->all());
    }

    public function test_get_designs_returns_at_most_100_ordered_by_updated_at_descending(): void
    {
        $admin = User::factory()->admin()->create();
        $base = now()->startOfDay();

        QrDesign::factory()->for($admin)->count(101)->sequence(
            fn ($sequence) => ['updated_at' => $base->copy()->addMinutes($sequence->index)]
        )->create();

        $this->actingAs($admin);

        $designs = Livewire::test(QrDesigns::class)->instance()->getDesigns();

        $this->assertCount(100, $designs);
        $timestamps = $designs->pluck('updated_at')->map->getTimestamp()->all();
        $sorted = $timestamps;
        rsort($sorted);
        $this->assertSame($sorted, $timestamps);
        $this->assertSame($base->copy()->addMinutes(100)->getTimestamp(), $timestamps[0]);
        $this->assertSame($base->copy()->addMinutes(1)->getTimestamp(), $timestamps[99]);
    }

    public function test_get_designs_aborts_with_403_for_a_non_admin(): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(HttpException::class);

        (new QrDesigns)->getDesigns();
    }
}
