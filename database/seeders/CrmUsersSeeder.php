<?php

namespace Database\Seeders;

use App\Enums\BusinessLine;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmUsersSeeder extends Seeder
{
    /**
     * Seed demo CRM users for local and QA testing.
     */
    public function run(): void
    {
        $password = bcrypt('Password123');

        $rootSponsorId = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
            ->value('id');

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@crm.demo'],
            [
                'name' => 'Edwin Lagadi',
                'password' => $password,
                'sponsor_id' => $rootSponsorId,
                'business_lines' => BusinessLine::values(),
            ],
        );
        $manager->roles()->syncWithoutDetaching([
            Role::query()->where('slug', 'manager')->value('id'),
        ]);

        foreach ([
            ['email' => 'agent1@crm.demo', 'name' => 'Alex Rivera', 'business_lines' => [BusinessLine::Hcc->value]],
            ['email' => 'agent2@crm.demo', 'name' => 'Jordan Kim', 'business_lines' => [BusinessLine::H2s->value]],
            ['email' => 'agent3@crm.demo', 'name' => 'Sam Patel', 'business_lines' => BusinessLine::values()],
        ] as $agent) {
            $user = User::query()->updateOrCreate(
                ['email' => $agent['email']],
                [
                    'name' => $agent['name'],
                    'password' => $password,
                    'sponsor_id' => $manager->id,
                    'business_lines' => $agent['business_lines'],
                ],
            );
            $user->roles()->syncWithoutDetaching([
                Role::query()->where('slug', 'consultant')->value('id'),
            ]);
        }
    }
}
