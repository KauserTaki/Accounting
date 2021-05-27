<?php

return [

    'name'              => 'PayFlexi Flexible Checkout',
    'description'       => 'Enable your clients to pay in flexible weekly or monthly instalments for invoices.',

    'form' => [
        'mode'                   => 'Mode',
        'api_test_public_key'    => 'Test Public Key',
        'api_test_secret_key'    => 'Test Secret Key',
        'api_live_public_key'    => 'Live Public Key',
        'api_live_secret_key'    => 'Live Secret Key',
        'webhook_url'            => 'Webhook URL',
        'customer'               => 'Show to Customer'
    ],

    'test_mode'         => 'Warning: The payment gateway is in \'Text Mode\'. Your account will not be charged.',

    'placed'         => ':type placed!',
];
