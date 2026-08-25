@extends('layouts.admin')

@section('content')
    <style>
        .installation-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .installation-stats {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .installation-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .installation-stats {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>

    <div class="installation-page">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-100">Installation Questionnaires</p>
                        <h1 class="mt-2 text-2xl font-black sm:text-3xl">Manage pre-installation submissions</h1>
                        <p class="mt-1 text-sm text-teal-50/75">
                            Review customer questionnaires, then assign an installer to create the installation job.
                        </p>
                    </div>
                    <a
                        class="inline-flex shrink-0 items-center justify-center rounded-md bg-teal-400 px-4 py-2.5 text-sm font-bold text-[#031a19] transition hover:bg-teal-300"
                        href="{{ $installationUrl }}"
                        rel="noreferrer"
                        target="_blank"
                    >
                        Open Public Page
                    </a>
                </div>
            </div>
        </section>

        <div class="installation-stats">
            @foreach ([
                ['label' => 'Total', 'value' => $totalSubmissions, 'meta' => 'All submissions'],
                ['label' => 'Unassigned', 'value' => $unassignedCount, 'meta' => 'Needs an installer'],
                ['label' => 'Assigned', 'value' => $assignedCount, 'meta' => 'On an installer job list'],
                ['label' => 'Last 30 Days', 'value' => $newSubmissions, 'meta' => 'Recent activity'],
            ] as $card)
                <div class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">{{ $card['meta'] }}</p>
                    <div class="mt-2 flex items-end justify-between gap-2">
                        <p class="text-2xl font-black text-slate-950">{{ $card['value'] }}</p>
                        <span class="rounded-md bg-teal-50 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-teal-700">
                            {{ $card['label'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Questionnaire Records</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Customer installation submissions</h2>
                </div>

                <form action="{{ route('admin.installation-questionnaires.index') }}" class="flex flex-wrap gap-2" method="GET">
                    <input
                        class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400"
                        name="search"
                        placeholder="Search name, email, city..."
                        type="search"
                        value="{{ request('search') }}"
                    >
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="property_type">
                        <option value="">All property types</option>
                        @foreach ($propertyTypes as $propertyType)
                            <option @selected(request('property_type') === $propertyType) value="{{ $propertyType }}">
                                {{ $propertyType }}
                            </option>
                        @endforeach
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="assignment">
                        <option value="">All assignments</option>
                        <option @selected(request('assignment') === 'unassigned') value="unassigned">Unassigned</option>
                        <option @selected(request('assignment') === 'assigned') value="assigned">Assigned</option>
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="seller_id">
                        <option value="">All sellers</option>
                        <option @selected(request('seller_id') === 'unassigned') value="unassigned">No seller</option>
                        @foreach ($sellers as $seller)
                            <option @selected((string) request('seller_id') === (string) $seller->id) value="{{ $seller->id }}">
                                {{ $seller->name }}
                            </option>
                        @endforeach
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="submitted">
                        <option value="">Any submitted date</option>
                        <option @selected(request('submitted') === '7_days') value="7_days">Last 7 days</option>
                        <option @selected(request('submitted') === '30_days') value="30_days">Last 30 days</option>
                        <option @selected(request('submitted') === '90_days') value="90_days">Last 90 days</option>
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="per_page">
                        @foreach ([10, 25, 50] as $size)
                            <option @selected((int) request('per_page', 10) === $size) value="{{ $size }}">{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700" type="submit">
                        Filter
                    </button>
                </form>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2.5">Customer</th>
                            <th class="px-3 py-2.5">Seller</th>
                            <th class="px-3 py-2.5">Location</th>
                            <th class="px-3 py-2.5">Property</th>
                            <th class="px-3 py-2.5">Installer</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">Photo</th>
                            <th class="px-3 py-2.5">Submitted</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($questionnaires as $questionnaire)
                            <tr class="hover:bg-teal-50/50">
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-slate-900">{{ $questionnaire->full_name }}</p>
                                    <p class="text-xs font-medium text-teal-700">{{ $questionnaire->email }}</p>
                                    <p class="text-xs text-slate-500">{{ $questionnaire->phone }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($questionnaire->seller)
                                        <p class="font-semibold text-slate-900">{{ $questionnaire->seller->name }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $questionnaire->city }}, {{ $questionnaire->state }} {{ $questionnaire->postal_code }}
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ $questionnaire->property_type }}</td>
                                <td class="px-3 py-3">
                                    @if ($questionnaire->installer)
                                        <p class="font-semibold text-slate-900">{{ $questionnaire->installer->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ collect([$questionnaire->installer->company, $questionnaire->installer->locationSummary()])->filter()->implode(' · ') ?: 'Assigned' }}
                                        </p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                        @if (auth()->user()?->hasPermission('installation-questionnaires.manage') && $assignableInstallers->isNotEmpty())
                                            <form
                                                class="mt-2 flex min-w-[14rem] flex-col gap-1.5"
                                                method="POST"
                                                action="{{ route('admin.installation-questionnaires.assign-installer', $questionnaire) }}"
                                            >
                                                @csrf
                                                <select
                                                    class="rounded-md border-teal-100 text-xs text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                    name="installer_id"
                                                    required
                                                >
                                                    <option value="">Choose installer</option>
                                                    @foreach ($assignableInstallers as $installer)
                                                        <option value="{{ $installer->id }}">
                                                            {{ $installer->name }}@if($installer->locationSummary()) — {{ $installer->locationSummary() }}@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="inline-flex items-center justify-center rounded-md bg-teal-600 px-2.5 py-1.5 text-xs font-bold text-white transition hover:bg-teal-700" type="submit">
                                                    Assign
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($questionnaire->assignment_response)
                                        @include('admin.installation-questionnaires._response_badge', [
                                            'response' => $questionnaire->assignment_response,
                                            'size' => 'sm',
                                        ])
                                    @elseif ($questionnaire->isAssigned())
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-emerald-700">
                                            Assigned
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-amber-800">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-slate-500">
                                    @php $photoCount = count($questionnaire->sinkPhotoItems()); @endphp
                                    {{ $photoCount > 0 ? $photoCount : 'No' }}
                                </td>
                                <td class="px-3 py-3 text-slate-500">{{ $questionnaire->created_at?->format('M j, Y') }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            aria-label="View"
                                            class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 text-teal-700 hover:bg-teal-50"
                                            href="{{ route('admin.installation-questionnaires.show', $questionnaire) }}"
                                            title="View"
                                        >
                                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        @if (auth()->user()?->hasPermission('installation-questionnaires.manage'))
                                            <a
                                                aria-label="{{ $questionnaire->isAssigned() ? 'Update installer' : 'Assign installer' }}"
                                                class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 text-teal-700 hover:bg-teal-50"
                                                href="{{ route('admin.installation-questionnaires.show', $questionnaire) }}#assign-installer"
                                                title="{{ $questionnaire->isAssigned() ? 'Update installer' : 'Assign installer' }}"
                                            >
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M4.5 20.25a7.5 7.5 0 0 1 15 0" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </a>
                                        @endif
                                        <a
                                            aria-label="Edit"
                                            class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 text-teal-700 hover:bg-teal-50"
                                            href="{{ route('admin.installation-questionnaires.edit', $questionnaire) }}"
                                            title="Edit"
                                        >
                                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="m14 7 3 3" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.installation-questionnaires.destroy', $questionnaire) }}" method="POST" onsubmit="return confirm('Delete this installation questionnaire?');">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                aria-label="Delete"
                                                class="inline-flex size-9 items-center justify-center rounded-md border border-red-100 text-red-600 hover:bg-red-50"
                                                title="Delete"
                                                type="submit"
                                            >
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-10 text-center text-slate-500" colspan="8">
                                    <p class="font-bold text-slate-900">No installation questionnaires yet</p>
                                    <p class="mt-1 text-sm">Customer submissions from the public installation page will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $questionnaires->links() }}
            </div>
        </section>
    </div>
@endsection
