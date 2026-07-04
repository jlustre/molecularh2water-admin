<?php

namespace App\Http\Controllers;

use App\Support\Shell\ShellNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function __invoke(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless(ShellNotifications::canView($user), 403);

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return redirect()->to(ShellNotifications::url($record, $user));
    }
}
