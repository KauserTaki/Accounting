<?php

namespace Modules\Parasut\Listeners;

use App\Events\Module\SettingShowing;

class ShowInSettingsPage
{
    /**
     * Handle the event.
     *
     * @param  SettingShowing $event
     * @return void
     */
    public function handle(SettingShowing $event)
    {
        $event->modules->settings['parasut'] = [
            'name' => trans('parasut::general.name'),
            'description' => trans('parasut::general.description'),
            'url' => route('parasut.settings.edit'),
            'icon' => 'fas fa-exchange-alt',
        ];
    }
}
