<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Auth;

use App\Models\Event;
use App\Services\EventService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;


class EventController extends Controller
{
    public function __construct(
        private EventService $service
    ) {}

    public function index()
    {
        return response()->json(
            $this->service->getAll()
        );
    }

    public function store(
        StoreEventRequest $request
    )
    {
     $creator = Auth::user();
    if ($creator->role !== 'general_manager') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    
        
        $event = $this->service->create(
            $request->validated()
        );

        return response()->json([

            'message' => app()->getLocale() === 'ar'
                ? 'تم إنشاء الفعالية بنجاح'
                : 'Event created successfully',

            'data' => $event

        ], 201);
    }

    public function destroy(Event $event)
    {
              $creator = Auth::user();
    if ($creator->role !== 'general_manager') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
        $this->service->delete($event);

        return response()->json([

            'message' => app()->getLocale() === 'ar'
                ? 'تم حذف الفعالية بنجاح'
                : 'Event deleted successfully'

        ]);
    }

public function allEvents()
{
    $creator = Auth::guard('staff')->user();

    if (!$creator) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    if ($creator->role !== 'general_manager') {
        return response()->json([
            'message' => 'Forbidden'
        ], 403);
    }

    $events = $this->service->getAll();

    return response()->json([
        'message' => 'Events fetched successfully',
        'data' => $events
    ]);
}
public function updateEvent(UpdateEventRequest $request, Event $event)
{
    $creator = Auth::guard('staff')->user();

    if (!$creator) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    if ($creator->role !== 'general_manager') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $updated = $this->service->update(
        $event,
        $request->validated()
    );

    return response()->json([
        'message' => 'Event updated successfully',
        'data' => $updated
    ]);
}

public function activeEvents()
{
    $user = Auth::guard('staff')->user();

    if (!$user || $user->role !== 'general_manager') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    return response()->json([
        'message' => 'Active events',
        'data' => $this->service->getActive()
    ]);
}
public function inactiveEvents()
{
    $user = Auth::guard('staff')->user();

    if (!$user || $user->role !== 'general_manager') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    return response()->json([
        'message' => 'Inactive events',
        'data' => $this->service->getInactive()
    ]);
}


public function todayEvents()
{
    $events = Event::whereDate('event_date', today())
        ->orderBy('event_date', 'asc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $events
    ]);
}
}