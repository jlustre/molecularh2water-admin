<?php

namespace Database\Seeders;

use App\Models\Crm\Funnel;
use App\Services\Crm\FunnelService;
use Illuminate\Database\Seeder;

class FunnelsSeeder extends Seeder
{
    /**
     * Seed CRM funnels and stages from the admin export generated at 2026-07-25 02:22:09.
     */
    public function run(): void
    {
        $funnels = array (
  0 => 
  array (
    'slug' => 'sales-funnel',
    'name' => 'Retail Sales Funnel',
    'description' => 'Demonstration-based direct sales pipeline for premium consumer products.',
    'is_default' => true,
    'is_active' => true,
    'stages' => 
    array (
      0 => 
      array (
        'name' => 'Demo Invitation Sent',
        'slug' => 'demo-invitation-sent',
        'color' => 'cyan',
        'sort_order' => 4,
      ),
      1 => 
      array (
        'name' => 'Demo Scheduled',
        'slug' => 'demo-scheduled',
        'color' => 'cyan',
        'sort_order' => 5,
      ),
      2 => 
      array (
        'name' => 'Demo Confirmed',
        'slug' => 'demo-confirmed',
        'color' => 'indigo',
        'sort_order' => 6,
      ),
      3 => 
      array (
        'name' => 'Demo Completed',
        'slug' => 'demo-completed',
        'color' => 'cyan',
        'sort_order' => 7,
      ),
      4 => 
      array (
        'name' => 'Follow-Up',
        'slug' => 'follow-up',
        'color' => 'amber',
        'sort_order' => 11,
      ),
      5 => 
      array (
        'name' => 'Decision Pending',
        'slug' => 'decision-pending',
        'color' => 'orange',
        'sort_order' => 12,
      ),
      6 => 
      array (
        'name' => 'Order Submitted',
        'slug' => 'order-submitted',
        'color' => 'orange',
        'sort_order' => 14,
      ),
      7 => 
      array (
        'name' => 'Payment Received',
        'slug' => 'payment-received',
        'color' => 'orange',
        'sort_order' => 15,
      ),
      8 => 
      array (
        'name' => 'Delivery Scheduled',
        'slug' => 'delivery-scheduled',
        'color' => 'cyan',
        'sort_order' => 16,
      ),
      9 => 
      array (
        'name' => 'Delivered / Installed',
        'slug' => 'delivered-installed',
        'color' => 'cyan',
        'sort_order' => 17,
      ),
      10 => 
      array (
        'name' => 'Referral Requested',
        'slug' => 'referral-requested',
        'color' => 'rose',
        'sort_order' => 19,
      ),
      11 => 
      array (
        'name' => 'Closed Won',
        'slug' => 'closed-won',
        'color' => 'cyan',
        'sort_order' => 20,
        'is_won' => true,
      ),
      12 => 
      array (
        'name' => 'Closed Lost',
        'slug' => 'closed-lost',
        'color' => 'rose',
        'sort_order' => 21,
        'is_lost' => true,
      ),
    ),
  ),
  1 => 
  array (
    'slug' => 'after-sales-funnel',
    'name' => 'After-Sales Service Funnel',
    'description' => 'Warranty, maintenance, upgrades, and VIP customer care.',
    'is_default' => false,
    'is_active' => true,
    'stages' => 
    array (
      0 => 
      array (
        'name' => 'Warranty Registration',
        'slug' => 'warranty-registration',
        'color' => 'slate',
        'sort_order' => 1,
      ),
      1 => 
      array (
        'name' => 'Installation Complete',
        'slug' => 'installation-complete',
        'color' => 'cyan',
        'sort_order' => 2,
      ),
      2 => 
      array (
        'name' => '30-Day Follow-Up',
        'slug' => 'thirty-day-follow-up',
        'color' => 'blue',
        'sort_order' => 3,
      ),
      3 => 
      array (
        'name' => '90-Day Satisfaction Check',
        'slug' => 'ninety-day-check',
        'color' => 'indigo',
        'sort_order' => 4,
      ),
      4 => 
      array (
        'name' => 'Annual Maintenance Reminder',
        'slug' => 'annual-maintenance',
        'color' => 'amber',
        'sort_order' => 5,
      ),
      5 => 
      array (
        'name' => 'Referral Campaign',
        'slug' => 'referral-campaign',
        'color' => 'orange',
        'sort_order' => 6,
      ),
      6 => 
      array (
        'name' => 'Product Upgrade Campaign',
        'slug' => 'upgrade-campaign',
        'color' => 'orange',
        'sort_order' => 7,
      ),
      7 => 
      array (
        'name' => 'Cross-Sell Opportunity',
        'slug' => 'cross-sell',
        'color' => 'amber',
        'sort_order' => 8,
      ),
      8 => 
      array (
        'name' => 'VIP Customer',
        'slug' => 'vip-customer',
        'color' => 'emerald',
        'sort_order' => 9,
      ),
      9 => 
      array (
        'name' => 'Brand Ambassador',
        'slug' => 'brand-ambassador',
        'color' => 'emerald',
        'sort_order' => 10,
        'is_won' => true,
      ),
    ),
  ),
  2 => 
  array (
    'slug' => 'customer-onboarding-funnel',
    'name' => 'Customer Onboarding Funnel',
    'description' => 'Welcome new customers through installation and first follow-up.',
    'is_default' => false,
    'is_active' => true,
    'stages' => 
    array (
      0 => 
      array (
        'name' => 'Welcome',
        'slug' => 'onboarding-welcome',
        'color' => 'cyan',
        'sort_order' => 1,
      ),
      1 => 
      array (
        'name' => 'Installation',
        'slug' => 'onboarding-installation',
        'color' => 'blue',
        'sort_order' => 2,
      ),
      2 => 
      array (
        'name' => 'Orientation',
        'slug' => 'onboarding-orientation',
        'color' => 'indigo',
        'sort_order' => 3,
      ),
      3 => 
      array (
        'name' => 'First Follow-Up',
        'slug' => 'onboarding-first-follow-up',
        'color' => 'amber',
        'sort_order' => 4,
      ),
      4 => 
      array (
        'name' => 'Active Customer',
        'slug' => 'active-customer',
        'color' => 'emerald',
        'sort_order' => 5,
        'is_won' => true,
      ),
    ),
  ),
  3 => 
  array (
    'slug' => 'recruiting-funnel',
    'name' => 'Recruiting Funnel',
    'description' => 'Prospect, interview, and onboard new distributors.',
    'is_default' => false,
    'is_active' => true,
    'stages' => 
    array (
      0 => 
      array (
        'name' => 'Prospecting',
        'slug' => 'prospecting',
        'color' => 'slate',
        'sort_order' => 1,
      ),
      1 => 
      array (
        'name' => 'Interview',
        'slug' => 'interview',
        'color' => 'cyan',
        'sort_order' => 2,
      ),
      2 => 
      array (
        'name' => 'Presentation',
        'slug' => 'presentation',
        'color' => 'blue',
        'sort_order' => 3,
      ),
      3 => 
      array (
        'name' => 'Follow-Up',
        'slug' => 'recruit-follow-up',
        'color' => 'amber',
        'sort_order' => 4,
      ),
      4 => 
      array (
        'name' => 'Registration',
        'slug' => 'registration',
        'color' => 'indigo',
        'sort_order' => 5,
      ),
      5 => 
      array (
        'name' => 'Training',
        'slug' => 'training',
        'color' => 'orange',
        'sort_order' => 6,
      ),
      6 => 
      array (
        'name' => 'Active Distributor',
        'slug' => 'active-distributor',
        'color' => 'emerald',
        'sort_order' => 7,
        'is_won' => true,
      ),
    ),
  ),
  4 => 
  array (
    'slug' => 'referral-funnel',
    'name' => 'Referral Funnel',
    'description' => 'Track referred prospects from introduction to close.',
    'is_default' => false,
    'is_active' => true,
    'stages' => 
    array (
      0 => 
      array (
        'name' => 'Referral Received',
        'slug' => 'referral-received',
        'color' => 'cyan',
        'sort_order' => 1,
      ),
      1 => 
      array (
        'name' => 'Contacted',
        'slug' => 'referral-contacted',
        'color' => 'cyan',
        'sort_order' => 2,
      ),
      2 => 
      array (
        'name' => 'Qualified',
        'slug' => 'qualified',
        'color' => 'cyan',
        'sort_order' => 3,
        'is_won' => true,
      ),
    ),
  ),
);

        $funnelService = app(FunnelService::class);

        foreach ($funnels as $funnelData) {
            $funnel = Funnel::query()->updateOrCreate(
                ['slug' => $funnelData['slug']],
                [
                    'name' => $funnelData['name'],
                    'description' => $funnelData['description'] ?? null,
                    'is_default' => (bool) ($funnelData['is_default'] ?? false),
                    'is_active' => (bool) ($funnelData['is_active'] ?? true),
                ],
            );

            $funnelService->seedStages($funnel, $funnelData['stages'] ?? []);
        }
    }
}