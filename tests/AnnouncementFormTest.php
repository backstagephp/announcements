<?php

use Backstage\Announcements\Enums\AnnouncementDisplay;
use Backstage\Announcements\Livewire\AnnouncementPopups;
use Backstage\Announcements\Models\Announcement;
use Backstage\Announcements\Resources\Announcements\Pages\CreateAnnouncement;
use Backstage\Announcements\Resources\Announcements\Pages\EditAnnouncement;
use Filament\Auth\Pages\Login;
use Filament\Forms\Components\Radio;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(fn () => login());

// Filament 5 stores helper text as a "below content" child schema with one Text component.
function helperTextOf(Radio $field): ?string
{
    $text = $field->getChildSchema($field::BELOW_CONTENT_SCHEMA_KEY)?->getComponents()[0] ?? null;

    return $text?->getContent();
}

it('disables the popup option when only login-type pages are selected', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['scopes' => [Login::class]])
        ->assertFormFieldExists('display', fn (Radio $f) => $f->isOptionDisabled('popup', AnnouncementDisplay::Popup->getLabel())
            && helperTextOf($f) === 'Select at least one page other than login or registration.')
        ->fillForm(['scopes' => [Login::class, Dashboard::class]])
        ->assertFormFieldExists('display', fn (Radio $f) => ! $f->isOptionDisabled('popup', AnnouncementDisplay::Popup->getLabel()));
});

it('shows the fallback hint only when popup is selected', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['scopes' => [Dashboard::class], 'display' => 'popup'])
        ->assertFormFieldExists('display', fn (Radio $f) => helperTextOf($f) === 'Login and registration pages always show a banner.')
        ->fillForm(['display' => 'banner'])
        ->assertFormFieldExists('display', fn (Radio $f) => blank(helperTextOf($f)));
});

it('resets display to banner when the last non-login scope is removed', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['scopes' => [Dashboard::class, Login::class], 'display' => 'popup'])
        ->fillForm(['scopes' => [Login::class]])
        ->assertFormSet(['display' => AnnouncementDisplay::Banner]);
});

it('creates a popup announcement with mixed scopes', function () {
    livewire(CreateAnnouncement::class)
        ->fillForm(['title' => 'Terms', 'content' => 'Read me', 'scopes' => [Dashboard::class, Login::class], 'color' => 'info', 'display' => 'popup'])
        ->call('create')->assertHasNoFormErrors();
    expect(Announcement::sole()->display)->toBe(AnnouncementDisplay::Popup);
});

it('opens a legacy row as banner and keeps it a banner on save', function () {
    $id = DB::table('announcements')->insertGetId(['title' => 'Old', 'content' => 'x', 'scopes' => json_encode([Dashboard::class]), 'color' => 'info', 'created_at' => now(), 'updated_at' => now()]);
    livewire(EditAnnouncement::class, ['record' => $id])
        ->assertFormSet(['display' => AnnouncementDisplay::Banner])
        ->call('save')->assertHasNoFormErrors();
    expect(Announcement::find($id)->display)->toBe(AnnouncementDisplay::Banner);
});

it('shows a confirmed popup again after Reset dismissals', function () {
    $a = Announcement::factory()->popup()->create(['title' => 'Again']);
    $a->dismissals()->create(['user_id' => auth()->id()]);
    livewire(EditAnnouncement::class, ['record' => $a->getRouteKey()])->callAction('reset-dismissals');
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])->assertSee('Again');
});
