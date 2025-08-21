<?php

namespace Backstage\Announcements;

use Backstage\Announcements\Livewire\AnnouncementsContainer;
use Backstage\Announcements\Resources\Announcements\AnnouncementResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Livewire\Livewire;

class AnnouncementsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'backstage-announcements';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AnnouncementResource::class,
        ]);

        AnnouncementResource::scopeToTenant(false);

        \Backstage\Announcements\Facades\Announcements::register();
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
