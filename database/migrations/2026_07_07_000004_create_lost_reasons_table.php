<?php

use App\Models\Crm\LostReason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('requires_detail')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('lost_reason_id')
                ->nullable()
                ->after('funnel_stage_id')
                ->constrained('lost_reasons')
                ->nullOnDelete();
        });

        foreach (config('crm.default_lost_reasons', []) as $index => $reason) {
            LostReason::query()->updateOrCreate(
                ['slug' => $reason['slug'] ?? Str::slug($reason['name'])],
                [
                    'name' => $reason['name'],
                    'description' => $reason['description'] ?? null,
                    'requires_detail' => (bool) ($reason['requires_detail'] ?? false),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lost_reason_id');
        });

        Schema::dropIfExists('lost_reasons');
    }
};
