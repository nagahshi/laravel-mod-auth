<?php

namespace Auth\Auth\Entities;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class SocialiteProvider extends Model
{

    protected static function boot()
    {
        parent::boot();

        // auto-sets values on creation
        static::creating(function ($query) {
            $query->{$query->getKeyName()} = Uuid::generate()->string;
        });
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'image',
        'provider',
        'provider_id'
    ];
}
