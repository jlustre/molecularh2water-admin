@extends('layouts.admin')

@php
    $isVideoLinkForm = $isVideoLinkForm ?? false;
@endphp

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Media Library</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ $isVideoLinkForm ? 'Add video link' : 'Add media item' }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                {{ $isVideoLinkForm ? 'Save an external video URL for members to watch from the Resources page.' : 'Upload a document or video file, or save a video link from an external platform.' }}
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
                @include('admin.media._form', [
                    'submitLabel' => $isVideoLinkForm ? 'Create Video Link' : 'Create Media',
                    'isVideoLinkForm' => $isVideoLinkForm,
                ])
            </form>
        </section>
    </div>
@endsection
