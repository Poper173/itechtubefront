<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Get current user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Build avatar URL
        $avatarUrl = null;
        if ($user->avatar) {
            $avatarUrl = asset('storage/' . $user->avatar);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'avatar_url' => $avatarUrl,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Update user profile (name and avatar).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate input
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
                'channel_name' => ['nullable', 'string', 'max:100'],
                'channel_description' => ['nullable', 'string', 'max:1000'],
            ]);

            // Update name
            $user->name = $validated['name'];

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Store new avatar in public disk
                $avatar = $request->file('avatar');
                $avatarPath = $avatar->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            // Update channel profile fields if provided
            if (isset($validated['channel_name'])) {
                $user->channel_name = $validated['channel_name'];
            }
            if (array_key_exists('channel_description', $validated)) {
                $user->channel_description = $validated['channel_description'];
            }

            $user->save();

            // Build avatar URL
            $avatarUrl = null;
            if ($user->avatar) {
                $avatarUrl = asset('storage/' . $user->avatar);
            }

            // Build channel banner URL
            $channelBannerUrl = null;
            if ($user->channel_banner) {
                $channelBannerUrl = asset('storage/' . $user->channel_banner);
            }

            // Return updated user data
            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                        'avatar_url' => $avatarUrl,
                        'channel_name' => $user->channel_name,
                        'channel_description' => $user->channel_description,
                        'channel_banner' => $user->channel_banner,
                        'channel_banner_url' => $channelBannerUrl,
                    ]
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change user password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate input
            $validated = $request->validate([
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            // Update password
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Revoke all tokens (force re-login)
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Password changed successfully. Please login again.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

