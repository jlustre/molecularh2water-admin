<?php

namespace App\Support\Portal;

class PortalRainbowTone
{
    /**
     * ROYGBIV rainbow tones for portal quick actions / matching stat cards.
     *
     * @var list<string>
     */
    public const TONES = [
        'red',
        'orange',
        'yellow',
        'green',
        'blue',
        'indigo',
        'violet',
    ];

    /**
     * Stable action → rainbow tone map so Quick Links and Network cards stay aligned.
     *
     * @var array<string, string>
     */
    private const ACTION_TONES = [
        'openMemberInvites' => 'red',
        'open-member-invites' => 'red',
        'openProspects' => 'orange',
        'open-prospects' => 'orange',
        'openDemos' => 'yellow',
        'open-demos' => 'yellow',
        'openPhoneCalls' => 'green',
        'open-phone-calls' => 'green',
        'openMeetings' => 'blue',
        'open-meetings' => 'blue',
        'openAppointments' => 'indigo',
        'open-appointments' => 'indigo',
        'openTasks' => 'violet',
        'open-tasks' => 'violet',
        'openReferrals' => 'red',
        'open-referrals' => 'red',
    ];

    public static function forAction(string $action, int $fallbackIndex = 0): string
    {
        if (isset(self::ACTION_TONES[$action])) {
            return self::ACTION_TONES[$action];
        }

        return self::at($fallbackIndex);
    }

    public static function at(int $index): string
    {
        $tones = self::TONES;

        return $tones[$index % count($tones)];
    }
}
