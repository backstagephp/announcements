<?php

namespace Backstage\Announcements\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Backstage\Announcements\Announcements
 */
class Announcements extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Backstage\Announcements\Announcements::class;
    }
}
