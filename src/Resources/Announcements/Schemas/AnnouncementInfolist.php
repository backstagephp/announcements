<?php

namespace Backstage\Announcements\Resources\Announcements\Schemas;

use Backstage\Announcements\Models\Announcement;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AnnouncementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(9)
                    ->schema([
                        Fieldset::make()
                            ->schema([
                                Section::make(__('Announcement Details'))
                                    ->description(__('Information about this announcement'))
                                    ->icon(Heroicon::OutlinedRectangleStack)
                                    ->schema([
                                        TextEntry::make('title')
                                            ->label(__('Title'))
                                            ->icon(Heroicon::OutlinedDocumentText)
                                            ->iconColor('primary')
                                            ->copyable()
                                            ->weight('bold')
                                            ->size('lg'),

                                        TextEntry::make('content')
                                            ->label(__('Content'))
                                            ->icon(Heroicon::OutlinedDocument)
                                            ->iconColor('primary')
                                            ->copyable()
                                            ->columnSpanFull()
                                            ->markdown(),

                                        TextEntry::make('color')
                                            ->label(__('Color'))
                                            ->icon(Heroicon::OutlinedPaintBrush)
                                            ->badge()
                                            ->color(fn (?string $state): string => $state ?? 'gray')
                                            ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'gray')),

                                        TextEntry::make('scopes')
                                            ->label(__('Scopes'))
                                            ->icon(Heroicon::OutlinedEye)
                                            ->iconColor('primary')
                                            ->badge()
                                            ->separator(',')
                                            ->formatStateUsing(fn (?string $state): string => $state === '*' ? __('All') : $state),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(6),

                        Fieldset::make()
                            ->schema([
                                Section::make(__('Schedule'))
                                    ->description(__('When this announcement is active'))
                                    ->icon(Heroicon::OutlinedClock)
                                    ->schema([
                                        TextEntry::make('start_date')
                                            ->label(__('Start Date'))
                                            ->icon(Heroicon::OutlinedCalendar)
                                            ->iconColor('success')
                                            ->dateTime()
                                            ->placeholder(__('No start date (active now)'))
                                            ->tooltip(__('Announcement becomes active on this date')),

                                        TextEntry::make('end_date')
                                            ->label(__('End Date'))
                                            ->icon(Heroicon::OutlinedCalendar)
                                            ->iconColor('danger')
                                            ->dateTime()
                                            ->placeholder(__('No end date (active indefinitely)'))
                                            ->tooltip(__('Announcement expires on this date')),

                                        TextEntry::make('status')
                                            ->label(__('Status'))
                                            ->icon(Heroicon::OutlinedCheckCircle)
                                            ->badge()
                                            ->color(fn (Announcement $record): string => $record->isActive() ? 'success' : 'danger')
                                            ->formatStateUsing(fn (Announcement $record): string => $record->isActive() ? __('Active') : __('Inactive')),
                                    ])
                                    ->columnSpanFull(),

                                Section::make(__('Metadata'))
                                    ->description(__('Tracking information'))
                                    ->icon(Heroicon::OutlinedInformationCircle)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label(__('Created'))
                                            ->icon(Heroicon::OutlinedPlus)
                                            ->iconColor('success')
                                            ->dateTime()
                                            ->tooltip(__('When this announcement was created')),

                                        TextEntry::make('updated_at')
                                            ->label(__('Updated'))
                                            ->icon(Heroicon::OutlinedPencil)
                                            ->iconColor('warning')
                                            ->dateTime()
                                            ->tooltip(__('When this announcement was last updated')),

                                        TextEntry::make('deleted_at')
                                            ->label(__('Deleted'))
                                            ->icon(Heroicon::OutlinedTrash)
                                            ->iconColor('danger')
                                            ->dateTime()
                                            ->placeholder(__('Not deleted'))
                                            ->tooltip(__('When this announcement was deleted')),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
