<?php

use App\Http\Controllers\Api\MediaResourceController;
use Illuminate\Support\Facades\Route;

Route::prefix('resources')
    ->name('api.resources.')
    ->group(function () {
        Route::get('/documents', [MediaResourceController::class, 'documents'])->name('documents');
        Route::get('/videos', [MediaResourceController::class, 'videos'])->name('videos');
        Route::get('/links', [MediaResourceController::class, 'links'])->name('links');
        Route::get('/images', [MediaResourceController::class, 'images'])->name('images');
        Route::get('/downloads', [MediaResourceController::class, 'downloads'])->name('downloads');
        Route::get('/embedded', [MediaResourceController::class, 'embedded'])->name('embedded');
    });
