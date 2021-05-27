<?php

namespace Modules\Payflexi\Listeners;

use App\Events\Module\SettingShowing as Event;

class ShowInSettingsPage
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $event->modules->settings['payflexi'] = [
            'name' => trans('payflexi::general.name'),
            'description' => trans('payflexi::general.description'),
            'url' => route('payflexi.settings.edit'),
            'icon' => 'fas fa-credit-card',
        ];
    }
}
