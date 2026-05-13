<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Route::get('/',function(){
//     echo "Welcome to the website";
// });
Route::get('/notification_test', 'Controller@notification');
Route::get('/import', 'BatchImportController@index');
Route::post('/import', 'BatchImportController@import');
// Route::get('/add/access-control',"Controller@addUserAccessPermission");
Route::get('/send-email', 'MailController@sendEmail');

Route::view('/login', 'pages.login')->name('login-web');
Route::view('/otp', 'pages.pin')->name('otp');

// Route::middleware(['auth'])->group(function () {
    Route::view('/profile', 'pages.profile')->name('user-profile');
    Route::view('/dashboard', 'pages.dashboard');
    Route::view('/orders', 'pages.orders');
    Route::view('/security', 'pages.security');
    Route::view('/change-pin', 'pages.change-pin');
    Route::view('/logout', 'pages.logout');
    Route::get('/dashboard-content', function () {
        return view('pages.dashboard-content');
    });
    Route::get('/profile-content', function () {
        return view('pages.profile-content');
    });
    Route::get('/orders-content', function () {
        return view('pages.orders-content');
    });
    Route::get('/security-content', function () {
        return view('pages.security-content');
    });
    Route::get('/change-pin-content', function () {
        return view('pages.change-pin-content');
    });
    Route::get('/logout-content', function () {
        return view('pages.logout-content');
    });
// });
