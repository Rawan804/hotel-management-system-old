<?php

namespace App\Services;

use App\Models\HotelNews;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class HotelNewsService
{
    public function create(array $data, $user): HotelNews
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('news', 'public');
        }

        $data['created_by'] = $user->staff_id;

        return HotelNews::create($data);
    }

    public function update(HotelNews $news, array $data): HotelNews
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('news', 'public');
        }

        $news->update($data);

        return $news->fresh();
    }
    public function getPinnedNews()
{
    return HotelNews::where('is_pinned', 1)
        ->latest()
        ->get();
}
}