@extends('layouts.admin')

@section('content')
<form method="POST" action="{{ route('admin.settings.sidebar-design') }}" class="max-w-2xl mx-auto mb-8">
    @csrf
    <h1 class="text-3xl font-bold text-navy-900 mb-6">Settings</h1>
    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label for="design-switcher" class="block text-sm font-medium text-gray-700 mb-2">Sidebar Design:</label>
            <select id="design-switcher" name="sidebar_design" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50">
                <option value="design1" {{ session('sidebar_design', 'design1') == 'design1' ? 'selected' : '' }}>Design 1</option>
                <option value="design2" {{ session('sidebar_design') == 'design2' ? 'selected' : '' }}>Design 2</option>
                <option value="design3" {{ session('sidebar_design') == 'design3' ? 'selected' : '' }}>Design 3</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" aria-label="Apply sidebar design" class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700 transition">Apply</button>
        </div>
    </div>
    @if(session('sidebar_design_updated'))
        <div class="mt-4 text-green-600 font-semibold">Sidebar design updated!</div>
    @endif
</form>
@endsection
