<?php

namespace App\Support;

class FrontendUrl
{
    public static function base(): string
    {
        return rtrim((string) config('frontend.url'), '/');
    }

    public static function path(string $path = '/'): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : "/{$path}";

        return self::base().$normalizedPath;
    }

    public static function warrantyRegistration(): string
    {
        return self::path((string) config('frontend.warranty_path', '/warranty'));
    }

    public static function environmentLabel(): string
    {
        return (string) config('frontend.environment_label', 'Production');
    }

    public static function environmentKey(): string
    {
        $environment = app()->environment();

        if (in_array($environment, ['local', 'staging', 'production'], true)) {
            return $environment;
        }

        return 'production';
    }
}
