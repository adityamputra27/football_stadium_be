<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Http\Responses\TheOneResponse;
use App\Models\FootballClub;
use App\Models\FootballStadium;
use App\Models\FootballStadiumFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FootballStadiumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stadiums = FootballStadium::leftJoin('football_stadium_files', function ($join) {
            $join->on('football_stadiums.id', '=', 'football_stadium_files.football_stadium_id')
                ->whereRaw('football_stadium_files.id = (select min(id) from football_stadium_files
                    where football_stadium_files.football_stadium_id = football_stadiums.id)');
            })
            ->select('football_stadiums.*', 'football_stadium_files.file as latest_file')
            ->get();

        $listFiles = storage_path('app/public/stadiums/images');
        $filePath = "stadiums/images/";
        $filesFolder = scandir($listFiles);

        $result = [];
        foreach ($stadiums as $key => $value) {
            $footballClub = FootballClub::select('id', 'name', 'logo_primary', 'logo_white')
                ->where('id', $value->football_club_id)
                ->first();
            
            $obj['id'] = $value->id;
            $obj['football_club_id'] = $value->football_club_id;

            if (isset($value->football_club_id)) {
                if (!empty($footballClub)) {
                    $obj['football_club'] = $footballClub;
                    $obj['football_club']['logo_primary'] = url('/') . Storage::url($footballClub->logo_primary);
                    $obj['football_club']['logo_white'] = url('/') . Storage::url($footballClub->logo_white);
                }
            }
            
            if (!empty($value->latest_file) && in_array($value->latest_file, $filesFolder)) {
                $obj['latest_file'] = url('/') . Storage::url($filePath . $value->latest_file);
            } else {
                $obj['latest_file'] = null;
            }

            $obj['name'] = $value->name;
            $obj['capacity'] = $value->capacity;
            $obj['country'] = $value->country;
            $obj['city'] = $value->city;
            $obj['cost'] = $value->cost;
            $obj['status'] = $value->status;
            $obj['description'] = $value->description;

            $result[] = $obj;
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved football stadium list',
            'data' => $result
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
            'description' => 'nullable',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $stadiumData = $request->except(['_token']);
        $stadiumData['status'] = "ACTIVE";
        $stadiumData['description'] = "";
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

        $stadium->footballStadiumFiles()->delete();
        $stadium->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football stadium deleted successfully',
        ]);
    }

    public function files(string $stadiumId)
    {
        $files = FootballStadiumFile::where('football_stadium_id', $stadiumId)->count();
        $listImages = FootballStadiumFile::where('football_stadium_id', $stadiumId)->pluck('file')->toArray();
        $storeFolder = storage_path('app/public/stadiums/images/');
        $filePath = storage_path('app/public/');
        $filesFolder = scandir($storeFolder);

        $result = [];
        foreach ($filesFolder as $key => $file) {
            if ($file != '.' && $file != '..' && in_array($file, $listImages)) {
                $stadium = FootballStadiumFile::where('football_stadium_id', $stadiumId)->where('file', $file)->first();
                $filePath = $storeFolder . $file;

                $object['id'] = $stadium->id;
                $object['file'] = $file;
                $object['file_size'] = filesize($filePath);
                $object['file_path'] = url('/') . Storage::url('stadiums/images/'.$file);
                $result[] = $object;
            }
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved football stadium files',
            'data' => $result
        ]);
    }

    public function uploadFile(Request $request, string $stadiumId)
    {
        $result = [];
        $stadium = FootballStadium::where('id', $stadiumId)->first();

        if (!$stadium) {
            return TheOneResponse::notFound('Football stadium not found');
        }

        $validator = Validator::make($request->all(), [
            'football_stadium_id' => 'required|exists:football_stadiums,id',
            'file' => 'required|file|mimes:jpg,bmp,png',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        if ($request->hasFile('file')) {
            $image = $request->file('file');
            $fileInfo = $image->getClientOriginalName();
            $fileName = pathinfo($fileInfo, PATHINFO_FILENAME);
            $fileExt = pathinfo($fileInfo, PATHINFO_EXTENSION);

            $fileName = $fileName.'-'.time().'.'.$fileExt;
            $filePath = UploadHelper::handleImageFile($image, $fileName, 'stadiums', $image->getSize());

            $result = [
                'football_stadium_id' => $request->football_stadium_id,
                'file' => $fileName,
                'file_ext' => $fileExt,
                'file_size' => $image->getSize(),
                'file_path' => $filePath,
            ];

            FootballStadiumFile::create($result);
        }

        return TheOneResponse::created([
            'status' => true,
            'message' => 'Football stadium file upload successfully',
            'data' => $result,
        ]);
    }

    public function deleteFile(Request $request, string $stadiumId, string $fileId)
    {
        $footballStadium = FootballStadium::find($stadiumId);
        if (!$footballStadium) {
            return TheOneResponse::notFound('Football stadium not found');
        }

        $footballStadiumFile = FootballStadiumFile::where('football_stadium_id', $stadiumId)->where('id', $fileId)->first();
        if (!$footballStadiumFile) {
            return TheOneResponse::notFound('Football stadium file not found');
        }

        if (isset($footballStadiumFile->file)) {
            if ($footballStadiumFile->file && file_exists(storage_path('app/public/stadiums/images/'.$footballStadiumFile->file))) {
                Storage::delete('public/stadiums/images/'.$footballStadiumFile->file);
            } else {
                return TheOneResponse::notFound('Football stadium file not found');
            }
        }

        if ($request->hasFile('image')) {
            if ($footballStadiumFile->file && file_exists(storage_path('app/public/stadiums/images/'.$footballStadiumFile->file))) {
                $image = $request->file('image');
                Storage::delete('public/stadiums/images/'.$image);
            } else {
                return TheOneResponse::notFound('Football stadium file not found');
            }
        }

        $footballStadiumFile->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Football stadium file deleted successfully',
        ]);
    }
}
