<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\LeadForm;
use App\Livewire\Crm\LeadProfile;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Note;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\TimelineEvent;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function crmAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function crmTimelineFor(object $contact)
{
    return TimelineEvent::query()
        ->where('contact_type', $contact->getMorphClass())
        ->where('contact_id', $contact->id);
}

function leadMgmtAgent(string $name = 'Test Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('creates a lead through the form', function () {
    $admin = crmAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.leads.create'))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(LeadForm::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'jane@example.com')
        ->set('phone', '555-0100')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $lead = Lead::query()->where('email', 'jane@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->lifecycle)->toBe(LeadLifecycle::Lead)
        ->and($lead->first_name)->toBe('Jane');

    expect(crmTimelineFor($lead)->where('event_type', 'record_created')->exists())->toBeTrue();
});

it('updates a lead and logs timeline activity', function () {
    $agent = leadMgmtAgent();
    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Original',
        'email' => 'original@example.com',
    ]);

    Livewire::actingAs($agent)
        ->test(LeadForm::class, ['lead' => $lead])
        ->set('first_name', 'Updated')
        ->call('save')
        ->assertRedirect(route('portal.crm.leads.show', $lead));

    expect($lead->fresh()->first_name)->toBe('Updated');
    expect(crmTimelineFor($lead)->where('event_type', 'record_updated')->exists())->toBeTrue();
});

it('shows lead profile with notes and timeline', function () {
    $agent = leadMgmtAgent();
    $lead = Lead::factory()->assignedTo($agent)->create();

    $this->actingAs($agent)
        ->get(route('portal.crm.leads.show', $lead))
        ->assertOk()
        ->assertSee($lead->fullName());

    Livewire::actingAs($agent)
        ->test(LeadProfile::class, ['lead' => $lead])
        ->set('noteBody', 'Called and left voicemail.')
        ->call('addNote')
        ->assertHasNoErrors();

    expect(Note::query()->where('noteable_id', $lead->id)->count())->toBe(1);
    expect(crmTimelineFor($lead)->where('event_type', 'note_added')->exists())->toBeTrue();
});

it('converts a lead to prospect and then to client', function () {
    $admin = crmAdmin();
    $lead = Lead::factory()->create([
        'email' => 'convert-flow@example.com',
        'assigned_user_id' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(LeadProfile::class, ['lead' => $lead])
        ->call('convertTo', LeadLifecycle::Prospect)
        ->assertRedirect(route('admin.crm.prospects.show', Prospect::query()->where('email', 'convert-flow@example.com')->first()));

    $prospect = Prospect::query()->where('email', 'convert-flow@example.com')->first();
    expect($prospect)->not->toBeNull()
        ->and(Lead::query()->where('email', 'convert-flow@example.com')->exists())->toBeFalse();

    Livewire::actingAs($admin)
        ->test(LeadProfile::class, ['lead' => $prospect])
        ->call('convertTo', LeadLifecycle::Client)
        ->assertRedirect(route('admin.crm.customers.show', Customer::query()->where('email', 'convert-flow@example.com')->first()));

    $customer = Customer::query()->where('email', 'convert-flow@example.com')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->lifecycleSlug())->toBe(LeadLifecycle::Client)
        ->and($customer->engagement_type?->value)->toBe('C');
});

it('marks a customer as both when converting to recruit and moves lead or prospect to recruit only', function () {
    $admin = crmAdmin();

    $customer = Customer::factory()->create([
        'email' => 'both-type@example.com',
        'assigned_user_id' => $admin->id,
        'engagement_type' => \App\Enums\Crm\EngagementType::Customer,
    ]);

    Livewire::actingAs($admin)
        ->test(LeadProfile::class, ['lead' => $customer])
        ->call('convertTo', LeadLifecycle::Recruit)
        ->assertRedirect(route('admin.crm.customers.show', $customer->fresh()));

    expect($customer->fresh()->engagement_type?->value)->toBe('B')
        ->and(Recruit::query()->where('email', 'both-type@example.com')->exists())->toBeFalse();

    $lead = Lead::factory()->create([
        'email' => 'recruit-only@example.com',
        'assigned_user_id' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(LeadProfile::class, ['lead' => $lead])
        ->call('convertTo', LeadLifecycle::Recruit)
        ->assertRedirect(route('admin.crm.recruits.show', Recruit::query()->where('email', 'recruit-only@example.com')->first()));

    $recruit = Recruit::query()->where('email', 'recruit-only@example.com')->first();

    expect($recruit)->not->toBeNull()
        ->and($recruit->engagement_type?->value)->toBe('R')
        ->and(Lead::query()->where('email', 'recruit-only@example.com')->exists())->toBeFalse();
});

it('renders customers index with customer label and redirects legacy clients urls', function () {
    $admin = crmAdmin();
    $customer = Customer::factory()->create([
        'assigned_user_id' => $admin->id,
        'first_name' => 'Pat',
        'last_name' => 'Customer',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.crm.customers.index'))
        ->assertOk()
        ->assertSee('Customer')
        ->assertDontSee('>Client<', false)
        ->assertSee('Pat Customer');

    $this->actingAs($admin)
        ->get('/admin/crm/clients')
        ->assertRedirect('/admin/crm/customers');

    $this->actingAs($admin)
        ->get('/admin/crm/clients/'.$customer->id)
        ->assertRedirect('/admin/crm/customers/'.$customer->id);
});

it('renders recruits index with recruit lifecycle filter and crud routes', function () {
    $admin = crmAdmin();
    $recruit = Recruit::factory()->create([
        'assigned_user_id' => $admin->id,
        'first_name' => 'Alex',
        'last_name' => 'Recruit',
    ]);

    Lead::factory()->create([
        'assigned_user_id' => $admin->id,
        'first_name' => 'Hidden',
        'last_name' => 'Lead',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.crm.recruits.index'))
        ->assertOk()
        ->assertSee('Recruit')
        ->assertSee('Alex Recruit')
        ->assertDontSee('Hidden Lead');

    $this->actingAs($admin)
        ->get(route('admin.crm.recruits.create'))
        ->assertOk()
        ->assertSee('Create Recruit');

    $this->actingAs($admin)
        ->get(route('admin.crm.recruits.show', $recruit))
        ->assertOk()
        ->assertSee('Alex Recruit');

    $this->actingAs($admin)
        ->get(route('admin.crm.recruits.edit', $recruit))
        ->assertOk()
        ->assertSee('Edit Recruit');

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Crm\Pages\RecruitsIndex::class)
        ->call('deleteLead', $recruit->id);

    expect(Recruit::query()->find($recruit->id))->toBeNull();
    $this->assertSoftDeleted('recruits', ['id' => $recruit->id]);
});

it('creates a recruit from the recruits create route with recruiting funnel', function () {
    $admin = crmAdmin();

    $this->actingAs($admin)
        ->get(route('portal.crm.recruits.create'))
        ->assertOk()
        ->assertSee('Create Recruit')
        ->assertSee('Back to recruit list')
        ->assertSee('Recruiting Funnel');
});

it('deletes a lead from the table', function () {
    $admin = crmAdmin();
    $lead = Lead::factory()->create(['assigned_user_id' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Crm\LeadTable::class)
        ->call('deleteLead', $lead->id);

    expect(Lead::query()->find($lead->id))->toBeNull();
});

it('bulk assigns selected leads and filters by assignee', function () {
    $admin = crmAdmin();
    $assignee = leadMgmtAgent('Bulk Assignee');
    $other = leadMgmtAgent('Other Assignee');

    $leadA = Lead::factory()->create(['assigned_user_id' => $admin->id, 'first_name' => 'Alpha']);
    $leadB = Lead::factory()->create(['assigned_user_id' => $admin->id, 'first_name' => 'Beta']);
    Lead::factory()->create(['assigned_user_id' => $other->id, 'first_name' => 'Gamma']);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Crm\LeadTable::class)
        ->set('selected', [$leadA->id, $leadB->id])
        ->set('bulkAssigneeId', (string) $assignee->id)
        ->call('bulkAssign')
        ->assertHasNoErrors();

    expect($leadA->fresh()->assigned_user_id)->toBe($assignee->id)
        ->and($leadB->fresh()->assigned_user_id)->toBe($assignee->id);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Crm\LeadTable::class)
        ->set('assignedUserId', (string) $assignee->id)
        ->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertDontSee('Gamma');
});

it('shows view button on prospects index linking to prospect profile', function () {
    $agent = leadMgmtAgent();
    $prospect = Prospect::factory()->assignedTo($agent)->create([
        'first_name' => 'Index',
        'last_name' => 'Prospect',
        'email' => 'index-prospect@example.com',
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.prospects.index'))
        ->assertOk()
        ->assertSee('View')
        ->assertSee(route('portal.crm.prospects.show', $prospect), false);

    $this->actingAs(crmAdmin())
        ->get(route('admin.crm.prospects.index'))
        ->assertOk()
        ->assertSee('View')
        ->assertSee(route('admin.crm.prospects.show', $prospect), false);
});

it('exports scoped leads as csv', function () {
    $manager = User::factory()->create();
    $manager->roles()->attach(Role::query()->where('slug', 'manager')->first());

    Lead::factory()->assignedTo($manager)->create(['first_name' => 'Visible', 'email' => 'visible@example.com']);
    Lead::factory()->create(['first_name' => 'Hidden', 'email' => 'hidden@example.com']);

    $response = $this->actingAs($manager)
        ->get(route('portal.crm.records.export', ['lifecycle' => 'lead']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $content = $response->streamedContent();
    expect($content)->toContain('Visible')
        ->and($content)->not->toContain('Hidden');
});

it('imports leads from csv', function () {
    $admin = crmAdmin();

    $csv = "first_name,last_name,email,phone,lifecycle,status,temperature,score,source,interested_in,message,tags,next_follow_up_at\n";
    $csv .= "Imported,User,imported@example.com,555-9999,lead,new,cold,10,Website,Water,,VIP|Hot Lead,\n";

    $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

    $this->actingAs($admin)
        ->post(route('admin.crm.records.import'), [
            'lifecycle' => 'lead',
            'file' => $file,
        ])
        ->assertRedirect(route('admin.crm.leads.index'))
        ->assertSessionHas('status');

    $lead = Lead::query()->where('email', 'imported@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->first_name)->toBe('Imported')
        ->and($lead->tags)->toHaveCount(2);
});

it('blocks agents from viewing another users lead profile', function () {
    $agentA = leadMgmtAgent('Agent A');
    $agentB = leadMgmtAgent('Agent B');
    $lead = Lead::factory()->assignedTo($agentB)->create();

    $this->actingAs($agentA)
        ->get(route('portal.crm.leads.show', $lead))
        ->assertNotFound();
});

it('lists create and export actions on the leads index', function () {
    $admin = crmAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.leads.index'))
        ->assertOk()
        ->assertSee('Add Lead')
        ->assertSee('Export CSV')
        ->assertSee('Import CSV');
});

it('renders leads index scoped to lead lifecycle on portal and admin', function () {
    $admin = crmAdmin();
    $agent = leadMgmtAgent();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Index',
        'last_name' => 'Lead',
    ]);
    $prospect = Prospect::factory()->assignedTo($agent)->create([
        'first_name' => 'Hidden',
        'last_name' => 'Prospect',
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.leads.index'))
        ->assertOk()
        ->assertSee('Lead')
        ->assertSee('Index Lead')
        ->assertDontSee('Hidden Prospect');

    $this->actingAs($admin)
        ->get(route('admin.crm.leads.index'))
        ->assertOk()
        ->assertSee('Index Lead')
        ->assertDontSee('Hidden Prospect');
});

it('shows convert to prospect button on leads index and profile', function () {
    $admin = crmAdmin();
    $lead = Lead::factory()->create([
        'assigned_user_id' => $admin->id,
        'first_name' => 'Convert',
        'last_name' => 'Candidate',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.crm.leads.index'))
        ->assertOk()
        ->assertSee('Convert to Prospect');

    $this->actingAs($admin)
        ->get(route('admin.crm.leads.show', $lead))
        ->assertOk()
        ->assertSee('Convert to Prospect');
});

it('converts a lead to prospect from the leads table', function () {
    $admin = crmAdmin();
    $lead = Lead::factory()->create([
        'email' => 'table-convert@example.com',
        'assigned_user_id' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Crm\Pages\LeadsIndex::class)
        ->call('convertToProspect', $lead->id)
        ->assertRedirect(route('admin.crm.prospects.show', Prospect::query()->where('email', 'table-convert@example.com')->first()));

    expect(Prospect::query()->where('email', 'table-convert@example.com')->exists())->toBeTrue()
        ->and(Lead::query()->where('email', 'table-convert@example.com')->exists())->toBeFalse();
});

it('shows leads link in portal navigation', function () {
    $agent = leadMgmtAgent();
    $labels = collect(\App\Support\Portal\PortalNavigation::links($agent))->pluck('label')->all();

    expect($labels)->toContain('Leads')
        ->and($labels)->not->toContain('CRM Home')
        ->and($labels)->toContain('Prospects')
        ->and($labels)->toContain('Customers')
        ->and($labels)->toContain('Recruits')
        ->and($labels)->toContain('Sales')
        ->and(array_search('Sales', $labels, true))
        ->toBe(array_search('Activities', $labels, true) + 1);
});

it('shows prospect profile tags business line picker and back link on create prospect', function () {
    $user = User::factory()->create([
        'business_lines' => ['hcc', 'h2s'],
    ]);
    $user->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $this->actingAs($user)
        ->get(route('portal.crm.prospects.create'))
        ->assertOk()
        ->assertSee('Back to prospect list')
        ->assertSee('HappyCookingCo')
        ->assertSee('H2S')
        ->assertDontSee('Both')
        ->assertSee('25+ years old')
        ->assertSee('w/ Dep. Children')
        ->assertSee('Health conscious')
        ->assertSee('Notes')
        ->assertSee('Address')
        ->assertSee('Occupation')
        ->assertSee('Spouse Name')
        ->assertSee('Spouse Occupation')
        ->assertSee('Best Time to Contact')
        ->assertDontSee('Select source')
        ->assertDontSee('Interested In')
        ->assertDontSee('VIP');
});

it('creates a prospect with business line and profile tags', function () {
    $user = User::factory()->create([
        'business_lines' => ['hcc', 'h2s'],
    ]);
    $user->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $marriedTag = \App\Models\Crm\Tag::query()->where('slug', 'married')->first();
    $homeownerTag = \App\Models\Crm\Tag::query()->where('slug', 'homeowner')->first();
    $source = \App\Models\Crm\LeadSource::query()->where('slug', 'referral')->first();

    Livewire::actingAs($user)
        ->test(LeadForm::class)
        ->set('lifecycle', LeadLifecycle::Prospect)
        ->set('first_name', 'Pat')
        ->set('email', 'pat@example.com')
        ->set('address', '123 Main St')
        ->set('occupation', 'Teacher')
        ->set('spouse_name', 'Sam Prospect')
        ->set('spouse_occupation', 'Nurse')
        ->set('best_time_to_contact', 'evening')
        ->set('lead_source_id', $source->id)
        ->set('message', 'Met at cooking show.')
        ->set('business_line', 'hcc')
        ->set('selectedTags', [$marriedTag->id, $homeownerTag->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $prospect = Prospect::query()->where('email', 'pat@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->lifecycleSlug())->toBe(LeadLifecycle::Prospect)
        ->and($prospect->business_line?->value)->toBe('hcc')
        ->and($prospect->address)->toBe('123 Main St')
        ->and($prospect->occupation)->toBe('Teacher')
        ->and($prospect->spouse_name)->toBe('Sam Prospect')
        ->and($prospect->spouse_occupation)->toBe('Nurse')
        ->and($prospect->best_time_to_contact)->toBe('evening')
        ->and($prospect->lead_source_id)->toBe($source->id)
        ->and($prospect->message)->toBe('Met at cooking show.')
        ->and($prospect->tags)->toHaveCount(2);
});

it('shows profile tag checkboxes on create prospect and persists selections', function () {
    \App\Models\Crm\Tag::query()->whereIn('slug', ['married', 'homeowner'])->delete();

    $user = User::factory()->create([
        'business_lines' => ['hcc', 'h2s'],
    ]);
    $user->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $this->actingAs($user)
        ->get(route('portal.crm.prospects.create'))
        ->assertOk()
        ->assertSee('Profile Tags')
        ->assertSee('married')
        ->assertSee('homeowner')
        ->assertSee('type="checkbox"', false);

    $marriedTag = \App\Models\Crm\Tag::query()->where('slug', 'married')->first();
    $homeownerTag = \App\Models\Crm\Tag::query()->where('slug', 'homeowner')->first();

    expect($marriedTag)->not->toBeNull()
        ->and($homeownerTag)->not->toBeNull();

    Livewire::actingAs($user)
        ->test(LeadForm::class)
        ->set('first_name', 'Checkbox')
        ->set('email', 'checkbox@example.com')
        ->set('selectedTags', [$marriedTag->id, $homeownerTag->id])
        ->call('save')
        ->assertHasNoErrors();

    $prospect = Lead::query()->where('email', 'checkbox@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->tags->pluck('slug')->sort()->values()->all())
        ->toBe(['homeowner', 'married']);
});

it('tracks engagement status separately from funnel stage', function () {
    $admin = crmAdmin();
    $lostStage = \App\Models\Crm\FunnelStage::query()->where('is_lost', true)->first();
    $lostReason = \App\Models\Crm\LostReason::query()->where('slug', 'not-interested')->first();

    Livewire::actingAs($admin)
        ->test(LeadForm::class)
        ->set('first_name', 'Engaged')
        ->set('email', 'engaged@example.com')
        ->set('status', 'engaged')
        ->set('funnel_stage_id', $lostStage->id)
        ->set('lost_reason_id', $lostReason->id)
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('email', 'engaged@example.com')->first();

    expect($lead->status?->value)->toBe('engaged')
        ->and($lead->funnel_stage_id)->toBe($lostStage->id)
        ->and($lead->lost_reason_id)->toBe($lostReason->id);
});

it('shows redesigned prospect profile with activities panel', function () {
    $agent = leadMgmtAgent();
    $prospect = Prospect::factory()->assignedTo($agent)->create([
        'first_name' => 'View',
        'last_name' => 'Prospect',
        'email' => 'view@example.com',
        'occupation' => 'Engineer',
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.prospects.show', $prospect))
        ->assertOk()
        ->assertSee('Contact Information')
        ->assertSee('CRM Details')
        ->assertSee('Activities')
        ->assertSee('Engineer')
        ->assertDontSee('Quick Log Activity');
});

it('allows consultants with leads.update to edit assigned prospects', function () {
    $agent = leadMgmtAgent();
    $prospect = Prospect::factory()->assignedTo($agent)->create([
        'first_name' => 'Editable',
        'last_name' => 'Prospect',
        'email' => 'editable@example.com',
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.prospects.edit', $prospect))
        ->assertOk()
        ->assertSee('Edit Prospect')
        ->assertSee('Editable')
        ->assertSee('layoutSidebar()', false);
});

it('denies prospect edit to users without record access', function () {
    $viewer = User::factory()->create();
    $viewer->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $prospect = Prospect::factory()->create([
        'first_name' => 'Locked',
        'email' => 'locked@example.com',
    ]);

    $this->actingAs($viewer)
        ->get(route('portal.crm.prospects.edit', $prospect))
        ->assertNotFound();
});

it('logs activities from the prospect activities panel', function () {
    $agent = leadMgmtAgent();
    $prospect = Prospect::factory()->assignedTo($agent)->create();
    $type = \App\Models\Crm\ActivityType::query()->where('slug', 'phone-call')->first();

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadActivitiesPanel::class, ['lead' => $prospect])
        ->call('toggleLogForm')
        ->set('activity_type_id', $type->id)
        ->set('description', 'Left voicemail about the cooking show.')
        ->call('logActivity')
        ->assertHasNoErrors();

    expect($prospect->activities()->count())->toBe(1)
        ->and($prospect->fresh()->last_contacted_at)->not->toBeNull();
});
