<?php

namespace Tests\Feature\Models;

use App\Models\LinkProject;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LinkProjectTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_url_hash_is_filled_with_the_sha256_of_the_target_url_on_save(): void
    {
        $project = LinkProject::factory()->create();

        $this->assertSame(hash('sha256', $project->target_url), $project->fresh()->url_hash);
    }

    public function test_url_hash_follows_the_target_url_when_it_changes(): void
    {
        $project = LinkProject::factory()->create();

        $project->update(['target_url' => 'https://search.google.com/local/writereview?placeid=YeniYerKimligi123']);

        $this->assertSame(hash('sha256', $project->target_url), $project->fresh()->url_hash);
    }

    public function test_url_hash_is_not_mass_assignable(): void
    {
        $project = LinkProject::factory()->create();

        $this->assertFalse($project->isFillable('url_hash'));
    }

    public function test_the_same_user_type_and_url_cannot_be_saved_twice(): void
    {
        $existing = LinkProject::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        LinkProject::factory()->for($existing->user)->create(['target_url' => $existing->target_url]);
    }

    public function test_another_user_can_save_the_same_url(): void
    {
        $existing = LinkProject::factory()->create();

        LinkProject::factory()->for(User::factory())->create(['target_url' => $existing->target_url]);

        $this->assertDatabaseCount('link_projects', 2);
    }

    public function test_a_different_type_with_the_same_url_is_a_separate_record(): void
    {
        $existing = LinkProject::factory()->create();

        LinkProject::factory()->instagramFollow()->for($existing->user)->create(['target_url' => $existing->target_url]);

        $this->assertDatabaseCount('link_projects', 2);
    }
}
