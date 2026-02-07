<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CalendarSet extends Model
{
    protected $fillable = [
        'key',
        'name',
        'emoji',
        'user_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the calendar set
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the calendar sources in this set
     */
    public function calendarSources(): BelongsToMany
    {
        return $this->belongsToMany(CalendarSource::class, 'calendar_set_sources');
    }

    /**
     * Scope to filter active sets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by user or global
     */
    public function scopeForUser($query, ?int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }

    /**
     * Get calendar sources grouped by type
     */
    public function getCalendarsByType(): array
    {
        $sources = $this->calendarSources()->active()->get();

        return [
            'google_calendars' => $sources->where('type', 'google')->keyBy('key')->map(function ($source) {
                return [
                    'name' => $source->name,
                    'src' => $source->src,
                    'color' => $source->color,
                    'emoji' => $source->emoji,
                ];
            })->toArray(),
            'ical_calendars' => $sources->where('type', 'ical')->keyBy('key')->map(function ($source) {
                return [
                    'name' => $source->name,
                    'src' => $source->src,
                    'color' => $source->color,
                    'emoji' => $source->emoji,
                ];
            })->toArray(),
            'merged_calendars' => [], // TODO: Implement merged calendar logic
        ];
    }
}
