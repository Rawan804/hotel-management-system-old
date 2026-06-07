<?php

namespace App\Http\Controllers\Api;

use App\Models\HotelNews;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\HotelNewsService;
use App\Http\Requests\StoreHotelNewsRequest;
use App\Http\Requests\UpdateHotelNewsRequest;

class HotelNewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')
            ->except(['index', 'show']);
    }

    public function index()
    {
        $news = HotelNews::latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'image_url' => $item->image_url,
                'is_pinned' => $item->is_pinned,
                'published_at' => $item->published_at,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($news);
    }

    public function show(HotelNews $news)
    {
        return response()->json([
            'id' => $news->id,
            'title' => $news->title,
            'content' => $news->content,
            'image_url' => $news->image_url,
            'is_pinned' => $news->is_pinned,
            'published_at' => $news->published_at,
            'created_at' => $news->created_at,
        ]);
    }

    public function store(
        StoreHotelNewsRequest $request,
        HotelNewsService $service
    ) {
        $news = $service->create(
            $request->validated(),
            Auth::user()
        );

        return response()->json([
            'message' => __('messages.news_created'),
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'content' => $news->content,
                'image_url' => $news->image_url,
                'is_pinned' => $news->is_pinned,
                'published_at' => $news->published_at,
                'created_at' => $news->created_at,
            ]
        ], 201);
    }

    public function update(
        UpdateHotelNewsRequest $request,
        HotelNews $news,
        HotelNewsService $service
    ) {
        $news = $service->update(
            $news,
            $request->validated()
        );

        return response()->json([
            'message' => __('messages.news_updated'),
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'content' => $news->content,
                'image_url' => $news->image_url,
                'is_pinned' => $news->is_pinned,
                'published_at' => $news->published_at,
                'created_at' => $news->created_at,
            ]
        ]);
    }

    public function destroy(HotelNews $news)
    {
        $news->delete();

        return response()->json([
            'message' => __('messages.news_deleted')
        ]);
    }
}