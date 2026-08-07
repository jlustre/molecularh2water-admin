<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_form_submissions', function (Blueprint $table) {
            $table->text('warranty_concern')->nullable()->after('message');
            $table->json('warranty_media')->nullable()->after('warranty_concern');
        });
    }

    public function down(): void
    {
        Schema::table('website_form_submissions', function (Blueprint $table) {
            $table->dropColumn(['warranty_concern', 'warranty_media']);
        });
    }
};
