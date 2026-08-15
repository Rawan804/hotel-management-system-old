<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateRoomStatuses extends Command
{
    protected $signature = 'rooms:update-statuses';

    protected $description = 'Update rooms statuses based on active bookings';

    public function handle()
    {
        app(\App\Services\RoomService::class)->updateRoomsStatus();

        $this->info('Room statuses updated successfully.');

        return Command::SUCCESS;
    }
}