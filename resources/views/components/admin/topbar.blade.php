<section class="sticky top-0 z-40 w-full backdrop-blur-xl bg-white/70 dark:bg-navy-950/80 shadow-lg border-b border-teal-100/30">
  <!-- Decorative elements -->
  <div class="absolute inset-0 pointer-events-none">
    <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-96 h-32 bg-teal-400/20 rounded-full blur-2xl"></div>
    <div class="absolute top-0 right-0 w-32 h-32 bg-teal-300/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-0 left-0 w-40 h-20 bg-teal-200/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-teal-400/0 via-teal-400/40 to-teal-400/0"></div>
  </div>
  <!-- Main Topbar -->
  <div class="relative flex items-center h-20 px-4 sm:px-8 lg:px-12">
    <!-- Left Section -->
    <div class="flex items-center gap-4 min-w-0">
      <!-- Sidebar Toggle -->
      <button type="button" @click="sidebarOpen = ! sidebarOpen" :aria-label="sidebarOpen ? 'Hide sidebar' : 'Show sidebar'" class="flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="24" height="24" fill="none" stroke="currentColor" class="text-teal-700" stroke-width="2" viewBox="0 0 24 24">
          <rect x="4" y="6" width="16" height="2" rx="1" fill="currentColor"/>
          <rect x="4" y="11" width="16" height="2" rx="1" fill="currentColor"/>
          <rect x="4" y="16" width="16" height="2" rx="1" fill="currentColor"/>
        </svg>
      </button>
      <!-- Page Title & Breadcrumbs -->
      <div class="flex flex-col min-w-0">
        <div class="flex items-center gap-2">
          <span class="text-xl font-semibold text-navy-900 dark:text-white tracking-tight">Dashboard</span>
        </div>
        <nav class="flex items-center gap-1 text-xs text-teal-900/70 dark:text-teal-100/70 mt-0.5" aria-label="Breadcrumb">
          <span class="flex items-center gap-1">
            <svg width="14" height="14" fill="none" class="inline-block" viewBox="0 0 14 14"><circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="7" cy="7" r="2" fill="currentColor" class="text-teal-400"/></svg>
            Home
          </span>
          <span aria-hidden="true" class="mx-1 text-teal-400">›</span>
          <span>Admin</span>
          <span aria-hidden="true" class="mx-1 text-cyan-400">›</span>
          <span class="font-medium text-teal-700 dark:text-teal-300">Dashboard</span>
        </nav>
      </div>
    </div>
    <!-- Center Section: Search -->
    <div class="flex-1 flex justify-center px-4">
      <form class="w-full max-w-xl flex items-center relative" role="search" aria-label="Global search">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-teal-400">
          <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2"/><path d="M16 16l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </span>
        <input type="search" class="w-full pl-12 pr-20 py-3 rounded-full bg-white/80 dark:bg-navy-900/80 border border-teal-100/60 focus:border-teal-400 focus:ring-2 focus:ring-teal-300/40 shadow-inner text-navy-900 dark:text-white placeholder-teal-900/40 dark:placeholder-teal-100/40 text-base font-medium transition focus:outline-none" placeholder="Search leads, pages, FAQs, blog articles..." aria-label="Search">
        <span class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1">
          <span class="hidden sm:inline-block px-2 py-0.5 rounded bg-teal-100/60 text-teal-700 text-xs font-semibold border border-teal-200">⌘ K</span>
          <span class="inline-block sm:hidden px-2 py-0.5 rounded bg-teal-100/60 text-teal-700 text-xs font-semibold border border-teal-200">CTRL + K</span>
        </span>
      </form>
    </div>
    <!-- Right Section -->
    <div class="flex items-center gap-2 sm:gap-3 ml-auto">
      <!-- Quick Actions -->
      <div class="relative">
        <button aria-label="New" class="flex items-center gap-1 px-4 py-2 rounded-full bg-teal-600 hover:bg-teal-700 text-white font-semibold shadow focus:outline-none focus:ring-2 focus:ring-teal-400 transition">
          New
          <svg width="16" height="16" fill="none" class="ml-1" viewBox="0 0 16 16"><path d="M8 4v8M4 8h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>
      <!-- Notifications -->
      <button aria-label="Notifications" class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><path d="M11 19a2 2 0 0 0 2-2H9a2 2 0 0 0 2 2Zm6-5V9a7 7 0 1 0-14 0v5l-2 2v1h18v-1l-2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border-2 border-white shadow">8</span>
      </button>
      <!-- Messages -->
      <button aria-label="Messages" class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><path d="M3 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Z" stroke="currentColor" stroke-width="2"/><path d="M3 5l8 7 8-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border-2 border-white shadow">3</span>
      </button>
      <!-- Tasks -->
      <button aria-label="Tasks" class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><rect x="3" y="5" width="16" height="12" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 9h8M7 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border-2 border-white shadow">5</span>
      </button>
      <!-- User Profile -->
      @php
        $user = auth()->user();
        $userName = $user?->name ?? 'Admin User';
        $userEmail = $user?->email ?? 'admin@molecularh2water.com';
        $initials = collect(explode(' ', trim($userName)))
          ->filter()
          ->take(2)
          ->map(fn ($part) => mb_substr($part, 0, 1))
          ->join('');
        $initials = $initials !== '' ? mb_strtoupper($initials) : 'AU';
        $avatarUrl = $user?->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) : null;
      @endphp

      <details class="group relative flex items-center gap-2 pl-2">
        <summary
          aria-label="Open user menu"
          class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-teal-50/80 focus:outline-none focus:ring-2 focus:ring-teal-400 [&::-webkit-details-marker]:hidden"
        >
          <span class="relative flex items-center">
            @if ($avatarUrl)
              <img src="{{ $avatarUrl }}" alt="{{ $userName }} avatar" class="h-10 w-10 rounded-full border-2 border-white object-cover shadow-inner">
            @else
              <span class="inline-flex w-10 h-10 rounded-full bg-gradient-to-br from-teal-400/80 to-teal-700/80 border-2 border-white shadow-inner items-center justify-center text-white font-bold text-sm">{{ $initials }}</span>
            @endif
            <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-white rounded-full"></span>
          </span>
          <span class="hidden sm:flex flex-col items-start ml-1">
            <span class="text-sm font-semibold text-navy-900 dark:text-white leading-tight">{{ $userName }}</span>
            <span class="text-xs text-teal-700 dark:text-teal-300">Administrator</span>
          </span>
          <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-700 transition group-open:rotate-180"><path d="M6 7l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </summary>

        <div
          class="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-lg border border-teal-100 bg-white shadow-xl shadow-teal-950/10"
        >
          <div class="border-b border-teal-50 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $userName }}</p>
            <p class="truncate text-xs text-teal-700">{{ $userEmail }}</p>
          </div>

          <div class="py-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
              <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><rect x="3" y="3" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M6 10.5l2.1-2.1 1.8 1.8L12 7.7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              My Dashboard
            </a>
            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
              <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 15c0-2.49 2.46-4.5 5.5-4.5s5.5 2.01 5.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
              Profile
            </a>
            <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
              <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><circle cx="9" cy="9" r="2.4" stroke="currentColor" stroke-width="1.6"/><path d="M9 2v2M9 14v2M2 9h2M14 9h2M4.05 4.05l1.42 1.42M12.53 12.53l1.42 1.42M4.05 13.95l1.42-1.42M12.53 5.47l1.42-1.42" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
              Settings
            </a>
          </div>

          <form method="POST" action="{{ route('logout') }}" class="border-t border-teal-50">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
              <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-red-500"><path d="M7 4H4.5A1.5 1.5 0 0 0 3 5.5v7A1.5 1.5 0 0 0 4.5 14H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M10.5 12.5 14 9l-3.5-3.5M14 9H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Log off
            </button>
          </form>
        </div>
      </details>
    </div>
  </div>
</section>
