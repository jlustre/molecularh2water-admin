<?php



namespace App\Models\Crm;



use App\Enums\Crm\CalendarEventCategory;

use Illuminate\Database\Eloquent\Attributes\Fillable;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;



#[Fillable([

    'name',

    'slug',

    'category',

    'color',

    'icon',

    'creates_activity',

    'activity_type_slug',

    'is_active',

    'sort_order',

])]

class CalendarEventType extends Model

{

    protected function casts(): array

    {

        return [

            'category' => CalendarEventCategory::class,

            'creates_activity' => 'boolean',

            'is_active' => 'boolean',

            'sort_order' => 'integer',

        ];

    }



    public function events(): HasMany

    {

        return $this->hasMany(CalendarEvent::class);

    }



    public function isShow(): bool

    {

        return $this->category?->isShow() ?? false;

    }



    /**

     * @param  Builder<CalendarEventType>  $query

     * @return Builder<CalendarEventType>

     */

    public function scopeShows(Builder $query): Builder

    {

        return $query->where('category', CalendarEventCategory::Show->value);

    }



    /**

     * @param  Builder<CalendarEventType>  $query

     * @return Builder<CalendarEventType>

     */

    public function scopeActive(Builder $query): Builder

    {

        return $query->where('is_active', true);

    }

}

