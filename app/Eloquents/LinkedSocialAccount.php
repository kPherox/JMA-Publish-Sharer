<?php

namespace App\Eloquents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class LinkedSocialAccount extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_name',
        'provider_id',
        'name',
        'nickname',
        'avatar',
        'token',
        'token_secret',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'token_secret',
    ];

    public function routeNotificationForTwitter() : array
    {
        return [
            config('services.twitter.consumer_key'),
            config('services.twitter.consumer_secret'),
            $this->token,
            $this->token_secret,
        ];
    }

    public function routeNotificationForLine() : string
    {
        return $this->provider_id;
    }

    /**
     * Relation: belong to user.
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo('App\Eloquents\User');
    }

    /**
     * Relation: has many account settings.
     */
    public function settings() : HasMany
    {
        return $this->hasMany('App\Eloquents\AccountSetting');
    }
}
