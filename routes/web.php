<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ResourcesController;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('resources', [ResourcesController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('resources');

Route::get('media/{mediaItem}/open', [ResourcesController::class, 'open'])
    ->name('media.open');

Route::get('media/{mediaItem}', [ResourcesController::class, 'show'])
    ->name('media.show');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

// Admin routes
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        // Placeholder routes for sidebar navigation
        Route::view('/leads', 'admin.placeholders.leads')->name('leads');
        Route::view('/faqs', 'admin.placeholders.faqs')->name('faqs');
        Route::view('/testimonials', 'admin.placeholders.testimonials')->name('testimonials');
        Route::view('/blog', 'admin.placeholders.blog')->name('blog');
        Route::view('/pages', 'admin.placeholders.pages')->name('pages');
        Route::post('/media/update-seeder', [MediaController::class, 'updateSeeder'])->name('media.update-seeder');
        Route::get('/media/{medium}/view-pdf', [MediaController::class, 'viewPdf'])->name('media.view-pdf');
        Route::resource('/media', MediaController::class)->except('show');
        Route::view('/settings', 'admin.placeholders.settings')->name('settings');
        Route::post('/settings/sidebar-design', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'sidebar_design' => 'required|in:design1,design2,design3',
            ]);
            session(['sidebar_design' => $request->sidebar_design]);
            session()->flash('sidebar_design_updated', true);
            return redirect()->route('admin.settings');
        })->name('settings.sidebar-design');
            Route::view('/contact-messages', 'admin.placeholders.contact-messages')->name('contact-messages');
            Route::view('/appointments', 'admin.placeholders.appointments')->name('appointments');
        Route::resource('/users', UserController::class)->except('show');
        Route::resource('/roles', RoleController::class)->except('show');
    });
