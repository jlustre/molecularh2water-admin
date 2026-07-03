<section style="width:280px;min-height:100vh;background:#ffffff;display:flex;flex-direction:column;position:relative;overflow:hidden;font-family:ui-sans-serif,system-ui,sans-serif;border-right:1px solid rgba(13,148,136,0.12);">
  <!-- Decorative glows -->
  <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(20,184,166,0.06) 0%,transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;bottom:120px;left:-40px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(13,148,136,0.05) 0%,transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;top:0;right:0;width:1px;height:100%;background:linear-gradient(180deg,transparent,rgba(20,184,166,0.15) 30%,rgba(20,184,166,0.08) 70%,transparent);pointer-events:none;"></div>
  <!-- Brand -->
  <div style="padding:24px 20px 20px;border-bottom:1px solid rgba(13,148,136,0.12);position:relative;background:#ffffff;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
    <a href="/admin/dashboard" style="display:block;text-decoration:none;min-width:0;flex:1;">
      <img
        src="{{ asset('images/brand/h2-systems-logo.png') }}"
        alt="H2 Systems — Endless Energy, Cellular Renewal"
        style="display:block;height:64px;width:auto;max-width:100%;object-fit:contain;object-position:left;"
      >
    </a>
    <x-sidebar.close class="border border-teal-100/60 bg-white/80 text-teal-700 hover:bg-teal-100/60" />
    <div style="position:absolute;bottom:0;left:20px;right:20px;height:0.5px;background:linear-gradient(90deg,transparent,rgba(20,184,166,0.35),transparent);"></div>
  </div>
  <!-- Nav -->
  @php
    $activeAdminSections = collect([
        'overview' => request()->routeIs('admin.dashboard', 'dashboard'),
        'content' => request()->routeIs('admin.pages', 'admin.faqs', 'admin.blog', 'admin.testimonials', 'admin.media'),
        'crm' => request()->routeIs('admin.crm.*'),
        'engagement' => request()->routeIs('admin.leads', 'admin.contact-messages', 'admin.appointments', 'admin.warranty-registrations.*'),
        'system' => request()->routeIs('admin.users', 'admin.roles', 'admin.settings'),
    ])->filter()->keys()->values()->all();
  @endphp
  <nav aria-label="Admin navigation" x-data="sidebarNavGroups('adminNavGroups', @js($activeAdminSections))" style="flex:1;overflow-y:auto;padding:16px 12px;scrollbar-width:thin;scrollbar-color:rgba(20,184,166,0.2) transparent;">
    <!-- Overview -->
    <div style="margin-bottom:4px;">
      <button type="button" @click="toggle('overview')" :aria-expanded="isOpen('overview')" style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 8px 6px;background:none;border:none;cursor:pointer;">
        <span style="font-size:10px;font-weight:600;color:rgba(13,148,136,0.72);letter-spacing:0.12em;text-transform:uppercase;">Overview</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :style="isOpen('overview') ? 'transform:rotate(0deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);' : 'transform:rotate(-90deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);'">
          <path d="M2.5 4.5 6 8 9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div x-show="isOpen('overview')">
      <a href="/admin/dashboard" aria-current="page" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;background:linear-gradient(135deg,rgba(20,184,166,0.18),rgba(13,148,136,0.09));border:0.5px solid rgba(20,184,166,0.35);text-decoration:none;box-shadow:0 0 14px rgba(20,184,166,0.12);margin-bottom:2px;">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1" y="1" width="6" height="6" rx="1.5" fill="#2dd4bf"/>
            <rect x="9" y="1" width="6" height="6" rx="1.5" fill="rgba(45,212,191,0.55)"/>
            <rect x="1" y="9" width="6" height="6" rx="1.5" fill="rgba(45,212,191,0.55)"/>
            <rect x="9" y="9" width="6" height="6" rx="1.5" fill="rgba(45,212,191,0.3)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;font-weight:500;color:#0f172a;flex:1;">Dashboard</span>
        <span style="font-size:10px;font-weight:600;background:rgba(45,212,191,0.2);color:#2dd4bf;padding:2px 7px;border-radius:20px;border:0.5px solid rgba(45,212,191,0.4);">Live</span>
      </a>
      <a href="/dashboard" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="2" y="2" width="12" height="12" rx="2" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M5 9.5L7.1 7.4L8.9 9.2L11 6.7" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="5" cy="5" r="1" fill="rgba(45,212,191,0.6)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">My Dashboard</span>
      </a>
      </div>
    </div>
    <!-- Content Management -->
    <div style="margin-top:16px;margin-bottom:4px;">
      <button type="button" @click="toggle('content')" :aria-expanded="isOpen('content')" style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 8px 6px;background:none;border:none;cursor:pointer;">
        <span style="font-size:10px;font-weight:600;color:rgba(13,148,136,0.72);letter-spacing:0.12em;text-transform:uppercase;">Content Management</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :style="isOpen('content') ? 'transform:rotate(0deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);' : 'transform:rotate(-90deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);'">
          <path d="M2.5 4.5 6 8 9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div x-show="isOpen('content')">
      <a href="/admin/pages" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="2" y="1" width="10" height="14" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <line x1="5" y1="5" x2="10" y2="5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="5" y1="8" x2="10" y2="8" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="5" y1="11" x2="8" y2="11" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Pages</span>
      </a>
      <a href="/admin/faqs" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="6.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M6.5 6C6.5 5.17 7.17 4.5 8 4.5C8.83 4.5 9.5 5.17 9.5 6C9.5 6.83 8 7.5 8 8.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <circle cx="8" cy="11" r="0.7" fill="rgba(45,212,191,0.6)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">FAQs</span>
        <span style="font-size:10px;font-weight:600;background:rgba(15,23,42,0.06);color:rgba(15,23,42,0.55);padding:2px 6px;border-radius:20px;">14</span>
      </a>
      <a href="/admin/blog" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="2" width="13" height="12" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <line x1="4" y1="6" x2="12" y2="6" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="4" y1="9" x2="10" y2="9" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Blog / Education</span>
        <span style="font-size:10px;font-weight:600;background:rgba(45,212,191,0.15);color:#2dd4bf;padding:2px 6px;border-radius:20px;border:0.5px solid rgba(45,212,191,0.3);">3 New</span>
      </a>
      <a href="/admin/testimonials" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M2 4.5C2 3.67 2.67 3 3.5 3H7C7.83 3 8.5 3.67 8.5 4.5V7C8.5 7.83 7.83 8.5 7 8.5H5.5L3.5 10.5V8.5H3.5C2.67 8.5 2 7.83 2 7V4.5Z" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M8.5 6H13C13.55 6 14 6.45 14 7V9.5C14 10.05 13.55 10.5 13 10.5H12V12L10.5 10.5H9C8.45 10.5 8 10.05 8 9.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Testimonials</span>
      </a>
      <a href="/admin/media" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="3" width="9" height="10" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M5 6.5L8 9L6.5 10.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="5.5" y="1.5" width="9" height="10" rx="1.5" stroke="rgba(45,212,191,0.3)" stroke-width="1" stroke-dasharray="2 1.5"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Media Library</span>
      </a>
      </div>
    </div>
    <!-- CRM & Sales -->
    @if (auth()->user()?->hasPermission('crm.dashboard.view') || auth()->user()?->hasPermission('leads.view'))
    <div style="margin-top:16px;margin-bottom:4px;">
      <button type="button" @click="toggle('crm')" :aria-expanded="isOpen('crm')" style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 8px 6px;background:none;border:none;cursor:pointer;">
        <span style="font-size:10px;font-weight:600;color:rgba(13,148,136,0.72);letter-spacing:0.12em;text-transform:uppercase;">CRM &amp; Sales</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :style="isOpen('crm') ? 'transform:rotate(0deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);' : 'transform:rotate(-90deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);'">
          <path d="M2.5 4.5 6 8 9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div x-show="isOpen('crm')">
      @php
        $leadCount = \Illuminate\Support\Facades\Schema::hasTable('leads')
            ? \App\Support\Crm\CrmScope::leads(\App\Models\Crm\Lead::query())->lifecycle('lead')->count()
            : 0;
        $crmLinks = array_filter([
            ['label' => 'Leads', 'route' => 'admin.crm.leads.index', 'permission' => 'leads.view', 'badge' => $leadCount ?: null],
            ['label' => 'Prospects', 'route' => 'admin.crm.prospects.index', 'permission' => 'prospects.view'],
            ['label' => 'Customers', 'route' => 'admin.crm.customers.index', 'permission' => 'clients.view'],
            ['label' => 'Recruits', 'route' => 'admin.crm.recruits.index', 'permission' => 'recruits.view'],
            ['label' => 'Funnel Board', 'route' => 'admin.crm.pipeline.index', 'permission' => 'pipeline.view'],
            ['label' => 'Funnel Builder', 'route' => 'admin.crm.funnels.index', 'permission' => 'funnel.manage'],
            ['label' => 'Activities', 'route' => 'admin.crm.activities.index', 'permission' => 'activities.view'],
            ['label' => 'Sales', 'route' => 'admin.crm.sales.index', 'permission' => 'sales.view'],
            ['label' => 'Tasks', 'route' => 'admin.crm.tasks.index', 'permission' => 'tasks.view'],
            ['label' => 'Calendar', 'route' => 'admin.crm.calendar.index', 'permission' => 'calendar.view'],
            ['label' => 'Appointments', 'route' => 'admin.crm.appointments.index', 'permission' => 'appointments.view'],
            ['label' => 'Landing Pages', 'route' => 'admin.crm.landing-pages.index', 'permission' => 'landing-pages.view'],
            ['label' => 'Executive Dashboard', 'route' => 'admin.crm.dashboard.index', 'permission' => 'crm.dashboard.view'],
            ['label' => 'Reports', 'route' => 'admin.crm.reports.index', 'permission' => 'reports.view'],
            ['label' => 'CRM Settings', 'route' => 'admin.crm.settings.index', 'permission' => 'crm.settings.manage'],
        ], fn ($link) => auth()->user()?->hasPermission($link['permission']));
      @endphp
      @foreach ($crmLinks as $link)
      <a href="{{ route($link['route']) }}" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="5.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">{{ $link['label'] }}</span>
        @if (!empty($link['badge']))
        <span style="font-size:10px;font-weight:700;background:rgba(45,212,191,0.2);color:#2dd4bf;padding:2px 7px;border-radius:20px;border:0.5px solid rgba(45,212,191,0.4);">{{ $link['badge'] }}</span>
        @endif
      </a>
      @endforeach
      </div>
    </div>
    @endif
    <!-- Customer Engagement -->
    <div style="margin-top:16px;margin-bottom:4px;">
      <button type="button" @click="toggle('engagement')" :aria-expanded="isOpen('engagement')" style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 8px 6px;background:none;border:none;cursor:pointer;">
        <span style="font-size:10px;font-weight:600;color:rgba(13,148,136,0.72);letter-spacing:0.12em;text-transform:uppercase;">Customer Engagement</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :style="isOpen('engagement') ? 'transform:rotate(0deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);' : 'transform:rotate(-90deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);'">
          <path d="M2.5 4.5 6 8 9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div x-show="isOpen('engagement')">
      <a href="/admin/leads" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="6" cy="5.5" r="2.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M1.5 13C1.5 10.79 3.57 9 6 9" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <path d="M11 9L13.5 11.5L11 14" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="9" y1="11.5" x2="13.5" y2="11.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Leads</span>
        <span style="font-size:10px;font-weight:700;background:rgba(45,212,191,0.2);color:#2dd4bf;padding:2px 7px;border-radius:20px;border:0.5px solid rgba(45,212,191,0.4);">28</span>
      </a>
      <a href="/admin/contact-messages" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="3.5" width="13" height="9" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M1.5 5.5L8 9L14.5 5.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Contact Messages</span>
        <span style="font-size:10px;font-weight:700;background:rgba(251,191,36,0.15);color:#fbbf24;padding:2px 6px;border-radius:20px;border:0.5px solid rgba(251,191,36,0.3);">7</span>
      </a>
      <a href="/admin/appointments" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="2.5" width="13" height="12" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <line x1="5" y1="1.5" x2="5" y2="4" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="11" y1="1.5" x2="11" y2="4" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="1.5" y1="7" x2="14.5" y2="7" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <rect x="4" y="9.5" width="2.5" height="2.5" rx="0.5" fill="rgba(45,212,191,0.45)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Appointments</span>
      </a>
      <a href="{{ route('admin.warranty-registrations.index') }}" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="2.5" y="3" width="11" height="10" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M5.5 6.5 7.5 8.5 10.5 5.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 2.5h8" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Warranty Registrations</span>
      </a>
      </div>
    </div>
    <!-- System -->
    <div style="margin-top:16px;margin-bottom:4px;">
      <button type="button" @click="toggle('system')" :aria-expanded="isOpen('system')" style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 8px 6px;background:none;border:none;cursor:pointer;">
        <span style="font-size:10px;font-weight:600;color:rgba(13,148,136,0.72);letter-spacing:0.12em;text-transform:uppercase;">System</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :style="isOpen('system') ? 'transform:rotate(0deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);' : 'transform:rotate(-90deg);transition:transform 0.2s;color:rgba(13,148,136,0.72);'">
          <path d="M2.5 4.5 6 8 9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div x-show="isOpen('system')">
      <a href="/admin/users" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="5.5" r="2.8" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M2 13.5C2 11.01 4.69 9 8 9C11.31 9 14 11.01 14 13.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Users</span>
      </a>
      <a href="/admin/roles" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="2" y="7" width="12" height="7.5" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M5 7V5C5 3.34 6.34 2 8 2C9.66 2 11 3.34 11 5V7" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <circle cx="8" cy="10.5" r="1.2" fill="rgba(45,212,191,0.6)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Roles &amp; Permissions</span>
      </a>
      <a href="/admin/settings" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="2.2" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M8 1.5V3M8 13V14.5M1.5 8H3M13 8H14.5M3.4 3.4L4.5 4.5M11.5 11.5L12.6 12.6M3.4 12.6L4.5 11.5M11.5 4.5L12.6 3.4" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(15,23,42,0.78);flex:1;">Settings</span>
      </a>
      </div>
    </div>
  </nav>
  <!-- Profile Card -->
  <div style="padding:12px;border-top:1px solid rgba(13,148,136,0.12);position:relative;background:#ffffff;">
    <div style="background:#f8fbfb;border:1px solid rgba(13,148,136,0.14);border-radius:10px;padding:12px;">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div style="position:relative;flex-shrink:0;">
          <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#063f3a,#0a6b63);border:1.5px solid rgba(45,212,191,0.45);display:flex;align-items:center;justify-content:center;">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="6.5" r="3" fill="rgba(45,212,191,0.75)"/>
              <path d="M3 15C3 12.24 5.69 10 9 10C12.31 10 15 12.24 15 15" stroke="rgba(45,212,191,0.75)" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
          </div>
          <div style="position:absolute;bottom:0;right:0;width:9px;height:9px;border-radius:50%;background:#22c55e;border:1.5px solid #ffffff;"></div>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:500;color:#0f172a;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Admin User</div>
          <div style="font-size:10.5px;color:rgba(13,148,136,0.78);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;">admin@molecularh2water.com</div>
        </div>
      </div>
      <a href="/admin/logout" aria-label="Sign out of admin portal" style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:7px;border-radius:7px;background:#ffffff;border:1px solid rgba(13,148,136,0.16);text-decoration:none;" onmouseover="this.style.background='rgba(20,184,166,0.08)';this.style.borderColor='rgba(45,212,191,0.35)'" onmouseout="this.style.background='#ffffff';this.style.borderColor='rgba(13,148,136,0.16)'">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M5.5 7H12.5M12.5 7L10 4.5M12.5 7L10 9.5" stroke="rgba(15,23,42,0.55)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M8.5 2H3C2.45 2 2 2.45 2 3V11C2 11.55 2.45 12 3 12H8.5" stroke="rgba(15,23,42,0.55)" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
        <span style="font-size:12px;color:rgba(15,23,42,0.62);font-weight:500;">Sign Out</span>
      </a>
    </div>
  </div>
</section>
