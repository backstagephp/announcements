<?php

namespace Backstage\Announcements\Livewire;

use Backstage\Announcements\Models\Announcement;
use Backstage\Announcements\Models\AnnouncementDismissal;
use Filament\Facades\Filament;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AnnouncementPopups extends Component
{
    /** @var array<int, int> */
    #[Locked]
    public array $announcementIds = [];

    #[Locked]
    public int $total = 0;

    /**
     * @param  array<int, int>  $announcementIds
     */
    public function mount(array $announcementIds): void
    {
        $this->announcementIds = array_values($announcementIds);

        $this->total = Announcement::pendingPopupsFor($this->announcementIds, Filament::auth()->id())->count();
    }

    public function confirm(int $id): void
    {
        if (! in_array($id, $this->announcementIds, true) || ! Filament::auth()->check()) {
            return;
        }

        AnnouncementDismissal::firstOrCreate([
            'announcement_id' => $id,
            'user_id' => Filament::auth()->id(),
        ]);
    }

    public function render()
    {
        $pending = Announcement::pendingPopupsFor($this->announcementIds, Filament::auth()->id());

        if ($pending->isEmpty()) {
            return '<div></div>';
        }

        return view('backstage/announcements::livewire.announcement-popups', [
            'current' => $pending->first(),
            'position' => max(1, $this->total - $pending->count() + 1),
            'total' => $this->total,
        ]);
    }
}
