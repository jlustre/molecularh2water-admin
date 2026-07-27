<?php

use App\Http\Controllers\Admin\Crm\LeadImportExportController;
use App\Http\Controllers\Admin\Crm\QuotationPdfController;
use App\Livewire\Crm\ActivityManager;
use App\Livewire\Crm\AppointmentCalendar;
use App\Livewire\Crm\Calendar\CalendarDashboard;
use App\Livewire\Crm\CrmSettingsManager;
use App\Livewire\Crm\FunnelBoard;
use App\Livewire\Crm\FunnelManager;
use App\Livewire\Crm\LandingPageManager;
use App\Livewire\Crm\LeadForm;
use App\Livewire\Crm\LeadProfile;
use App\Livewire\Crm\CrmProductManager;
use App\Livewire\Crm\InventoryManager;
use App\Livewire\Crm\MemberSalesManager;
use App\Livewire\Crm\MySales;
use App\Livewire\Crm\Pages\ClientsIndex;
use App\Livewire\Crm\Pages\LeadsIndex;
use App\Livewire\Crm\Pages\ProspectsIndex;
use App\Livewire\Crm\Pages\RecruitsIndex;
use App\Livewire\Crm\ExecutiveDashboard;
use App\Livewire\Crm\ReportDashboard;
use App\Livewire\Crm\TaskManagement;
use App\Livewire\Crm\TaskManager;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:leads.export'])->group(function () {
    Route::get('/records/export', [LeadImportExportController::class, 'export'])->name('records.export');
});

Route::get('/quotations/{quotation}/pdf', QuotationPdfController::class)->name('quotations.pdf');

Route::middleware(['permission:leads.import'])->group(function () {
    Route::post('/records/import', [LeadImportExportController::class, 'import'])->name('records.import');
});

Route::middleware(['permission:leads.view'])->group(function () {
    Route::get('/leads', LeadsIndex::class)->name('leads.index');
});

Route::middleware(['permission:leads.create'])->group(function () {
    Route::get('/leads/create', LeadForm::class)->name('leads.create');
});

Route::middleware(['permission:leads.update'])->group(function () {
    Route::get('/leads/{lead}/edit', LeadForm::class)->name('leads.edit');
});

Route::middleware(['permission:leads.view'])->group(function () {
    Route::get('/leads/{lead}', LeadProfile::class)->name('leads.show');
});

Route::middleware(['permission:prospects.view'])->group(function () {
    Route::get('/prospects', ProspectsIndex::class)->name('prospects.index');
});

Route::middleware(['permission.any:prospects.manage,leads.create'])->group(function () {
    Route::get('/prospects/create', LeadForm::class)->name('prospects.create');
});

Route::middleware(['permission.any:prospects.manage,leads.update'])->group(function () {
    Route::get('/prospects/{lead}/edit', LeadForm::class)->name('prospects.edit');
});

Route::middleware(['permission:prospects.view'])->group(function () {
    Route::get('/prospects/{lead}', LeadProfile::class)->name('prospects.show');
});

Route::middleware(['permission:clients.view'])->group(function () {
    Route::get('/customers', ClientsIndex::class)->name('customers.index');
});

Route::middleware(['permission.any:clients.manage,leads.create'])->group(function () {
    Route::get('/customers/create', LeadForm::class)->name('customers.create');
});

Route::middleware(['permission.any:clients.manage,leads.update'])->group(function () {
    Route::get('/customers/{lead}/edit', LeadForm::class)->name('customers.edit');
});

Route::middleware(['permission:clients.view'])->group(function () {
    Route::get('/customers/{lead}', LeadProfile::class)->name('customers.show');
});

Route::middleware(['permission:recruits.view'])->group(function () {
    Route::get('/recruits', RecruitsIndex::class)->name('recruits.index');
});

Route::middleware(['permission.any:recruits.manage,leads.create'])->group(function () {
    Route::get('/recruits/create', LeadForm::class)->name('recruits.create');
});

Route::middleware(['permission.any:recruits.manage,leads.update'])->group(function () {
    Route::get('/recruits/{lead}/edit', LeadForm::class)->name('recruits.edit');
});

Route::middleware(['permission:recruits.view'])->group(function () {
    Route::get('/recruits/{lead}', LeadProfile::class)->name('recruits.show');
});

Route::get('clients/create', function () {
    $name = request()->is('admin/*') ? 'admin.crm.customers.create' : 'portal.crm.customers.create';

    return redirect()->route($name, [], 301);
});

Route::get('clients/{customerId}/edit', function (int $customerId) {
    $customer = Customer::query()->findOrFail($customerId);
    $name = request()->is('admin/*') ? 'admin.crm.customers.edit' : 'portal.crm.customers.edit';

    return redirect()->route($name, ['lead' => $customer], 301);
});

Route::get('clients/{customerId}', function (int $customerId) {
    $customer = Customer::query()->findOrFail($customerId);
    $name = request()->is('admin/*') ? 'admin.crm.customers.show' : 'portal.crm.customers.show';

    return redirect()->route($name, ['lead' => $customer], 301);
});

Route::get('clients', function () {
    $name = request()->is('admin/*') ? 'admin.crm.customers.index' : 'portal.crm.customers.index';

    return redirect()->route($name, [], 301);
});

Route::middleware(['permission:pipeline.view'])->group(function () {
    Route::get('/pipeline', FunnelBoard::class)->name('pipeline.index');
});

Route::middleware(['permission:funnel.manage'])->group(function () {
    Route::get('/funnels', FunnelManager::class)->name('funnels.index');
});

Route::middleware(['permission:activities.view'])->group(function () {
    Route::get('/activities', ActivityManager::class)->name('activities.index');
});

Route::middleware(['permission:sales.view'])->group(function () {
    Route::get('/sales', MemberSalesManager::class)->name('sales.index');
    Route::get('/member-sales', function () {
        $name = request()->is('admin/*') ? 'admin.crm.sales.index' : 'portal.crm.sales.index';

        return redirect()->route($name, [], 301);
    })->name('member-sales.index');
});

Route::middleware(['permission:products.view'])->group(function () {
    Route::get('/products', CrmProductManager::class)->name('products.index');
    Route::get('/inventory', InventoryManager::class)->name('inventory.index');
});

Route::middleware(['permission:tasks.view'])->group(function () {
    Route::get('/tasks', TaskManager::class)->name('tasks.index');
});

Route::middleware(['permission:tasks.assign'])->group(function () {
    Route::get('/task-management', TaskManagement::class)->name('task-management.index');
});

Route::middleware(['permission:portal.dashboard.view'])->group(function () {
    Route::get('/my-sales', MySales::class)->name('my-sales.index');
});

Route::middleware(['permission:calendar.view'])->group(function () {
    Route::get('/my-calendar', CalendarDashboard::class)->name('my-calendar.index');
    Route::get('/calendar', CalendarDashboard::class)->name('calendar.index');
});

Route::middleware(['permission:appointments.view'])->group(function () {
    Route::get('/appointments', AppointmentCalendar::class)->name('appointments.index');
});

Route::middleware(['permission:landing-pages.view'])->group(function () {
    Route::get('/landing-pages', LandingPageManager::class)->name('landing-pages.index');
});

Route::middleware(['permission:crm.dashboard.view'])->group(function () {
    Route::get('/dashboard', ExecutiveDashboard::class)->name('dashboard.index');
});

Route::middleware(['permission:reports.view'])->group(function () {
    Route::get('/reports', ReportDashboard::class)->name('reports.index');
});

Route::middleware(['permission:crm.settings.manage'])->group(function () {
    Route::get('/settings', CrmSettingsManager::class)->name('settings.index');
});
