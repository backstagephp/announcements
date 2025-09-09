<?php

namespace Backstage\Announcements\Collections;

use Filament\Panel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ScopeCollection extends Collection
{
    public static function create(Panel $panel)
    {
        $resources = $panel->getResources();

        $mutatedResources = static::mutateResources($resources);

        $extraActions = [
            $panel->getLoginRouteAction(),
        ];

        if ($panel->hasRegistration()) {
            $extraActions[] = $panel->getRegistrationRouteAction();
        }

        $pages = array_merge($panel->getPages(), $extraActions);

        $mutatedPages = static::mutatePages($pages);

        $scopes = array_merge($mutatedResources, $mutatedPages);

        $cached = parent::make($scopes);

        return $cached;
    }

    public static function mutateResources($resources)
    {
        $resources = new static($resources);

        $resources = $resources->values()->map(function ($resource, $x) {
            $modelLabel = $resource::getPluralModelLabel() ?? $resource::getNavigationLabel();

            $pages = $resource::getPages();

            $labels = [];

            foreach ($pages as $page) {
                $labels[$page->getPage()] = str($modelLabel)
                    ->append(' ')
                    ->append('(')
                    ->append(strtolower($page->getPage()::getNavigationLabel()))
                    ->append(')')
                    ->append(' ')
                    ->when(
                        $page->getPage()::getResource()::getNavigationGroup(),
                        fn ($group) => $group->append('(')->append(strtolower($page->getPage()::getResource()::getNavigationGroup()))
                            ->append(')')
                    )

                    ->toString();
            }

            return $labels;
        })
            ->pipe(fn ($c) => Arr::collapse($c->toArray()));

        return $resources;
    }

    public static function mutatePages($pages)
    {
        $pages = new static($pages);

        $pages = $pages->values()->mapWithKeys(function ($page) {
            $value = str(method_exists($page, 'getNavigationLabel') ? $page::getNavigationLabel() : (method_exists($page, 'getLabel') ? $page::getLabel() : app($page)->getTitle()))
                ->when(
                    method_exists($page, 'getNavigationGroup') ? $page::getNavigationGroup() : null,
                    fn ($group) => $group->append(' (')->append(strtolower($page::getNavigationGroup()))->append(')')
                )
                ->toString();

            return [$page => $value];
        })
            ->toArray();

        return $pages;
    }
}
