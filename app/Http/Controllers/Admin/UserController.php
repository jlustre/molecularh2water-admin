<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BusinessLine;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SponsorHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly SponsorHierarchyService $sponsors,
    ) {}

    public function index(Request $request): View
    {
        $query = User::query()->with('sponsor:id,name,email')->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->status === 'verified') {
            $query->whereNotNull('email_verified_at');
        }

        if ($request->status === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        if ($request->filled('joined')) {
            match ($request->joined) {
                '7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                '30_days' => $query->where('created_at', '>=', now()->subDays(30)),
                '90_days' => $query->where('created_at', '>=', now()->subDays(90)),
                default => null,
            };
        }

        $users = $query
            ->paginate((int) $request->integer('per_page', 10))
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'totalUsers' => User::query()->count(),
            'verifiedUsers' => User::query()->whereNotNull('email_verified_at')->count(),
            'unverifiedUsers' => User::query()->whereNull('email_verified_at')->count(),
            'newUsers' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    public function hierarchy(): View
    {
        return view('admin.users.hierarchy', [
            'forest' => $this->sponsors->forestForAdmin(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['email_verified_at' => now()]),
            'sponsorOptions' => $this->sponsors->eligibleSponsors(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isSuperAdmin = $request->boolean('is_super_admin');

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'email_status' => ['required', 'in:verified,unverified'],
            'sponsor_id' => [
                Rule::requiredIf(! $isSuperAdmin),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'is_super_admin' => ['sometimes', 'boolean'],
            'business_lines' => ['nullable', 'array'],
            'business_lines.*' => [Rule::in(BusinessLine::values())],
        ]);

        $this->sponsors->assertValidSponsor(null, $isSuperAdmin ? null : ($attributes['sponsor_id'] ?? null), $isSuperAdmin);

        $user = User::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'sponsor_id' => $isSuperAdmin ? null : $attributes['sponsor_id'],
            'business_lines' => $this->normalizeBusinessLines($attributes['business_lines'] ?? [], $isSuperAdmin),
        ]);

        $user->forceFill([
            'email_verified_at' => $attributes['email_status'] === 'verified' ? now() : null,
        ])->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('sponsor:id,name'),
            'sponsorOptions' => $this->sponsors->eligibleSponsors($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'email_status' => ['required', 'in:verified,unverified'],
            'sponsor_id' => [
                Rule::requiredIf($user->requiresSponsor()),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'business_lines' => ['nullable', 'array'],
            'business_lines.*' => [Rule::in(BusinessLine::values())],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $isSuperAdmin = $user->isSuperAdmin();
        $this->sponsors->assertValidSponsor($user, $isSuperAdmin ? null : $attributes['sponsor_id'], $isSuperAdmin);

        $user->forceFill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'email_verified_at' => $attributes['email_status'] === 'verified'
                ? ($user->email_verified_at ?? now())
                : null,
            'sponsor_id' => $isSuperAdmin ? null : $attributes['sponsor_id'],
            'business_lines' => $this->normalizeBusinessLines($attributes['business_lines'] ?? [], $isSuperAdmin),
        ]);

        if (! empty($attributes['password'])) {
            $user->password = $attributes['password'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted.');
    }

    /**
     * Export current users into the ExistingUsersSeeder data file so they survive migrate:fresh --seed.
     */
    public function updateSeeder(): RedirectResponse
    {
        $users = User::query()
            ->with(['roles:id,slug', 'sponsor:id,email'])
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->getRawOriginal('password'),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'sponsor_email' => $user->sponsor?->email,
                'business_lines' => $user->business_lines,
                'roles' => $user->roles->pluck('slug')->values()->all(),
            ])
            ->all();

        $exportedUsers = var_export($users, true);
        $generatedAt = now()->toDateTimeString();
        $directory = database_path('seeders/data');
        $path = $directory.'/existing_users.php';

        File::ensureDirectoryExists($directory);
        File::put($path, <<<PHP
<?php

/**
 * Existing users export for migrate:fresh --seed.
 * Generated from Admin → User Management at {$generatedAt}.
 * Password values are already hashed — do not re-hash.
 */
return {$exportedUsers};

PHP);

        $count = count($users);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Users seeder updated with '.$count.' user'.($count === 1 ? '' : 's').'.');
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function normalizeBusinessLines(array $lines, bool $isSuperAdmin): array
    {
        $lines = collect($lines)
            ->filter()
            ->map(fn ($line) => (string) $line)
            ->unique()
            ->values()
            ->all();

        if ($isSuperAdmin) {
            return BusinessLine::values();
        }

        return $lines !== [] ? $lines : [BusinessLine::H2s->value];
    }
}
