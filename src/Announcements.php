<?php

namespace Backstage\Announcements;

use Backstage\Announcements\Livewire\AnnouncementPopups;
use Backstage\Announcements\Models\Announcement;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Pages\SimplePage;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class Announcements
{
    public function register()
    {
        if (! Schema::hasTable(app(Announcement::class)->getTable())) {
            return;
        }

        if (App::runningInConsole()) {
            return;
        }

        $this->registerHooks();
    }

    public function registerHooks(): void
    {
        /** @var array<class-string, array<int, int|string>> $popupIdsByScope */
        $popupIdsByScope = [];

        Announcement::query()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->filter(fn (Announcement $announcement) => $announcement->isActive())
            ->each(function (Announcement $announcement) use (&$popupIdsByScope) {
                collect($announcement->scopes)->each(function ($scope) use ($announcement, &$popupIdsByScope) {
                    if (! is_string($scope) || ! class_exists($scope)) {
                        return;
                    }

                    $isSimplePage = is_a($scope, SimplePage::class, true);

                    if (! $announcement->isPopup() || $isSimplePage) {
                        $hook = match (true) {
                            $isSimplePage => PanelsRenderHook::SIMPLE_PAGE_START,
                            is_a($scope, Page::class, true) => PanelsRenderHook::CONTENT_START,
                            default => null,
                        };

                        if ($hook) {
                            FilamentView::registerRenderHook(
                                name: $hook,
                                hook: function () use ($announcement, $scope) {
                                    if ($announcement->isDismissedBy(Filament::auth()->user()?->id)) {
                                        return '';
                                    }

                                    return $announcement->render($scope);
                                },
                                scopes: $scope
                            );
                        }

                        return;
                    }

                    if (is_a($scope, Page::class, true)) {
                        $popupIdsByScope[$scope][] = $announcement->getKey();
                    }
                });
            });

        foreach ($popupIdsByScope as $scope => $ids) {
            FilamentView::registerRenderHook(
                name: PanelsRenderHook::BODY_END,
                hook: function () use ($ids) {
                    if (! Filament::auth()->check()) {
                        return '';
                    }

                    if (Announcement::pendingPopupsFor($ids, Filament::auth()->id())->isEmpty()) {
                        return '';
                    }

                    return new HtmlString(Livewire::mount(AnnouncementPopups::class, ['announcementIds' => $ids]));
                },
                scopes: $scope
            );
        }
    }
}
