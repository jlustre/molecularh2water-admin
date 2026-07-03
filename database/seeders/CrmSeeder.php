<?php

namespace Database\Seeders;

use App\Models\Crm\ActivityType;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LandingPage;
use App\Models\Crm\LeadCaptureForm;
use App\Models\Crm\LeadSource;
use App\Models\Crm\LostReason;
use App\Models\Crm\Tag;
use App\Models\Crm\Team;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\FunnelService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('crm.lead_sources', []) as $index => $name) {
            LeadSource::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        foreach (config('crm.default_lost_reasons', []) as $index => $reason) {
            LostReason::query()->updateOrCreate(
                ['slug' => $reason['slug'] ?? Str::slug($reason['name'])],
                [
                    'name' => $reason['name'],
                    'description' => $reason['description'] ?? null,
                    'requires_detail' => (bool) ($reason['requires_detail'] ?? false),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        foreach (['VIP', 'Hot Lead', 'Referral', 'Follow-Up', 'Cookware', 'H2 Machine', 'Health Products'] as $name) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }

        foreach (config('crm.prospect_profile_tags', []) as $name) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }

        foreach (config('crm.activity_types', []) as $index => $type) {
            ActivityType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        ActivityType::query()
            ->whereIn('slug', config('crm.legacy_inactive_activity_slugs', []))
            ->update(['is_active' => false]);

        foreach (config('crm.default_products', []) as $product) {
            CrmProduct::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'category' => $product['category'] ?? null,
                    'description' => $product['description'] ?? null,
                    'unit_price' => $product['unit_price'] ?? 0,
                    'is_active' => true,
                    'sort_order' => $product['sort_order'] ?? 0,
                ],
            );
        }

        $funnel = Funnel::query()->updateOrCreate(
            ['slug' => config('crm.default_funnel_slug', 'sales-funnel')],
            [
                'name' => 'Retail Sales Funnel',
                'description' => 'Demonstration-based direct sales pipeline for premium consumer products.',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        app(FunnelService::class)->seedStages($funnel, config('crm.default_stages', []));

        foreach (config('crm.additional_pipelines', []) as $pipeline) {
            $extraFunnel = Funnel::query()->updateOrCreate(
                ['slug' => $pipeline['slug']],
                [
                    'name' => $pipeline['name'],
                    'description' => $pipeline['description'] ?? null,
                    'is_default' => false,
                    'is_active' => true,
                ],
            );

            app(FunnelService::class)->seedStages($extraFunnel, $pipeline['stages'] ?? []);
        }

        $team = Team::query()->updateOrCreate(
            ['slug' => 'sales-team'],
            [
                'name' => 'Sales Team',
                'description' => 'Primary sales and customer engagement team.',
                'is_active' => true,
            ],
        );

        $managerRole = Role::query()->where('slug', 'manager')->first();
        if ($managerRole) {
            $manager = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'manager'))->first();
            if ($manager) {
                $team->users()->syncWithoutDetaching([$manager->id => ['role' => 'lead']]);
                $team->update(['manager_id' => $manager->id]);
            }
        }

        $page = LandingPage::query()->updateOrCreate(
            ['slug' => 'water-awareness-show'],
            [
                'funnel_id' => $funnel->id,
                'business_line' => 'h2s',
                'title' => 'Water Awareness Show',
                'headline' => 'Experience Molecular Hydrogen Water',
                'subheadline' => 'Schedule a show or request more information.',
                'cta_label' => 'Get Started',
                'thank_you_headline' => 'Thank you!',
                'thank_you_body' => 'A team member will contact you shortly.',
                'tracking_source' => 'Landing Page',
                'is_published' => true,
            ],
        );

        LeadCaptureForm::query()->updateOrCreate(
            ['landing_page_id' => $page->id],
            [
                'fields' => config('crm.landing_pages.default_form_fields', []),
                'settings' => config('crm.landing_pages.default_form_settings', []),
            ],
        );
    }
}
