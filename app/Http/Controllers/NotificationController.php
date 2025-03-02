<?php

namespace App\Http\Controllers;

use App\Http\Responses\TheOneResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notifications = Notification::all();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved notification list',
            'data' => $notifications
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
            'title' => 'required',
            'description' => 'required|max:2048',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        $notificationData = $request->except(['_token']);
        $notification = Notification::create($notificationData);

        $notification->status = $request->status;
        $notification->save();

        return TheOneResponse::created([
            'status' => true,
            'message' => 'Notification created successfully',
            'data' => $notification,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return TheOneResponse::notFound('Notification not found');
        }

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Notification details retrieved successfully',
            'data' => $notification,
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
        $notification = Notification::find($id);

        if (!$notification) {
            return TheOneResponse::notFound('Notification not found');
        }

        $notificationData = $request->except(['_token']);
        $notification->update($notificationData);
        $notification->save();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Notification updated successfully',
            'data' => $notification,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return TheOneResponse::notFound('Notification not found');
        }

        $notification->delete();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }
}
