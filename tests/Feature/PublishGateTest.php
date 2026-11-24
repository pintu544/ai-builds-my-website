<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\IngestionService;
use App\Services\SiteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishGateTest extends TestCase
{
    use RefreshDatabase;

    private function readyProject(): Project
    {
        $p = Project::create(['business_name' => 'Sharma Bakery', 'city' => 'Mumbai', 'slug' => 'sharma-gate', 'status' => 'draft']);
        $p->facts()->create(['field' => 'name', 'value' => 'Sharma Bakery', 'snippet' => 'Typed by the user', 'confirmed' => true]);
        $p->facts()->create(['field' => 'city', 'value' => 'Mumbai', 'snippet' => 'Typed by the user', 'confirmed' => true]);
        return $p;
    }

    public function test_publish_is_blocked_by_unresolved_conflict(): void
    {
        $p = $this->readyProject();
        $ing = app(IngestionService::class);
        $ing->addManualText($p, 'Call 98200 12345');
        $ing->addManualText($p, 'Call 98200 99999');
        app(SiteGenerator::class)->generate($p);

        $this->post(route('projects.publish', $p))->assertSessionHas('warn');
        $this->assertNull($p->fresh()->published_at);
        $this->get('/s/sharma-gate')->assertNotFound();
    }

    public function test_publish_works_after_resolution_and_public_page_renders(): void
    {
        $p = $this->readyProject();
        $ing = app(IngestionService::class);
        $ing->addManualText($p, 'Call 98200 12345');
        app(SiteGenerator::class)->generate($p);

        $this->post(route('projects.publish', $p))->assertSessionHas('ok');
        $this->assertNotNull($p->fresh()->published_at);
        $this->get('/s/sharma-gate')->assertOk()->assertSee('Sharma Bakery');
    }

    public function test_fallback_generation_uses_only_confirmed_facts(): void
    {
        // phpunit.xml empties the AI key, so generation must take the facts-only fallback.
        $p = $this->readyProject();
        app(IngestionService::class)->addManualText($p, 'Call 98200 12345');
        $result = app(SiteGenerator::class)->generate($p);

        $this->assertSame('fallback', $result['path']);
        $all = $p->sections()->get()->map(fn ($s) => json_encode($s->content))->implode(' ');
        $this->assertStringContainsString('98200 12345', $all);
        $this->assertStringNotContainsString('99999', $all);
    }
}
