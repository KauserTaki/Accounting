<?php

use Illuminate\Support\Facades\Route;

Route::admin('payflexi', function () {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::post('settings', 'Settings@update')->name('update');
    });
});