<?php

namespace Backstage\Announcements\Resources\Announcements\Schemas;

use Backstage\Announcements\AnnouncementsPlugin;
use Backstage\Announcements\Collections\ScopeCollection;
use Backstage\Announcements\Enums\AnnouncementDisplay;
use Backstage\Announcements\Models\Announcement;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->required(),

                        Textarea::make('content')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('scopes')
                            ->searchable()
                            ->multiple()
                            ->options(function ($state) {
                                $plugin = AnnouncementsPlugin::get();
                                $forcedScopes = $plugin->getForcedScopes();

                                return ScopeCollection::create(Filament::getCurrentPanel(), $forcedScopes)->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                if (AnnouncementDisplay::isPopupState($get('display')) && ! Announcement::scopesSupportPopup($state ?? [])) {
                                    $set('display', AnnouncementDisplay::Banner->value);
                                }
                            })
                            ->required(),

                        Radio::make('display')
                            ->label(__('backstage/announcements::backstage-announcements.display.label'))
                            ->options(AnnouncementDisplay::class)
                            ->default(AnnouncementDisplay::Banner->value)
                            ->required()
                            ->columns(2)
                            ->live()
                            ->disableOptionWhen(fn (string $value, Get $get): bool => $value === AnnouncementDisplay::Popup->value
                                && ! Announcement::scopesSupportPopup($get('scopes') ?? []))
                            ->helperText(function (Get $get): ?string {
                                if (! Announcement::scopesSupportPopup($get('scopes') ?? [])) {
                                    return __('backstage/announcements::backstage-announcements.hints.popup_unavailable');
                                }

                                return AnnouncementDisplay::isPopupState($get('display'))
                                    ? __('backstage/announcements::backstage-announcements.hints.popup_fallback')
                                    : null;
                            }),

                        Select::make('color')
                            ->live()
                            ->prefixIcon(Heroicon::OutlinedCube, true)
                            ->prefixIconColor(fn (?string $state) => $state ?? 'gray')
                            ->options(function (Select $select) {
                                $colors = ColorManager::DEFAULT_COLORS;

                                return collect($colors)
                                    ->keys()
                                    ->mapWithKeys(function ($key) use ($select) {
                                        return [
                                            $key => (string) Text::make(ucfirst($key))->color($key)->container($select->getContainer())->toHtmlString(),
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->getOptionLabelsUsing(fn ($value) => ucfirst($value))
                            ->native(false)
                            ->allowHtml()
                            ->required(),

                        FusedGroup::make()
                            ->schema([
                                DateTimePicker::make('start_date')
                                    ->native(false)
                                    ->seconds(false)
                                    ->placeholder(__('Start Date')),

                                DateTimePicker::make('end_date')
                                    ->native(false)
                                    ->seconds(false)
                                    ->placeholder(__('End Date'))
                                    ->registerActions([
                                        Action::make('information')
                                            ->label(__('Information'))
                                            ->icon(Heroicon::OutlinedInformationCircle)
                                            ->color('gray')
                                            ->modal()
                                            ->modalIcon(Heroicon::OutlinedInformationCircle)
                                            ->modalIconColor('info')
                                            ->modalHeading(__('Information'))
                                            ->modalContent(Html::make(__('If setting a start date, the announcement will only be displayed after the start date. If setting an end date, the announcement will only be displayed before the end date. If setting both, the announcement will only be displayed between the start and end dates.')))
                                            ->modalFooterActions([])
                                            ->modalWidth(Width::Large),
                                    ]),
                            ])
                            ->columns(2)
                            ->beforeLabel(fn () => __('Start date'))
                            ->afterLabel(function (FusedGroup $component): Htmlable {
                                $target = $component->getChildComponents()[1]->getActions()['information']->getLivewireClickHandler();

                                $link = view('backstage/announcements::components.after-label', [
                                    'target' => $target,
                                    'label' => __('End date'),
                                ])->render();

                                return new HtmlString($link);
                            }),
                    ]),
            ]);
    }
}
