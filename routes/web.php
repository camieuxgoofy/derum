<?php

use App\Http\Controllers\ArtistAlbumController;
use App\Http\Controllers\Auth\RedirectAuthenticatedUsersController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MerchController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/', [ArtistAlbumController::class, 'index'])->name('home');
Route::get('/album-info/{id}', [ArtistAlbumController::class, 'albumInfo'])->name('album-info');
Route::get('/merch', [MerchController::class, 'merchIndex'])->name('merch');
Route::get('/merch-info/{id}', [MerchController::class, 'merchInfo'])->name('merch-info');
Route::get('/download-album/{id}', [ArtistAlbumController::class, 'downloadAlbum']);
Route::get('/search/{key}', [ArtistAlbumController::class, 'search']);
Route::get('/search/merch/{key}', [MerchController::class, 'search']);


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get("/redirectAuthenticatedUsers", [RedirectAuthenticatedUsersController::class, "home"]);

    // Route::group(['middleware' => 'checkRole:fan'], function () {
    //     Route::inertia('/fan-dashboard', 'Menus/Fan/FanDashboard')->name('fanDashboard');
    // });

    Route::group(['middleware' => 'checkRole:fan|artist'], function () {
        Route::post('checkout', [OrderController::class, 'checkout'])->name('checkout');
        Route::get('checkout-page', [OrderController::class, 'index'])->name('checkout-page');
        Route::get('invoice', [OrderController::class, 'invoiceindex'])->name('invoice');
        Route::post('add-invoice', [OrderController::class, 'createInvoice'])->name('invoice.store');
        Route::get('ongkir', [OrderController::class, 'ongkir'])->name('ongkir');
        Route::delete('/delete-order/{id}', [OrderController::class, 'destroyOrder'])->name('order.delete');
        Route::post('album-info/likeAlbum/{id}', [LikeController::class, 'likeAlbum'])->name('likeAlbum');
        Route::post('album-info/unlikeAlbum/{id}', [LikeController::class, 'unlikeAlbum'])->name('unlikeAlbum');
        Route::post('merch-info/likeMerch/{id}', [LikeController::class, 'likeMerch'])->name('likeMerch');
        Route::post('merch-info/unlikeMerch/{id}', [LikeController::class, 'unlikeMerch'])->name('unlikeMerch');
    });

    Route::group(['middleware' => 'checkRole:artist'], function () {
        Route::get('artist/dashboard', [ArtistAlbumController::class, 'artistDashboard'])->name('artist.dashboard');

        Route::controller(ArtistAlbumController::class)->group(function () {
            Route::get('/add-album', 'create')->name('add-album');
            Route::post('/add-album/store', 'store')->name('add-album.store');
            Route::get('/edit-album/{id}', 'edit')->name('edit-album');
            Route::put('/edit-album/update/{id}', 'update')->name('update-album');
            Route::delete('/delete-album/{id}',  'destroy')->name('delete-album');
        });
        Route::controller(MerchController::class)->group(function () {
            Route::get('/add-merch', 'create')->name('add-merch');
            Route::post('/add-merch/store', 'store')->name('add-merch.store');
            Route::get('/edit-merch/{id}', 'edit')->name('edit-merch');
            Route::put('/edit-merch/update/{id}', 'update')->name('update-merch');
            Route::delete('/delete-merch/{id}', 'destroy')->name('delete-merch');
        });
    });
});
require __DIR__ . '/auth.php';
