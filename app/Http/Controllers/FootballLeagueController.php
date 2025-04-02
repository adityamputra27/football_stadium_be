<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Http\Responses\TheOneResponse;
use App\Models\FootballLeague;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FootballLeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $leagues = FootballLeague::orderBy('created_at', 'DESC')->get();
        $originFolder = storage_path('app/public/leagues/images/');
        $footballLeagueFiles = scandir($originFolder);

        $newLeagues = [];
        foreach ($leagues as $key => $value) {
            $row = [];
            if (!empty($value->name) && !empty($value->logo_primary)) {
                $detectFile = explode('leagues/images/', $value->logo_white)[1];
                if (in_array($detectFile, $footballLeagueFiles)) {
                    $row['id'] = $value->id;
                    $row['name'] = $value->name;
                    $row['logo_primary'] = url('/') . Storage::url($value->logo_primary);
                    $row['logo_white'] = url('/') . Storage::url($value->logo_white);
                    $row['visit_count'] = $value->visit_count;
                    $row['status'] = $value->status;
                    $row['inc_key'] = $key;
                }
            }
            $newLeagues[] = $row;
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved football league list',
            'data' => $newLeagues
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
            'name' => 'required|string|unique:football_leagues',
            'logo_primary' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5242880',
            'logo_white' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5242880',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $leagueData = $request->except(['logo_primary', 'logo_white']);
        $league = FootballLeague::create($leagueData);

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
            'message' => 'Football league created successfully',
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
        $league = FootballLeague::find($id);

        if (!$league) {
            return TheOneResponse::notFound('Football league not found');
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football league details retrieved successfully',
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
        $league = FootballLeague::find($id);

        if (!$league) {
            return TheOneResponse::notFound('Football league not found');
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
        $league = FootballLeague::find($id);

        if (!$league) {
            return TheOneResponse::notFound('Football league not found');
        }

        if ($league->logo_primary && file_exists(storage_path('app/public/'.$league->logo_primary))) {
            Storage::delete('public/'.$league->logo_primary);
        }

        if ($league->logo_white && file_exists(storage_path('app/public/'.$league->logo_white))) {
            Storage::delete('public/'.$league->logo_white);
        }

        $league->footballClubs()->delete();
        $league->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football league deleted successfully',
        ]);
    }
}
