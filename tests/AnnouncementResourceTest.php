<?php

use Backstage\Announcements\Models\Announcement;
use Backstage\Announcements\Resources\Announcements\Pages\ListAnnouncements;
use Backstage\Announcements\Resources\Announcements\Pages\ViewAnnouncement;

use function Pest\Livewire\livewire;

beforeEach(fn () => login());

it('shows the display type in the table', function () {
    Announcement::factory()->popup()->create();

    livewire(ListAnnouncements::class)
        ->assertTableColumnExists('display')
        ->assertSee('Popup with confirmation required');
});

it('shows the display type on the view page', function () {
    $announcement = Announcement::factory()->popup()->create();

    livewire(ViewAnnouncement::class, ['record' => $announcement->getRouteKey()])
        ->assertSee('Display as')
        ->assertSee('Popup with confirmation required');
});
