@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-100">Sponsor Network</p>
                        <h1 class="mt-2 text-3xl font-black">Member hierarchy</h1>
                        <p class="mt-2 max-w-2xl text-sm text-teal-50/75">
                            Full sponsor tree across the organization. Super-admins appear at the top level without a sponsor.
                        </p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/30 bg-white/10 px-5 py-3 text-sm font-bold text-white hover:bg-white/15">
                        ← Back to users
                    </a>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <x-sponsor-tree :nodes="$forest" />
        </section>
    </div>
@endsection
