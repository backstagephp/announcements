<?php

use Backstage\Announcements\Enums\AnnouncementDisplay;
use Backstage\Announcements\Models\Announcement;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\Register;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\DB;

it('resolves the package translation namespace', function () {
    expect(__('backstage/announcements::backstage-announcements.popup.confirm'))->toBe("I've read this")
        ->and(__('backstage/announcements::backstage-announcements.popup.counter', ['current' => 1, 'total' => 2]))->toBe('1 of 2');
});

it('defaults display to banner and casts to the enum', function () {
    expect((new Announcement)->display)->toBe(AnnouncementDisplay::Banner);
    $a = Announcement::factory()->create();
    expect($a->fresh()->display)->toBe(AnnouncementDisplay::Banner);
});

it('reads legacy rows without display as banner', function () {
    $id = DB::table('announcements')->insertGetId(['title' => 'Old', 'content' => 'x', 'scopes' => json_encode([Dashboard::class]), 'color' => 'info', 'created_at' => now(), 'updated_at' => now()]);
    expect(Announcement::find($id)->display)->toBe(AnnouncementDisplay::Banner);
});

it('decides popup support from the scopes', function () {
    expect(Announcement::scopesSupportPopup([Dashboard::class, Login::class]))->toBeTrue()
        ->and(Announcement::scopesSupportPopup([Login::class, Register::class]))->toBeFalse()
        ->and(Announcement::scopesSupportPopup(['*']))->toBeFalse()
        ->and(Announcement::scopesSupportPopup(['App\\Does\\Not\\Exist']))->toBeFalse()
        ->and(Announcement::scopesSupportPopup([123, null]))->toBeFalse()
        ->and(Announcement::scopesSupportPopup([]))->toBeFalse();
});

it('downgrades a popup with only login-type scopes to banner on save', function () {
    $a = Announcement::factory()->popup()->scopes([Login::class])->create();
    expect($a->fresh()->display)->toBe(AnnouncementDisplay::Banner);
});

it('downgrades a popup with empty scopes (saved as *) to banner', function () {
    $a = Announcement::factory()->popup()->scopes([])->create();
    expect($a->fresh()->scopes)->toBe(['*'])->and($a->fresh()->display)->toBe(AnnouncementDisplay::Banner);
});

it('keeps a popup with a mix of scopes', function () {
    $a = Announcement::factory()->popup()->scopes([Dashboard::class, Login::class])->create();
    expect($a->fresh()->isPopup())->toBeTrue();
});

it('returns pending popups in the given order, skipping dismissed and trashed ones', function () {
    $user = login();
    [$a, $b, $c, $d] = Announcement::factory()->popup()->count(4)->create()->all();
    $b->dismissals()->create(['user_id' => $user->id]);
    $d->delete();
    expect(Announcement::pendingPopupsFor([$c->id, $a->id, $b->id, $d->id], $user->id)->pluck('id')->all())->toBe([$c->id, $a->id])
        ->and(Announcement::pendingPopupsFor([$a->id], null))->toBeEmpty();
});

it('labels the enum cases', function () {
    expect(AnnouncementDisplay::Popup->getLabel())->toBe('Popup with confirmation required')
        ->and(AnnouncementDisplay::Banner->getDescription())->toBe('A coloured bar at the top of the page that users can dismiss.');
});
