<?php

namespace Backstage\Announcements\Resources\Announcements\Schemas;

use Backstage\Announcements\Collections\ScopeCollection;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                    ->options(['*' => __('All pages')] + ScopeCollection::create(Filament::getCurrentPanel())->toArray())
                    ->required(),
            ]);
    }
}
