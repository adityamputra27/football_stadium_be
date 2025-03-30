<?php

namespace App\Http\Controllers;

use App\Http\Responses\TheOneResponse;
use App\Models\FootballClub;
use App\Models\FootballLeague;
use App\Models\FootballStadium;
use App\Models\Notification as NotificationModel;
use App\Models\NotificationMark;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class APIController extends Controller
{
    var $placeholderImage = "https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png";

    public function notificationCounter(Request $request, $userId)
    {
        if (!User::where('id', $userId)->exists()) {
            return TheOneResponse::notFound('User not found');
        }

        $unreadNotificationCount = Cache::remember("user_{$userId}_unread_notification_count", now()->addMinutes(15), function () use ($userId) {
            return NotificationMark::where('user_id', $userId)->where('mark_status', 'unread')->count();
        });

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved notification counter',
            'data' => [
                'unread_notification_count' => $unreadNotificationCount,
                'last_updated' => now()->toISOString(),
            ]
        ]);
    }

    public function notificationUser(Request $request, $userId)
    {
        if (!User::where('id', $userId)->exists()) {
            return TheOneResponse::notFound('User not found');
        }

        $perPage = $request->get('per_page', 15);
        $notifications = NotificationModel::select([
                'notifications.id',
                'notifications.title',
                'notifications.description',
                'notifications.category',
                'notifications.params',
                'notifications.status',
                'notifications.created_at',
            ])
            ->with(['notificationMarks' => function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->select(['id', 'notification_id', 'user_id', 'mark_status']);
            }])
            ->whereHas('notificationMarks', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('notifications.created_at', 'DESC')
            ->paginate($perPage);

        $unreadNotificationCount = Cache::remember("user_{$userId}_unread_notification_count", now()->addMinutes(15), function () use ($userId) {
            return NotificationMark::where('user_id', $userId)->where('mark_status', 'unread')->count();
        });

        $formattedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'description' => $notification->description,
                'category' => $notification->category,
                'params' => $notification->params != 'none' ? json_decode($notification->params, true) : $notification->params,
                'status' => $notification->status,
                'is_read' => $notification->notificationMarks->first()->mark_status === 'read',
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'time_ago' => $notification->created_at->diffForHumans(),
            ];
        });

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved notification list',
            'data' => $formattedNotifications,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'total' => $notifications->total(),
                'unread_notification_count' => $unreadNotificationCount
            ]
        ]);
    }

    public function markNotificationUser(Request $request, $userId, $notificationId)
    {
        if (!User::where('id', $userId)->exists()) {
            return TheOneResponse::notFound('User not found');
        }

        $notificationMark = NotificationMark::where('notification_id', $notificationId)->where('user_id', $userId)->first();

        if (!$notificationMark) {
            return TheOneResponse::notFound('Notification not found');
        }

        if ($notificationMark->mark_status == 'read') {
            return TheOneResponse::ok([
                'status' => true,
                'message' => 'Notification already marked as read',
            ]);
        }

        DB::transaction(function () use ($notificationMark, $userId) {
            $notificationMark->update(['mark_status' => 'read']);
            Cache::forget("user_{$userId}_unread_notification_count");
        });

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully updated mark notification',
        ]);
    }

    public function createFirstNotificationUser(Request $request)
    {
        $request->validate([
            'device_id' => 'nullable',
            'fcm_token' => 'required|string|min:10',
        ]);

        $user = User::where('device_id', $request->device_id)->orWhere('fcm_token', $request->fcm_token)->first();

        if (!$user) {
            $user = User::create([
                'device_id' => $request->device_id,
                'fcm_token' => $request->fcm_token,
                'last_login' => Carbon::now(),
                'first_notif' => 'success',
            ]);

            $messaging = app('firebase.messaging');
            $notificationTitle = 'Welcome to Football Stadium App!';
            $notificationDescription = "Hi! Thanks for installing our app, we hope you enjoy and let's check it out all of about our stadium information!";
            
            $notification = NotificationModel::create([
                'title' => $notificationTitle,
                'description' => $notificationDescription,
                'status' => 'success',
                'category' => NotificationModel::CATEGORY_WELCOME,
                'user_id' => $user->id,
            ]);

            if ($notification) {
                $message = CloudMessage::withTarget('token', $request->fcm_token)
                    ->withNotification(Notification::create(
                        $notificationTitle, 
                        $notificationDescription
                    ))
                    ->withData([
                        'notification_id' => $notification->id,
                        'category' => $notification->category,
                        'params' => $notification->params != 'none' ? json_decode($notification->params, true) : 'none',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]);

                $result = $messaging->send($message);

                if ($result) {
                    NotificationMark::create([
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                        'mark_status' => 'unread',
                    ]);
                }
            }

            return TheOneResponse::ok([
                'status' => true,
                'message' => 'User registered successfully. Welcome notification has been sent',
                'data' => $user,
                'is_new_device' => true,
            ]);
        }

        if ($request->fcm_token && $user->fcm_token !== $request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token, 'last_login' => Carbon::now()]);
        }

        $user->update(['last_login' => Carbon::now()]);

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'User already registered',
            'data' => $user,
            'is_new_device' => false,
        ]);
    }

    public function resetUser(Request $request)
    {
        if (!User::where('id', $request->user_id)->orWhere('fcm_token', $request->fcm_token)->exists()) {
            return TheOneResponse::notFound('User not found');
        }

        $user = User::where('id', $request->user_id)->orWhere('fcm_token', $request->fcm_token)->first();
        DB::transaction(function () use ($user) {
            $user->notificationMarks()->delete();
            $user->delete();
        });

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully reset mobile user',
        ]);
    }

    public function mainScreenUser(Request $request)
    {
        try {
            $popularStadiums = $this->getPopularStadiums();
            $excludedIds = collect($popularStadiums)->pluck('football_club_id')->toArray();

            $data = [
                'popular_stadiums' => $popularStadiums,
                'popular_leagues' => $this->getPopularLeagues(),
                'popular_clubs' => $this->getPopularClubs($excludedIds),
            ];

            return TheOneResponse::ok([
                'status' => true,
                'message' => 'Successfully retrieved main screen data',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            report($th);
            return TheOneResponse::internalServerError('Failed retrieved main screen data');
        }
    }

    protected function getPopularStadiums()
    {
        $cacheKey = 'popular_stadiums_' . now()->format('Ymd');
        return Cache::remember($cacheKey, now()->addHours(1), function () {
            $stadiums = FootballClub::select(
                            'football_clubs.id as football_club_id',
                            'football_clubs.name as club_name', 
                            'football_clubs.logo_primary',
                            'football_stadiums.name as stadium_name',
                            'football_stadiums.capacity',
                            'football_stadiums.status',
                            'football_stadium_files.file_path as stadium_file_path',
                        )
                        ->leftJoin('football_stadiums', 'football_stadiums.football_club_id', '=', 'football_clubs.id')
                        ->leftJoin('football_stadium_files', function ($join) {
                            $join->on('football_stadium_files.football_stadium_id', '=', 'football_stadiums.id')
                                ->whereRaw('football_stadium_files.id = (
                                    SELECT MAX(id) FROM football_stadium_files
                                    WHERE football_stadium_id = football_stadium_files.football_stadium_id
                                )');
                        })
                        ->where('football_clubs.status', 'ACTIVE')
                        ->orderBy('football_clubs.visit_count', 'DESC')
                        ->take(3)
                        ->get();

            return $stadiums->map(function ($stadium) {
                $filePath = $stadium->stadium_file_path;

                return [
                    'football_club_id' => $stadium->football_club_id,
                    'club_name' => $stadium->club_name,
                    'logo_primary' => $stadium->logo_primary,
                    'stadium_name' => $stadium->stadium_name,
                    'capacity' => $stadium->capacity,
                    'status' => $stadium->status,
                    'stadium_file_path' => $filePath,
                ];
            })->toArray();
        });
    }   
    
    protected function getPopularLeagues()
    {
        $cacheKey = 'popular_leagues_' . now()->format('Ymd');
        return Cache::remember($cacheKey, now()->addHours(1), function () {
            $leagues = FootballLeague::select('id', 'name', 'logo_white')
                    ->where('status', 'ACTIVE')
                    ->orderBy('visit_count', 'DESC')
                    ->take(3)
                    ->get();

            return $leagues->map(function ($league) {
                $filePath = $this->getValidFilePath(
                    $league->logo_white,
                    'leagues/images/',
                    storage_path('app/public/leagues/images'),
                );

                return [
                    'id' => $league->id,
                    'name' => $league->name,
                    'logo_white' => $filePath,
                ];
            })->toArray();
        });
    }

    protected function getPopularClubs(array $excludedClubIds = [])
    {
        $cacheKey = 'popular_clubs_' . now()->format('Ymd') . '_' . implode('_', $excludedClubIds);
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($excludedClubIds) {
            $clubs = FootballClub::select(
                        'football_clubs.id as football_club_id',
                        'football_clubs.name as club_name',
                        'football_clubs.logo_white',
                        'football_stadiums.name as stadium_name',
                        'football_stadiums.capacity',
                        'football_stadiums.status',    
                    )
                    ->leftJoin('football_stadiums', 'football_stadiums.football_club_id', '=', 'football_clubs.id')
                    ->where('football_clubs.status', 'ACTIVE')
                    ->when(!empty($excludedClubIds), function ($query) use ($excludedClubIds) {
                        $query->whereNotIn('football_clubs.id', $excludedClubIds);
                    })
                    ->orderBy('football_clubs.visit_count', 'DESC')
                    ->take(3)
                    ->get();

            return $clubs->map(function ($club) {
                $filePath = $this->getValidFilePath(
                    $club->logo_white,
                    'clubs/images/',
                    storage_path('app/public/clubs/images')
                );

                return [
                    'football_club_id' => $club->football_club_id,
                    'club_name' => $club->club_name,
                    'stadium_name' => $club->stadium_name,
                    'capacity' => $club->capacity,
                    'status' => $club->status,
                    'logo_white' => $filePath,
                ];
            });
        });
    }

    protected function getValidFilePath($file, $storagePath, $directoryPath) 
    {
        if (empty($file)) {
            return $this->placeholderImage;
        }
        
        $fileName = last(explode('/', $file));
        $filesInDirectory = scandir($directoryPath);

        if (in_array($fileName, $filesInDirectory)) {
            return url('/') . Storage::url($storagePath . $fileName);
        }

        return $this->placeholderImage;
    }

    public function allLeagues()
    {
        $mappedData = [];
        $leagues = FootballLeague::select(
                        'football_leagues.id',
                        'football_leagues.name', 
                        'football_leagues.logo_white',
                        DB::raw('COUNT(football_clubs.id) as club_total'))
                    ->leftJoin('football_clubs', function ($join) {
                        $join->on('football_clubs.football_league_id', '=', 'football_leagues.id')
                            ->where('football_clubs.status', 'ACTIVE');
                    })
                    ->where('football_leagues.status', 'ACTIVE')
                    ->orderBy('football_leagues.name', 'ASC')
                    ->groupBy('football_leagues.id', 'football_leagues.name')
                    ->get();

        $mappedData = $leagues->map(function ($league) {
            $filePath = $this->getValidFilePath(
                $league->logo_white,
                'leagues/images/',
                storage_path('app/public/leagues/images'),
            );

            return [
                'id' => $league->id,
                'name' => $league->name,
                'logo_white' => $filePath,
                'club_total' => $league->club_total
            ];
        })->toArray();

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved all leagues data',
            'data' => $mappedData
        ]);
    }

    public function allClubsPerLeague($leagueId)
    {
        $checkLeague = FootballLeague::where('id', $leagueId)->first();
        if (!$checkLeague) {
            return TheOneResponse::notFound('Football league not found');
        }

        $clubs = FootballClub::select(
                    'football_stadiums.id as football_stadium_id',
                    'football_clubs.id as football_club_id',
                    'football_clubs.name as club_name',
                    'football_clubs.logo_white',
                    'football_stadiums.name as stadium_name',
                    'football_stadiums.capacity',
                    'football_stadiums.status',    
                )
                ->leftJoin('football_stadiums', 'football_stadiums.football_club_id', '=', 'football_clubs.id')
                ->where('football_clubs.status', 'ACTIVE')
                ->where('football_clubs.football_league_id', $leagueId)
                ->orderBy('football_clubs.visit_count', 'DESC')
                ->get();

        $mappedData =  $clubs->map(function ($club) {
            $filePath = $this->getValidFilePath(
                $club->logo_white,
                'clubs/images/',
                storage_path('app/public/clubs/images')
            );

            return [
                'football_stadium_id' => $club->football_stadium_id,
                'football_club_id' => $club->football_club_id,
                'club_name' => $club->club_name,
                'stadium_name' => $club->stadium_name,
                'capacity' => $club->capacity,
                'status' => $club->status,
                'logo_white' => $filePath,
            ];
        });

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved all clubs per league',
            'data' => $mappedData,
        ]);
    }

    public function clubStadium($leagueId, $clubId)
    {
        $checkLeague = FootballLeague::where('id', $leagueId)->first();
        if (!$checkLeague) {
            return TheOneResponse::notFound('Football league not found');
        }

        $checkClub = FootballClub::where('id', $clubId)->first();
        if (!$checkClub) {
            return TheOneResponse::notFound('Football club not found');
        }

        $stadium = FootballStadium::with(['footballClub', 'footballStadiumFiles'])
                        ->whereHas('footballClub', function ($query) use ($clubId, $leagueId) {
                            $query->where('football_clubs.id', $clubId);
                            $query->where('football_clubs.football_league_id', $leagueId);
                        })
                        ->first();

        $mappedData = [
            'id' => $stadium->id,
            'football_club_id' => $stadium->football_club_id,
            'name' => $stadium->name,
            'capacity' => $stadium->capacity,
            'country' => $stadium->country,
            'city' => $stadium->city,
            'cost' => $stadium->cost,
            'status' => $stadium->status,
            'description' => $stadium->description,
            'football_club' => [
                'id' => $stadium->footballClub->id,
                'name' => $stadium->footballClub->name,
                'logo_white' => $this->getValidFilePath($stadium->footballClub->logo_white, 'clubs/images/', storage_path('app/public/clubs/images/')),
                'visit_count' => $stadium->footballClub->visit_count,
                'status' => $stadium->footballClub->status,
            ],
            'football_stadium_files' => $stadium->footballStadiumFiles->map(function ($file) {
                return [
                    'id' => $file->id,
                    'football_stadium_id' => $file->football_stadium_id,
                    'file_path' => $file->file_path,
                ];
            }),
        ];

        return TheOneResponse::ok([
            'status' => true,
            'message' => 'Successfully retrieved club stadium data',
            'data' => $mappedData,
        ]);
    }
}
