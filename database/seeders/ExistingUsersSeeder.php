<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExistingUsersSeeder extends Seeder
{
    /**
     * Restore users exported from Admin → User Management.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/existing_users.php');

        if (! is_file($path)) {
            return;
        }

        /** @var list<array<string, mixed>> $users */
        $users = require $path;

        if (! is_array($users) || $users === []) {
            return;
        }

        foreach ($users as $userData) {
            $email = $userData['email'] ?? null;

            if (! is_string($email) || $email === '') {
                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $userData['name'] ?? $email,
                    'password' => $userData['password'],
                    'email_verified_at' => $userData['email_verified_at'] ?? null,
                    'business_lines' => $userData['business_lines'] ?? null,
                    'sponsor_id' => null,
                ],
            );

            $roleSlugs = collect($userData['roles'] ?? [])
                ->filter(fn ($slug) => is_string($slug) && $slug !== '')
                ->values()
                ->all();

            $roleIds = Role::query()
                ->whereIn('slug', $roleSlugs)
                ->pluck('id')
                ->all();

            $user->roles()->sync($roleIds);
        }

        foreach ($users as $userData) {
            $email = $userData['email'] ?? null;
            $sponsorEmail = $userData['sponsor_email'] ?? null;

            if (! is_string($email) || $email === '' || ! is_string($sponsorEmail) || $sponsorEmail === '') {
                continue;
            }

            $sponsorId = User::query()->where('email', $sponsorEmail)->value('id');

            if (! $sponsorId) {
                continue;
            }

            User::query()
                ->where('email', $email)
                ->update(['sponsor_id' => $sponsorId]);
        }
    }
}
