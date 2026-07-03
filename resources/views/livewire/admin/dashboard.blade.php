<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Overview of content operations and CRM performance.</p>
    </div>

    @if (auth()->user()?->hasPermission('crm.dashboard.view'))
        <livewire:crm.dashboard-stats />
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">FAQs</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">0</h2>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Testimonials</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">0</h2>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Articles</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">0</h2>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Media Items</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ \App\Models\MediaItem::count() }}</h2>
        </div>
    </div>
</div>
