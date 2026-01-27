<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreatorController extends Controller
{
    /**
     * Get channel profile for the authenticated creator.
     */
    public function getChannelProfile(Request $request)
    {
        $user = $request->user();

        // Calculate accurate stats from database
        $videosCount = $user->videos()->count();
        $totalViews = $user->videos()->sum('views_count');
        $totalSubscribers = \Illuminate\Support\Facades\DB::table('subscriptions')
            ->where('channel_id', $user->id)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'channel_name' => $user->channel_name ?? $user->name,
                'channel_description' => $user->channel_description,
                'channel_banner' => $user->channel_banner ? asset('storage/' . $user->channel_banner) : null,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'stream_key' => $user->stream_key,
                'stream_status' => $user->stream_status ?? 'offline',
                'stream_title' => $user->stream_title,
                'stream_viewers' => $user->stream_viewers ?? 0,
                'stream_started_at' => $user->stream_started_at,
                'total_views' => $totalViews,
                'total_subscribers' => $totalSubscribers,
                'videos_count' => $videosCount,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * Update channel profile.
     * Supports updating channel name, description, avatar, and banner.
     */
    public function updateChannelProfile(Request $request)
    {
        $user = $request->user();

        // Debug: Log incoming request data
        Log::info('UpdateChannelProfile - Request received', [
            'user_id' => $user->id,
            'has_channel_name' => $request->has('channel_name'),
            'has_channel_description' => $request->has('channel_description'),
            'has_avatar' => $request->hasFile('avatar'),
            'has_channel_banner' => $request->hasFile('channel_banner'),
            'avatar_name' => $request->file('avatar')?->getClientOriginalName(),
            'banner_name' => $request->file('channel_banner')?->getClientOriginalName(),
        ]);

        // Validate request
        $validated = $request->validate([
            'channel_name' => 'nullable|string|max:100|unique:users,channel_name,' . $user->id,
            'channel_description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'channel_banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        Log::info('UpdateChannelProfile - Validation passed', $validated);

        // Update channel name
        if (isset($validated['channel_name'])) {
            $user->channel_name = $validated['channel_name'];
        }

        // Update channel description
        if (isset($validated['channel_description'])) {
            $user->channel_description = $validated['channel_description'];
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            Log::info('UpdateChannelProfile - Processing avatar upload');
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
                Log::info('UpdateChannelProfile - Deleted old avatar', ['old_path' => $user->avatar]);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
            Log::info('UpdateChannelProfile - Avatar saved', ['path' => $avatarPath]);
        } else {
            Log::info('UpdateChannelProfile - No avatar file in request');
        }

        // Handle channel banner upload
        if ($request->hasFile('channel_banner')) {
            Log::info('UpdateChannelProfile - Processing banner upload');
            // Delete old banner if exists
            if ($user->channel_banner && Storage::disk('public')->exists($user->channel_banner)) {
                Storage::disk('public')->delete($user->channel_banner);
                Log::info('UpdateChannelProfile - Deleted old banner', ['old_path' => $user->channel_banner]);
            }

            $bannerPath = $request->file('channel_banner')->store('banners', 'public');
            $user->channel_banner = $bannerPath;
            Log::info('UpdateChannelProfile - Banner saved', ['path' => $bannerPath]);
        } else {
            Log::info('UpdateChannelProfile - No banner file in request');
        }

        $user->save();

        Log::info('UpdateChannelProfile - User saved', [
            'avatar' => $user->avatar,
            'channel_banner' => $user->channel_banner,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Channel profile updated successfully',
            'data' => [
                'channel_name' => $user->channel_name,
                'channel_description' => $user->channel_description,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'channel_banner' => $user->channel_banner ? asset('storage/' . $user->channel_banner) : null,
            ]
        ]);
    }

    /**
     * Regenerate stream key.
     */
    public function regenerateStreamKey(Request $request)
    {
        $user = $request->user();

        // Generate new stream key
        $streamKey = 'sk_' . Str::random(32);
        $user->stream_key = Hash::make($streamKey);
        $user->save();

        // Return the plain stream key (in production, you might want to show it only once)
        return response()->json([
            'success' => true,
            'message' => 'Stream key regenerated successfully',
            'data' => [
                'stream_key' => $streamKey,
                'stream_key_masked' => substr($streamKey, 0, 8) . '****' . substr($streamKey, -8),
            ]
        ]);
    }

    /**
     * Get stream key (masked).
     */
    public function getStreamKey(Request $request)
    {
        $user = $request->user();

        if (!$user->stream_key) {
            // Generate if doesn't exist
            $streamKey = 'sk_' . Str::random(32);
            $user->stream_key = Hash::make($streamKey);
            $user->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'stream_key' => $streamKey,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stream_key_masked' => 'sk_' . substr(md5($user->id), 0, 8) . '****',
                'has_stream_key' => true,
            ]
        ]);
    }

    /**
     * Start a live stream.
     */
    public function startStream(Request $request)
    {
        $user = $request->user();

        // Validate
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Check if already streaming
        if ($user->stream_status === 'live') {
            return response()->json([
                'success' => false,
                'message' => 'You are already streaming',
            ], 400);
        }

        // Update stream status
        $user->stream_status = 'live';
        $user->stream_title = $validated['title'];
        $user->stream_started_at = now();
        $user->stream_viewers = 0;
        $user->save();

        // Get stream key for RTMP URL
        $rtmpUrl = config('app.streaming_rtmp_url', 'rtmp://localhost/live');
        $streamKey = $user->stream_key ? 'sk_' . $user->id . '_' . substr(md5($user->id), 0, 8) : 'demo';

        return response()->json([
            'success' => true,
            'message' => 'Stream started successfully',
            'data' => [
                'stream_status' => 'live',
                'stream_title' => $user->stream_title,
                'stream_started_at' => $user->stream_started_at,
                'stream_url' => $rtmpUrl,
                'stream_key' => $streamKey,
                'websocket_url' => config('app.streaming_ws_url', 'ws://localhost:8080'),
                'channel_id' => $user->id,
            ]
        ]);
    }

    /**
     * Stop the live stream.
     */
    public function stopStream(Request $request)
    {
        $user = $request->user();

        if ($user->stream_status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'No active stream to stop',
            ], 400);
        }

        // Calculate stream duration
        $duration = 0;
        if ($user->stream_started_at) {
            $duration = now()->diffInSeconds($user->stream_started_at);
        }

        // Update stream status
        $user->stream_status = 'offline';
        $user->stream_title = null;
        $user->stream_started_at = null;
        $user->stream_viewers = 0;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Stream ended successfully',
            'data' => [
                'stream_status' => 'offline',
                'duration_seconds' => $duration,
                'duration_formatted' => gmdate('H:i:s', $duration),
            ]
        ]);
    }

    /**
     * Get current stream status (for real-time updates).
     */
    public function getStreamStatus(Request $request)
    {
        $user = $request->user();

        $streamData = [
            'stream_status' => $user->stream_status,
            'stream_title' => $user->stream_title,
            'stream_viewers' => $user->stream_viewers,
            'stream_started_at' => $user->stream_started_at,
        ];

        if ($user->stream_status === 'live' && $user->stream_started_at) {
            $streamData['stream_duration'] = now()->diffInSeconds($user->stream_started_at);
            $streamData['stream_duration_formatted'] = gmdate('H:i:s', $streamData['stream_duration']);
        }

        return response()->json([
            'success' => true,
            'data' => $streamData
        ]);
    }

    /**
     * Update stream viewer count (called by streaming server).
     */
    public function updateStreamViewers(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'viewers' => 'required|integer|min:0',
        ]);

        $user->stream_viewers = $validated['viewers'];
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'stream_viewers' => $user->stream_viewers,
            ]
        ]);
    }

    /**
     * Get channel statistics.
     */
    public function getChannelStats(Request $request)
    {
        $user = $request->user();

        $videos = $user->videos();
        $totalViews = $videos->sum('views_count');
        $totalLikes = $user->videos()->withCount('likes')->get()->sum('likes_count');

        // Get subscriber count from subscriptions table
        $subscribersCount = \Illuminate\Support\Facades\DB::table('subscriptions')
            ->where('channel_id', $user->id)
            ->count();

        // Get recent streams
        $recentStreams = $user->where('stream_status', 'live')
            ->where('id', $user->id)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_videos' => $videos->count(),
                'total_views' => $totalViews,
                'total_likes' => $totalLikes,
                'total_subscribers' => $subscribersCount,
                'is_streaming' => $user->stream_status === 'live',
                'current_viewers' => $user->stream_status === 'live' ? $user->stream_viewers : 0,
            ]
        ]);
    }

    /**
     * Get channel analytics (detailed).
     */
    public function getChannelAnalytics(Request $request)
    {
        $user = $request->user();

        // Get video performance data
        $videos = $user->videos()->selectRaw('
            id, title, views_count, likes_count, created_at,
            (SELECT COUNT(*) FROM watch_histories WHERE video_id = videos.id) as watch_count
        ')->get();

        // Calculate trends (last 7 days vs previous 7 days)
        $lastWeekViews = $videos->sum(function($video) {
            return \Illuminate\Support\Facades\DB::table('video_viewers')
                ->where('video_id', $video->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_videos' => $videos->count(),
                    'total_views' => $videos->sum('views_count'),
                    'total_likes' => $videos->sum('likes_count'),
                    'avg_views_per_video' => $videos->count() > 0 ? round($videos->sum('views_count') / $videos->count()) : 0,
                ],
                'top_videos' => $videos->sortByDesc('views_count')->take(5)->values(),
                'recent_activity' => [
                    'last_week_views' => $lastWeekViews,
                    'new_videos' => $videos->where('created_at', '>=', now()->subDays(7))->count(),
                ]
            ]
        ]);
    }

    /**
     * Get public channel profile (for viewers).
     */
    public function getPublicChannel($channelId)
    {
        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        $videos = $channel->videos()->where('visibility', 'public')->latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $channel->id,
                'name' => $channel->channel_name ?? $channel->name,
                'channel_description' => $channel->channel_description,
                'channel_banner' => $channel->channel_banner ? asset('storage/' . $channel->channel_banner) : null,
                'avatar' => $channel->avatar ? asset('storage/' . $channel->avatar) : null,
                'is_live' => $channel->stream_status === 'live',
                'current_stream_title' => $channel->stream_status === 'live' ? $channel->stream_title : null,
                'current_viewers' => $channel->stream_status === 'live' ? $channel->stream_viewers : 0,
                'total_views' => $channel->total_views,
                'total_subscribers' => $channel->total_subscribers,
                'videos_count' => $videos->count(),
                'videos' => $videos->map(function($video) {
                    return [
                        'id' => $video->id,
                        'title' => $video->title,
                        'thumbnail' => $video->thumbnail_path ? asset('storage/' . $video->thumbnail_path) : null,
                        'views_count' => $video->views_count,
                        'created_at' => $video->created_at,
                    ];
                }),
                'created_at' => $channel->created_at,
            ]
        ]);
    }

    /**
     * Get all live streams (for discovery page).
     */
    public function getAllLiveStreams()
    {
        $liveStreams = User::where('stream_status', 'live')
            ->orderBy('stream_started_at', 'desc')
            ->get()
            ->map(function($channel) {
                // Get subscriber count
                $subscribersCount = DB::table('subscriptions')
                    ->where('channel_id', $channel->id)
                    ->count();

                // Get video count
                $videosCount = $channel->videos()->count();

                // Calculate total views
                $totalViews = $channel->videos()->sum('views_count');

                return [
                    'id' => $channel->id,
                    'channel_name' => $channel->channel_name ?? $channel->name,
                    'name' => $channel->name,
                    'channel_description' => $channel->channel_description,
                    'avatar' => $channel->avatar ? asset('storage/' . $channel->avatar) : null,
                    'stream_title' => $channel->stream_title,
                    'stream_viewers' => $channel->stream_viewers ?? 0,
                    'stream_started_at' => $channel->stream_started_at,
                    'total_subscribers' => $subscribersCount,
                    'total_videos' => $videosCount,
                    'total_views' => $totalViews,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $liveStreams,
            'count' => $liveStreams->count()
        ]);
    }

    /**
     * Get a specific live stream by channel ID.
     */
    public function getLiveStream($channelId)
    {
        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        if ($channel->stream_status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'This channel is not currently streaming',
            ], 404);
        }

        // Get subscriber count
        $subscribersCount = DB::table('subscriptions')
            ->where('channel_id', $channel->id)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $channel->id,
                'channel_name' => $channel->channel_name ?? $channel->name,
                'channel_description' => $channel->channel_description,
                'avatar' => $channel->avatar ? asset('storage/' . $channel->avatar) : null,
                'channel_banner' => $channel->channel_banner ? asset('storage/' . $channel->channel_banner) : null,
                'stream_title' => $channel->stream_title,
                'stream_viewers' => $channel->stream_viewers,
                'stream_started_at' => $channel->stream_started_at,
                'total_subscribers' => $subscribersCount,
                'total_views' => $channel->total_views,
                'total_videos' => $channel->videos()->count(),
            ]
        ]);
    }

    // ============================================================
    // LIVE STREAM CHAT AND VIEWER TRACKING
    // ============================================================

    /**
     * Send a chat message to a live stream.
     * POST /api/live/{channelId}/chat
     */
    public function sendChatMessage(Request $request, $channelId)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required to send messages',
            ], 401);
        }

        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        if ($channel->stream_status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'This channel is not currently streaming',
            ], 400);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500|min:1',
        ]);

        // Create the chat message
        $chatMessage = \App\Models\StreamChat::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'is_system' => false,
        ]);

        // Load user relation for response
        $chatMessage->load('user:id,name,avatar');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chatMessage->id,
                'channel_id' => $chatMessage->channel_id,
                'user_id' => $chatMessage->user_id,
                'user_name' => $chatMessage->user->name,
                'user_avatar' => $chatMessage->user->avatar ? asset('storage/' . $chatMessage->user->avatar) : null,
                'message' => $chatMessage->message,
                'created_at' => $chatMessage->created_at,
            ]
        ]);
    }

    /**
     * Get chat messages for a live stream.
     * GET /api/live/{channelId}/chat
     *
     * Query params:
     * - since_id: Get messages after this ID (for polling)
     * - limit: Maximum messages to return (default 50)
     */
    public function getChatMessages(Request $request, $channelId)
    {
        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        if ($channel->stream_status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'This channel is not currently streaming',
            ], 400);
        }

        $sinceId = $request->input('since_id');
        $limit = min((int) $request->input('limit', 50), 100);

        $messages = \App\Models\StreamChat::getMessagesForChannel($channelId, $limit, $sinceId);

        return response()->json([
            'success' => true,
            'data' => $messages->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'channel_id' => $msg->channel_id,
                    'user_id' => $msg->user_id,
                    'user_name' => $msg->user->name,
                    'user_avatar' => $msg->user->avatar ? asset('storage/' . $msg->user->avatar) : null,
                    'message' => $msg->message,
                    'created_at' => $msg->created_at,
                    'is_system' => $msg->is_system,
                ];
            }),
            'count' => $messages->count(),
        ]);
    }

    /**
     * Join a live stream (increment viewer count).
     * POST /api/live/{channelId}/join
     */
    public function joinStream(Request $request, $channelId)
    {
        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        if ($channel->stream_status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'This channel is not currently streaming',
            ], 400);
        }

        // Increment viewer count
        $channel->stream_viewers = ($channel->stream_viewers ?? 0) + 1;
        $channel->save();

        return response()->json([
            'success' => true,
            'data' => [
                'stream_viewers' => $channel->stream_viewers,
                'message' => 'Joined stream successfully',
            ]
        ]);
    }

    /**
     * Leave a live stream (decrement viewer count).
     * POST /api/live/{channelId}/leave
     */
    public function leaveStream(Request $request, $channelId)
    {
        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        // Decrement viewer count (but not below 0)
        $channel->stream_viewers = max(0, ($channel->stream_viewers ?? 0) - 1);
        $channel->save();

        return response()->json([
            'success' => true,
            'data' => [
                'stream_viewers' => $channel->stream_viewers,
                'message' => 'Left stream successfully',
            ]
        ]);
    }

    /**
     * Get current viewer count for a stream.
     * GET /api/live/{channelId}/viewers
     */
    public function getViewerCount(Request $request, $channelId)
    {
        $channel = User::find($channelId);

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_streaming' => $channel->stream_status === 'live',
                'stream_viewers' => $channel->stream_status === 'live' ? ($channel->stream_viewers ?? 0) : 0,
            ]
        ]);
    }

    /**
     * Get stream info with chat for the stream owner.
     * This is used by creators to monitor their own stream.
     * GET /api/creator/stream/monitor
     */
    public function getStreamMonitor(Request $request)
    {
        $user = $request->user();

        if ($user->stream_status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'You are not currently streaming',
            ], 400);
        }

        // Get recent chat messages
        $recentMessages = \App\Models\StreamChat::getMessagesForChannel($user->id, 20, null);

        return response()->json([
            'success' => true,
            'data' => [
                'stream_status' => $user->stream_status,
                'stream_title' => $user->stream_title,
                'stream_viewers' => $user->stream_viewers ?? 0,
                'stream_started_at' => $user->stream_started_at,
                'stream_duration' => now()->diffInSeconds($user->stream_started_at),
                'stream_duration_formatted' => gmdate('H:i:s', now()->diffInSeconds($user->stream_started_at)),
                'recent_chat' => $recentMessages->map(function($msg) {
                    return [
                        'id' => $msg->id,
                        'user_name' => $msg->user->name,
                        'user_avatar' => $msg->user->avatar ? asset('storage/' . $msg->user->avatar) : null,
                        'message' => $msg->message,
                        'created_at' => $msg->created_at,
                    ];
                }),
            ]
        ]);
    }
}

