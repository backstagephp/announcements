<?php

namespace Backstage\Announcements\Models;

use Backstage\Announcements\Database\Factories\AnnouncementFactory;
use Backstage\Announcements\Livewire\Announcement as LivewireAnnouncement;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class Announcement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'scopes',
        'color',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'scopes' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $announcement) {
            $announcement->scopes = ! empty($announcement->scopes) ? $announcement->scopes : ['*'];
        });
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }

    public function render(string $scope): Htmlable
    {
        $livewire = Livewire::mount(LivewireAnnouncement::class, ['announcement' => $this, 'scope' => $scope]);

        return new HtmlString($livewire);
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(AnnouncementDismissal::class);
    }

    public function isDismissedBy(?int $userId = null): bool
    {
        if (! Filament::auth()->check()) {
            return false;
        }

        if (is_null($userId)) {
            $userId = Filament::auth()->user()->id;
        }

        return $this->dismissals()
            ->where('user_id', $userId)
            ->exists();
    }

    public function isActive(): bool
    {
        $now = now();

        // No dates → active
        if (! $this->start_date && ! $this->end_date) {
            return true;
        }

        // Inside full range → active
        if ($this->start_date && $this->end_date) {
            return $now->between($this->start_date, $this->end_date, true);
        }

        // Start in past → active
        if ($this->start_date) {
            return $now->gte($this->start_date);
        }

        // End in future → active
        if ($this->end_date) {
            return $now->lte($this->end_date);
        }

        return true;
    }
}
