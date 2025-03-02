<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Http\Responses\TheOneResponse;
use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $leagues = League::all();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved league list',
            'data' => $leagues
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:leagues',
            'logo_primary' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5242880',
            'logo_white' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5242880',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $leagueData = $request->except(['logo_primary', 'logo_white']);
        $league = League::create($leagueData);

        if ($request->hasFile('logo_primary')) {
            $logoPrimary = $request->file('logo_primary');
            $fileName = $logoPrimary->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoPrimary, $fileName, 'leagues', $logoPrimary->getSize());
            $league->logo_primary = $path;
        }

        if ($request->hasFile('logo_white')) {
            $logoWhite = $request->file('logo_white');
            $fileName = $logoWhite->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoWhite, $fileName, 'leagues', $logoWhite->getSize());
            $league->logo_white = $path;
        }

        $league->status = 'ACTIVE';
        $league->save();

        return TheOneResponse::created([
            'status' => true,
            'message' => 'League created successfully',
            'data' => $league,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $league = League::find($id);

        if (!$league) {
            return TheOneResponse::notFound('League not found');
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'League details retrieved successfully',
            'data' => $league,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $league = League::find($id);

        if (!$league) {
            return TheOneResponse::notFound('League not found');
        }

        $leagueData = $request->except(['logo_primary', 'logo_white']);
        $league->update($leagueData);

        if ($request->hasFile('logo_primary')) {
            Storage::delete('public/'.$league->logo_primary);

            $logoPrimary = $request->file('logo_primary');
            $fileName = $logoPrimary->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoPrimary, $fileName, 'leagues', $logoPrimary->getSize());
            $league->logo_primary = $path;
        }

        if ($request->hasFile('logo_white')) {
            Storage::delete('public/'.$league->logo_white);

            $logoWhite = $request->file('logo_white');
            $fileName = $logoWhite->getClientOriginalName();
            $path = UploadHelper::handleImageFile($logoWhite, $fileName, 'leagues', $logoWhite->getSize());
            $league->logo_white = $path;
        }

        $league->save();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'League updated successfully',
            'data' => $league,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $league = League::find($id);

        if (!$league) {
            return TheOneResponse::notFound('League not found');
        }

        if ($league->logo_primary && file_exists(storage_path('app/public/'.$league->logo_primary))) {
            Storage::delete('public/'.$league->logo_primary);
        }

        if ($league->logo_white && file_exists(storage_path('app/public/'.$league->logo_white))) {
            Storage::delete('public/'.$league->logo_white);
        }

        $league->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'League deleted successfully',
        ]);
    }
}
