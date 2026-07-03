# CRM & Funnel System — Technical Plan

**Project:** Molecular H2 Water Admin — CRM & Funnel System  
**Stack:** TALL (Tailwind CSS, Alpine.js, Laravel 13, Livewire 3, MySQL)  
**Status:** Phase 1 implemented; Phases 2–7 planned below

---

## 1. Executive Summary

A role-based CRM and sales funnel platform integrated into the existing `molecularh2water-admin` Laravel application. The system captures leads, tracks prospects and clients, manages pipeline stages, schedules appointments, logs activities, assigns tasks, builds landing pages, and surfaces analytics in a premium SaaS-style admin UI.

**Integration strategy:** Extend the existing admin shell (`layouts/admin`), JSON-based role permissions (`roles.permissions`), and Livewire patterns. CRM modules live under `App\Livewire\Crm`, `App\Models\Crm`, and `routes/crm.php`.

---

## 2. Phased Roadmap

| Phase | Scope | Deliverables |
|-------|--------|--------------|
| **1** | Core setup | Permissions, teams, CRM config, routes, dashboard, nav, layout |
| **2** | Lead management | Leads CRUD, sources, tags, notes, timeline, import/export |
| **3** | Funnel & pipeline | Funnel builder, stages, Kanban board, drag-and-drop |
| **4** | Activities & scheduling | Activities, tasks, appointments, follow-up reminders |
| **5** | Landing pages | Page builder, lead forms, capture API, conversion tracking |
| **6** | Reports & settings | Analytics, agent performance, CRM settings, user/team mgmt |
| **7** | Polish | Policies, queues, notifications, tests, performance, deployment |

---

## 3. Folder Structure

```
app/
├── Enums/Crm/
│   ├── LeadTemperature.php
│   ├── LeadLifecycle.php          # lead | prospect | client | recruit
│   ├── LeadStatus.php
│   ├── TaskPriority.php
│   ├── TaskStatus.php
│   ├── AppointmentStatus.php
│   └── ActivityOutcome.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                 # Existing CMS controllers
│   │   └── Crm/                   # CRM controllers (export, webhooks)
│   └── Middleware/
│       ├── EnsureAdminAccess.php
│       └── EnsurePermission.php
├── Livewire/Crm/
│   ├── DashboardStats.php
│   ├── LeadTable.php
│   ├── LeadForm.php
│   ├── LeadProfile.php
│   ├── LeadTimeline.php
│   ├── FunnelBoard.php
│   ├── FunnelStageColumn.php
│   ├── ActivityForm.php
│   ├── TaskManager.php
│   ├── AppointmentCalendar.php
│   ├── LandingPageManager.php
│   ├── ReportDashboard.php
│   └── SettingsManager.php
├── Models/Crm/
│   ├── Team.php
│   ├── Lead.php
│   ├── LeadSource.php
│   ├── Tag.php
│   ├── Funnel.php
│   ├── FunnelStage.php
│   ├── Activity.php
│   ├── ActivityType.php
│   ├── Task.php
│   ├── Appointment.php
│   ├── Note.php
│   ├── Attachment.php
│   ├── LandingPage.php
│   ├── LeadForm.php
│   ├── EmailTemplate.php
│   ├── SmsTemplate.php
│   ├── FollowupSequence.php
│   └── FollowupSequenceStep.php
├── Policies/Crm/
│   └── LeadPolicy.php
├── Services/Crm/
│   ├── LeadService.php
│   ├── FunnelService.php
│   ├── TimelineService.php
│   └── ConversionService.php
└── Support/Crm/
    └── CrmPermissions.php

config/crm.php
database/migrations/2026_06_30_*_crm_*.php
database/seeders/CrmSeeder.php
resources/views/
├── livewire/crm/
├── admin/crm/                     # Blade wrappers for Livewire full-page
└── components/crm/                # Reusable CRM UI components

routes/crm.php
tests/Feature/Crm/
```

---

## 4. Roles & Permissions

### Roles

| Role | Slug | Access |
|------|------|--------|
| Super Admin | `super-admin` | Full system + CRM settings |
| Admin | `admin` | Full CRM, no destructive system settings |
| Manager | `manager` | Team CRM, reports, assign leads |
| Agent | `agent` | Own leads, tasks, activities, appointments |

Legacy roles (`editor`, `member`) remain for CMS/member portal; they do not receive CRM permissions by default.

### Permission Keys

Defined centrally in `App\Support\Crm\CrmPermissions` and seeded via `RolesSeeder`.

```
crm.dashboard.view
leads.view | leads.create | leads.update | leads.delete | leads.import | leads.export | leads.assign
prospects.view | prospects.manage
clients.view | clients.manage
funnel.view | funnel.manage
pipeline.view | pipeline.manage
activities.view | activities.manage
tasks.view | tasks.manage
appointments.view | appointments.manage
landing-pages.view | landing-pages.manage
reports.view
crm.teams.manage
crm.settings.manage
notifications.view
```

**Enforcement:** `EnsureAdminAccess` on `/admin/*`; `EnsurePermission` on CRM routes; `LeadPolicy` for record-level checks (own vs team vs all).

---

## 5. Database Design

### Design Decision: Unified Contact Record

Leads, prospects, clients, and recruits share one `leads` table with a `lifecycle` column. Separate nav modules filter the same data:

- **Leads** → `lifecycle = lead`
- **Prospects** → `lifecycle = prospect`
- **Clients** → `lifecycle = client`
- **Recruits** → `lifecycle = recruit`

Conversion updates `lifecycle` and logs a timeline event. This avoids duplicate polymorphic relations and keeps funnel/pipeline on one record.

### Entity Relationship Diagram

```
users ─────┬──── team_user ──── teams
           │
           ├──── leads (assigned_user_id)
           ├──── activities
           ├──── tasks
           └──── appointments

lead_sources ──── leads
funnels ──── funnel_stages ──── leads (funnel_stage_id)
tags ──── lead_tag ──── leads
activity_types ──── activities
leads ──── notes (polymorphic noteable)
leads ──── attachments (polymorphic attachable)
leads ──── activities, tasks, appointments
landing_pages ──── funnels
lead_forms ──── landing_pages
followup_sequences ──── followup_sequence_steps
email_templates | sms_templates (standalone, future automation)
notifications (Laravel database notifications)
```

### Table Schemas

#### `teams`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string unique | |
| description | text nullable | |
| manager_id | FK users nullable | |
| is_active | boolean | default true |
| timestamps | | |
| soft_deletes | | |

#### `team_user` (pivot)
| Column | Type |
|--------|------|
| team_id, user_id | FK |
| role | string nullable | member, lead |
| timestamps | |

#### `lead_sources`
| Column | Type |
|--------|------|
| id | bigint PK |
| name, slug | string |
| description | text nullable |
| is_active | boolean |
| sort_order | unsigned int |
| timestamps | |

#### `tags`
| Column | Type |
|--------|------|
| id | bigint PK |
| name | string |
| slug | string unique |
| color | string nullable |
| timestamps | |

#### `funnels`
| Column | Type |
|--------|------|
| id | bigint PK |
| name, slug | string |
| description | text nullable |
| team_id | FK nullable |
| user_id | FK nullable | owner |
| is_default | boolean |
| is_active | boolean |
| timestamps | soft_deletes |

#### `funnel_stages`
| Column | Type |
|--------|------|
| id | bigint PK |
| funnel_id | FK |
| name | string |
| slug | string |
| color | string nullable |
| sort_order | unsigned int |
| is_won | boolean default false |
| is_lost | boolean default false |
| timestamps | |

#### `leads` (unified contact)
| Column | Type |
|--------|------|
| id | bigint PK |
| lifecycle | enum: lead, prospect, client, recruit |
| status | string | new, contacted, qualified, etc. |
| temperature | enum: cold, warm, hot |
| score | unsigned smallint default 0 |
| first_name, last_name | string |
| email, phone | string nullable |
| city, state, country | string nullable |
| company | string nullable |
| lead_source_id | FK nullable |
| funnel_id, funnel_stage_id | FK nullable |
| assigned_user_id | FK users nullable |
| team_id | FK nullable |
| interested_in | string nullable |
| message | text nullable |
| lost_reason | string nullable |
| last_contacted_at | datetime nullable |
| next_follow_up_at | datetime nullable |
| converted_at | datetime nullable |
| consent_given | boolean default false |
| metadata | json nullable |
| timestamps | soft_deletes |

#### `lead_tag` (pivot)
| lead_id, tag_id | FK |

#### `activity_types`
| id, name, slug, icon, is_active, sort_order | |

#### `activities`
| id | bigint PK |
| activity_type_id | FK |
| user_id | FK | performer |
| lead_id | FK |
| title | string |
| description | text nullable |
| outcome | string nullable |
| next_action | string nullable |
| scheduled_at, completed_at | datetime nullable |
| duration_minutes | int nullable |
| metadata | json nullable |
| timestamps | soft_deletes |

#### `tasks`
| id, lead_id, user_id, title, description, priority, status, due_at, completed_at, reminder_at | |
| timestamps, soft_deletes |

#### `appointments`
| id, lead_id, user_id, title, meeting_type, location, zoom_link, status, starts_at, ends_at, reminder_notes | |
| timestamps, soft_deletes |

#### `notes`
| id, noteable_type, noteable_id, user_id, body | polymorphic |
| timestamps, soft_deletes |

#### `attachments`
| id, attachable_type, attachable_id, user_id, disk, path, filename, mime_type, size | |
| timestamps, soft_deletes |

#### `landing_pages`
| id, funnel_id, title, slug, headline, subheadline, hero_media, cta_label, cta_url, thank_you_headline, thank_you_body, tracking_source, conversion_count, is_published | |
| timestamps, soft_deletes |

#### `lead_forms`
| id, landing_page_id, fields (json), settings (json) | |

#### `email_templates` / `sms_templates`
| id, name, slug, subject/body, variables (json), is_active | |

#### `followup_sequences` / `followup_sequence_steps`
| sequence: name, trigger_event, is_active | |
| step: sequence_id, channel, template_id, delay_minutes, sort_order | |

#### `timeline_events` (recommended)
| id, lead_id, user_id, event_type, description, properties (json) | |
| Powers unified timeline without querying every relation |

---

## 6. Module Specifications

### 6.1 Authentication & Roles
- **Purpose:** Secure CRM access with granular permissions.
- **Workflow:** User logs in via Breeze → `EnsureAdminAccess` checks `crm.dashboard.view` or legacy `admin.dashboard.view` → per-route permission middleware.
- **Security:** Super-admin bypass; agents scoped to `assigned_user_id`; managers see team records.

### 6.2 Dashboard (`DashboardStats` Livewire)
- **Metrics:** Total leads, new leads (7d), hot prospects, follow-ups due today, appointments today, closed won (month), conversion rate, funnel stage breakdown.
- **Widgets:** Recent activities, upcoming tasks, pipeline summary chart.

### 6.3 Lead Management (`LeadTable`, `LeadForm`, `LeadProfile`)
- **CRUD** with search, filters (source, temperature, status, assigned, date range), pagination.
- **Import/export:** CSV via queued job.
- **Validation:** Unique email per lifecycle optional; required first_name, email or phone.

### 6.4 Funnel Builder & Pipeline (`FunnelBoard`, `FunnelStageColumn`)
- **Alpine.js + Livewire** drag-and-drop between columns.
- **Livewire action:** `moveLead($leadId, $stageId)` updates stage + logs timeline.
- **Lost reason** modal when moving to lost stage.

### 6.5 Activities, Tasks, Appointments
- **Activities:** Typed log entries linked to lead.
- **Tasks:** Priority, status, due date, daily follow-up query: `next_follow_up_at <= today`.
- **Appointments:** FullCalendar-style month view (Livewire + Alpine).

### 6.6 Landing Pages & Forms
- **Public API:** `POST /api/crm/leads` with `source`, `landing_page_id`, honeypot.
- **Post-submit:** Create lead, assign round-robin or default user, notify, redirect URL.

### 6.7 Email/SMS Automation (Future-Ready)
- Tables seeded empty; `FollowupSequence` model relationships defined; dispatch via Laravel queues in Phase 7.

### 6.8 Reporting (`ReportDashboard`)
- Lead sources pie chart, funnel conversion funnel chart, agent leaderboard, monthly trends.
- Use Livewire + Chart.js or ApexCharts via Vite.

### 6.9 Notifications
- Laravel `Notifiable` on User; database notifications for assign, follow-up due, appointment reminder.
- Scheduled command: `crm:send-reminders` every 15 minutes.

### 6.10 Settings (`SettingsManager`)
- Tabs: Funnel stages, lead sources, tags, activity types, notification prefs.

---

## 7. Livewire Component Contracts

### `LeadTable`
```php
// Properties: search, filters[], perPage
// Methods: deleteLead($id), bulkAssign($userId, $ids)
// Query: Lead::query()->with(['source','assignedUser','stage'])->when(...)
```

### `FunnelBoard`
```php
// Properties: funnelId, stages (computed), leadsByStage
// Methods: moveLead($leadId, $stageId) — authorize, update, TimelineService::log
// Alpine: Sortable.js or native drag events calling @this.moveLead
```

### `LeadTimeline`
```php
// mount(Lead $lead)
// Renders merged timeline from timeline_events + notes + activities
```

---

## 8. UI Design System

- **Layout:** Existing admin sidebar + topbar; new "CRM & Sales" nav section.
- **Colors:** Teal/emerald primary (match existing `#041f1e` sidebar); status badges: cold=slate, warm=amber, hot=rose.
- **Components:** `x-crm.stat-card`, `x-crm.data-table`, `x-crm.modal`, `x-crm.badge`, `x-crm.page-header`.
- **Page pattern:** Hero banner → stat row → filters → content (table or board).
- **Reference views:** `admin/warranty-registrations/index.blade.php`, `admin/users/index.blade.php`.

---

## 9. API Endpoints (Public)

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/crm/leads` | Landing page form submission |
| GET | `/api/crm/leads/check-email` | Optional duplicate check |

Throttled, CORS-enabled for frontend domains (same as warranty API).

---

## 10. Security Checklist

- [x] Permission middleware on all CRM routes
- [x] User-scoped record visibility via `App\Support\Crm\CrmScope` (agents/managers see only `assigned_user_id = self`)
- [x] `crm.records.view-all` for Super Admin and Admin oversight
- [x] `LeadPolicy` for view/update/delete/pipeline actions
- [ ] Mass assignment protection via `$fillable` / `#[Fillable]`
- [ ] CSV import file validation and row limits
- [ ] Public form honeypot + rate limiting
- [ ] Soft deletes on PII tables
- [ ] Audit trail via `timeline_events`
- [ ] Queue sensitive exports

---

## 11. Testing Strategy

```
tests/Feature/Crm/
├── DashboardTest.php
├── LeadManagementTest.php
├── FunnelBoardTest.php
├── LeadCaptureApiTest.php
├── PermissionEnforcementTest.php
└── TaskReminderTest.php
```

**Patterns:** Pest, `RefreshDatabase`, `actingAs($userWithRole)`, factories for `Lead`, `Funnel`, `Team`.

---

## 12. Step-by-Step Development Instructions

### Phase 1 (Done / In Progress)
1. Run `php artisan migrate` for teams tables.
2. Seed roles: `php artisan db:seed --class=RolesSeeder`
3. Assign `super-admin` to your user.
4. Visit `/admin/dashboard` — CRM stats widgets appear.
5. Verify sidebar CRM links route to module placeholders.

### Phase 2
1. Run CRM lookup migrations (`lead_sources`, `tags`).
2. Run `leads` migration.
3. `php artisan db:seed --class=CrmSeeder`
4. Implement `LeadTable`, `LeadForm` Livewire components.
5. Replace `admin/crm/leads` placeholder with Livewire full-page route.
6. Add `LeadPolicy` + tests.

### Phase 3
1. Migrate `funnels`, `funnel_stages`.
2. Seed default funnel stages (New Lead → Closed Won/Lost).
3. Build `FunnelBoard` with Alpine drag-and-drop.
4. Add `FunnelService::moveLead()`.

### Phase 4
1. Migrate `activities`, `activity_types`, `tasks`, `appointments`, `notes`, `timeline_events`.
2. Build activity form modal, task manager, calendar component.
3. Schedule `crm:send-reminders` in `routes/console.php`.

### Phase 5
1. Migrate `landing_pages`, `lead_forms`.
2. Public API controller + tests.
3. Admin landing page manager.

### Phase 6
1. `ReportDashboard` with aggregated queries.
2. CRM settings Livewire tabs.
3. Team management UI.

### Phase 7
1. Full Pest coverage for dashboard cache, demo seeders, and queued notifications.
2. Queue workers for CRM notifications (`ShouldQueue` + configurable queue name).
3. Cache dashboard stats (5 min TTL via `DashboardStatsService`).
4. Production deploy checklist (see §13).

**Seeders**

| Seeder | Purpose |
|--------|---------|
| `RolesSeeder` | Roles and permissions |
| `CrmSeeder` | Lookup tables, funnel, landing page |
| `CrmUsersSeeder` | Demo manager + agents (`*@crm.demo` / `Password123`) |
| `CrmMarketingSeeder` | Email/SMS templates, follow-up sequences |
| `CrmDemoSeeder` | Leads, activities, tasks, appointments, notes, attachments |

```bash
php artisan db:seed --class=CrmDemoSeeder   # after CrmSeeder + CrmUsersSeeder
php artisan db:seed                          # runs full stack including demo data
```

---

## 13. Deployment Checklist

```bash
composer install --no-dev
php artisan migrate --force
php artisan db:seed --class=RolesSeeder
php artisan db:seed --class=CrmSeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
```

**Optional demo data (local/QA only):**

```bash
php artisan db:seed --class=CrmUsersSeeder
php artisan db:seed --class=CrmMarketingSeeder
php artisan db:seed --class=CrmDemoSeeder
```

**Cron:**
```
* * * * * php /path/to/artisan schedule:run
```

**Queue:** `php artisan queue:work --queue=default,crm` for imports, emails, and CRM notifications.

**Environment:**

```
CRM_DASHBOARD_CACHE_TTL=300
CRM_NOTIFICATIONS_QUEUE=crm
QUEUE_CONNECTION=database
```

---

## 14. Sample Code References

Implementation samples live in the codebase:

| Artifact | Path |
|----------|------|
| Permissions registry | `app/Support/Crm/CrmPermissions.php` |
| User permission checks | `app/Models/User.php` |
| Teams model | `app/Models/Crm/Team.php` |
| Lead model | `app/Models/Crm/Lead.php` |
| Dashboard stats | `app/Livewire/Crm/DashboardStats.php` |
| Lead table (starter) | `app/Livewire/Crm/LeadTable.php` |
| Funnel board (starter) | `app/Livewire/Crm/FunnelBoard.php` |
| CRM routes | `routes/crm.php` |
| Migrations | `database/migrations/2026_06_30_*` |
| Seeder | `database/seeders/CrmSeeder.php` |

---

## 15. Navigation Map

| Nav Item | Route Name | Permission |
|----------|------------|------------|
| Dashboard | `admin.dashboard` | `crm.dashboard.view` |
| Leads | `admin.crm.leads.index` | `leads.view` |
| Prospects | `admin.crm.prospects.index` | `prospects.view` |
| Clients | `admin.crm.clients.index` | `clients.view` |
| Funnel Board | `admin.crm.pipeline.index` | `pipeline.view` |
| Activities | `admin.crm.activities.index` | `activities.view` |
| Tasks | `admin.crm.tasks.index` | `tasks.view` |
| Appointments | `admin.crm.appointments.index` | `appointments.view` |
| Landing Pages | `admin.crm.landing-pages.index` | `landing-pages.view` |
| Reports | `admin.crm.reports.index` | `reports.view` |
| CRM Settings | `admin.crm.settings.index` | `crm.settings.manage` |

---

*Document version: 1.0 — Phase 1 foundation*
