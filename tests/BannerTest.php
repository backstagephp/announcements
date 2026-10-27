<?php

use Backstage\Announcements\Livewire\Announcement as LivewireAnnouncement;
use Backstage\Announcements\Models\Announcement;
use Filament\Auth\Pages\Login;
use Filament\Pages\Dashboard;

use function Pest\Livewire\livewire;

it('renders a banner with title, content and dismiss button for a logged-in user', function () {
    login();
    $a = Announcement::factory()->create(['title' => 'Maintenance', 'content' => 'Tonight']);
    livewire(LivewireAnnouncement::class, ['announcement' => $a, 'scope' => Dashboard::class])
        ->assertSee('Maintenance')->assertSee('Tonight')
        ->assertSeeHtml('wire:click="markAsRead"');
});

it('hides the dismiss button for guests', function () {
    $a = Announcement::factory()->create();
    livewire(LivewireAnnouncement::class, ['announcement' => $a, 'scope' => Login::class])
        ->assertSee($a->title)->assertDontSeeHtml('wire:click="markAsRead"');
});

it('stops rendering a banner once dismissed', function () {
    $user = login();
    $a = Announcement::factory()->create();
    livewire(LivewireAnnouncement::class, ['announcement' => $a, 'scope' => Dashboard::class])
        ->call('markAsRead')
        ->assertDontSee($a->title);
    expect($a->dismissals()->where('user_id', $user->id)->count())->toBe(1);
});
