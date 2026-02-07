<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarSource;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarSourceController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Display a listing of calendar sources.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;

        $sources = CalendarSource::active()
            ->forUser($userId)
            ->get();

        return response()->json([
            'data' => $sources->map(function ($source) {
                return [
                    'id' => $source->id,
                    'key' => $source->key,
                    'name' => $source->name,
                    'type' => $source->type,
                    'src' => $source->src,
                    'color' => $source->color,
                    'emoji' => $source->emoji,
                    'is_active' => $source->is_active,
                    'user_id' => $source->user_id,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created calendar source.
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:calendar_sources,key',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['google', 'ical'])],
            'src' => 'required|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'emoji' => 'nullable|string|max:10',
        ]);

        $user = $request->user();

        $calendarSource = CalendarSource::create([
            'key' => $request->key,
            'name' => $request->name,
            'type' => $request->type,
            'src' => $request->src,
            'color' => $request->color,
            'emoji' => $request->emoji,
            'user_id' => $user ? $user->id : null,
            'is_active' => true,
        ]);

        // Clear cache when creating new sources
        $this->calendarService->clearCache();

        return response()->json([
            'message' => 'Calendar source created successfully',
            'data' => $calendarSource,
        ], 201);
    }

    /**
     * Display the specified calendar source.
     */
    public function show(CalendarSource $calendarSource)
    {
        return response()->json([
            'data' => [
                'id' => $calendarSource->id,
                'key' => $calendarSource->key,
                'name' => $calendarSource->name,
                'type' => $calendarSource->type,
                'src' => $calendarSource->src,
                'color' => $calendarSource->color,
                'emoji' => $calendarSource->emoji,
                'is_active' => $calendarSource->is_active,
                'user_id' => $calendarSource->user_id,
            ],
        ]);
    }

    /**
     * Update the specified calendar source.
     */
    public function update(Request $request, CalendarSource $calendarSource)
    {
        $request->validate([
            'key' => 'sometimes|string|max:255|unique:calendar_sources,key,'.$calendarSource->id,
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['google', 'ical'])],
            'src' => 'sometimes|string',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'emoji' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        $calendarSource->update($request->only([
            'key',
            'name',
            'type',
            'src',
            'color',
            'emoji',
            'is_active',
        ]));

        // Clear cache when updating sources
        $this->calendarService->clearCache();

        return response()->json([
            'message' => 'Calendar source updated successfully',
            'data' => $calendarSource,
        ]);
    }

    /**
     * Remove the specified calendar source.
     */
    public function destroy(CalendarSource $calendarSource)
    {
        $calendarSource->delete();

        // Clear cache when deleting sources
        $this->calendarService->clearCache();

        return response()->json([
            'message' => 'Calendar source deleted successfully',
        ]);
    }
}
