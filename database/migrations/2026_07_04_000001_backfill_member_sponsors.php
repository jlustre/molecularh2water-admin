<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superAdminIds = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.slug', 'super-admin')
            ->pluck('role_user.user_id');

        User::query()
            ->whereIn('id', $superAdminIds)
            ->update(['sponsor_id' => null]);

        $defaultSponsorId = $superAdminIds->first();

        if ($defaultSponsorId) {
            User::query()
                ->whereNull('sponsor_id')
                ->whereNotIn('id', $superAdminIds)
                ->update(['sponsor_id' => $defaultSponsorId]);
        }
    }

    public function down(): void
    {
        // No rollback — sponsor assignments may have changed since migration.
    }
};
