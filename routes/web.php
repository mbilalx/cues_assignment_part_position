<?php

use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

Route::get('/sort', function () {
    for ($i = 1; $i <= 5; $i++) {
        $pos = 1;
        foreach (\App\Models\Part::where('episode_id', $i)->orderBy('id')->get() as $part) {
            $part->update(['position' => $pos]);
            $pos++;
        }
    }
});

Route::get('/test-create', function () {
    return (new \App\Http\Controllers\PartController(new \App\Services\PartService()))->store(new App\Http\Requests\PartRequest([
        'episode_id' => fake()->numberBetween(1, 5),
        'title' => fake()->sentence(3),
        'position' => fake()->numberBetween(1, 50)
    ]));
});

Route::get('/test-update', function () {
    $part = \App\Models\Part::query()->where('episode_id', 1)
        ->inRandomOrder()->first();
    if ($part) {
        return (new \App\Http\Controllers\PartController(new \App\Services\PartService()))->update(new App\Http\Requests\PartRequest([
            'title' => fake()->sentence(3),
            'position' => fake()->numberBetween(1, 50)
        ]), $part);
    }
});

Route::get('/test-sort', function () {
    $part = \App\Models\Part::query()->inRandomOrder()->first();
    return (new \App\Http\Controllers\PartController(new \App\Services\PartService()))->update(new App\Http\Requests\PartRequest([
        'position' => fake()->numberBetween(1, 50)
    ]), $part);
});

Route::get('/test-delete', function () {
    $part = \App\Models\Part::query()->where('episode_id', fake()->numberBetween(1, 5))
        ->inRandomOrder()->first();
    if ($part){
        return (new \App\Http\Controllers\PartController(new \App\Services\PartService()))->destroy($part);
    }
});
