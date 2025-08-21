<?php

namespace Backstage\Announcements\Models;

use Backstage\Announcements\Livewire\Announcement as LivewireAnnouncement;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'scopes',
        'color',
    ];

    protected $casts = [
        'scopes' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $announcement) {
            $announcement->scopes = ! empty($announcement->scopes) ? $announcement->scopes : ['*'];
        });
    }

    public function render(string $scope): Htmlable
    {
        $livewire = Livewire::mount(LivewireAnnouncement::class, ['announcement' => $this, 'scope' => $scope]);

        return new HtmlString($livewire);
    }
}
