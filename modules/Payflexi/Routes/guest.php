<?php

use Illuminate\Support\Facades\Route;

/**
 * 'guest' middleware and 'portal/payflexi' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('payflexi', function () {
    Route::post('invoices/{invoice}/initiate/transaction', 'StandardPayment@initiateTransaction')->name('invoices.initiate.transaction');
}, ['middleware' => 'guest']);

Route::portal('payflexi', function () {
    Route::post('webhook/', 'Modules\Payflexi\Http\Controllers\Webhook@index')->name('invoices.webhook');
});