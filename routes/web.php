<?php

use App\Http\Controllers\EntryController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EntryController::class, 'home'])->name('home');

Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:10,1')->name('setup.store');

Route::post('/forms/{form:slug}', [FormSubmissionController::class, 'store'])
    ->middleware('throttle:form-submissions')
    ->name('forms.submit');

// Every other address is looked up as an entry's path. A fallback route only
// runs when no other route matches, so it never shadows the admin panel.
Route::fallback([EntryController::class, 'show']);
