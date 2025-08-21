<?php

namespace Backstage\Announcements\Commands;

use Illuminate\Console\Command;
use Backstage\Announcements\Models\Announcement;

class AnnouncementsCommand extends Command
{
    public $signature = 'backstage-announcements';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        $newAnnouncement = Announcement::create([
            'title' => 'New Announcement',
            'content' => 'This is a new announcement.',
        ]);

        return self::SUCCESS;
    }
}
