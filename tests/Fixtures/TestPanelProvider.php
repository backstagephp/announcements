<?php

namespace Backstage\Announcements\Tests\Fixtures;

use Backstage\Announcements\AnnouncementsPlugin;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->login()
            ->registration()
            ->pages([Dashboard::class])
            ->plugin(AnnouncementsPlugin::make());
    }
}
