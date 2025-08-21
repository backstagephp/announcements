<?php

namespace Backstage\Announcements\Resources\Announcements\Schemas;

use Backstage\Announcements\Collections\ScopeCollection;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),

                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),

                Select::make('scopes')
                    ->searchable()
                    ->multiple()
                    ->options(ScopeCollection::create(Filament::getCurrentPanel())->toArray())
                    ->required(),

                Select::make('color')
                    ->live()
                    ->prefixIcon(Heroicon::OutlinedCube,true)
                    ->prefixIconColor(fn(?string $state) => $state ?? 'gray')
                    ->options(function () {
                        $colors = ColorManager::DEFAULT_COLORS;
                        
                        return collect($colors)
                            ->keys()
                            ->mapWithKeys(function ($key) use ($colors) {
                                return [
                                    $key => $key
                                ];
                            });
                    })
            ]);
    }
}
