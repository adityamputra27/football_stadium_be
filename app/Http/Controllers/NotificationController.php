<?php

namespace App\Http\Controllers;

use App\Http\Responses\TheOneResponse;
use App\Models\Notification;
use App\Models\NotificationMark;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

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
            'send_push' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return TheOneResponse::badRequest($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $notification = Notification::create([
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->status,
                'category' => $request->category,
                'params' => $request->params ? json_encode($request->params) : 'none',
            ]); 

            if ($request->boolean('send_push')) {
                $this->sendPushNotification($notification, []);
            }

            DB::commit();

            return TheOneResponse::created([
                'status' => true,
                'message' => 'Notification created successfully',
                'data' => $notification,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return TheOneResponse::internalServerError('Failed to create notification');
        }
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

        try {
            DB::beginTransaction();

            $notification->update($request->only([
                'title', 'description', 'status', 'category', 'params'
            ]));

            if ($request->boolean('send_push') && $notification->status == 'success') {
                $this->sendPushNotification($notification, []);
            }

            DB::commit();

            return TheOneResponse::created([
                'status' => true,
                'message' => 'Notification updated successfully',
                'data' => $notification,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return TheOneResponse::internalServerError('Failed to create notification');
        }
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

    public function sendPushNotification(Notification $notification, array $targetUserIds)
    {
        try {
            $messaging = app('firebase.messaging');
            $baseQuery = User::query();

            if (!empty($targetUserIds) && !is_null($targetUserIds) && count($targetUserIds)) {
                $baseQuery->whereIn('id', $targetUserIds);
            }

            $users = $baseQuery->whereNotNull('fcm_token')->get();
            if (!$users || $users->isEmpty()) {
                return false;
            }

            $successCount = 0;
            $failureTokens = [];

            foreach ($users as $key => $user) {
                try {
                    $token = $user->fcm_token ?? '';
                    $targetType = $user->fcm_token ? 'token' : 'apns_token';

                    $message = CloudMessage::new()
                        ->withTarget($targetType, $token)
                        ->withNotification(FirebaseNotification::create(
                            $notification->title,
                            $notification->description
                        ))
                        ->withData([
                            'notification_id' => $notification->id,
                            'category' => $notification->category,
                            'params' => $notification->params != 'none' ? json_decode($notification->params, true) : 'none',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ]);

                    $messaging->send($message);
                    $successCount++;

                    $notificationMark = NotificationMark::where('notification_id', $notification->id)->where('user_id', $user->id)->first();
                    if (!$notificationMark) {
                        NotificationMark::create([
                            'notification_id' => $notification->id,
                            'user_id' => $user->id,
                            'mark_status' => 'unread',
                        ]);
                    }
                } catch (\Throwable $th) {
                    $failureTokens[] = [
                        'token' => $token,
                        'error' => $th->getMessage(),
                    ];
                    continue;
                }
            }

            $notification->update([
                'sent_at' => now(),
                'sent_at_status' => $successCount > 0 ? 'partially_sent' : 'failed',
                'meta' => [
                    'success_count' => $successCount,
                    'failure_tokens' => $failureTokens,
                ]
            ]);

            return $successCount > 0;

        } catch (\Throwable $th) {
            report($th);

            return false;
        }
    }
}
