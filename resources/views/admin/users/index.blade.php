@extends('layouts.admin')

@php
    $statusClasses = [
        'verified' => 'bg-emerald-50 text-emerald-700',
        'unverified' => 'bg-amber-50 text-amber-700',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:36px_36px]"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            User Directory
                        </p>
                        <h1 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Manage members, access, and account health.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                            Create users, update account details, reset passwords, filter by verification status, and remove stale accounts.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                            Add User
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total Users', 'value' => $totalUsers, 'meta' => 'All accounts'],
                ['label' => 'Verified', 'value' => $verifiedUsers, 'meta' => 'Confirmed email'],
                ['label' => 'Unverified', 'value' => $unverifiedUsers, 'meta' => 'Needs attention'],
                ['label' => 'New 30 Days', 'value' => $newUsers, 'meta' => 'Recent signups'],
            ] as $card)
                <div class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">{{ $card['meta'] }}</p>
                    <div class="mt-3 flex items-end justify-between gap-4">
                        <h2 class="text-3xl font-black text-slate-950">{{ $card['value'] }}</h2>
                        <span class="rounded-md bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ $card['label'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Manage Users</p>
                    <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">User accounts</h2>
                </div>

                <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-2 md:grid-cols-2 xl:flex">
                    <input name="search" type="search" value="{{ request('search') }}" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 xl:w-72" placeholder="Search name or email...">
                    <select name="status" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">All statuses</option>
                        <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                        <option value="unverified" @selected(request('status') === 'unverified')>Unverified</option>
                    </select>
                    <select name="joined" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">Any joined date</option>
                        <option value="7_days" @selected(request('joined') === '7_days')>Last 7 days</option>
                        <option value="30_days" @selected(request('joined') === '30_days')>Last 30 days</option>
                        <option value="90_days" @selected(request('joined') === '90_days')>Last 90 days</option>
                    </select>
                    <select name="per_page" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        @foreach ([10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Filter</button>
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Joined</th>
                            <th class="px-4 py-3">Updated</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($users as $user)
                            @php
                                $initials = collect(explode(' ', trim($user->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($part) => mb_substr($part, 0, 1))
                                    ->join('');
                                $status = $user->email_verified_at ? 'verified' : 'unverified';
                            @endphp
                            <tr class="transition hover:bg-teal-50/50">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-teal-50 text-sm font-black text-teal-800">
                                            {{ mb_strtoupper($initials ?: 'U') }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                            <p class="truncate text-xs font-medium text-teal-700">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$status] }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-500">{{ $user->created_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-4 text-slate-500">{{ $user->updated_at?->diffForHumans() }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" aria-label="Edit" title="Edit" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50 hover:text-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3 3"/>
                                            </svg>
                                            <span class="sr-only">Edit</span>
                                            <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Delete" title="Delete" @disabled(auth()->id() === $user->id) class="group relative inline-flex size-9 items-center justify-center rounded-md border border-red-100 bg-white text-red-600 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50 disabled:text-slate-300">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13"/>
                                                </svg>
                                                <span class="sr-only">Delete</span>
                                                <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <p class="text-base font-bold text-slate-900">No users found</p>
                                    <p class="mt-1 text-sm text-slate-500">Try a different search or create a new account.</p>
                                    <a href="{{ route('admin.users.create') }}" class="mt-4 inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                                        Add User
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $users->links() }}
            </div>
        </section>
    </div>
@endsection
