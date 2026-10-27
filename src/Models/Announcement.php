<?php

namespace Backstage\Announcements\Models;

use Backstage\Announcements\Database\Factories\AnnouncementFactory;
use Backstage\Announcements\Enums\AnnouncementDisplay;
use Backstage\Announcements\Livewire\Announcement as LivewireAnnouncement;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
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
        'display',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'scopes' => 'array',
        'display' => AnnouncementDisplay::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $attributes = [
        'display' => 'banner',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $announcement) {
            $announcement->scopes = ! empty($announcement->scopes) ? $announcement->scopes : ['*'];

            if ($announcement->display === AnnouncementDisplay::Popup && ! static::scopesSupportPopup($announcement->scopes)) {
                $announcement->display = AnnouncementDisplay::Banner;
            }
        });
    }

    /**
     * A popup needs at least one scope that is a regular page (not a login-type SimplePage).
     *
     * @param  array<int, string>  $scopes
     */
    public static function scopesSupportPopup(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (is_string($scope) && class_exists($scope) && is_a($scope, Page::class, true) && ! is_a($scope, SimplePage::class, true)) {
                return true;
            }
        }

        return false;
    }

    public function isPopup(): bool
    {
        return $this->display === AnnouncementDisplay::Popup;
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, static>
     */
    public static function pendingPopupsFor(array $ids, ?int $userId): Collection
    {
        if ($userId === null || $ids === []) {
            return new Collection;
        }

        $order = array_flip(array_values($ids));

        return static::query()
            ->whereKey($ids)
            ->whereDoesntHave('dismissals', fn ($query) => $query->where('user_id', $userId))
            ->get()
            ->sortBy(fn (self $announcement) => $order[$announcement->getKey()] ?? PHP_INT_MAX)
            ->values();
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
