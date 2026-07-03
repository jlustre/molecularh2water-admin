<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">CRM Marketing</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Landing Pages</h1>
            <p class="mt-1 text-sm text-slate-500">Build published capture pages, track conversions, and wire forms to the CRM funnel.</p>
        </div>
        @if (auth()->user()?->hasPermission('landing-pages.manage'))
            <button
                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                type="button"
                wire:click="openForm"
            >
                New Landing Page
            </button>
        @endif
    </div>

    @error('page')
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="mb-4">
        <input
            class="w-full max-w-md rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Search landing pages..."
            type="search"
            wire:model.live.debounce.300ms="search"
        />
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($pages as $page)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="landing-page-{{ $page->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $page->title }}</h2>
                        <p class="mt-1 text-xs text-slate-500">/{{ $page->slug }}</p>
                    </div>
                    <span @class([
                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-100 text-emerald-800' => $page->is_published,
                        'bg-slate-100 text-slate-600' => ! $page->is_published,
                    ])>
                        {{ $page->is_published ? 'Published' : 'Draft' }}
                    </span>
                </div>
                @if ($page->headline)
                    <p class="mt-3 text-sm font-medium text-slate-800">{{ $page->headline }}</p>
                @endif
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conversions</dt>
                        <dd class="font-bold text-slate-900">{{ number_format($page->conversion_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Funnel</dt>
                        <dd class="text-slate-700">{{ $page->funnel?->name ?? 'Default' }}</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-slate-500">API: <code class="rounded bg-slate-50 px-1">/api/crm/landing-pages/{{ $page->slug }}</code></p>
                @if (auth()->user()?->hasPermission('landing-pages.manage'))
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="text-sm font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="openForm({{ $page->id }})">Edit</button>
                        <button class="text-sm font-semibold text-slate-700 hover:text-slate-900" type="button" wire:click="togglePublish({{ $page->id }})">
                            {{ $page->is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                        <button class="text-sm font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deletePage({{ $page->id }})" wire:confirm="Delete this landing page?">Delete</button>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">
                No landing pages yet. Create one to start capturing leads from campaigns.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $pages->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">{{ $editingPageId ? 'Edit Landing Page' : 'New Landing Page' }}</h3>
                <form class="mt-4 grid gap-4 sm:grid-cols-2" wire:submit="save">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Title *</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="title" />
                        @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Slug</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" placeholder="auto-generated" type="text" wire:model="slug" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Tracking Source</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="tracking_source" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Headline</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="headline" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Subheadline</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="subheadline" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Hero Media URL</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="hero_media" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">CTA Label</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="cta_label" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">CTA / Redirect URL</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="url" wire:model="cta_url" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Thank You Headline</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="thank_you_headline" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Thank You Body</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="thank_you_body"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Funnel</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="funnel_id">
                            <option value="">Default funnel</option>
                            @foreach ($funnels as $funnel)
                                <option value="{{ $funnel->id }}">{{ $funnel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Lead Assignment</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="assignment">
                            <option value="round_robin">Round robin (consultants)</option>
                            <option value="none">Unassigned (admin pool)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Post-submit Redirect URL</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" placeholder="Optional override" type="url" wire:model="redirect_url" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="is_published" />
                            Publish immediately
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700" type="button" wire:click="closeForm">Cancel</button>
                        <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Save Page</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
