@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:36px_36px]"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            Media Library
                        </p>
                        <h1 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Organize every asset by type, source, and purpose.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                            Manage documents, videos, external links, images, downloads, and educational resources for the Molecular H2 Water portal.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="button" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                            Upload Media
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                            Add Link
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['title' => 'Documents', 'count' => 42, 'meta' => 'PDF, DOCX, brochures', 'tone' => 'teal'],
                ['title' => 'Videos', 'count' => 12, 'meta' => 'Training, testimonials', 'tone' => 'cyan'],
                ['title' => 'Links', 'count' => 28, 'meta' => 'Studies, references, URLs', 'tone' => 'emerald'],
                ['title' => 'Images', 'count' => 64, 'meta' => 'Products, banners, icons', 'tone' => 'amber'],
            ] as $category)
                <article class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">{{ $category['meta'] }}</p>
                            <h2 class="mt-2 text-xl font-black text-slate-950">{{ $category['title'] }}</h2>
                        </div>
                        <span class="flex size-11 items-center justify-center rounded-md bg-teal-50 text-sm font-black text-teal-700">{{ $category['count'] }}</span>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-teal-400" style="width: {{ min(92, 28 + $category['count']) }}%"></div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Recent Assets</p>
                        <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Media queue</h2>
                    </div>
                    <div class="relative">
                        <input type="search" class="w-full rounded-full border border-teal-100 bg-white py-2.5 pl-4 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 sm:w-72" placeholder="Search media...">
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-lg border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                            @foreach ([
                                ['name' => 'Hydrogen Water Guide.pdf', 'type' => 'Documents', 'status' => 'Published', 'date' => 'Today'],
                                ['name' => 'Product Demo Video', 'type' => 'Videos', 'status' => 'Review', 'date' => 'Yesterday'],
                                ['name' => 'Clinical Research References', 'type' => 'Links', 'status' => 'Published', 'date' => 'Jun 20'],
                                ['name' => 'Homepage Product Banner', 'type' => 'Images', 'status' => 'Draft', 'date' => 'Jun 18'],
                            ] as $asset)
                                <tr class="transition hover:bg-teal-50/50">
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $asset['name'] }}</td>
                                    <td class="px-4 py-4">{{ $asset['type'] }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $asset['status'] === 'Published' ? 'bg-emerald-50 text-emerald-700' : ($asset['status'] === 'Review' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                            {{ $asset['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ $asset['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Design Prompt</p>
                    <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Media page brief</h2>
                    <div class="mt-5 rounded-md border border-teal-100 bg-teal-50 p-4 text-sm leading-7 text-teal-950">
                        Create an admin media library page for Molecular H2 Water using a deep teal and aqua theme. Include category cards for documents, videos, links, images, downloads, and embedded resources. Add upload actions, searchable media table, status badges, storage overview, and quick filters by media type.
                    </div>
                </div>

                <div class="rounded-lg border border-teal-100 bg-[#041f1e] p-6 text-white shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-200/65">Storage</p>
                    <div class="mt-5 flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-black">68%</p>
                            <p class="mt-1 text-sm text-teal-100/60">Media capacity used</p>
                        </div>
                        <span class="rounded-full bg-teal-300/15 px-3 py-1 text-xs font-bold text-teal-200">Healthy</span>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-teal-300" style="width: 68%"></div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
@endsection
