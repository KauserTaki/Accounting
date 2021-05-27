<?php

namespace Modules\Parasut\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;
use Modules\Parasut\Listeners\FinishInstallation;
use Modules\Parasut\Listeners\ShowInSettingsPage;

class Event extends Provider
{
    /**
     * The event listener mappings for the module.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\Module\Installed::class => [
            FinishInstallation::class,
        ],
        \App\Events\Module\SettingShowing::class => [
            ShowInSettingsPage::class,
        ]
    ];
}
