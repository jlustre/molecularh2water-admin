<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('allows an admin to manage users', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);
    $existingUser = User::factory()->create([
        'name' => 'Taylor Verified',
        'email' => 'taylor@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('User Directory')
        ->assertSee('Add User')
        ->assertSee('Taylor Verified');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'taylor', 'status' => 'verified']))
        ->assertOk()
        ->assertSee('Taylor Verified');

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('Add user')
        ->assertSee('Email Status');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Morgan Member',
            'email' => 'morgan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'email_status' => 'verified',
        ])
        ->assertRedirect(route('admin.users.index'));

    $createdUser = User::where('email', 'morgan@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->email_verified_at)->not->toBeNull();
    expect(Hash::check('password', $createdUser->password))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $createdUser))
        ->assertOk()
        ->assertSee('Edit user')
        ->assertSee('morgan@example.com');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $createdUser), [
            'name' => 'Morgan Updated',
            'email' => 'morgan.updated@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'email_status' => 'unverified',
        ])
        ->assertRedirect(route('admin.users.index'));

    $createdUser->refresh();

    expect($createdUser->name)->toBe('Morgan Updated');
    expect($createdUser->email)->toBe('morgan.updated@example.com');
    expect($createdUser->email_verified_at)->toBeNull();
    expect(Hash::check('new-password', $createdUser->password))->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $existingUser))
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $existingUser->id,
    ]);
});

it('prevents an admin from deleting their own account', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'You cannot delete your own account.');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});
