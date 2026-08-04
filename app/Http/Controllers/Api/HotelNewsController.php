<?php

namespace App\Http\Controllers\Api;

use App\Models\HotelNews;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\HotelNewsService;
use App\Http\Requests\StoreHotelNewsRequest;
use App\Http\Requests\UpdateHotelNewsRequest;
use Exception;


class HotelNewsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'staff']);
    }

    private function lang()
    {
        return request()->header('Accept-Language', 'ar');
    }

    private function format($news, $lang)
    {
        return [
            'id' => $news->id,
            'title' => $lang === 'en' ? $news->title_en : $news->title_ar,
            'content' => $lang === 'en' ? $news->content_en : $news->content_ar,
            'image_url' => $news->image_url,
            'is_pinned' => $news->is_pinned,
            'published_at' => $news->published_at,
            'created_by' => $news->created_by,
            'created_at' => $news->created_at,
        ];
    }


    public function index()
    {
        
        $lang = $this->lang();

        return HotelNews::latest()
            ->get()
            ->map(fn ($item) => $this->format($item, $lang));
    }

  
    public function show(HotelNews $news)
    {
        $lang = $this->lang();

        return response()->json($this->format($news, $lang));
    }

   
    public function store(StoreHotelNewsRequest $request, HotelNewsService $service)
    {
     $creator = Auth::user();
    if ($creator->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
        $news = $service->create($request->validated(), Auth::user());

        return response()->json([
            'message' => __('messages.news_created'),
            'news' => $this->format($news, $this->lang())
        ], 201);
    }

    public function update(UpdateHotelNewsRequest $request, HotelNews $news, HotelNewsService $service)
    {
         $creator = Auth::user();
    if ($creator->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
        $news = $service->update($news, $request->validated());

        return response()->json([
            'message' => __('messages.news_updated'),
            'news' => $this->format($news, $this->lang())
        ]);
    }
public function destroy(HotelNews $news)
{
    $creator = Auth::user();

    if ($creator->role === 'employee') {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $news->update([
        'is_pinned' => false
    ]);

    return response()->json([
 'message' => __('messages.news_deleted')    ]);
}

public function pinnedNews(HotelNewsService $service)
{
    $creator = Auth::guard('staff')->user();
    if (!$creator) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    if (!in_array($creator->role, ['supervisor', 'general_manager'])) {
        return response()->json([
            'message' => 'Forbidden'
        ], 403);
    }

    $lang = $this->lang();

    return $service->getPinnedNews()
        ->map(fn ($item) => $this->format($item, $lang));
}
}