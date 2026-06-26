<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('mime_type');
            $table->string('thumbnail_name')->nullable()->after('thumbnail_path');
            $table->unsignedBigInteger('thumbnail_size')->nullable()->after('thumbnail_name');
            $table->string('thumbnail_mime_type')->nullable()->after('thumbnail_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropColumn([
                'thumbnail_path',
                'thumbnail_name',
                'thumbnail_size',
                'thumbnail_mime_type',
            ]);
        });
    }
};
