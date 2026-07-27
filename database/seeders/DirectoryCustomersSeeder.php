<?php

namespace Database\Seeders;

use App\Enums\Crm\EngagementType;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Lifecycle;
use Illuminate\Database\Seeder;

class DirectoryCustomersSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'first_name' => 'Maria',
                'last_name' => 'Gonzalez',
                'email' => 'maria.gonzalez@example.com',
                'phone' => '(310) 555-0142',
                'address' => '1847 Ocean Ave',
                'city' => 'Santa Monica',
                'state' => 'CA',
                'postal_code' => '90401',
                'message' => 'Prefers morning installations.',
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Chen',
                'email' => 'james.chen@example.com',
                'phone' => '(415) 555-0188',
                'address' => '92 Valencia St',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postal_code' => '94103',
                'message' => null,
            ],
            [
                'first_name' => 'Aisha',
                'last_name' => 'Patel',
                'email' => 'aisha.patel@example.com',
                'phone' => '(619) 555-0117',
                'address' => '455 Harbor Dr',
                'city' => 'San Diego',
                'state' => 'CA',
                'postal_code' => '92101',
                'message' => 'Gate code in notes at arrival.',
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Kim',
                'email' => 'robert.kim@example.com',
                'phone' => '(408) 555-0164',
                'address' => '1200 Park Ave',
                'city' => 'San Jose',
                'state' => 'CA',
                'postal_code' => '95126',
                'message' => null,
            ],
            [
                'first_name' => 'Elena',
                'last_name' => 'Rossi',
                'email' => 'elena.rossi@example.com',
                'phone' => '(916) 555-0193',
                'address' => '33 Capitol Mall',
                'city' => 'Sacramento',
                'state' => 'CA',
                'postal_code' => '95814',
                'message' => 'Condo building; check in with lobby.',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Nguyen',
                'email' => 'david.nguyen@example.com',
                'phone' => '(714) 555-0129',
                'address' => '780 Beach Blvd',
                'city' => 'Huntington Beach',
                'state' => 'CA',
                'postal_code' => '92648',
                'message' => null,
            ],
            [
                'first_name' => 'Sofia',
                'last_name' => 'Alvarez',
                'email' => 'sofia.alvarez@example.com',
                'phone' => '(661) 555-0175',
                'address' => '510 Palm Dr',
                'city' => 'Bakersfield',
                'state' => 'CA',
                'postal_code' => '93301',
                'message' => 'Dog in backyard — call ahead.',
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brooks',
                'email' => 'michael.brooks@example.com',
                'phone' => '(805) 555-0136',
                'address' => '221 Figueroa St',
                'city' => 'Ventura',
                'state' => 'CA',
                'postal_code' => '93001',
                'message' => null,
            ],
        ];

        foreach ($customers as $customer) {
            $postalCode = $customer['postal_code'];
            unset($customer['postal_code']);

            Customer::query()->updateOrCreate(
                ['email' => $customer['email']],
                [
                    ...$customer,
                    'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Client),
                    'business_line' => 'h2s',
                    'status' => LeadStatus::Customer,
                    'engagement_type' => EngagementType::Customer,
                    'temperature' => 'cold',
                    'score' => 0,
                    'country' => 'US',
                    'consent_given' => true,
                    'converted_at' => now(),
                    'metadata' => ['postal_code' => $postalCode],
                ],
            );
        }
    }
}
