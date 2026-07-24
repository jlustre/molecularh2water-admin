@php
    $columns = collect([
        $showPipeline ?? false,
        $showEvents ?? false,
        $showTasks ?? false,
    ])->filter()->count();

    $gridClass = match ($columns) {
        3 => 'xl:grid-cols-3',
        2 => 'xl:grid-cols-2',
        default => 'xl:grid-cols-1',
    };
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div @class(['grid grid-cols-1 divide-y divide-slate-200', $gridClass, 'xl:divide-x xl:divide-y-0' => $columns > 1])>
        @if ($showPipeline)
            <div class="p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="text-base font-bold text-slate-900">Pipeline Summary</h3>
                    <a class="text-xs font-semibold text-teal-700 hover:text-teal-900" href="{{ route(\App\Support\Crm\CrmRoutes::name('pipeline.index')) }}">
                        Open Board
                    </a>
                </div>

                @if ($crmDetail['funnelStages']->isEmpty())
                    <p class="text-sm text-slate-500">Pipeline stages will appear once CRM data is available.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($crmDetail['groupedFunnelStages'] ?? [] as $group)
                            <div class="space-y-2">
                                <h4 class="text-sm font-bold uppercase tracking-wide text-teal-800">{{ $group['label'] }}</h4>
                                <div class="space-y-2">
                                    @foreach ($group['stages'] as $stage)
                                        <div>
                                            <div class="mb-1 flex items-center justify-between gap-2 text-xs sm:text-sm">
                                                <span class="font-medium text-slate-700">{{ $stage->name }}</span>
                                                <div class="flex shrink-0 items-center gap-1.5">
                                                    <span class="text-slate-500">{{ $stage->leads_count }}</span>
                                                    @if ($stage->leads_count > 0 && ($pipelineInteractive ?? true))
                                                        <button
                                                            type="button"
                                                            wire:click="$dispatch('open-pipeline-stage-leads', { stageId: {{ $stage->id }} })"
                                                            class="inline-flex items-center rounded-md p-1 text-teal-700 transition hover:bg-teal-50 hover:text-teal-900"
                                                            title="View leads in {{ $stage->name }}"
                                                            aria-label="View {{ $stage->leads_count }} leads in {{ $stage->name }}"
                                                        >
                                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($stage->leads_count > 0)
                                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                    <div
                                                        class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500"
                                                        style="width: {{ ($crmDetail['totalLeads'] ?? 0) > 0 ? max(4, ($stage->leads_count / max($crmDetail['totalLeads'], 1)) * 100) : 0 }}%"
                                                    ></div>
                                                </div>
                                            @else
                                                <div class="h-1.5 rounded-full bg-slate-100" aria-hidden="true"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if ($showEvents)
            <div class="p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="text-base font-bold text-slate-900">Upcoming Events</h3>
                    <a class="text-xs font-semibold text-teal-700 hover:text-teal-900" href="{{ route(\App\Support\Crm\CrmRoutes::name('calendar.index')) }}">
                        Open Calendar
                    </a>
                </div>

                <div class="space-y-2">
                    @forelse ($crmDetail['upcomingEvents'] as $entry)
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                            <p class="text-sm font-semibold leading-5 text-slate-800">{{ $entry->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $entry->type_name }}
                                · {{ $entry->start_at?->format('M j, g:i A') }}
                            </p>
                            @if (! empty($entry->lead_name))
                                <p class="mt-1 text-xs text-slate-400">{{ $entry->lead_name }}</p>
                            @elseif (! empty($entry->meeting_link))
                                <p class="mt-1 truncate text-xs text-teal-700">Zoom / online meeting</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ $eventsEmptyMessage ?? 'No upcoming shows, demos, or meetings on your calendar.' }}</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if ($showTasks)
            <div class="p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="text-base font-bold text-slate-900">Upcoming Tasks</h3>
                    <a class="text-xs font-semibold text-teal-700 hover:text-teal-900" href="{{ route(\App\Support\Crm\CrmRoutes::name('calendar.index')) }}">
                        Open Calendar
                    </a>
                </div>

                <div class="space-y-2">
                    @forelse ($crmDetail['upcomingCalendarTasks'] as $entry)
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                            <p class="text-sm font-semibold leading-5 text-slate-800">{{ $entry->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $entry->type_name }}
                                · {{ $entry->start_at?->format('M j, g:i A') }}
                            </p>
                            @if (! empty($entry->lead_name))
                                <p class="mt-1 text-xs text-slate-400">{{ $entry->lead_name }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ $tasksEmptyMessage ?? 'No upcoming calls, invites, or follow-up tasks scheduled.' }}</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
