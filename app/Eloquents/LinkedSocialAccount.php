<?php

namespace App\Eloquents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedSocialAccount extends Model
{
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

    /**
     * Relation: belong to user.
     **/
    public function user() : BelongsTo
    {
        return $this->belongsTo('App\Eloquents\User');
    }
}
