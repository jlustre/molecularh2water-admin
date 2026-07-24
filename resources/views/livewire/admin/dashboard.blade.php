<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Operational snapshot of content, inboxes, and CRM activity.</p>
    </div>

    @php
        use App\Enums\WebsiteFormSubmissionStatus;
        use App\Models\BlogPost;
        use App\Models\Faq;
        use App\Models\MediaItem;
        use App\Models\WebsiteFormSubmission;
        use Illuminate\Support\Facades\Schema;

        $faqCount = Schema::hasTable('faqs') ? Faq::query()->count() : 0;
        $blogCount = Schema::hasTable('blog_posts') ? BlogPost::query()->count() : 0;
        $publishedBlogCount = Schema::hasTable('blog_posts')
            ? BlogPost::query()->where('status', 'published')->count()
            : 0;
        $mediaCount = Schema::hasTable('media_items') ? MediaItem::query()->count() : 0;
        $newFormCount = Schema::hasTable('website_form_submissions')
            ? WebsiteFormSubmission::query()->where('status', WebsiteFormSubmissionStatus::New)->count()
            : 0;
        $formCount = Schema::hasTable('website_form_submissions')
            ? WebsiteFormSubmission::query()->count()
            : 0;
    @endphp

    @if (auth()->user()?->hasPermission('crm.dashboard.view'))
        <livewire:crm.dashboard-stats />
    @endif

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if (auth()->user()?->hasPermission('faqs.manage'))
            <a href="{{ route('admin.faqs.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                <p class="text-sm text-slate-500">FAQs</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $faqCount }}</h2>
                <p class="mt-2 text-xs font-semibold text-teal-700">Manage FAQ library →</p>
            </a>
        @endif

        @if (auth()->user()?->hasPermission('blog.manage'))
            <a href="{{ route('admin.blog.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                <p class="text-sm text-slate-500">Blog articles</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $blogCount }}</h2>
                <p class="mt-2 text-xs text-slate-500">{{ $publishedBlogCount }} published</p>
            </a>
        @endif

        @if (auth()->user()?->hasPermission('media.view'))
            <a href="{{ route('admin.media.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                <p class="text-sm text-slate-500">Media items</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $mediaCount }}</h2>
                <p class="mt-2 text-xs font-semibold text-teal-700">Open media library →</p>
            </a>
        @endif

        @if (auth()->user()?->hasPermission('website-forms.view'))
            <a href="{{ route('admin.website-forms.index', 'contact-us') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                <p class="text-sm text-slate-500">Website form inbox</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $formCount }}</h2>
                <p class="mt-2 text-xs {{ $newFormCount > 0 ? 'font-semibold text-amber-700' : 'text-slate-500' }}">
                    {{ $newFormCount }} new awaiting follow-up
                </p>
            </a>
        @endif
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-3">
        @if (auth()->user()?->hasPermission('leads.view'))
            <a href="{{ route('admin.crm.leads.index') }}" class="rounded-2xl border border-teal-100 bg-gradient-to-br from-teal-50 to-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-teal-700">CRM</p>
                <h3 class="mt-2 text-lg font-black text-slate-950">Work leads</h3>
                <p class="mt-1 text-sm text-slate-600">Open the lead queue and assign follow-ups.</p>
            </a>
        @endif
        @if (auth()->user()?->hasPermission('calendar.view'))
            <a href="{{ route('admin.crm.calendar.index') }}" class="rounded-2xl border border-teal-100 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">Schedule</p>
                <h3 class="mt-2 text-lg font-black text-slate-950">Team calendar</h3>
                <p class="mt-1 text-sm text-slate-600">Calls, demos, and meetings in one place.</p>
            </a>
        @endif
        @if (auth()->user()?->hasPermission('reports.view'))
            <a href="{{ route('admin.crm.reports.index') }}" class="rounded-2xl border border-teal-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Insights</p>
                <h3 class="mt-2 text-lg font-black text-slate-950">Reports</h3>
                <p class="mt-1 text-sm text-slate-600">Export performance metrics for the team.</p>
            </a>
        @endif
    </div>
</div>
