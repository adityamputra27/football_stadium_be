<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Http\Responses\TheOneResponse;
use App\Models\FootballClub;
use App\Models\FootballNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FootballNewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $news = FootballNews::orderBy('created_at', 'DESC')->get();
        $originFolder = storage_path('app/public/news/images/');
        $footballNewsFiles = scandir($originFolder);

        $newsData = [];
        foreach ($news as $key => $value) {
            if (!empty($value->title) && !empty($value->image)) {
                $detectFile = last(explode('news/images/', $value->image));
                if (in_array($detectFile, $footballNewsFiles)) {
                    $obj['id'] = $value->id;
                    $obj['title'] = $value->title;
                    $obj['image'] = url('/') . Storage::url($value->image);
                    $obj['body'] = $value->body;
                    $obj['category'] = $value->category;
                    $obj['inc_key'] = $key+1;
                    $obj['is_featured_news'] = $value->is_featured_news;
                    $obj['diff'] = $value->created_at->diffForHumans();
                }
                $newsData[] = $obj;
            }
        }
        
        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved football news list',
            'data' => $newsData
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:10',
            'body' => 'required|min:50',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:102400',
            'category' => 'in:Stadiums,Leagues,Clubs,Players,International',
            'is_featured_news' => 'boolean',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $newsData = $request->except(['image']);
        $news = FootballNews::create($newsData);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = $image->getClientOriginalName();
            $path = UploadHelper::handleImageFile($image, $fileName, 'news', $image->getSize());
            $news->image = $path;
        }

        $news->save();

        return TheOneResponse::created([
            'status' => true,
            'message' => 'Football news created successfully',
            'data' => $news,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $news = FootballNews::find($id);

        if (!$news) {
            return TheOneResponse::notFound('Football news not found');
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football news details retrieved successfully',
            'data' => $news,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $news = FootballNews::find($id);

        if (!$news) {
            return TheOneResponse::notFound('Football news not found');
        }

        $newsData = $request->except(['image']);
        $news->update($newsData);

        if ($request->hasFile('image')) {
            Storage::delete('public/'.$news->image);

            $image = $request->file('image');
            $fileName = $image->getClientOriginalName();
            $path = UploadHelper::handleImageFile($image, $fileName, 'news', $image->getSize());
            $news->image = $path;
        }

        $news->save();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football news updated successfully',
            'data' => $news,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $news = FootballNews::find($id);

        if (!$news) {
            return TheOneResponse::notFound('Football news not found');
        }

        if ($news->image && file_exists(storage_path('app/public/'.$news->image))) {
            Storage::delete('public/'.$news->image);
        }

        $news->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football news deleted successfully',
        ]);
    }
}
