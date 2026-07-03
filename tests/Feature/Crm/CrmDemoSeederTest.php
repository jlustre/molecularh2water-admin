<?php

use App\Models\Crm\Activity;
use App\Models\Crm\Appointment;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Note;
use App\Models\Crm\Task;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use Database\Seeders\CrmDemoSeeder;
use Database\Seeders\CrmMarketingSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\CrmUsersSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([
        RolesSeeder::class,
        CrmSeeder::class,
        CalendarSeeder::class,
        CrmUsersSeeder::class,
        CrmMarketingSeeder::class,
        CrmDemoSeeder::class,
    ]);
});

it('seeds demo crm users with known credentials', function () {
    expect(User::query()->where('email', 'manager@crm.demo')->exists())->toBeTrue();
    expect(User::query()->where('email', 'agent1@crm.demo')->exists())->toBeTrue();
    expect(User::query()->where('email', 'agent3@crm.demo')->exists())->toBeTrue();
});

it('seeds leads across lifecycles and pipeline stages', function () {
    expect(Lead::query()->count())->toBeGreaterThanOrEqual(12);
    expect(Prospect::query()->count())->toBeGreaterThanOrEqual(9);
    expect(Customer::query()->count())->toBeGreaterThanOrEqual(6);
    expect(Lead::query()->whereNotNull('funnel_stage_id')->count()
        + Prospect::query()->whereNotNull('funnel_stage_id')->count()
        + Customer::query()->whereNotNull('funnel_stage_id')->count())->toBeGreaterThanOrEqual(20);
});

it('seeds engagement records for dashboard and module testing', function () {
    expect(Activity::query()->count())->toBeGreaterThanOrEqual(30);
    expect(Task::query()->count())->toBeGreaterThanOrEqual(30);
    expect(Appointment::query()->count())->toBeGreaterThan(10);
    expect(\App\Models\Crm\CalendarEvent::query()->count())->toBeGreaterThanOrEqual(9);
    expect(TimelineEvent::query()->count())->toBeGreaterThanOrEqual(30);
    expect(Note::query()->count())->toBeGreaterThan(10);
    expect(DB::table('crm_contact_tag')->count())->toBeGreaterThan(10);
    expect(DB::table('attachments')->count())->toBeGreaterThan(10);
});

it('seeds marketing templates and follow-up sequences', function () {
    expect(DB::table('email_templates')->count())->toBeGreaterThanOrEqual(3);
    expect(DB::table('sms_templates')->count())->toBeGreaterThanOrEqual(2);
    expect(DB::table('followup_sequences')->where('slug', 'new-prospect-nurture')->exists())->toBeTrue();
    expect(DB::table('followup_sequence_steps')->count())->toBeGreaterThanOrEqual(3);
});

it('assigns demo agents to the sales team', function () {
    $team = DB::table('teams')->where('slug', 'sales-team')->first();

    expect($team)->not->toBeNull();
    expect(DB::table('team_user')->where('team_id', $team->id)->count())->toBeGreaterThanOrEqual(4);
});

it('renders populated crm modules for a demo agent', function () {
    $agent = User::query()->where('email', 'agent1@crm.demo')->first();

    $this->actingAs($agent)
        ->get(route('portal.crm.leads.index'))
        ->assertOk()
        ->assertSee('Lead');

    $manager = User::query()->where('email', 'manager@crm.demo')->first();

    $this->actingAs($manager)
        ->get(route('portal.crm.reports.index'))
        ->assertOk()
        ->assertSee('Total Records');
});
