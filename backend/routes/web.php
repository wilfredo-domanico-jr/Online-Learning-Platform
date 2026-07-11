<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\SocialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Browser-navigated routes
|--------------------------------------------------------------------------
|
| Everything else is a JSON API served from routes/api.php and consumed by
| the decoupled React SPA. The routes below must stay here because they
| involve a real browser redirect (OAuth, signed email links) rather than
| an XHR/fetch call.
|
*/

Route::get('/auth/{provider}/redirect', [SocialController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialController::class, 'callback'])->name('social.callback');

Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');
