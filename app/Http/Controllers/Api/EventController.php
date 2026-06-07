<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Services\EventService;

class EventController extends Controller
{
    public function __construct(private EventService $service) {}

    public function index()
    {
        return response()->json(
            $this->service->getAll()
        );
    }

    public function store(StoreEventRequest $request)
    {
        $event = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event
        ]);
    }

    public function destroy(Event $event)
    {
        $this->service->delete($event);

        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }
}