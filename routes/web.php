<?php

use App\Livewire\Portal\MemberHierarchy;
use App\Livewire\Portal\RegistrationInvites;
use App\Livewire\Portal\Dashboard as PortalDashboard;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarrantyRegistrationController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\NotificationReadController;
use App\Http\Controllers\ResourcesController;

Route::view('/', 'welcome');

// Serve avatars from storage/app/public without requiring public/storage symlink.
Route::get('avatars/{filename}', AvatarController::class)
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('avatars.show');

Route::get('dashboard', PortalDashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('resources', [ResourcesController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('resources');

Route::middleware(['auth', 'verified', 'permission:invites.manage'])
    ->get('invites', RegistrationInvites::class)
    ->name('portal.invites');

Route::middleware(['auth', 'verified', 'permission:sponsors.view-tree'])
    ->get('team', MemberHierarchy::class)
    ->name('portal.team');

Route::get('media/{mediaItem}/open', [ResourcesController::class, 'open'])
    ->name('media.open');

Route::get('media/{mediaItem}/thumbnail', [ResourcesController::class, 'thumbnail'])
    ->name('media.thumbnail');

Route::get('media/{mediaItem}', [ResourcesController::class, 'show'])
    ->name('media.show');

Route::view('profile', 'profile', ['header' => 'Profile', 'title' => 'Profile'])
    ->middleware(['auth'])
    ->name('profile');

Route::get('notifications/{notification}', NotificationReadController::class)
    ->middleware(['auth'])
    ->name('notifications.read');

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])
    ->prefix('crm')
    ->name('portal.crm.')
    ->group(function () {
        require base_path('routes/crm.php');
    });

// Admin routes
Route::middleware(['auth', 'admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        Route::prefix('crm')
            ->name('crm.')
            ->group(function () {
                require base_path('routes/crm.php');
            });

        // Legacy placeholder — redirects to CRM leads when permitted
        Route::get('/leads', function () {
            if (auth()->user()?->hasPermission('leads.view')) {
                return redirect()->route('admin.crm.leads.index');
            }

            return view('admin.placeholders.leads');
        })->name('leads');

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
            Route::get('/appointments', function () {
                if (auth()->user()?->hasPermission('appointments.view')) {
                    return redirect()->route('admin.crm.appointments.index');
                }

                return view('admin.placeholders.appointments');
            })->name('appointments');
        Route::resource('/warranty-registrations', WarrantyRegistrationController::class)
            ->except(['create', 'store']);
        Route::post('/users/update-seeder', [UserController::class, 'updateSeeder'])
            ->middleware('permission:users.export')
            ->name('users.update-seeder');
        Route::resource('/users', UserController::class)->except('show');
        Route::get('/users-hierarchy', [UserController::class, 'hierarchy'])->name('users.hierarchy');
        Route::resource('/roles', RoleController::class)->except('show');
    });
