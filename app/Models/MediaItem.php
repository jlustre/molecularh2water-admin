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
}
