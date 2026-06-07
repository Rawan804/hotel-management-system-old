<?php

namespace App\Services;

use App\Models\HotelNews;
use Illuminate\Http\UploadedFile;

class HotelNewsService
{
    public function create(array $data, $staff): HotelNews
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('news', 'public');
        }

        $data['created_by'] = $staff->staff_id;

        return HotelNews::create($data);
    }

    public function update(
        HotelNews $news,
        array $data
    ): HotelNews {

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('news', 'public');
        }

        $news->update($data);

        return $news->fresh();
    }
}