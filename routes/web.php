<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\File;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return File::get(public_path() . '/website.html');
    //return view('welcome');
});

//Route::get('/{id}', 'WebhookController@index')->name('webhook');

// Route::group(array('middleware' => ['public']), function () {
//   Route::post('/{alias}', function () {
//      echo 1;
//      return;
//   });
// });

Route::get('/spark-billing', function () {
    return redirect('billing');
})->name('platform.billing');

Route::get('/{alias}', function () {
    return view('webhook-application');
});

Route::post('/{alias}', [WebhookController::class, 'send'])->name('webhook.send');

Route::get('/verify/{uuid}', [WebhookController::class, 'channelVerify'])->name('webhook.whatsapp.verification');

