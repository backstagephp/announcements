<?php

namespace Backstage\Announcements\Livewire;

use Livewire\Component;
use Backstage\Announcements\Collections\ScopeCollection;

class AnnouncementsContainer extends Component
{
    public function mount(array $scopes) {}

    public function render()
    {
        return view('backstage/announcements::livewire.announcements-container');
    }
}
