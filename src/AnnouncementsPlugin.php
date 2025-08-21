<?php

namespace Backstage\Announcements;

use Filament\Panel;
use Livewire\Livewire;
use Filament\Contracts\Plugin;
use Filament\View\PanelsRenderHook;
use Backstage\Announcements\Collections\ScopeCollection;
use Backstage\Announcements\Livewire\AnnouncementsContainer;
use Backstage\Announcements\Resources\Announcements\AnnouncementResource;

class AnnouncementsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'backstage-announcements';
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(PanelsRenderHook::CONTENT_START, function (array $scopes) {
            return Livewire::mount(AnnouncementsContainer::class, ['scopes' => $scopes]);
        });

        $panel->resources([
            AnnouncementResource::class
        ]);

        AnnouncementResource::scopeToTenant(false);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
