@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Global Search</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">
                @if ($q !== '')
                    Results for “{{ $q }}”
                @else
                    Search the workspace
                @endif
            </h1>
            <form method="GET" action="{{ route('search') }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
                <input
                    type="search"
                    name="q"
                    value="{{ $q }}"
                    autofocus
                    placeholder="Search leads, prospects, FAQs, blog, media, users…"
                    class="w-full rounded-full border border-teal-100 px-4 py-2.5 text-sm shadow-sm focus:border-teal-400 focus:ring-teal-400"
                >
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-700">Search</button>
            </form>
            @if ($q !== '' && strlen($q) < 2)
                <p class="mt-3 text-sm text-amber-700">Enter at least 2 characters.</p>
            @elseif ($q !== '')
                <p class="mt-3 text-sm text-slate-500">{{ $total }} result{{ $total === 1 ? '' : 's' }}</p>
            @endif
        </section>

        @if ($q !== '' && strlen($q) >= 2)
            @foreach ([
                'leads' => ['label' => 'Leads', 'route' => $crmPrefix.'leads.show'],
                'prospects' => ['label' => 'Prospects', 'route' => $crmPrefix.'prospects.show'],
                'customers' => ['label' => 'Customers', 'route' => $crmPrefix.'customers.show'],
            ] as $key => $meta)
                @if ($results[$key]->isNotEmpty())
                    <section class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">{{ $meta['label'] }}</h2>
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($results[$key] as $item)
                                <li>
                                    <a href="{{ route($meta['route'], $item) }}" class="block py-3 transition hover:bg-teal-50/60">
                                        <p class="font-semibold text-slate-900">{{ trim($item->first_name.' '.$item->last_name) }}</p>
                                        <p class="text-xs text-slate-500">{{ $item->email }} @if($item->phone)· {{ $item->phone }}@endif</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endforeach

            @if ($results['faqs']->isNotEmpty())
                <section class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">FAQs</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($results['faqs'] as $faq)
                            <li>
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="block py-3 hover:bg-teal-50/60">
                                    <p class="font-semibold text-slate-900">{{ $faq->question }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['blog']->isNotEmpty())
                <section class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Blog</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($results['blog'] as $post)
                            <li>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="block py-3 hover:bg-teal-50/60">
                                    <p class="font-semibold text-slate-900">{{ $post->title }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['media']->isNotEmpty())
                <section class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Media</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($results['media'] as $item)
                            <li>
                                <a href="{{ route('admin.media.edit', $item) }}" class="block py-3 hover:bg-teal-50/60">
                                    <p class="font-semibold text-slate-900">{{ $item->title }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['users']->isNotEmpty())
                <section class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Users</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($results['users'] as $person)
                            <li>
                                <a href="{{ route('admin.users.edit', $person) }}" class="block py-3 hover:bg-teal-50/60">
                                    <p class="font-semibold text-slate-900">{{ $person->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $person->email }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($results['forms']->isNotEmpty())
                <section class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Website Form Submissions</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach ($results['forms'] as $submission)
                            <li>
                                <a href="{{ route('admin.website-forms.show', [$submission->form_type->routeKey(), $submission]) }}" class="block py-3 hover:bg-teal-50/60">
                                    <p class="font-semibold text-slate-900">{{ $submission->displayName() }}</p>
                                    <p class="text-xs text-slate-500">{{ $submission->form_type->label() }} · {{ $submission->email }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($total === 0)
                <section class="rounded-lg border border-dashed border-teal-200 bg-teal-50/40 px-6 py-12 text-center">
                    <p class="text-base font-bold text-slate-900">No matches</p>
                    <p class="mt-1 text-sm text-slate-500">Try a different name, email, or keyword.</p>
                </section>
            @endif
        @endif
    </div>
@endsection
