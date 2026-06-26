<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->latest();

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

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['email_verified_at' => now()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'email_status' => ['required', 'in:verified,unverified'],
        ]);

        $user = User::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
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
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'email_status' => ['required', 'in:verified,unverified'],
        ]);

        $user->forceFill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'email_verified_at' => $attributes['email_status'] === 'verified'
                ? ($user->email_verified_at ?? now())
                : null,
        ]);

        if (! empty($attributes['password'])) {
            $user->password = $attributes['password'];
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
}
