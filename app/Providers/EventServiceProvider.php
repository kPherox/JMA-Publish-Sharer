<?php

namespace App\Providers;

use App\Events\EntrySaved;
use App\Listeners\EntrySavedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Line\LineExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitter\TwitterExtendSocialite;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        SocialiteWasCalled::class => [
            TwitterExtendSocialite::class,
            LineExtendSocialite::class,
        ],
        EntrySaved::class => [
            EntrySavedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
