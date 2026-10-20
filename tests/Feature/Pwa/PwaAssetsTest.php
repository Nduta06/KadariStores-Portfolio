<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Static files under public/ are served directly by the web server
     * before Laravel's router ever sees the request, so these are checked
     * on disk rather than through the HTTP test client.
     */
    public function test_manifest_has_the_expected_fields(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertEquals('GrainLedger Demo', $manifest['name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_and_offline_page_exist(): void
    {
        $this->assertFileExists(public_path('service-worker.js'));
        $this->assertFileExists(public_path('offline.html'));
    }

    public function test_authenticated_pages_link_the_manifest_and_icons(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('manifest.json', false)
            ->assertSee('apple-touch-icon.png', false);
    }

    public function test_guest_pages_link_the_manifest_too(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('manifest.json', false);
    }
}
