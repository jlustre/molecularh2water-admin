<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailMappingsSeeder extends Seeder
{
    /**
     * Seed email mappings from the admin export generated at 2026-07-24 16:25:11.
     */
    public function run(): void
    {
        $mappings = array (
  0 => 
  array (
    'id' => 1,
    'form_key' => 'installation_questionnaire',
    'email' => 'shipping@happycookingco.com',
    'name' => 'Shipping Team',
    'is_active' => true,
    'notes' => 'Default recipient for pre-installation questionnaires.',
    'created_at' => '2026-07-24 16:17:41',
    'updated_at' => '2026-07-24 16:20:14',
  ),
);

        foreach ($mappings as $mapping) {
            DB::table('email_mappings')->updateOrInsert(
                ['id' => $mapping['id']],
                $mapping
            );
        }
    }
}