<?php

namespace Backstage\Announcements\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'scopes'
    ];

    protected $casts = [
        'scopes' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $announcement) {
            $announcement->scopes = !empty($announcement->scopes) ? $announcement->scopes : ['*'];
        });
    }
}
