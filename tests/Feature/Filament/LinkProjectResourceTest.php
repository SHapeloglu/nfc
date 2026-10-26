<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Filament\Resources\LinkProjects\Pages\CreateLinkProject;
use App\Filament\Resources\LinkProjects\Pages\EditLinkProject;
use App\Filament\Resources\LinkProjects\Pages\ListLinkProjects;
use App\Models\LinkProject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LinkProjectResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const GOOGLE_URL = 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4';

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_non_admin_cannot_open_list_create_or_edit_pages(): void
    {
        $user = User::factory()->create();
        $project = LinkProject::factory()->for($user)->create();

        $this->actingAs($user)->get(LinkProjectResource::getUrl('index'))->assertForbidden();
        $this->actingAs($user)->get(LinkProjectResource::getUrl('create'))->assertForbidden();
        $this->actingAs($user)->get(LinkProjectResource::getUrl('edit', ['record' => $project]))->assertForbidden();
    }

    public function test_admin_sees_only_their_own_records_in_the_table(): void
    {
        $admin = User::factory()->admin()->create();
        $own = LinkProject::factory()->for($admin)->create();
        $foreign = LinkProject::factory()->for(User::factory()->admin())->create();

        $this->actingAs($admin);

        Livewire::test(ListLinkProjects::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_create_stores_a_google_review_link_owned_by_the_signed_in_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateLinkProject::class)
            ->fillForm([
                'name' => 'Olap Teknoloji',
                'type' => 'google_review',
                'target_url' => self::GOOGLE_URL,
                'user_id' => $other->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = LinkProject::where('name', 'Olap Teknoloji')->sole();
        $this->assertSame($admin->id, $project->user_id);
        $this->assertSame(self::GOOGLE_URL, $project->target_url);
        $this->assertSame('google_review', $project->type);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidGoogleUrls(): array
    {
        return [
            'http scheme' => ['google_review', 'http://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4'],
            'wrong host' => ['google_review', 'https://evil.example.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4'],
            'missing placeid' => ['google_review', 'https://search.google.com/local/writereview'],
            'wrong path' => ['google_review', 'https://search.google.com/maps?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4'],
            'instagram without xtok' => ['instagram_follow', 'https://www.instagram.com/ornek.hesap'],
            'instagram wrong host' => ['instagram_follow', 'https://evil.example.com/ornek.hesap?xtok=abcdEFGH1234'],
        ];
    }

    #[DataProvider('invalidGoogleUrls')]
    public function test_create_rejects_an_invalid_target_url(string $type, string $url): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateLinkProject::class)
            ->fillForm(['name' => 'Hatalı', 'type' => $type, 'target_url' => $url])
            ->call('create')
            ->assertHasFormErrors(['target_url']);

        $this->assertDatabaseCount('link_projects', 0);
    }

    public function test_create_stores_an_instagram_link_with_a_valid_url(): void
    {
        $admin = User::factory()->admin()->create();
        $url = 'https://www.instagram.com/ornek.hesap?xtok=abcdEFGH1234';

        $this->actingAs($admin);

        Livewire::test(CreateLinkProject::class)
            ->fillForm(['name' => 'Instagram', 'type' => 'instagram_follow', 'target_url' => $url])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = LinkProject::where('name', 'Instagram')->sole();
        $this->assertSame($admin->id, $project->user_id);
        $this->assertSame('instagram_follow', $project->type);
        $this->assertSame($url, $project->target_url);
    }

    public function test_admin_can_edit_their_own_record(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create(['name' => 'Eski Ad']);

        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['name' => 'Yeni Ad'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Yeni Ad', $project->fresh()->name);
    }

    public function test_admin_cannot_open_the_edit_page_of_someone_elses_record(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = LinkProject::factory()->for(User::factory()->admin())->create(['name' => 'Yabancı']);

        $this->actingAs($admin)
            ->get(LinkProjectResource::getUrl('edit', ['record' => $foreign]))
            ->assertNotFound();

        $this->assertSame('Yabancı', $foreign->fresh()->name);
    }

    public function test_edit_rejects_an_invalid_target_url(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->create();
        $original = $project->target_url;

        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['target_url' => 'https://evil.example.com/x'])
            ->call('save')
            ->assertHasFormErrors(['target_url']);

        $this->assertSame($original, $project->fresh()->target_url);
    }

    public function test_create_rejects_a_duplicate_link_with_a_form_error_instead_of_a_server_error(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for($admin)->googleReview()->create(['target_url' => self::GOOGLE_URL]);

        $this->actingAs($admin);

        Livewire::test(CreateLinkProject::class)
            ->fillForm(['name' => 'Tekrar', 'type' => 'google_review', 'target_url' => self::GOOGLE_URL])
            ->call('create')
            ->assertHasFormErrors(['target_url' => 'unique']);

        $this->assertDatabaseCount('link_projects', 1);
    }

    public function test_create_allows_the_same_link_that_another_admin_already_saved(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for(User::factory()->admin())->googleReview()->create(['target_url' => self::GOOGLE_URL]);

        $this->actingAs($admin);

        Livewire::test(CreateLinkProject::class)
            ->fillForm(['name' => 'Benim', 'type' => 'google_review', 'target_url' => self::GOOGLE_URL])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('link_projects', 2);
    }

    public function test_edit_rejects_changing_the_url_to_one_that_is_already_saved(): void
    {
        $admin = User::factory()->admin()->create();
        LinkProject::factory()->for($admin)->googleReview()->create(['target_url' => self::GOOGLE_URL]);
        $other = LinkProject::factory()->for($admin)->googleReview()->create();

        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $other->getRouteKey()])
            ->fillForm(['target_url' => self::GOOGLE_URL])
            ->call('save')
            ->assertHasFormErrors(['target_url' => 'unique']);
    }

    public function test_edit_keeps_working_when_the_url_is_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $project = LinkProject::factory()->for($admin)->googleReview()->create(['name' => 'Eski']);

        $this->actingAs($admin);

        Livewire::test(EditLinkProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['name' => 'Yeni'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Yeni', $project->fresh()->name);
    }
}
