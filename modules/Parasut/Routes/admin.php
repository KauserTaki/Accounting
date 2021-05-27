<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'parasut' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('parasut', function () {
    Route::group(['as' => 'settings.'], function () {
        Route::get('settings', 'Settings@edit')->name('edit');
        Route::post('settings', 'Settings@update')->name('update');
    });

    Route::group(['middleware' => [
            'parasut.contact',
            'parasut.category',
            'parasut.product',
            'parasut.account',
            'parasut.invoice',
            'parasut.employee',
            'parasut.bill'
        ],
        'as' => 'sync.'
    ], function () {
        Route::get('sync', 'Sync@send')->name('send');
        Route::get('sync/count', 'Sync@count')->name('count');
        Route::get('sync/sync', 'Sync@sync')->name('sync');
    });

    Route::group(['middleware' => 'parasut.contact', 'as' => 'contacts.'], function () {
        Route::get('contacts/count', 'Contacts@count')->name('count');
        Route::get('contacts/sync', 'Contacts@sync')->name('sync');
        Route::post('contacts/sync/{id}', 'Contacts@store')->name('store');
    });

    Route::group(['middleware' => 'parasut.category', 'as' => 'categories.'], function () {
        Route::get('categories/count', 'Categories@count')->name('count');
        Route::get('categories/sync', 'Categories@sync')->name('sync');
        Route::post('categories/sync/{id}', 'Categories@store')->name('store');
    });

    Route::group(['middleware' => 'parasut.product', 'as' => 'products.'], function () {
        Route::get('products/count', 'Products@count')->name('count');
        Route::get('products/sync', 'Products@sync')->name('sync');
        Route::post('products/sync/{id}', 'Products@store')->name('store');
    });

    Route::group(['middleware' => 'parasut.account', 'as' => 'accounts.'], function () {
        Route::get('accounts/count', 'Accounts@count')->name('count');
        Route::get('accounts/sync', 'Accounts@sync')->name('sync');
        Route::post('accounts/sync/{id}', 'Accounts@store')->name('store');
    });

    Route::group(['middleware' => 'parasut.invoice', 'as' => 'invoices.'], function () {
        Route::get('invoices/count', 'Invoices@count')->name('count');
        Route::get('invoices/sync', 'Invoices@sync')->name('sync');
        Route::post('invoices/sync/{id}', 'Invoices@store')->name('store');
    });

    Route::group(['middleware' => 'parasut.employee', 'as' => 'employees.'], function () {
        Route::get('employees/count', 'Employees@count')->name('count');
        Route::get('employees/sync', 'Employees@sync')->name('sync');
        Route::post('employees/sync/{id}', 'Employees@store')->name('store');
    });

    Route::group(['middleware' => 'parasut.bill', 'as' => 'bills.'], function () {
        Route::get('bills/count', 'Bills@count')->name('count');
        Route::get('bills/sync', 'Bills@sync')->name('sync');
        Route::post('bills/sync/{id}', 'Bills@store')->name('store');
    });
});
