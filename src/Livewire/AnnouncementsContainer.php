<?php

namespace Backstage\Announcements\Livewire;

use Livewire\Component;

class AnnouncementsContainer extends Component
{
    public function mount(array $scopes) {}

    public function render()
    {
        return view('backstage/announcements::livewire.announcements-container');
    }
}
