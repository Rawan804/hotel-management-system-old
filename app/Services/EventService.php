<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Storage;

class EventService
{
    public function getAll()
    {
        return Event::where(
            'is_active',
            true
        )
        ->orderBy('event_date')
        ->get();
    }

    public function create(array $data)
    {
        if (!empty($data['image'])) {

            $data['image'] = $data['image']->store(
                'events',
                'public'
            );
        }

        return Event::create($data);
    }


public function delete(Event $event)
{
   
    $event->update([
        'is_active' => false
    ]);

    return $event->fresh();
}
    public function update(Event $event, array $data)
{
    // إذا في صورة جديدة
    if (!empty($data['image'])) {

        // احذف الصورة القديمة إذا موجودة
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        // خزّن الصورة الجديدة
        $data['image'] = $data['image']->store('events', 'public');
    } else {
        // لا تغيّر الصورة القديمة إذا ما انبعتت صورة جديدة
        unset($data['image']);
    }


    // تحديث البيانات
    $event->update($data);

    return $event->fresh();
}

public function getActive()
{
    return Event::where('is_active', true)
        ->orderBy('event_date')
        ->get();
}

public function getInactive()
{
    return Event::where('is_active', false)
        ->orderBy('event_date')
        ->get();
}
}