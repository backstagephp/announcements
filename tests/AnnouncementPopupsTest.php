<?php

use Backstage\Announcements\Livewire\AnnouncementPopups;
use Backstage\Announcements\Models\Announcement;
use Backstage\Announcements\Models\AnnouncementDismissal;

use function Pest\Livewire\livewire;

it('renders the first pending popup as a dialog with a single confirm button', function () {
    login();
    $a = Announcement::factory()->popup()->create(['title' => 'New terms', 'content' => "Line one\nLine two"]);
    $html = livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->assertSee('New terms')->assertSee("I've read this")
        ->assertSeeHtml('role="dialog"')->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('whitespace-pre-line')->assertSeeHtml('x-trap.noscroll')
        ->assertDontSee('1 of 1')
        ->html();
    expect(substr_count($html, '<button'))->toBe(1);
});

it('escapes markup in title and content', function () {
    login();
    $a = Announcement::factory()->popup()->create(['title' => '<b>Bold</b>', 'content' => '<script>alert(1)</script>']);
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->assertDontSeeHtml('<script>alert(1)</script>')->assertDontSeeHtml('<b>Bold</b>')
        ->assertSee('<b>Bold</b>');
});

it('confirm creates exactly one dismissal even when called twice', function () {
    $user = login();
    $a = Announcement::factory()->popup()->create();
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->call('confirm', $a->id)->call('confirm', $a->id)
        ->assertDontSee($a->title);
    expect($a->dismissals()->where('user_id', $user->id)->count())->toBe(1);
});

it('walks through several popups with a counter', function () {
    login();
    $first = Announcement::factory()->popup()->create(['title' => 'First']);
    $second = Announcement::factory()->popup()->create(['title' => 'Second']);
    livewire(AnnouncementPopups::class, ['announcementIds' => [$first->id, $second->id]])
        ->assertSee('First')->assertDontSee('Second')->assertSee('1 of 2')
        ->call('confirm', $first->id)
        ->assertSee('Second')->assertSee('2 of 2')
        ->call('confirm', $second->id)
        ->assertDontSee('Second')->assertDontSeeHtml('role="dialog"');
});

it('ignores ids that are not in its list', function () {
    login();
    $a = Announcement::factory()->popup()->create();
    $other = Announcement::factory()->popup()->create();
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->call('confirm', $other->id)->assertSee($a->title);
    expect(AnnouncementDismissal::count())->toBe(0);
});

it('skips a popup that was soft-deleted after mounting', function () {
    login();
    $a = Announcement::factory()->popup()->create(['title' => 'Gone']);
    $b = Announcement::factory()->popup()->create(['title' => 'Stays']);
    $component = livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id, $b->id]]);
    $a->delete();
    $component->call('$refresh')->assertDontSee('Gone')->assertSee('Stays');
});

it('renders nothing and confirms nothing for guests', function () {
    $a = Announcement::factory()->popup()->create();
    livewire(AnnouncementPopups::class, ['announcementIds' => [$a->id]])
        ->assertDontSeeHtml('role="dialog"')->call('confirm', $a->id);
    expect(AnnouncementDismissal::count())->toBe(0);
});
