<?php

use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\PartController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::prefix('episodes')->name('episodes.')->group(function () {
    // Episode Parts Routes
    Route::resource('parts', PartController::class)->except(['create', 'edit']);
    Route::patch('parts/sort/{part}', [PartController::class, 'sort'])->name('parts.sort');

    // Episodes Routes
    Route::get('/', [EpisodeController::class, 'index'])->name('index');
    Route::post('store', [EpisodeController::class, 'store'])->name('store');
    Route::get('{episode}', [EpisodeController::class, 'show'])->name('show');
    Route::put('{episode}', [EpisodeController::class, 'update'])->name('update');
    Route::delete('{episode}', [EpisodeController::class, 'destroy'])->name('destroy');
});
