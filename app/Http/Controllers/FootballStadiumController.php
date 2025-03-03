<?php

namespace App\Http\Controllers;

use App\Http\Responses\TheOneResponse;
use App\Models\FootballStadium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FootballStadiumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stadiums = FootballStadium::all();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved football stadium list',
            'data' => $stadiums
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
            'football_club_id' => 'required|exists:football_clubs,id',
            'name' => 'required|string',
            'capacity' => 'required|string',
            'country' => 'required|string',
            'city' => 'required|string',
            'cost' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $stadiumData = $request->except(['_token']);
        $stadiumData['status'] = "ACTIVE";
        $stadium = FootballStadium::create($stadiumData);
        $stadium->save();

        return TheOneResponse::created([
            'status' => true,
            'message' => 'Football stadium created successfully',
            'data' => $stadium,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stadium = FootballStadium::find($id);

        if (!$stadium) {
            return TheOneResponse::notFound('Football stadium not found');
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football stadium details retrieved successfully',
            'data' => $stadium,
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
        $stadium = FootballStadium::find($id);

        if (!$stadium) {
            return TheOneResponse::notFound('Football stadium not found');
        }

        $validator = Validator::make($request->all(), [
            'football_club_id' => 'required|exists:football_clubs,id',
            'name' => 'required|string',
            'capacity' => 'required|string',
            'country' => 'required|string',
            'city' => 'required|string',
            'cost' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $stadiumData = $request->except(['_token']);
        $stadiumData['status'] = "ACTIVE";

        $stadium->update($stadiumData);
        $stadium->save();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football stadium updated successfully',
            'data' => $stadium,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $stadium = FootballStadium::find($id);

        if (!$stadium) {
            return TheOneResponse::notFound('Football stadium not found');
        }

        $stadium->stadiumFiles()->delete();
        $stadium->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football stadium deleted successfully',
        ]);
    }
}
