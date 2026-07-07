{{-- Topbar fills its fixed header slot (left: brand width → right: 0). No margin/sticky/z-index here. --}}
@php
    use App\Support\Shell\ShellNotifications;
    use App\Support\Shell\ShellTasks;

    $user = auth()->user();
    $showNotifications = ShellNotifications::canView($user);
    $showTasks = ShellTasks::canView($user);
    $unreadNotificationCount = $showNotifications ? ShellNotifications::unreadCount($user) : 0;
    $openTaskCount = $showTasks ? ShellTasks::openCount($user) : 0;
    $recentNotifications = $showNotifications ? ShellNotifications::recent($user) : collect();
    $recentTasks = $showTasks ? ShellTasks::recent($user) : collect();
    $tasksIndexUrl = $showTasks ? ShellTasks::indexUrl($user) : null;
@endphp

<section class="flex h-full w-full items-center border-b border-teal-100/60 bg-white">
    <div class="flex h-full w-full items-center gap-3 pl-2 pr-4 sm:gap-4 sm:pr-6">
        {{-- Left: toggle + search --}}
        <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
            <x-sidebar.toggle />
            <form class="relative hidden min-w-0 max-w-xl flex-1 items-center lg:flex" role="search" aria-label="Global search">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-teal-400">
                    <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2"/><path d="M16 16l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <input type="search" class="w-full rounded-full border border-teal-100/60 bg-white py-2.5 pl-12 pr-20 text-base font-medium text-navy-900 shadow-inner placeholder-teal-900/40 transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-300/40 dark:bg-navy-900/80 dark:text-white dark:placeholder-teal-100/40" placeholder="Search leads, pages, FAQs, blog articles..." aria-label="Search">
                <span class="absolute right-4 top-1/2 flex -translate-y-1/2 items-center gap-1">
                    <span class="hidden rounded bg-teal-100/60 px-2 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200 sm:inline-block">⌘ K</span>
                    <span class="inline-block rounded bg-teal-100/60 px-2 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200 sm:hidden">CTRL + K</span>
                </span>
            </form>
        </div>

        {{-- Right: actions + user --}}
        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
            @if ($user?->canAccessPortal() || $user?->canAccessAdmin())
                <div class="hidden xl:block">
                    <livewire:business-line-switcher />
                </div>
            @endif

            @if ($showNotifications)
                <div class="relative hidden lg:block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button
                        type="button"
                        aria-label="Notifications"
                        aria-haspopup="true"
                        :aria-expanded="open.toString()"
                        @click="open = ! open"
                        class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white shadow transition hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400"
                    >
                        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><path d="M11 19a2 2 0 0 0 2-2H9a2 2 0 0 0 2 2Zm6-5V9a7 7 0 1 0-14 0v5l-2 2v1h18v-1l-2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @if ($unreadNotificationCount > 0)
                            <span class="absolute -right-1 -top-1 rounded-full border-2 border-white bg-teal-500 px-1.5 py-0.5 text-xs font-bold text-white shadow">
                                {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                            </span>
                        @endif
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute right-0 top-full z-[80] mt-3 w-80 overflow-hidden rounded-xl border border-teal-100 bg-white shadow-xl shadow-teal-950/10"
                        role="menu"
                        aria-label="Notifications"
                    >
                        <div class="flex items-center justify-between border-b border-teal-50 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">Notifications</p>
                            @if ($unreadNotificationCount > 0)
                                <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">
                                    {{ $unreadNotificationCount }} unread
                                </span>
                            @endif
                        </div>

                        <ul class="max-h-80 overflow-y-auto py-1">
                            @forelse ($recentNotifications as $notification)
                                <li>
                                    <a
                                        href="{{ ShellNotifications::readUrl($notification) }}"
                                        class="block px-4 py-3 transition hover:bg-teal-50 {{ $notification->read_at ? '' : 'bg-teal-50/70' }}"
                                        role="menuitem"
                                    >
                                        <p class="text-sm font-medium text-slate-900 {{ $notification->read_at ? '' : 'font-semibold' }}">
                                            {{ ShellNotifications::message($notification) }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-teal-700">
                                            {{ $notification->created_at?->diffForHumans() }}
                                        </p>
                                    </a>
                                </li>
                            @empty
                                <li class="px-4 py-8 text-center text-sm text-slate-500">
                                    No notifications yet.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif

            @if ($showTasks)
                <div class="relative hidden lg:block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button
                        type="button"
                        aria-label="Tasks"
                        aria-haspopup="true"
                        :aria-expanded="open.toString()"
                        @click="open = ! open"
                        class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white shadow transition hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400"
                    >
                        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><rect x="3" y="5" width="16" height="12" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 9h8M7 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        @if ($openTaskCount > 0)
                            <span class="absolute -right-1 -top-1 rounded-full border-2 border-white bg-teal-500 px-1.5 py-0.5 text-xs font-bold text-white shadow">
                                {{ $openTaskCount > 99 ? '99+' : $openTaskCount }}
                            </span>
                        @endif
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute right-0 top-full z-[80] mt-3 w-80 overflow-hidden rounded-xl border border-teal-100 bg-white shadow-xl shadow-teal-950/10"
                        role="menu"
                        aria-label="Tasks"
                    >
                        <div class="flex items-center justify-between border-b border-teal-50 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">Open tasks</p>
                            @if ($openTaskCount > 0)
                                <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">
                                    {{ $openTaskCount }}
                                </span>
                            @endif
                        </div>

                        <ul class="max-h-80 overflow-y-auto py-1">
                            @forelse ($recentTasks as $task)
                                @php
                                    $isOverdue = $task->due_at && $task->due_at->isPast();
                                @endphp
                                <li>
                                    <a
                                        href="{{ $tasksIndexUrl }}"
                                        class="block px-4 py-3 transition hover:bg-teal-50 {{ $isOverdue ? 'bg-rose-50/60' : '' }}"
                                        role="menuitem"
                                    >
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</p>
                                        <p class="mt-0.5 text-xs {{ $isOverdue ? 'font-medium text-rose-700' : 'text-teal-700' }}">
                                            @if ($task->due_at)
                                                {{ $task->due_at->format('M j · g:i A') }}
                                            @else
                                                No due date
                                            @endif
                                            @if ($task->priority)
                                                · {{ $task->priority->label() }}
                                            @endif
                                        </p>
                                    </a>
                                </li>
                            @empty
                                <li class="px-4 py-8 text-center text-sm text-slate-500">
                                    No open tasks.
                                </li>
                            @endforelse
                        </ul>

                        <div class="border-t border-teal-50 px-4 py-3">
                            <a href="{{ $tasksIndexUrl }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">
                                View all tasks
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <x-user-menu class="pl-2" />
        </div>
    </div>
</section>
