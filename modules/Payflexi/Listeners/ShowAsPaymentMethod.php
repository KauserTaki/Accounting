<?php

namespace Modules\Payflexi\Listeners;

use App\Events\Module\PaymentMethodShowing as Event;

class ShowAsPaymentMethod
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $method = setting('payflexi');

        $method['code'] = 'payflexi';

        $event->modules->payment_methods[] = $method;
    }
}
