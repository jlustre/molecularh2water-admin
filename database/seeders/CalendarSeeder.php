<?php



namespace Database\Seeders;



use App\Models\Crm\CalendarEventType;

use Illuminate\Database\Seeder;



class CalendarSeeder extends Seeder

{

    public function run(): void

    {

        foreach (config('calendar.event_types', []) as $index => $type) {

            CalendarEventType::query()->updateOrCreate(

                ['slug' => $type['slug']],

                [

                    'name' => $type['name'],

                    'category' => $type['category'] ?? 'meeting',

                    'color' => $type['color'] ?? 'teal',

                    'icon' => $type['icon'] ?? null,

                    'creates_activity' => $type['creates_activity'] ?? true,

                    'activity_type_slug' => $type['activity_type_slug'] ?? null,

                    'is_active' => true,

                    'sort_order' => $type['sort_order'] ?? ($index + 1),

                ],

            );

        }



        CalendarEventType::query()

            ->whereIn('slug', config('calendar.legacy_inactive_slugs', []))

            ->update(['is_active' => false]);

    }

}

