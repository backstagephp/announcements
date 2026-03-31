<?php

namespace Backstage\Announcements\Resources\Announcements\Schemas;

use Backstage\Announcements\AnnouncementsPlugin;
use Backstage\Announcements\Collections\ScopeCollection;
use Backstage\Announcements\Models\Announcement;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(9)
                    ->schema([
                        Section::make(__('Announcement Details'))
                            ->schema([
                                TextEntry::make('content')
                                    ->label(__('Content'))
                                    ->columnSpanFull()
                                    ->markdown(),

                                TextEntry::make('color')
                                    ->label(__('Color'))
                                    ->badge()
                                    ->color(fn (?string $state): string => $state ?? 'gray')
                                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'gray')),

                                TextEntry::make('scopes')
                                    ->label(__('Scopes'))
                                    ->badge()
                                    ->separator(',')
                                    ->formatStateUsing(function ($state): string {
                                        $plugin = AnnouncementsPlugin::get();
                                        $forcedScopes = $plugin->getForcedScopes();
                                        $scopeOptions = ScopeCollection::create(Filament::getCurrentPanel(), $forcedScopes)->toArray();

                                        return $scopeOptions[$state] ?? $state;
                                    }),
                            ])
                            ->columns(2)
                            ->columnSpan(6),

                        Grid::make(1)
                            ->schema([
                                Section::make(__('Schedule'))
                                    ->schema([
                                        TextEntry::make('start_date')
                                            ->label(__('Start Date'))
                                            ->dateTime()
                                            ->placeholder(__('No start date (active now)'))
                                            ->tooltip(__('Announcement becomes active on this date')),

                                        TextEntry::make('end_date')
                                            ->label(__('End Date'))
                                            ->dateTime()
                                            ->placeholder(__('No end date (active indefinitely)'))
                                            ->tooltip(__('Announcement expires on this date')),

                                        TextEntry::make('status')
                                            ->label(__('Status'))
                                            ->badge()
                                            ->color(fn (Announcement $record): string => $record->isActive() ? 'success' : 'danger')
                                            ->formatStateUsing(fn (Announcement $record): string => $record->isActive() ? __('Active') : __('Inactive')),
                                    ]),

                                Section::make(__('Metadata'))
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label(__('Created'))
                                            ->dateTime()
                                            ->tooltip(__('When this announcement was created')),

                                        TextEntry::make('updated_at')
                                            ->label(__('Updated'))
                                            ->dateTime()
                                            ->tooltip(__('When this announcement was last updated')),

                                        TextEntry::make('deleted_at')
                                            ->label(__('Deleted'))
                                            ->dateTime()
                                            ->placeholder(__('Not deleted'))
                                            ->tooltip(__('When this announcement was deleted')),
                                    ]),
                            ])
                            ->columnSpan(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
