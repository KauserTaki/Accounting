<?php

return [

    'name'              => 'Paraşüt',
    'description'       => 'Migrate your data in just a couple of minutes, seamless',

    'form' => [
        'client_id'     => 'Client Id',
        'client_secret' => 'Client Secret',
        'redirect_uri'  => 'Redirect Url',
        'username'      => 'Username',
        'password'      => 'Password',
        'company_id'    => 'Company Id',

        'sync' => [
            'title'    => 'Get Current Data',
            'contact'  => 'Sync Contacts',
            'category' => 'Sync Categories',
            'customer' => 'Sync Customers',
            'product'  => 'Sync Products',
            'account'  => 'Sync Accounts',
            'order'    => 'Sync Orders',
            'invoice'  => 'Sync Invoices',
            'employee' => 'Sync Employees',
            'bill'     => 'Sync Bills',
            'all'      => 'Sync All',
        ]
    ],

    'types' => [
        'customers'   => 'Customer|Customers',
        'supplier'    => 'Vendor|Vendors',
        'products'    => 'Product|Products',
        'employees'   => 'Employee|Employees',
        'contacts'    => 'Contact|Contacts',
    ],

    'success' => [
        'transactions_synced' => 'Transactions synced'
    ],

    'sync_text' => 'Sync this :type: :value',
    'finished'  => ':type sync finished',
    'total'     => 'Total :type count: :count',
    'total_all' => 'Total Customers: :customers, Total Products: :products, Total Orders: :orders',

    'buttons' => [
        'buy'      => [
            'custom_fields' => 'Buy Custom Fields',
            'inventory' => 'Buy Inventory',
            'payroll' => 'Buy Payroll',
        ],
        'continue' => 'Continue',
    ],

    'error' => [
        'nothing_to_sync' => 'Nothing to sync',
        'no_settings'     => 'Please, save the settings first.',
    ],

    'custom_fields' => [
        'title'    => 'You need Custom Fields app for more healthy integration',
        'contacts' => [
            'short_name'   => 'Short Name',
            'contact_type' => 'CONTACT TYPE',
            'tax_office'   => 'TAX OFFICE',
            'district'     => 'DISTRICT',
            'city'         => 'CITY',
            'fax'          => 'FAX',
            'iban'         => 'IBAN',
            'ibans'        => 'IBANS',
            'balances'     => 'BALANCES',
        ],
        'products' => [
            'barcode' => 'BARCODE',
        ],
        'accounts' => [
            'account_type' => 'ACCOUNT TYPE',
            'bank_branch'  => 'BANK BRANCH',
            'iban'         => 'IBAN',
        ],
    ],

    'coupon' => [
        'description' => 'You can buy Custom Fields App and transfer these missing fields.</br>
        30% discount to any user coming from Paraşüt.',
        'code' => 'Coupon: PARASUT30',
        'inventory' => 'You can buy Inventory App and track inventory for item stock. </br>
        30% discount to any user coming from Paraşüt.',
        'payroll' => 'You can buy PAyroll App and added Employees. </br>
        30% discount to any user coming from Paraşüt.'
    ],
];
