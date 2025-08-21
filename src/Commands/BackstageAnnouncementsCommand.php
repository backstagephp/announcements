<?php

namespace Backstage\Announcements\Commands;

use Illuminate\Console\Command;

class AnnouncementsCommand extends Command
{
    public $signature = 'backstage-announcements';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
