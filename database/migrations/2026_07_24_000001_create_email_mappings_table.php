<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('form_key');
            $table->string('email');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['form_key', 'email']);
            $table->index(['form_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_mappings');
    }
};
