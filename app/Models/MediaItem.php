<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaItem extends Model
{
    /** @use HasFactory<\Database\Factories\MediaItemFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'status',
        'url',
        'file_path',
        'description',
        'file_name',
        'file_size',
        'mime_type',
        'thumbnail_path',
        'thumbnail_name',
        'thumbnail_size',
        'thumbnail_mime_type',
    ];

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf'
            || str_ends_with(strtolower((string) $this->file_name), '.pdf')
            || str_ends_with(strtolower((string) $this->file_path), '.pdf');
    }

    public function isVideo(): bool
    {
        $fileName = strtolower((string) $this->file_name);
        $filePath = strtolower((string) $this->file_path);

        return $this->category === 'videos'
            || str_starts_with((string) $this->mime_type, 'video/')
            || str_ends_with($fileName, '.mp4')
            || str_ends_with($fileName, '.mov')
            || str_ends_with($fileName, '.avi')
            || str_ends_with($fileName, '.webm')
            || str_ends_with($fileName, '.mkv')
            || str_ends_with($filePath, '.mp4')
            || str_ends_with($filePath, '.mov')
            || str_ends_with($filePath, '.avi')
            || str_ends_with($filePath, '.webm')
            || str_ends_with($filePath, '.mkv');
    }

    public function videoThumbnailUrl(): ?string
    {
        if (! $this->isVideo() || ! $this->url) {
            return null;
        }

        $youtubeId = $this->youtubeVideoId($this->url);

        if ($youtubeId) {
            return "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg";
        }

        $vimeoId = $this->vimeoVideoId($this->url);

        if ($vimeoId) {
            return "https://vumbnail.com/{$vimeoId}.jpg";
        }

        return null;
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail_path) {
            return url(\Illuminate\Support\Facades\Storage::disk('public')->url($this->thumbnail_path));
        }

        return $this->videoThumbnailUrl();
    }

    private function youtubeVideoId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if (str_contains($host, 'youtu.be')) {
            return $this->cleanVideoId(explode('/', $path)[0] ?? null);
        }

        if (! str_contains($host, 'youtube.com')) {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (! empty($query['v'])) {
            return $this->cleanVideoId($query['v']);
        }

        $segments = array_values(array_filter(explode('/', $path)));

        if (count($segments) >= 2 && in_array($segments[0], ['embed', 'shorts', 'live'], true)) {
            return $this->cleanVideoId($segments[1]);
        }

        return null;
    }

    private function vimeoVideoId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! str_contains($host, 'vimeo.com')) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim((string) parse_url($url, PHP_URL_PATH), '/'))));

        foreach (array_reverse($segments) as $segment) {
            if (ctype_digit($segment)) {
                return $segment;
            }
        }

        return null;
    }

    private function cleanVideoId(mixed $value): ?string
    {
        $id = trim((string) $value);

        return preg_match('/^[A-Za-z0-9_-]+$/', $id) ? $id : null;
    }
}
