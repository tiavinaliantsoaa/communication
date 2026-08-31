<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;

class AppSubdirectoryPrefixTest extends TestCase
{
    public function test_it_detects_prefix_from_app_url(): void
    {
        $this->app['config']->set('app.url', 'https://www.escm.mg/communication');
        $this->app->instance('request', Request::create('http://127.0.0.1/anything'));

        $this->assertSame('/communication', app_subdirectory_prefix());
    }

    public function test_it_detects_communication_prefix_from_request(): void
    {
        $this->app['config']->set('app.url', 'http://localhost');
        $this->app->instance('request', Request::create('https://www.escm.mg/communication/crm/pipeline'));

        $this->assertSame('/communication', app_subdirectory_prefix());
    }

    public function test_it_is_empty_without_subdirectory(): void
    {
        $this->app['config']->set('app.url', 'http://localhost');
        $this->app->instance('request', Request::create('http://127.0.0.1:8000/crm/pipeline'));

        $this->assertSame('', app_subdirectory_prefix());
    }

    public function test_livewire_scripts_prefix_update_endpoint_under_subdirectory(): void
    {
        $this->app['config']->set('app.url', 'https://www.escm.mg/communication');
        $this->app->instance('request', Request::create('https://www.escm.mg/communication/crm/pipeline'));
        $this->app['config']->set('app.debug', false);

        $html = livewire_frontend_scripts();

        // L'endpoint POST doit viser le sous-dossier, jamais la racine du domaine.
        $this->assertStringContainsString('data-update-uri="/communication/livewire/update"', $html);
        $this->assertStringNotContainsString('data-update-uri="/livewire/update"', $html);
    }

    public function test_livewire_js_is_served_as_static_asset(): void
    {
        // Les assets publiés (public/vendor/livewire) doivent être servis en statique
        // par Apache, pas via PHP (route /livewire/livewire.min.js) qui se coupe sur OVH.
        $this->assertFileExists(public_path('vendor/livewire/livewire.min.js'));
        $this->assertFileExists(public_path('vendor/livewire/manifest.json'));

        $this->app['config']->set('app.debug', false);
        $html = livewire_frontend_scripts();

        $this->assertStringContainsString('/vendor/livewire/livewire.min.js', $html);
        $this->assertStringNotContainsString('src="/livewire/livewire.min.js', $html);
    }
}
