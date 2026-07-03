<?php

use App\Models\Crm\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('crm.prospect_profile_tags', []) as $name) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }

    public function down(): void
    {
        $slugs = collect(config('crm.prospect_profile_tags', []))
            ->map(fn (string $name) => Str::slug($name));

        Tag::query()->whereIn('slug', $slugs)->delete();
    }
};
