<?php

use Backstage\Announcements\Announcements;
use Backstage\Announcements\Models\Announcement;
use Filament\Auth\Pages\Login;
use Filament\Pages\Dashboard;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

function hook(string $name, string $scope): string
{
    return FilamentView::renderHook($name, scopes: $scope)->toHtml();
}

it('shows a popup on Page scopes and a banner on SimplePage scopes, never both', function () {
    login();
    Announcement::factory()->popup()->scopes([Dashboard::class, Login::class])->create(['title' => 'Terms']);
    app(Announcements::class)->registerHooks();

    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toContain('Terms')->toContain('role="dialog"')
        ->and(hook(PanelsRenderHook::CONTENT_START, Dashboard::class))->not->toContain('Terms')
        ->and(hook(PanelsRenderHook::SIMPLE_PAGE_START, Login::class))->toContain('Terms')->not->toContain('role="dialog"');
});

it('mounts one popup component per page, oldest announcement first', function () {
    login();
    Announcement::factory()->popup()->create(['title' => 'Newer', 'created_at' => now()]);
    Announcement::factory()->popup()->create(['title' => 'Older', 'created_at' => now()->subDay()]);
    app(Announcements::class)->registerHooks();

    $html = hook(PanelsRenderHook::BODY_END, Dashboard::class);
    expect(substr_count($html, 'role="dialog"'))->toBe(1)
        ->and($html)->toContain('Older')->toContain('1 of 2')->not->toContain('Newer');
});

it('keeps banners on CONTENT_START for banner announcements', function () {
    login();
    Announcement::factory()->create(['title' => 'Plain banner']);
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::CONTENT_START, Dashboard::class))->toContain('Plain banner')
        ->and(hook(PanelsRenderHook::BODY_END, Dashboard::class))->not->toContain('Plain banner');
});

it('renders nothing for inactive announcements', function () {
    login();
    Announcement::factory()->popup()->create(['title' => 'Expired', 'end_date' => now()->subDay()]);
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->not->toContain('Expired');
});

it('renders no popup component for guests or when everything is confirmed', function () {
    $a = Announcement::factory()->popup()->create();
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toBe('');

    $user = login();
    $a->dismissals()->create(['user_id' => $user->id]);
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toBe('');
});

it('ignores scope classes that no longer exist', function () {
    login();
    Announcement::factory()->popup()->scopes([Dashboard::class, 'App\\Filament\\Pages\\Removed'])->create(['title' => 'Survivor']);
    app(Announcements::class)->registerHooks();
    expect(hook(PanelsRenderHook::BODY_END, Dashboard::class))->toContain('Survivor');
});
