<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarSet;
use App\Services\CalendarService;
use Illuminate\Http\Request;

class CalendarSetController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Display a listing of calendar sets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;

        $sets = CalendarSet::active()
            ->forUser($userId)
            ->with('calendarSources')
            ->get();

        return response()->json([
            'data' => $sets->map(function ($set) {
                return [
                    'id' => $set->id,
                    'key' => $set->key,
                    'name' => $set->name,
                    'emoji' => $set->emoji,
                    'is_default' => $set->is_default,
                    'is_active' => $set->is_active,
                    'calendar_count' => $set->calendarSources->count(),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created calendar set.
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:calendar_sets,key',
            'name' => 'required|string|max:255',
            'emoji' => 'nullable|string|max:10',
            'is_default' => 'boolean',
            'calendar_sources' => 'array',
            'calendar_sources.*' => 'exists:calendar_sources,id',
        ]);

        $user = $request->user();

        $calendarSet = CalendarSet::create([
            'key' => $request->key,
            'name' => $request->name,
            'emoji' => $request->emoji,
            'user_id' => $user ? $user->id : null,
            'is_default' => $request->is_default ?? false,
            'is_active' => true,
        ]);

        if ($request->has('calendar_sources')) {
            $calendarSet->calendarSources()->attach($request->calendar_sources);
        }

        // Clear cache when creating new sets
        $this->calendarService->clearCache();

        return response()->json([
            'message' => 'Calendar set created successfully',
            'data' => $calendarSet->load('calendarSources'),
        ], 201);
    }

    /**
     * Display the specified calendar set.
     */
    public function show(CalendarSet $calendarSet)
    {
        $calendarSet->load('calendarSources');

        return response()->json([
            'data' => [
                'id' => $calendarSet->id,
                'key' => $calendarSet->key,
                'name' => $calendarSet->name,
                'emoji' => $calendarSet->emoji,
                'is_default' => $calendarSet->is_default,
                'is_active' => $calendarSet->is_active,
                'calendar_sources' => $calendarSet->calendarSources->map(function ($source) {
                    return [
                        'id' => $source->id,
                        'key' => $source->key,
                        'name' => $source->name,
                        'type' => $source->type,
                        'color' => $source->color,
                        'emoji' => $source->emoji,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified calendar set.
     */
    public function update(Request $request, CalendarSet $calendarSet)
    {
        $request->validate([
            'key' => 'sometimes|string|max:255|unique:calendar_sets,key,'.$calendarSet->id,
            'name' => 'sometimes|string|max:255',
            'emoji' => 'nullable|string|max:10',
            'is_default' => 'boolean',
            'calendar_sources' => 'array',
            'calendar_sources.*' => 'exists:calendar_sources,id',
        ]);

        $calendarSet->update($request->only(['key', 'name', 'emoji', 'is_default']));

        if ($request->has('calendar_sources')) {
            $calendarSet->calendarSources()->sync($request->calendar_sources);
        }

        // Clear cache when updating sets
        $this->calendarService->clearCache();

        return response()->json([
            'message' => 'Calendar set updated successfully',
            'data' => $calendarSet->load('calendarSources'),
        ]);
    }

    /**
     * Remove the specified calendar set.
     */
    public function destroy(CalendarSet $calendarSet)
    {
        $calendarSet->delete();

        // Clear cache when deleting sets
        $this->calendarService->clearCache();

        return response()->json([
            'message' => 'Calendar set deleted successfully',
        ]);
    }
}
