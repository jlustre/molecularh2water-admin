<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmMarketingSeeder extends Seeder
{
    /**
     * Seed email/SMS templates and follow-up sequence scaffolding.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome Prospect',
                'slug' => 'welcome-prospect',
                'subject' => 'Welcome to Molecular H2 Water',
                'body' => '<p>Hi {{first_name}}, thank you for your interest in molecular hydrogen water.</p>',
                'variables' => json_encode(['first_name', 'last_name']),
            ],
            [
                'name' => 'Appointment Confirmation',
                'slug' => 'appointment-confirmation',
                'subject' => 'Your appointment is confirmed',
                'body' => '<p>Hi {{first_name}}, we look forward to meeting you on {{appointment_date}}.</p>',
                'variables' => json_encode(['first_name', 'appointment_date']),
            ],
            [
                'name' => 'Follow-Up After Presentation',
                'slug' => 'follow-up-presentation',
                'subject' => 'Great meeting you today',
                'body' => '<p>Hi {{first_name}}, thanks for taking the time to learn about our products.</p>',
                'variables' => json_encode(['first_name']),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('email_templates')->updateOrInsert(
                ['slug' => $template['slug']],
                array_merge($template, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $smsTemplates = [
            [
                'name' => 'Appointment Reminder SMS',
                'slug' => 'appointment-reminder-sms',
                'body' => 'Reminder: your Molecular H2 Water appointment is coming up soon.',
                'variables' => json_encode(['first_name', 'starts_at']),
            ],
            [
                'name' => 'Quick Follow-Up SMS',
                'slug' => 'quick-follow-up-sms',
                'body' => 'Hi {{first_name}}, just checking in about molecular hydrogen water.',
                'variables' => json_encode(['first_name']),
            ],
        ];

        foreach ($smsTemplates as $template) {
            DB::table('sms_templates')->updateOrInsert(
                ['slug' => $template['slug']],
                array_merge($template, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        DB::table('followup_sequences')->updateOrInsert(
            ['slug' => 'new-prospect-nurture'],
            [
                'name' => 'New Prospect Nurture',
                'trigger_event' => 'prospect_captured',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $sequenceId = DB::table('followup_sequences')->where('slug', 'new-prospect-nurture')->value('id');
        $welcomeEmailId = DB::table('email_templates')->where('slug', 'welcome-prospect')->value('id');
        $smsId = DB::table('sms_templates')->where('slug', 'quick-follow-up-sms')->value('id');

        if ($sequenceId) {
            DB::table('followup_sequence_steps')->where('followup_sequence_id', $sequenceId)->delete();

            $steps = [
                ['channel' => 'email', 'template_id' => $welcomeEmailId, 'delay_minutes' => 0, 'sort_order' => 1],
                ['channel' => 'sms', 'template_id' => $smsId, 'delay_minutes' => 1440, 'sort_order' => 2],
                ['channel' => 'email', 'template_id' => DB::table('email_templates')->where('slug', 'follow-up-presentation')->value('id'), 'delay_minutes' => 4320, 'sort_order' => 3],
            ];

            foreach ($steps as $step) {
                DB::table('followup_sequence_steps')->insert([
                    'followup_sequence_id' => $sequenceId,
                    'channel' => $step['channel'],
                    'template_id' => $step['template_id'],
                    'delay_minutes' => $step['delay_minutes'],
                    'sort_order' => $step['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('followup_sequences')->updateOrInsert(
            ['slug' => 'demo-reminder-sequence'],
            [
                'name' => 'Demo Reminder Sequence',
                'trigger_event' => 'demonstration.scheduled',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $demoSequenceId = DB::table('followup_sequences')->where('slug', 'demo-reminder-sequence')->value('id');
        $appointmentEmailId = DB::table('email_templates')->where('slug', 'appointment-confirmation')->value('id');
        $reminderSmsId = DB::table('sms_templates')->where('slug', 'appointment-reminder-sms')->value('id');

        if ($demoSequenceId) {
            DB::table('followup_sequence_steps')->where('followup_sequence_id', $demoSequenceId)->delete();

            foreach ([
                ['channel' => 'email', 'template_id' => $appointmentEmailId, 'delay_minutes' => 0, 'sort_order' => 1],
                ['channel' => 'sms', 'template_id' => $reminderSmsId, 'delay_minutes' => 60, 'sort_order' => 2],
            ] as $step) {
                DB::table('followup_sequence_steps')->insert([
                    'followup_sequence_id' => $demoSequenceId,
                    'channel' => $step['channel'],
                    'template_id' => $step['template_id'],
                    'delay_minutes' => $step['delay_minutes'],
                    'sort_order' => $step['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
