<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::apiResource('notes', NoteController::class);
Route::post('notes/{note}/notarize', [NoteController::class, 'notarize']);
