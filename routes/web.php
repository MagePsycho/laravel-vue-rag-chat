<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// RAG chat: Inertia page + NDJSON streaming endpoint, behind auth like the rest of the app.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('chat', [ChatController::class, 'index'])->name('chat');
    Route::post('chat/ask', [ChatController::class, 'ask'])->name('chat.ask');
});

// Read-only retrieval endpoint for the MCP server. Token-guarded in the controller.
Route::get('api/search', [ChatController::class, 'search'])->name('api.search');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
