<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SubscriptionController
 *
 * Handles all subscription-related operations including subscribe/unsubscribe,
 * subscription status checks, and subscriber management.
 * All operations require authentication.
 */
class SubscriptionController extends Controller
{
    /**
     * Toggle subscription status for a channel.
     *
     * @param Request $request
     * @param int $channelId
     * @return JsonResponse
     */
    public function toggleSubscription(Request $request, int $channelId): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            // Validate channel exists
            $channel = User::find($channelId);
            if (!$channel) {
                return response()->json([
                    'message' => 'Channel not found',
                ], 404);
            }

            // Prevent subscribing to yourself
            if ($user->id === $channelId) {
                return response()->json([
                    'message' => 'You cannot subscribe to yourself',
                    'data' => [
                        'subscribed' => false,
                        'subscribers_count' => Subscription::getSubscribersCount($channelId),
                    ],
                ], 400);
            }

            $result = Subscription::toggleSubscription($user->id, $channelId);

            if (isset($result['error'])) {
                return response()->json([
                    'message' => $result['error'],
                    'data' => [
                        'subscribed' => false,
                        'subscribers_count' => $result['subscribers_count'],
                    ],
                ], 400);
            }

            return response()->json([
                'message' => $result['subscribed'] ? 'Subscribed successfully' : 'Unsubscribed successfully',
                'data' => [
                    'subscribed' => $result['subscribed'],
                    'subscribers_count' => $result['subscribers_count'],
                    'channel_id' => $channelId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription status for a specific channel.
     *
     * @param Request $request
     * @param int $channelId
     * @return JsonResponse
     */
    public function getSubscriptionStatus(Request $request, int $channelId): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            // Validate channel exists
            $channel = User::find($channelId);
            if (!$channel) {
                return response()->json([
                    'message' => 'Channel not found',
                ], 404);
            }

            $isSubscribed = Subscription::isSubscribed($user->id, $channelId);
            $subscribersCount = Subscription::getSubscribersCount($channelId);

            return response()->json([
                'message' => 'Subscription status retrieved',
                'data' => [
                    'subscribed' => $isSubscribed,
                    'subscribers_count' => $subscribersCount,
                    'channel_id' => $channelId,
                    'channel' => [
                        'id' => $channel->id,
                        'name' => $channel->name,
                        'avatar' => $channel->avatar,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get subscription status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all channels the authenticated user is subscribed to.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function mySubscriptions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            $perPage = min($request->input('per_page', 12), 50);

            $subscriptions = DB::table('subscriptions')
                ->where('subscriber_id', $user->id)
                ->join('users', 'subscriptions.channel_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.avatar',
                    'subscriptions.created_at as subscribed_at'
                )
                ->orderByDesc('subscriptions.created_at')
                ->paginate($perPage);

            return response()->json([
                'message' => 'Subscriptions retrieved successfully',
                'data' => [
                    'subscriptions' => $subscriptions->items(),
                    'total' => $subscriptions->total(),
                    'current_page' => $subscriptions->currentPage(),
                    'last_page' => $subscriptions->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get subscriptions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all subscribers of a specific channel.
     *
     * @param Request $request
     * @param int $channelId
     * @return JsonResponse
     */
    public function getChannelSubscribers(Request $request, int $channelId): JsonResponse
    {
        try {
            // Validate channel exists
            $channel = User::find($channelId);
            if (!$channel) {
                return response()->json([
                    'message' => 'Channel not found',
                ], 404);
            }

            $user = $request->user();
            $perPage = min($request->input('per_page', 12), 50);

            $subscribers = DB::table('subscriptions')
                ->where('channel_id', $channelId)
                ->join('users', 'subscriptions.subscriber_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.avatar',
                    'subscriptions.created_at as subscribed_at'
                )
                ->orderByDesc('subscriptions.created_at')
                ->paginate($perPage);

            // Check if current user is subscribed
            $isSubscribed = false;
            if ($user) {
                $isSubscribed = Subscription::isSubscribed($user->id, $channelId);
            }

            return response()->json([
                'message' => 'Subscribers retrieved successfully',
                'data' => [
                    'channel' => [
                        'id' => $channel->id,
                        'name' => $channel->name,
                    ],
                    'subscribers' => $subscribers->items(),
                    'total_subscribers' => $subscribers->total(),
                    'is_subscribed' => $isSubscribed,
                    'current_page' => $subscribers->currentPage(),
                    'last_page' => $subscribers->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get subscribers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user is subscribed to multiple channels at once.
     * Useful for displaying subscription status on video cards.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkMultipleSubscriptions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            $channelIds = $request->input('channel_ids', []);

            if (empty($channelIds)) {
                return response()->json([
                    'message' => 'No channel IDs provided',
                    'data' => [],
                ], 400);
            }

            // Get all subscriptions for these channels
            $subscriptions = DB::table('subscriptions')
                ->where('subscriber_id', $user->id)
                ->whereIn('channel_id', $channelIds)
                ->pluck('channel_id')
                ->toArray();

            $subscribedChannels = array_flip($subscriptions);

            // Build result
            $result = [];
            foreach ($channelIds as $channelId) {
                $result[$channelId] = isset($subscribedChannels[$channelId]);
            }

            return response()->json([
                'message' => 'Subscription status retrieved',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to check subscriptions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription statistics for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMySubscriptionStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            $subscriptionsCount = Subscription::getSubscriptionsCount($user->id);
            $subscribersCount = Subscription::getSubscribersCount($user->id);

            return response()->json([
                'message' => 'Subscription stats retrieved',
                'data' => [
                    'subscriptions_count' => $subscriptionsCount,
                    'subscribers_count' => $subscribersCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get subscription stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

