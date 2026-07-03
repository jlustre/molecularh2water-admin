# CRM Funnel Stages Reference

Internal reference for Molecular H2 Water field-sales CRM pipeline stages, transitions, and automation.

**Source of truth:** `config/crm.php` · seeded by `CrmSeeder` · enforced by `FunnelService` and domain services · migration `2026_07_08_000001_refresh_funnel_stages_pipeline.php`

---

## Architecture

### Funnels vs business line

The CRM defines **6 funnels** (1 default retail pipeline + 5 additional templates). Funnels are **not** scoped per business line (`hcc` / `h2s`); business line lives on the **lead** record. All funnels are shared across lines.

Landing pages may override the target funnel (`LandingPage.funnel_id`). Otherwise new captures use the default `sales-funnel`.

### Lifecycle vs funnel stage vs lead status

| Concept | Purpose | Examples |
|---------|---------|----------|
| **Funnel stage** | Position on the pipeline board (Kanban column). | `demo-scheduled`, `closed-won` |
| **Lead lifecycle** | Record type / nav module filter. | `lead`, `prospect`, `client`, `recruit` |
| **Lead status** | Engagement state (`LeadStatus` enum). | `new`, `contacting`, `active`, `customer` |

These are **orthogonal**. Moving funnel stage does **not** automatically change lifecycle or status, except:

- **`closed-won`** → `AfterSalesService::enrollInAfterSales` converts lifecycle to **`client`** and moves the record to **`after-sales-funnel`** → **`warranty-registration`**.

Lifecycle can also be changed manually on the lead/prospect/client form.

### Central stage-move path

All service-driven and board-driven moves should go through **`FunnelService::moveLead()`**, which:

1. Validates lost-reason requirements on lost stages
2. Updates `funnel_stage_id`
3. Records **`PipelineStageHistory`**
4. Logs a timeline funnel event
5. Dispatches **`CrmAutomationService`** (`stage.moved`)
6. On **`closed-won`**: runs referral conversion + after-sales enrollment

**Bypass:** `LeadForm` can set `funnel_stage_id` directly via `LeadService` — this path does **not** write history, timeline funnel events, or automation side effects.

**No model observers** move pipeline stages.

### Portal vs admin CRM

- **Admin CRM:** Full funnel board drag-and-drop (`FunnelBoard`), lead profile panels, funnel configuration (`FunnelManager`).
- **Member portal:** No funnel board. Stage moves happen **indirectly** (e.g. scheduling a demo via `PortalDemoService` → `DemonstrationService::schedule` → `demo-scheduled`).

---

## Permissions and roles

| Permission | Purpose |
|------------|---------|
| `pipeline.view` | View funnel board |
| `pipeline.manage` | Drag leads between stages (`FunnelBoard`, `LeadPolicy::moveOnPipeline`) |
| `funnel.manage` | Create, edit, reorder funnel stages (`FunnelManager`) — does **not** move leads |
| `leads.update` / `prospects.manage` / `clients.manage` | Edit records and trigger service-based moves from lead profile panels |
| `crm.records.view-all` | Super Admin / Admin see all records; others see only assigned (or team) records |

| Role | Pipeline move | Funnel config | Service triggers (demo, order, quote, etc.) |
|------|---------------|---------------|-----------------------------------------------|
| **super-admin**, **admin** | Yes | Yes | Yes (all records) |
| **manager** | Yes | View only (`funnel.view`) | Yes (team-scoped via `crm.records.view-team`) |
| **consultant**, **agent**, **member** | Yes | No | Yes (own assigned leads) |
| **editor** | No CRM pipeline | No | No |

Portal users with the **member** role inherit consultant CRM permissions, including `pipeline.manage`.

Moving to any **lost** stage on the board requires a **lost reason** (`LostReason`).

---

## Funnel index

| # | Slug | Name | Default | Stages |
|---|------|------|---------|--------|
| 1 | `sales-funnel` | Retail Sales Funnel | Yes | 21 |
| 2 | `recruiting-funnel` | Recruiting Funnel | No | 7 |
| 3 | `customer-onboarding-funnel` | Customer Onboarding Funnel | No | 5 |
| 4 | `referral-funnel` | Referral Funnel | No | 5 |
| 5 | `after-sales-funnel` | After-Sales Service Funnel | No | 10 |
| 6 | `corporate-sales-funnel` | Corporate Sales Funnel | No | 6 |

---

## 1. Retail Sales Funnel (`sales-funnel`)

Primary direct-sales pipeline for cookware, H2 machines, and health products.

| # | Slug | Label | Description | Trigger(s) | Auto / Manual | Who triggers |
|---|------|-------|-------------|------------|---------------|--------------|
| 1 | `new-lead` | New Lead | Fresh inbound contact; not yet worked. | Prospect/landing-page capture (`ProspectCaptureService` → first stage); manual lead create (`LeadService` default); CSV import. | **Auto** on capture/create; **Manual** otherwise | System/assignee on capture; `leads.create` users; manual: `pipeline.manage` or form |
| 2 | `contacted` | Contacted | Initial outreach made (call, text, email). | Pipeline drag; lead form stage field. | **Manual** | `pipeline.manage` or `leads.update` |
| 3 | `qualified` | Qualified as Prospect | Contact meets basic criteria (interest, budget, fit). | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 4 | `demo-invitation-sent` | Demo Invitation Sent | Demo invite sent; awaiting booking. | Pipeline drag; lead form (e.g. after sending invite from portal). | **Manual** | `pipeline.manage` or `leads.update` |
| 5 | `demo-scheduled` | Demo Scheduled | Demo/show appointment booked. | `DemonstrationService::schedule()` → automation `demonstration.scheduled` → `move_stage: demo-scheduled`; also calendar event + assignee notification. Portal: `PortalDemoService::schedule()`. Demo outcome **Rescheduled** returns here. | **Automatic** on demo schedule | User with `leads.update` on accessible lead; portal demo modal |
| 6 | `demo-confirmed` | Demo Confirmed | Customer confirmed attendance. | Pipeline drag; lead form. `DemonstrationStatus::Confirmed` does **not** auto-move. | **Manual** | `pipeline.manage` or `leads.update` |
| 7 | `demo-completed` | Demo Completed | Demo finished; outcome pending or default. | Demo marked completed with no outcome, or outcome **Pending**. | **Automatic** on demo completion (default) | `LeadDemonstrationsPanel` / calendar demo completion (`leads.update`) |
| 8 | `interested` | Interested | Post-demo interest; moving toward purchase. | Demo outcome **Interested** (`demo_outcome_stage_map`). Manual otherwise. | **Auto** on demo outcome; else **Manual** | Demo completion UI; or `pipeline.manage` |
| 9 | `consultation` | Consultation | Needs assessment / product consultation recorded. | `ConsultationService::record()` (default `$moveStage=true`). | **Automatic** on consultation save | `LeadConsultationsPanel` (`leads.update`) |
| 10 | `quote-presented` | Quote Presented | Formal quotation shared with customer. | `QuotationService::present()`. | **Automatic** | `LeadQuotationsPanel` (`leads.update`) |
| 11 | `follow-up` | Follow-Up | Active nurturing; customer still deciding. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 12 | `decision-pending` | Decision Pending | Customer evaluating; decision expected soon. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 13 | `ready-to-purchase` | Ready to Purchase | Committed to buy; preparing order. | Demo outcome **Sold**; manual otherwise. | **Auto** on sold outcome; else **Manual** | Demo completion UI; or `pipeline.manage` |
| 14 | `order-submitted` | Order Submitted | Order formally submitted in CRM. | `OrderService::submit()`. | **Automatic** | `LeadOrdersPanel` (`leads.update`) |
| 15 | `payment-received` | Payment Received | Full payment recorded. | `OrderService::recordPayment()` when `PaymentStatus::Paid`. Fires `order.paid` automation (delivery scheduling task). | **Automatic** | `LeadOrdersPanel` |
| 16 | `delivery-scheduled` | Delivery Scheduled | Delivery appointment set. | `DeliveryService::schedule()`. | **Automatic** | `LeadOrdersPanel` |
| 17 | `delivered-installed` | Delivered / Installed | Product delivered and installation complete. | `InstallationService::complete()` (delivery complete alone does **not** move this stage). Marks order fulfilled. | **Automatic** | `LeadOrdersPanel` |
| 18 | `customer-orientation` | Customer Orientation | Post-install orientation / walkthrough. | Pipeline drag; lead form. **Side effect:** `stage.moved` automation creates 30-day follow-up task. | **Manual** move; auto task | `pipeline.manage` or `leads.update` |
| 19 | `referral-requested` | Referral Requested | Customer asked for referrals. | Pipeline drag; lead form. **Side effect:** 7-day referral campaign task. | **Manual** move; auto task | `pipeline.manage` or `leads.update` |
| 20 | `closed-won` | Closed Won | Sale successfully closed. **`is_won: true`** | Pipeline drag; lead form. **Side effects:** lifecycle → **client**, move to **after-sales-funnel** → `warranty-registration`, mark referral converted if applicable. | **Manual** stage; **automatic** downstream enrollment | `pipeline.manage` or `leads.update` |
| 21 | `closed-lost` | Closed Lost | Opportunity lost. **`is_lost: true`** | Pipeline drag (requires lost reason); demo outcome **Not Interested** (auto-sets lost reason `not-interested`). | **Manual** or **Automatic** (not interested) | `pipeline.manage` (lost reason required); demo completion UI |

### Demo outcome → stage mapping

From `config/crm.php` → `demo_outcome_stage_map`:

| Outcome | Target stage |
|---------|--------------|
| `interested` | `interested` |
| `not_interested` | `closed-lost` |
| `sold` | `ready-to-purchase` |
| `rescheduled` | `demo-scheduled` |
| `pending` | `demo-completed` |

---

## 2. Recruiting Funnel (`recruiting-funnel`)

Prospect, interview, and onboard new distributors. All stage transitions are **manual** unless custom automation is added later.

| # | Slug | Label | Description | Trigger(s) | Auto / Manual | Who triggers |
|---|------|-------|-------------|------------|---------------|--------------|
| 1 | `prospecting` | Prospecting | Identifying potential distributor candidates. | Manual lead create assigned to this funnel; pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 2 | `interview` | Interview | Initial interview conducted or scheduled. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 3 | `presentation` | Presentation | Business opportunity presented. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 4 | `recruit-follow-up` | Follow-Up | Post-presentation nurture. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 5 | `registration` | Registration | Candidate completing enrollment paperwork. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 6 | `training` | Training | Onboarding and product training in progress. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 7 | `active-distributor` | Active Distributor | Candidate is an active distributor. **`is_won: true`** | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |

Typical lifecycle: **`recruit`**. No service-layer auto-moves are wired for this funnel today.

---

## 3. Customer Onboarding Funnel (`customer-onboarding-funnel`)

Welcome new customers through installation and first follow-up. Template pipeline; not auto-enrolled from Closed Won (after-sales funnel handles post-win path).

| # | Slug | Label | Description | Trigger(s) | Auto / Manual | Who triggers |
|---|------|-------|-------------|------------|---------------|--------------|
| 1 | `onboarding-welcome` | Welcome | Welcome contact after purchase. | Manual funnel assignment; pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 2 | `onboarding-installation` | Installation | Installation scheduling / in progress. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 3 | `onboarding-orientation` | Orientation | Product orientation session. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 4 | `onboarding-first-follow-up` | First Follow-Up | Initial satisfaction check after onboarding. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 5 | `active-customer` | Active Customer | Customer fully onboarded. **`is_won: true`** | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |

---

## 4. Referral Funnel (`referral-funnel`)

Track referred prospects from introduction to close.

| # | Slug | Label | Description | Trigger(s) | Auto / Manual | Who triggers |
|---|------|-------|-------------|------------|---------------|--------------|
| 1 | `referral-received` | Referral Received | Referred contact entered the system. | `ReferralService::recordReferral()` creates referred lead at entry stage on `referral-funnel`. | **Automatic** on referral create | User with `leads.update` logging referral (`LeadReferralsPanel`) |
| 2 | `referral-contacted` | Contacted | First outreach to referred prospect. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 3 | `referral-qualified` | Qualified | Referral validated as a fit. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 4 | `referral-demo` | Demo | Demo scheduled or completed for referral. | Pipeline drag; lead form. (Demo schedule on sales funnel moves via `DemonstrationService` only when lead is on that funnel.) | **Manual** | `pipeline.manage` or `leads.update` |
| 5 | `referral-closed` | Closed | Referral converted or closed successfully. **`is_won: true`** | Pipeline drag; lead form. Referrer conversion also triggered when referred lead hits **`closed-won`** on sales funnel. | **Manual** | `pipeline.manage` or `leads.update` |

---

## 5. After-Sales Service Funnel (`after-sales-funnel`)

Warranty, maintenance, upgrades, and VIP customer care.

| # | Slug | Label | Description | Trigger(s) | Auto / Manual | Who triggers |
|---|------|-------|-------------|------------|---------------|--------------|
| 1 | `warranty-registration` | Warranty Registration | **Entry stage** after Closed Won; warranty onboarding. | `AfterSalesService::enrollInAfterSales()` when sales funnel reaches **`closed-won`**. | **Automatic** on Closed Won | System via `FunnelService::moveLead` side effect |
| 2 | `installation-complete` | Installation Complete | Install verified complete in after-sales context. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 3 | `thirty-day-follow-up` | 30-Day Follow-Up | First-month satisfaction check. | Pipeline drag; lead form. May align with task created when entering `customer-orientation` on sales funnel. | **Manual** | `pipeline.manage` or `clients.manage` |
| 4 | `ninety-day-check` | 90-Day Satisfaction Check | Quarter review of product satisfaction. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 5 | `annual-maintenance` | Annual Maintenance Reminder | Scheduled maintenance outreach. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 6 | `referral-campaign` | Referral Campaign | Active referral generation campaign. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 7 | `upgrade-campaign` | Product Upgrade Campaign | Upgrade / upsell outreach. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 8 | `cross-sell` | Cross-Sell Opportunity | Additional product opportunity identified. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 9 | `vip-customer` | VIP Customer | High-value retained customer. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |
| 10 | `brand-ambassador` | Brand Ambassador | Customer actively advocates for the brand. **`is_won: true`** | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `clients.manage` |

---

## 6. Corporate Sales Funnel (`corporate-sales-funnel`)

B2B and corporate account pipeline.

| # | Slug | Label | Description | Trigger(s) | Auto / Manual | Who triggers |
|---|------|-------|-------------|------------|---------------|--------------|
| 1 | `corporate-inquiry` | Inquiry | Corporate lead or RFP received. | Manual lead create assigned to this funnel; pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 2 | `corporate-meeting` | Meeting | Discovery or stakeholder meeting held. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 3 | `corporate-proposal` | Proposal | Formal proposal delivered. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 4 | `corporate-negotiation` | Negotiation | Terms and pricing under negotiation. | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 5 | `corporate-closed` | Closed | Deal won. **`is_won: true`** | Pipeline drag; lead form. | **Manual** | `pipeline.manage` or `leads.update` |
| 6 | `corporate-closed-lost` | Closed Lost | Deal lost. **`is_lost: true`** | Pipeline drag (requires lost reason); lead form. | **Manual** | `pipeline.manage` or `leads.update` |

---

## Automation rules (stage-related)

Configured in `config/crm.php` → `automation.rules`. Active when `CRM_AUTOMATION_ENABLED=true`.

| Event | Stage effect | Other actions |
|-------|--------------|---------------|
| `demonstration.scheduled` | → `demo-scheduled` | Calendar event (`home-demo`); notify assignee; enroll demo reminder sequence |
| `demonstration.completed` | Via `DemonstrationService` outcome map | Post-demo follow-up task (1 day, high priority) |
| `order.paid` | Stage already set by `OrderService` | Schedule delivery task (2 days, high priority) |
| `delivery.completed` | — | Customer orientation task (3 days) |
| `stage.moved` → `customer-orientation` | — | 30-day follow-up task |
| `stage.moved` → `referral-requested` | — | Referral campaign task (7 days) |
| `prospect_captured` | — | Enroll in `new-prospect-nurture` sequence |

Automation stage moves call `FunnelService::moveLeadToStageSlug` and respect the lead's current funnel.

---

## Calendar suggestions on manual moves

When a user drags a card on the pipeline board (`FunnelBoard`), the app may suggest a calendar event from `config/calendar.php` → `funnel_stage_actions` (e.g. **Contacted** → follow-up call, **Demo Scheduled** → home demo). This is a **suggestion only**; it does not change the stage.

---

## Legacy stage slug mapping

Pre-2026 pipeline refresh slugs (`config/crm.php` → `legacy_funnel_stage_slug_map`):

| Legacy slug | Current slug |
|-------------|--------------|
| `show-booked` | `demo-scheduled` |
| `show-completed` | `demo-completed` |
| `order-started` | `order-submitted` |

---

## Code references

| Area | Location |
|------|----------|
| Stage definitions | `config/crm.php` |
| Move lead / history | `app/Services/Crm/FunnelService.php` |
| Board drag-and-drop | `app/Livewire/Crm/FunnelBoard.php` |
| Funnel stage config UI | `app/Livewire/Crm/FunnelManager.php` |
| Demo → stage | `app/Services/Crm/DemonstrationService.php` |
| Quote / order / delivery / install | `QuotationService`, `OrderService`, `DeliveryService`, `InstallationService` |
| After-sales enrollment | `app/Services/Crm/AfterSalesService.php` |
| Referral entry stage | `app/Services/Crm/ReferralService.php` |
| Capture entry stage | `app/Services/Crm/ProspectCaptureService.php` |
| Automation | `app/Services/Crm/CrmAutomationService.php` |
| Stage history model | `app/Models/Crm/PipelineStageHistory.php` |
| Permissions | `app/Support/Crm/CrmPermissions.php` |
| Lead policy | `app/Policies/Crm/LeadPolicy.php` |
| Seeding | `database/seeders/CrmSeeder.php` |

---

*Last aligned with codebase: July 2026 (pipeline refresh migration `2026_07_08_000001_refresh_funnel_stages_pipeline`).*
