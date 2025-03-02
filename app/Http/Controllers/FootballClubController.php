<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Http\Responses\TheOneResponse;
use App\Models\FootballClub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FootballClubController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clubs = FootballClub::all();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved football club list',
            'data' => $clubs
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
            'football_league_id' => 'required|exists:football_leagues,id',
            'name' => 'required|string|unique:clubs',
            'logo_primary' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5242880',
            'logo_white' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5242880',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $clubData = $request->except(['logo_primary', 'logo_white']);
        $club = FootballClub::create($clubData);

        if ($request->hasFile('logo_primary')) {
            $logoPrimary = $request->file('logo_primary');
            $fileName = $logoPrimary->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoPrimary, $fileName, 'clubs', $logoPrimary->getSize());
            $club->logo_primary = $path;
        }

        if ($request->hasFile('logo_white')) {
            $logoWhite = $request->file('logo_white');
            $fileName = $logoWhite->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoWhite, $fileName, 'clubs', $logoWhite->getSize());
            $club->logo_white = $path;
        }

        $club->status = 'ACTIVE';
        $club->save();

        return TheOneResponse::created([
            'status' => true,
            'message' => 'Football club created successfully',
            'data' => $club,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $club = FootballClub::find($id);

        if (!$club) {
            return TheOneResponse::notFound('Football club not found');
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football club details retrieved successfully',
            'data' => $club,
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
        $club = FootballClub::find($id);

        if (!$club) {
            return TheOneResponse::notFound('Football club not found');
        }

        $clubData = $request->except(['logo_primary', 'logo_white']);
        $club->update($clubData);

        if ($request->hasFile('logo_primary')) {
            Storage::delete('public/'.$club->logo_primary);

            $logoPrimary = $request->file('logo_primary');
            $fileName = $logoPrimary->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoPrimary, $fileName, 'clubs', $logoPrimary->getSize());
            $club->logo_primary = $path;
        }

        if ($request->hasFile('logo_white')) {
            Storage::delete('public/'.$club->logo_white);

            $logoWhite = $request->file('logo_white');
            $fileName = $logoWhite->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoWhite, $fileName, 'clubs', $logoWhite->getSize());
            $club->logo_white = $path;
        }

        $club->save();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football club updated successfully',
            'data' => $club,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $club = FootballClub::find($id);

        if (!$club) {
            return TheOneResponse::notFound('Football club not found');
        }

        if ($club->logo_primary && file_exists(storage_path('app/public/'.$club->logo_primary))) {
            Storage::delete('public/'.$club->logo_primary);
        }

        if ($club->logo_white && file_exists(storage_path('app/public/'.$club->logo_white))) {
            Storage::delete('public/'.$club->logo_white);
        }

        $club->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football club deleted successfully',
        ]);
    }
}
