<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $agent = Role::query()->where('slug', 'agent')->first();

        if ($agent && ! Role::query()->where('slug', 'consultant')->exists()) {
            $agent->update([
                'slug' => 'consultant',
                'name' => 'Consultant',
                'description' => 'Field associate with portal CRM access to assigned leads, tasks, and calendar.',
            ]);
        }
    }

    public function down(): void
    {
        Role::query()
            ->where('slug', 'consultant')
            ->whereDoesntHave('users')
            ->update(['slug' => 'agent', 'name' => 'Agent']);
    }
};
