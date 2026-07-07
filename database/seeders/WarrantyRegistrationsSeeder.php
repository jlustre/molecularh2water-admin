<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WarrantyRegistrationsSeeder extends Seeder
{
    /**
     * Seed warranty registrations from the admin export.
     */
    public function run(): void
    {
        $registrations = array (
        );

        foreach ($registrations as $registration) {
            \Illuminate\Support\Facades\DB::table('warranty_registrations')->updateOrInsert(
                ['id' => $registration['id']],
                $registration
            );
        }
    }
}
