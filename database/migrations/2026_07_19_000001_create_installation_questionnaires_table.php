<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('street_address');
            $table->string('street_address_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country');
            $table->string('property_type');
            $table->json('existing_equipment')->nullable();
            $table->string('ownership')->nullable();
            $table->string('water_source');
            $table->string('water_source_other')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('sink_photo_path')->nullable();
            $table->string('sink_photo_original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_questionnaires');
    }
};
