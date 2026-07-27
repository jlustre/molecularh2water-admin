<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 32)->default('teal');
            $table->string('kind', 40)->default('custom');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });

        Schema::create('user_calendar_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_calendar_id')->constrained('user_calendars')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_calendar_id', 'shared_with_user_id']);
        });

        Schema::create('user_calendar_visibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_calendar_id')->constrained('user_calendars')->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'user_calendar_id']);
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreignId('user_calendar_id')
                ->nullable()
                ->after('calendar_event_type_id')
                ->constrained('user_calendars')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_calendar_id');
        });

        Schema::dropIfExists('user_calendar_visibilities');
        Schema::dropIfExists('user_calendar_shares');
        Schema::dropIfExists('user_calendars');
    }
};
