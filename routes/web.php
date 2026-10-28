<?php

use App\Http\Controllers\EntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EntryController::class, 'home'])->name('home');

// Every other address is looked up as an entry's path. A fallback route only
// runs when no other route matches, so it never shadows the admin panel.
Route::fallback([EntryController::class, 'show']);
