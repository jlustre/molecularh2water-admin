<?php

use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\InstallationQuestionnaireController;
use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\LandingPageController;
use App\Http\Controllers\Api\MediaResourceController;
use App\Http\Controllers\Api\ProspectController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\WarrantyRegistrationController;
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

Route::get('/faqs', [FaqController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.faqs.index');

Route::get('/site-settings', SiteSettingsController::class)
    ->middleware('throttle:60,1')
    ->name('api.site-settings.show');

Route::get('/warranty-registrations/check-serial', [WarrantyRegistrationController::class, 'checkSerial'])
    ->middleware('throttle:30,1')
    ->name('api.warranty-registrations.check-serial');

Route::post('/warranty-registrations', [WarrantyRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.warranty-registrations.store');

Route::post('/installation-questionnaires', [InstallationQuestionnaireController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.installation-questionnaires.store');

Route::post('/prospects', [ProspectController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.prospects.store');

Route::prefix('crm')->name('api.crm.')->group(function () {
    Route::get('/landing-pages/{slug}', [LandingPageController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('landing-pages.show');

    Route::get('/leads/check-email', [LeadCaptureController::class, 'checkEmail'])
        ->middleware('throttle:30,1')
        ->name('leads.check-email');

    Route::post('/leads', [LeadCaptureController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('leads.store');
});
