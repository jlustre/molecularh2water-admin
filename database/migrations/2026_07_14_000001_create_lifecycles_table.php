<?php

use App\Models\Crm\Lifecycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BREAKING CHANGE: Introduces lifecycles lookup table.
 * lifecycle_id will replace the string lifecycle column on CRM contact tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifecycles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $rows = [
            ['slug' => 'lead', 'label' => 'Lead', 'sort_order' => 1],
            ['slug' => 'prospect', 'label' => 'Prospect', 'sort_order' => 2],
            ['slug' => 'client', 'label' => 'Customer', 'sort_order' => 3],
            ['slug' => 'recruit', 'label' => 'Recruit', 'sort_order' => 4],
        ];

        foreach ($rows as $row) {
            DB::table('lifecycles')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        Lifecycle::flushCache();
    }

    public function down(): void
    {
        Schema::dropIfExists('lifecycles');
        Lifecycle::flushCache();
    }
};
