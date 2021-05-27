<?php

namespace Modules\Parasut\Listeners;

use App\Events\Module\Installed as Event;
use App\Traits\Permissions;

class FinishInstallation
{
    use Permissions;

    public $alias = 'parasut';

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if ($event->alias != $this->alias) {
            return;
        }

        $this->updatePermissions();
    }

    public function updatePermissions()
    {
        $this->attachPermissionsToAdminRoles([
            $this->alias . '-settings' => 'r,u',
        ]);
    }
}
