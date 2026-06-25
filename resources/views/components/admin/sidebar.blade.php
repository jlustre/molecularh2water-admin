<section style="width:280px;min-height:100vh;background:linear-gradient(180deg,#041f1e 0%,#062926 60%,#031a19 100%);display:flex;flex-direction:column;position:relative;overflow:hidden;font-family:ui-sans-serif,system-ui,sans-serif;">
  <!-- Decorative glows -->
  <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(20,184,166,0.1) 0%,transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;bottom:120px;left:-40px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(13,148,136,0.07) 0%,transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;top:0;right:0;width:1px;height:100%;background:linear-gradient(180deg,transparent,rgba(20,184,166,0.3) 30%,rgba(20,184,166,0.15) 70%,transparent);pointer-events:none;"></div>
  <!-- Brand -->
  <div style="padding:24px 20px 20px;border-bottom:0.5px solid rgba(20,184,166,0.15);position:relative;">
    <div style="display:flex;align-items:center;gap:14px;">
      <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,rgba(20,184,166,0.18),rgba(13,148,136,0.1));border:1.5px solid rgba(20,184,166,0.5);display:flex;align-items:center;justify-content:center;position:relative;flex-shrink:0;box-shadow:0 0 16px rgba(20,184,166,0.25);">
        <svg width="22" height="16" viewBox="0 0 22 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <text x="1" y="13" font-size="13" font-weight="700" font-family="monospace" fill="#2dd4bf" letter-spacing="-0.5">H₂</text>
        </svg>
        <div style="position:absolute;inset:-3px;border-radius:50%;border:1px solid rgba(20,184,166,0.2);"></div>
      </div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#ffffff;letter-spacing:0.01em;line-height:1.2;">Molecular H2 Water</div>
        <div style="font-size:11px;color:rgba(45,212,191,0.75);letter-spacing:0.08em;text-transform:uppercase;margin-top:2px;">Admin Portal</div>
      </div>
    </div>
    <div style="position:absolute;bottom:0;left:20px;right:20px;height:0.5px;background:linear-gradient(90deg,transparent,rgba(20,184,166,0.35),transparent);"></div>
  </div>
  <!-- Nav -->
  <nav aria-label="Admin navigation" style="flex:1;overflow-y:auto;padding:16px 12px;scrollbar-width:thin;scrollbar-color:rgba(20,184,166,0.2) transparent;">
    <!-- Overview -->
    <div style="margin-bottom:4px;">
      <div style="font-size:10px;font-weight:600;color:rgba(45,212,191,0.45);letter-spacing:0.12em;text-transform:uppercase;padding:0 8px 6px;">Overview</div>
      <a href="/admin/dashboard" aria-current="page" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;background:linear-gradient(135deg,rgba(20,184,166,0.18),rgba(13,148,136,0.09));border:0.5px solid rgba(20,184,166,0.35);text-decoration:none;box-shadow:0 0 14px rgba(20,184,166,0.12);margin-bottom:2px;">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1" y="1" width="6" height="6" rx="1.5" fill="#2dd4bf"/>
            <rect x="9" y="1" width="6" height="6" rx="1.5" fill="rgba(45,212,191,0.55)"/>
            <rect x="1" y="9" width="6" height="6" rx="1.5" fill="rgba(45,212,191,0.55)"/>
            <rect x="9" y="9" width="6" height="6" rx="1.5" fill="rgba(45,212,191,0.3)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;font-weight:500;color:#ffffff;flex:1;">Dashboard</span>
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
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">My Dashboard</span>
      </a>
    </div>
    <!-- Content Management -->
    <div style="margin-top:16px;margin-bottom:4px;">
      <div style="font-size:10px;font-weight:600;color:rgba(45,212,191,0.45);letter-spacing:0.12em;text-transform:uppercase;padding:0 8px 6px;">Content Management</div>
      <a href="/admin/pages" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="2" y="1" width="10" height="14" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <line x1="5" y1="5" x2="10" y2="5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="5" y1="8" x2="10" y2="8" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="5" y1="11" x2="8" y2="11" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Pages</span>
      </a>
      <a href="/admin/faqs" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="6.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M6.5 6C6.5 5.17 7.17 4.5 8 4.5C8.83 4.5 9.5 5.17 9.5 6C9.5 6.83 8 7.5 8 8.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <circle cx="8" cy="11" r="0.7" fill="rgba(45,212,191,0.6)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">FAQs</span>
        <span style="font-size:10px;font-weight:600;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.5);padding:2px 6px;border-radius:20px;">14</span>
      </a>
      <a href="/admin/blog" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="2" width="13" height="12" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <line x1="4" y1="6" x2="12" y2="6" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <line x1="4" y1="9" x2="10" y2="9" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Blog / Education</span>
        <span style="font-size:10px;font-weight:600;background:rgba(45,212,191,0.15);color:#2dd4bf;padding:2px 6px;border-radius:20px;border:0.5px solid rgba(45,212,191,0.3);">3 New</span>
      </a>
      <a href="/admin/testimonials" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M2 4.5C2 3.67 2.67 3 3.5 3H7C7.83 3 8.5 3.67 8.5 4.5V7C8.5 7.83 7.83 8.5 7 8.5H5.5L3.5 10.5V8.5H3.5C2.67 8.5 2 7.83 2 7V4.5Z" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M8.5 6H13C13.55 6 14 6.45 14 7V9.5C14 10.05 13.55 10.5 13 10.5H12V12L10.5 10.5H9C8.45 10.5 8 10.05 8 9.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Testimonials</span>
      </a>
      <a href="/admin/media" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="3" width="9" height="10" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M5 6.5L8 9L6.5 10.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="5.5" y="1.5" width="9" height="10" rx="1.5" stroke="rgba(45,212,191,0.3)" stroke-width="1" stroke-dasharray="2 1.5"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Media Library</span>
      </a>
    </div>
    <!-- Customer Engagement -->
    <div style="margin-top:16px;margin-bottom:4px;">
      <div style="font-size:10px;font-weight:600;color:rgba(45,212,191,0.45);letter-spacing:0.12em;text-transform:uppercase;padding:0 8px 6px;">Customer Engagement</div>
      <a href="/admin/leads" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="6" cy="5.5" r="2.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M1.5 13C1.5 10.79 3.57 9 6 9" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <path d="M11 9L13.5 11.5L11 14" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="9" y1="11.5" x2="13.5" y2="11.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Leads</span>
        <span style="font-size:10px;font-weight:700;background:rgba(45,212,191,0.2);color:#2dd4bf;padding:2px 7px;border-radius:20px;border:0.5px solid rgba(45,212,191,0.4);">28</span>
      </a>
      <a href="/admin/contact-messages" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="3.5" width="13" height="9" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M1.5 5.5L8 9L14.5 5.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Contact Messages</span>
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
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Appointments</span>
      </a>
    </div>
    <!-- System -->
    <div style="margin-top:16px;margin-bottom:4px;">
      <div style="font-size:10px;font-weight:600;color:rgba(45,212,191,0.45);letter-spacing:0.12em;text-transform:uppercase;padding:0 8px 6px;">System</div>
      <a href="/admin/users" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="5.5" r="2.8" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M2 13.5C2 11.01 4.69 9 8 9C11.31 9 14 11.01 14 13.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Users</span>
      </a>
      <a href="/admin/roles" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="2" y="7" width="12" height="7.5" rx="1.5" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M5 7V5C5 3.34 6.34 2 8 2C9.66 2 11 3.34 11 5V7" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
            <circle cx="8" cy="10.5" r="1.2" fill="rgba(45,212,191,0.6)"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Roles &amp; Permissions</span>
      </a>
      <a href="/admin/settings" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;margin-bottom:2px;" onmouseover="this.style.background='rgba(20,184,166,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="2.2" stroke="rgba(45,212,191,0.6)" stroke-width="1.2"/>
            <path d="M8 1.5V3M8 13V14.5M1.5 8H3M13 8H14.5M3.4 3.4L4.5 4.5M11.5 11.5L12.6 12.6M3.4 12.6L4.5 11.5M11.5 4.5L12.6 3.4" stroke="rgba(45,212,191,0.6)" stroke-width="1.2" stroke-linecap="round"/>
          </svg>
        </span>
        <span style="font-size:13.5px;color:rgba(255,255,255,0.75);flex:1;">Settings</span>
      </a>
    </div>
  </nav>
  <!-- Profile Card -->
  <div style="padding:12px;border-top:0.5px solid rgba(20,184,166,0.12);position:relative;">
    <div style="background:rgba(255,255,255,0.04);border:0.5px solid rgba(20,184,166,0.18);border-radius:10px;padding:12px;">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div style="position:relative;flex-shrink:0;">
          <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#063f3a,#0a6b63);border:1.5px solid rgba(45,212,191,0.45);display:flex;align-items:center;justify-content:center;">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="6.5" r="3" fill="rgba(45,212,191,0.75)"/>
              <path d="M3 15C3 12.24 5.69 10 9 10C12.31 10 15 12.24 15 15" stroke="rgba(45,212,191,0.75)" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
          </div>
          <div style="position:absolute;bottom:0;right:0;width:9px;height:9px;border-radius:50%;background:#22c55e;border:1.5px solid #062926;"></div>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:500;color:#ffffff;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Admin User</div>
          <div style="font-size:10.5px;color:rgba(45,212,191,0.55);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;">admin@molecularh2water.com</div>
        </div>
      </div>
      <a href="/admin/logout" aria-label="Sign out of admin portal" style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:7px;border-radius:7px;background:rgba(255,255,255,0.04);border:0.5px solid rgba(255,255,255,0.1);text-decoration:none;" onmouseover="this.style.background='rgba(20,184,166,0.1)';this.style.borderColor='rgba(45,212,191,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.borderColor='rgba(255,255,255,0.1)'">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path d="M5.5 7H12.5M12.5 7L10 4.5M12.5 7L10 9.5" stroke="rgba(255,255,255,0.6)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M8.5 2H3C2.45 2 2 2.45 2 3V11C2 11.55 2.45 12 3 12H8.5" stroke="rgba(255,255,255,0.6)" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
        <span style="font-size:12px;color:rgba(255,255,255,0.6);font-weight:500;">Sign Out</span>
      </a>
    </div>
  </div>
</section>
