<?php

use Illuminate\Support\Facades\Route;

/**
 * 'signed' middleware and 'signed/payflexi' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::signed('payflexi', function () {
    Route::get('invoices/{invoice}/', 'StandardPayment@show')->name('invoices.show');
    Route::get('invoices/{invoice}/confirm', 'StandardPayment@confirm')->name('invoices.confirm');
    Route::get('invoices/{invoice}/return', 'StandardPayment@return')->name('invoices.return');
    Route::get('invoices/{invoice}/cancel', 'StandardPayment@cancel')->name('invoices.cancel');
});
