<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/payflexi' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('payflexi', function () {
    Route::post('invoices/{invoice}/confirm', 'StandardPayment@confirm')->name('invoices.confirm');
});
