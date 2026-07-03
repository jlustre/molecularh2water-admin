<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sponsor_id')
                ->nullable()
                ->after('avatar_path')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('registration_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->string('label')->nullable();
            $table->foreignId('registered_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['sponsor_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_invites');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_id');
        });
    }
};
