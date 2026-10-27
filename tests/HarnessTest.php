<?php

use Backstage\Announcements\Models\Announcement;
use Backstage\Announcements\Resources\Announcements\AnnouncementResource;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Schema;

it('migrates the package tables and builds announcements', function () {
    $a = Announcement::factory()->create();
    expect($a->scopes)->toBe([Dashboard::class])
        ->and(Schema::hasTable('announcement_dismissals'))->toBeTrue();
});

it('registers the announcement resource on the test panel', function () {
    expect(Filament::getPanel('admin')->getResources())->toContain(AnnouncementResource::class);
});
