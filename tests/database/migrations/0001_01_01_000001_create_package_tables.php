<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Package migrations ship as stubs; run them in order. */
    private array $stubs = [
        'create_backstage_announcements_table',
        'create_announcement_dismissals_table',
        'add_start_date_end_date_to_announcements_table',
        'add_display_to_announcements_table',
    ];

    public function up(): void
    {
        foreach ($this->stubs as $name) {
            (require __DIR__ . '/../../../database/migrations/' . $name . '.php.stub')->up();
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->stubs) as $name) {
            (require __DIR__ . '/../../../database/migrations/' . $name . '.php.stub')->down();
        }
    }
};
