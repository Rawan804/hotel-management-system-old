<?php
namespace App\Services;

use App\Models\Event;

class EventService
{
    public function getAll()
    {
        return Event::where('is_active', true)
            ->orderBy('event_date')
            ->get();
    }

    public function create(array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('events', 'public');
        }

        return Event::create($data);
    }

    public function delete(Event $event)
    {
        return $event->delete();
    }
}