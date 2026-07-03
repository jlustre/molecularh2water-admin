<section class="sticky top-0 z-[60] w-full backdrop-blur-xl bg-white/70 dark:bg-navy-950/80 shadow-lg border-b border-teal-100/30 lg:z-40">
  <!-- Decorative elements -->
  <div class="absolute inset-0 pointer-events-none">
    <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-96 h-32 bg-teal-400/20 rounded-full blur-2xl"></div>
    <div class="absolute top-0 right-0 w-32 h-32 bg-teal-300/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-0 left-0 w-40 h-20 bg-teal-200/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-teal-400/0 via-teal-400/40 to-teal-400/0"></div>
  </div>
  <!-- Main Topbar -->
  <div class="relative flex items-center gap-3 h-16 px-4 sm:h-20 sm:gap-4 sm:px-8 lg:px-12">
    <!-- Left Section -->
    <div class="flex shrink-0 items-center gap-3 sm:gap-4">
      <!-- Sidebar Toggle -->
      <x-sidebar.toggle />
      <!-- Page Title & Breadcrumbs -->
      <div class="hidden min-w-0 flex-col sm:flex">
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
    <div class="hidden min-w-0 flex-1 justify-center px-4 lg:flex">
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
    <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
      @if (auth()->user()?->canAccessPortal() || auth()->user()?->canAccessAdmin())
        <div class="hidden xl:block">
          <livewire:business-line-switcher />
        </div>
      @endif
      <!-- Quick Actions -->
      <div class="relative hidden 2xl:block">
        <button aria-label="New" class="flex items-center gap-1 px-4 py-2 rounded-full bg-teal-600 hover:bg-teal-700 text-white font-semibold shadow focus:outline-none focus:ring-2 focus:ring-teal-400 transition">
          New
          <svg width="16" height="16" fill="none" class="ml-1" viewBox="0 0 16 16"><path d="M8 4v8M4 8h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>
      <!-- Notifications -->
      <button aria-label="Notifications" class="relative hidden lg:flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><path d="M11 19a2 2 0 0 0 2-2H9a2 2 0 0 0 2 2Zm6-5V9a7 7 0 1 0-14 0v5l-2 2v1h18v-1l-2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border-2 border-white shadow">8</span>
      </button>
      <!-- Messages -->
      <button aria-label="Messages" class="relative hidden lg:flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><path d="M3 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Z" stroke="currentColor" stroke-width="2"/><path d="M3 5l8 7 8-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border-2 border-white shadow">3</span>
      </button>
      <!-- Tasks -->
      <button aria-label="Tasks" class="relative hidden lg:flex items-center justify-center w-10 h-10 rounded-full bg-white/60 hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 shadow transition">
        <svg width="22" height="22" fill="none" viewBox="0 0 22 22" class="text-teal-700"><rect x="3" y="5" width="16" height="12" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 9h8M7 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border-2 border-white shadow">5</span>
      </button>
      <x-user-menu class="pl-2" />
    </div>
  </div>
</section>
