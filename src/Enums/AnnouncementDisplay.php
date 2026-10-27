<?php

namespace Backstage\Announcements\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementDisplay: string implements HasDescription, HasLabel
{
    case Banner = 'banner';
    case Popup = 'popup';

    public function getLabel(): string
    {
        return __('backstage/announcements::backstage-announcements.display.' . $this->value . '.label');
    }

    public function getDescription(): string
    {
        return __('backstage/announcements::backstage-announcements.display.' . $this->value . '.description');
    }

    /** Form state may be the enum or its raw string value. */
    public static function isPopupState(mixed $state): bool
    {
        return $state === self::Popup || $state === self::Popup->value;
    }
}
