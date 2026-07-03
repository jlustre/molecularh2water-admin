# CRM Calendar System — Architecture & Implementation Guide

Unified calendar module for the Molecular H2 Water admin CRM (TALL stack). Integrates with leads, prospects, clients, tasks, activities, funnel stages, teams, and reminders.

**Status:** Phases 1–3 implemented · Phases 4–7 documented and partially wired

**Route:** `GET /admin/crm/calendar` → `admin.crm.calendar.index`  
**Permission:** `calendar.view` (manage: `calendar.manage`)

---

## 1. Recommended Folder Structure

```
app/
├── Enums/Crm/
│   ├── CalendarEventStatus.php
│   └── CalendarEventPriority.php
├── Livewire/Crm/Calendar/
│   └── CalendarDashboard.php          # Hosts views, modals, widgets, filters
├── Models/Crm/
│   ├── CalendarEvent.php
│   ├── CalendarEventType.php
│   ├── CalendarEventAttendee.php
│   ├── CalendarEventReminder.php
│   └── CalendarEventNote.php
├── Services/Crm/
│   ├── CalendarEventService.php       # CRUD, complete → activity
│   └── CalendarQueryService.php       # Unified feed (events + tasks + appointments)
└── Support/Crm/
    └── CalendarScope.php              # Row-level visibility

config/calendar.php                    # Types, reminders, funnel actions, colors

database/
├── migrations/2026_07_01_000001_create_calendar_tables.php
├── seeders/CalendarSeeder.php
└── factories/CalendarEventFactory.php

resources/views/livewire/crm/calendar/
├── calendar-dashboard.blade.php
└── partials/
    ├── month-view.blade.php
    ├── week-view.blade.php
    ├── day-view.blade.php
    ├── agenda-view.blade.php
    ├── event-modal.blade.php
    ├── detail-panel.blade.php
    └── widgets.blade.php

tests/Feature/Crm/CalendarSystemTest.php
```

---

## 2. Database Design

### `calendar_event_types`
Lookup for event kinds (phone call, presentation, follow-up, etc.).

| Column | Purpose |
|--------|---------|
| slug | Stable key (`phone-call`) |
| color | Tailwind color key for UI badges |
| creates_activity | Auto-log activity on completion |
| activity_type_slug | Maps to `activity_types.slug` |

### `calendar_events` (core)
| Column | Purpose |
|--------|---------|
| user_id | Assigned agent |
| team_id | Team context |
| related_type / related_id | Morph to `Lead`, `FunnelStage`, etc. |
| task_id | Optional link to CRM task |
| start_at / end_at / timezone | Scheduling |
| status | scheduled, confirmed, completed, cancelled, missed |
| priority | low, normal, high, urgent |
| reminder_enabled | Master switch |
| completed_at / cancelled_at / completion_notes | Lifecycle |

### `calendar_event_attendees`
Internal users or external guests (`user_id` nullable).

### `calendar_event_reminders`
Automation-ready rows: `channel`, `minutes_before`, `remind_at`, `sent_at`.

### `calendar_event_notes`
Threaded notes on an event (separate from completion notes).

### Existing tables (integrated, not duplicated)
- `leads` — morph `related` (lifecycle = lead/prospect/client)
- `tasks` — shown on calendar via `CalendarQueryService`; link via `task_id`
- `activities` — created when events complete (via `CalendarEventService`)
- `appointments` — legacy month data merged into calendar feed until migrated

---

## 3. Development Phases

### Phase 1 — Schema, models, seeders ✅

**Purpose:** Persistent calendar domain separate from legacy `appointments`.

**Database:** Migration `2026_07_01_000001_create_calendar_tables.php`

**Models & relationships:**
- `CalendarEvent` → `user`, `team`, `type`, `related` (morph), `task`, `attendees`, `reminders`, `notes`
- `Lead` → `calendarEvents()` morphMany
- `User` → `calendarEvents()` hasMany

**Seeders:** `CalendarSeeder` (from `config/calendar.php` event types)  
**Permissions:** `calendar.view`, `calendar.manage`, `calendar.view-team`, `calendar.view-all`

**Testing checklist:**
- [x] Migration runs on SQLite/MySQL
- [x] `CalendarSeeder` creates 12 event types
- [x] Factory creates valid events

**Sample workflow:** `php artisan db:seed --class=CalendarSeeder`

---

### Phase 2 — Dashboard & views ✅

**Purpose:** Professional calendar UI with month/week/day/agenda.

**Livewire:** `CalendarDashboard`  
**Views:** Tailwind grid (month), column week, hourly day list, grouped agenda  
**Widgets:** Upcoming events, overdue follow-ups, tasks due today  
**Navigation:** Today · Prev/Next · View switcher

**Alpine.js:** Detail panel toggle (`x-data` on dashboard root)

**Testing checklist:**
- [x] Agent can load `/admin/crm/calendar`
- [x] Month grid renders
- [x] View switcher changes template

---

### Phase 3 — Event CRUD ✅

**Purpose:** Create, edit, complete, cancel, delete, reschedule.

**Service:** `CalendarEventService`  
**Validation:** Livewire rules in `CalendarDashboard::eventRules()`  
**Authorization:** `calendar.manage` + `CalendarScope::eventIsAccessible()`

**Testing checklist:**
- [x] Create event linked to lead
- [x] Complete event logs activity
- [x] Scoped agents cannot see others' events

**Sample workflow:**
1. Agent opens Calendar → **+ New Event**
2. Selects lead, type, start/end, reminders
3. Saves → timeline event `calendar_event_scheduled`

---

### Phase 4 — CRM profile integration 🔜

**Purpose:** Schedule from lead/prospect/client profiles.

**Planned components:**
- `LeadEngagementPanel` → “Schedule on calendar” button → `route('admin.crm.calendar.index', ['lead' => $lead->id])`
- `CalendarEventForm` props: `lead_id`, suggested `event_type` from lifecycle

**Authorization:** `CrmScope::leadIsAccessible()` before prefill

**Testing checklist:**
- [ ] Open calendar from lead profile with lead pre-selected
- [ ] Prospect vs client shows correct route back link

**Sample workflow:** Lead profile → Schedule follow-up → Calendar opens with lead + `follow-up` type

---

### Phase 5 — Funnel next-action prompts 🔜

**Purpose:** Prompt scheduling when moving pipeline stages.

**Config:** `config/calendar.php` → `funnel_stage_actions`  
**Service:** `CalendarEventService::suggestForStage(FunnelStage $stage)`

**Planned Livewire:** `FunnelBoard` dispatches browser event / opens modal with suggested title + type

**Testing checklist:**
- [ ] Move to “Contacted” suggests follow-up call
- [ ] Move to “Appointment Scheduled” suggests zoom meeting
- [ ] Skip prompt still allows move without event

**Sample workflow:**
```
Move lead → Contacted
  → Modal: "Schedule follow-up call?"
  → Yes → CalendarEvent created, lead stays on stage
```

---

### Phase 6 — Reminders, notifications, filters 🔜

**Purpose:** Reliable reminders and manager visibility.

**Implemented now:**
- Reminder rows on create/update
- Filters: agent, type, status, tasks, appointments
- `CalendarScope` team visibility for managers

**Remaining:**
- `SendCrmReminders` → process `calendar_event_reminders`
- `CalendarEventReminderNotification` (queued)
- SMS/browser channels (structure ready via `channel` column)

**Permissions matrix:**

| Role | Scope |
|------|-------|
| Super Admin / Admin | All events (`calendar.view-all` / `crm.records.view-all`) |
| Manager | Own + team (`calendar.view-team`) |
| Agent | Own events only |

---

### Phase 7 — Reports & deployment 🔜

**Purpose:** Calendar analytics in Reports dashboard.

**Planned metrics:**
- Appointments booked / completed / missed / cancelled
- Events by agent, by funnel stage
- Activities auto-created from events

**Deployment:**
```bash
php artisan migrate --force
php artisan db:seed --class=CalendarSeeder
php artisan queue:work --queue=default,crm
```

---

## 4. Authorization Rules

```php
// Route middleware
Route::middleware(['permission:calendar.view'])->group(...);

// Component mount
abort_unless(auth()->user()?->hasPermission('calendar.view'), 403);

// Mutations
abort_unless(auth()->user()?->hasPermission('calendar.manage'), 403);

// Row scope
CalendarScope::events($query, $user);
```

---

## 5. Example: CRM Integration Workflow

```php
// Schedule from lead profile (Phase 4)
app(CalendarEventService::class)->create([
    'calendar_event_type_id' => CalendarEventType::where('slug', 'follow-up')->value('id'),
    'lead_id' => $lead->id,
    'title' => 'Follow-up — '.$lead->fullName(),
    'start_at' => now()->addDays(2)->setHour(10),
    'end_at' => now()->addDays(2)->setHour(11),
    'reminder_minutes' => [15, 60],
], auth()->user());
```

On completion → `ActivityService::log()` + `last_contacted_at` updated.

---

## 6. Example: Funnel Next-Action Workflow

```php
$suggestion = app(CalendarEventService::class)->suggestForStage($stage);
// ['event_type_slug' => 'follow-up', 'title' => 'Follow-up call']

if ($suggestion) {
    // Show Livewire modal; on confirm:
    $type = CalendarEventType::where('slug', $suggestion['event_type_slug'])->first();
    app(CalendarEventService::class)->create([
        'calendar_event_type_id' => $type->id,
        'lead_id' => $lead->id,
        'title' => $suggestion['title'].' — '.$lead->fullName(),
        'start_at' => $proposedStart,
    ], $user);
}
```

---

## 7. Unified Calendar Feed

`CalendarQueryService::entries()` merges:
1. **calendar_events** — primary
2. **tasks** — `due_at` as start (amber badges)
3. **appointments** — legacy (blue badges)

Toggle via dashboard filters `show_tasks` / `show_appointments`.

---

## 8. Demo Data

After `CrmDemoSeeder`, each demo agent receives 3 calendar events linked to their leads.

**Login:** `agent1@crm.demo` / `Password123`  
**URL:** `/admin/crm/calendar`

---

## 9. Related Documentation

- [CRM_FUNNEL_SYSTEM.md](./CRM_FUNNEL_SYSTEM.md) — Core CRM phases 1–7
- `config/calendar.php` — Event types, reminder presets, funnel actions
