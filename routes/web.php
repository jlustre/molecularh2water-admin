<?php

use App\Livewire\Portal\MemberHierarchy;
use App\Livewire\Portal\RegistrationInvites;
use App\Livewire\Portal\Dashboard as PortalDashboard;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\EmailMappingController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InstallationQuestionnaireController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\WebsiteContentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarrantyRegistrationController;
use App\Http\Controllers\Admin\WebsiteFormSubmissionController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\NotificationReadController;
use App\Http\Controllers\ResourcesController;

Route::view('/', 'welcome');

// Serve avatars from storage/app/public without requiring public/storage symlink.
Route::get('avatars/{filename}', AvatarController::class)
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('avatars.show');

Route::get('search', GlobalSearchController::class)
    ->middleware(['auth'])
    ->name('search');

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

Route::middleware(['auth', 'verified'])
    ->get('team/{user}', \App\Livewire\Portal\MemberOverview::class)
    ->name('portal.team.member');

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

        Route::middleware('permission:blog.manage')
            ->resource('/blog', BlogPostController::class)
            ->except('show')
            ->parameters(['blog' => 'blogPost']);
        Route::resource('/faqs', FaqController::class)->except('show');
        Route::post('/faqs/{faq}/move-up', [FaqController::class, 'moveUp'])->name('faqs.move-up');
        Route::post('/faqs/{faq}/move-down', [FaqController::class, 'moveDown'])->name('faqs.move-down');
        Route::post('/media/update-seeder', [MediaController::class, 'updateSeeder'])->name('media.update-seeder');
        Route::get('/media/{medium}/view-pdf', [MediaController::class, 'viewPdf'])->name('media.view-pdf');
        Route::resource('/media', MediaController::class)->except('show');
        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
            Route::match(['put', 'patch'], '/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('/website-content', [WebsiteContentController::class, 'edit'])->name('website-content.edit');
            Route::match(['put', 'patch'], '/website-content', [WebsiteContentController::class, 'update'])->name('website-content.update');
        });
        Route::middleware('permission:email-mappings.view')->group(function () {
            Route::get('/email-mappings', [EmailMappingController::class, 'index'])
                ->name('email-mappings.index');
        });
        Route::middleware('permission:email-mappings.manage')->group(function () {
            Route::get('/email-mappings/create', [EmailMappingController::class, 'create'])
                ->name('email-mappings.create');
            Route::post('/email-mappings', [EmailMappingController::class, 'store'])
                ->name('email-mappings.store');
            Route::get('/email-mappings/{email_mapping}/edit', [EmailMappingController::class, 'edit'])
                ->name('email-mappings.edit');
            Route::match(['put', 'patch'], '/email-mappings/{email_mapping}', [EmailMappingController::class, 'update'])
                ->name('email-mappings.update');
            Route::delete('/email-mappings/{email_mapping}', [EmailMappingController::class, 'destroy'])
                ->name('email-mappings.destroy');
        });
        Route::post('/email-mappings/update-seeder', [EmailMappingController::class, 'updateSeeder'])
            ->name('email-mappings.update-seeder');
        Route::get('/contact-messages', function () {
            return redirect()->route('admin.website-forms.index', 'contact-us');
        })->name('contact-messages');
        Route::get('/appointments', function () {
            if (auth()->user()?->hasPermission('appointments.view')) {
                return redirect()->route('admin.crm.appointments.index');
            }

            return view('admin.placeholders.appointments');
        })->name('appointments');
        Route::middleware('permission:website-forms.view')->prefix('website-forms/{formType}')->group(function () {
            Route::get('/', [WebsiteFormSubmissionController::class, 'index'])->name('website-forms.index');
        });
        Route::middleware('permission:website-forms.manage')->prefix('website-forms/{formType}')->group(function () {
            Route::get('/create', [WebsiteFormSubmissionController::class, 'create'])->name('website-forms.create');
            Route::post('/', [WebsiteFormSubmissionController::class, 'store'])->name('website-forms.store');
        });
        Route::middleware('permission:website-forms.view')->prefix('website-forms/{formType}')->group(function () {
            Route::get('/{websiteFormSubmission}', [WebsiteFormSubmissionController::class, 'show'])
                ->whereNumber('websiteFormSubmission')
                ->name('website-forms.show');
        });
        Route::middleware('permission:website-forms.manage')->prefix('website-forms/{formType}')->group(function () {
            Route::get('/{websiteFormSubmission}/edit', [WebsiteFormSubmissionController::class, 'edit'])
                ->whereNumber('websiteFormSubmission')
                ->name('website-forms.edit');
            Route::match(['put', 'patch'], '/{websiteFormSubmission}', [WebsiteFormSubmissionController::class, 'update'])
                ->whereNumber('websiteFormSubmission')
                ->name('website-forms.update');
            Route::post('/{websiteFormSubmission}/convert-to-prospect', [WebsiteFormSubmissionController::class, 'convertToProspect'])
                ->whereNumber('websiteFormSubmission')
                ->name('website-forms.convert-to-prospect');
            Route::delete('/{websiteFormSubmission}', [WebsiteFormSubmissionController::class, 'destroy'])
                ->whereNumber('websiteFormSubmission')
                ->name('website-forms.destroy');
        });
        Route::post('/warranty-registrations/update-seeder', [WarrantyRegistrationController::class, 'updateSeeder'])
            ->middleware('permission:warranty.export')
            ->name('warranty-registrations.update-seeder');
        Route::middleware('permission:warranty.view')->group(function () {
            Route::get('/warranty-registrations', [WarrantyRegistrationController::class, 'index'])
                ->name('warranty-registrations.index');
            Route::get('/warranty-registrations/{warranty_registration}', [WarrantyRegistrationController::class, 'show'])
                ->name('warranty-registrations.show');
        });
        Route::middleware('permission:warranty.manage')->group(function () {
            Route::get('/warranty-registrations/{warranty_registration}/edit', [WarrantyRegistrationController::class, 'edit'])
                ->name('warranty-registrations.edit');
            Route::match(['put', 'patch'], '/warranty-registrations/{warranty_registration}', [WarrantyRegistrationController::class, 'update'])
                ->name('warranty-registrations.update');
            Route::delete('/warranty-registrations/{warranty_registration}', [WarrantyRegistrationController::class, 'destroy'])
                ->name('warranty-registrations.destroy');
        });
        Route::middleware('permission:installation-questionnaires.view')->group(function () {
            Route::get('/installation-questionnaires', [InstallationQuestionnaireController::class, 'index'])
                ->name('installation-questionnaires.index');
            Route::get('/installation-questionnaires/{installation_questionnaire}', [InstallationQuestionnaireController::class, 'show'])
                ->name('installation-questionnaires.show');
        });
        Route::middleware('permission:installation-questionnaires.manage')->group(function () {
            Route::get('/installation-questionnaires/{installation_questionnaire}/edit', [InstallationQuestionnaireController::class, 'edit'])
                ->name('installation-questionnaires.edit');
            Route::match(['put', 'patch'], '/installation-questionnaires/{installation_questionnaire}', [InstallationQuestionnaireController::class, 'update'])
                ->name('installation-questionnaires.update');
            Route::delete('/installation-questionnaires/{installation_questionnaire}', [InstallationQuestionnaireController::class, 'destroy'])
                ->name('installation-questionnaires.destroy');
        });
        Route::post('/users/update-seeder', [UserController::class, 'updateSeeder'])
            ->middleware('permission:users.export')
            ->name('users.update-seeder');
        Route::resource('/users', UserController::class)->except('show');
        Route::get('/users-hierarchy', [UserController::class, 'hierarchy'])->name('users.hierarchy');
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        });
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });
    });
