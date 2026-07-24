<?php

namespace App\Models;

use App\Enums\NotifiableForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailMapping extends Model
{
    /** @use HasFactory<\Database\Factories\EmailMappingFactory> */
    use HasFactory;

    protected $fillable = [
        'form_key',
        'email',
        'name',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'form_key' => NotifiableForm::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForForm(Builder $query, NotifiableForm|string $form): Builder
    {
        $key = $form instanceof NotifiableForm ? $form->value : $form;

        return $query->where('form_key', $key);
    }
}
