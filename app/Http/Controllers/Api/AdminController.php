<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Video;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function getStats()
    {
        $stats = [
            'total_videos' => Video::count(),
            'total_users' => User::count(),
            'pending_videos' => Video::where('status', 'pending')->count(),
            'total_views' => Video::sum('views_count') ?: 0,
            'total_categories' => Category::count(),
            'total_creators' => User::where('role', 'creator')->count(),
            'total_admins' => User::where('role', 'admin')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all users
     */
    public function getUsers(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search', '');
        $role = $request->input('role', '');
        $status = $request->input('status', '');

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'banned') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total()
            ]
        ]);
    }

    /**
     * Toggle user active status (ban/unban)
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot ban an admin'
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User unbanned' : 'User banned',
            'data' => [
                'user_id' => $user->id,
                'is_active' => $user->is_active
            ]
        ]);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:user,creator,admin'
        ]);

        $user = User::findOrFail($id);

        $currentUser = Auth::user();
        if ($currentUser && $user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change your own role'
            ], 403);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated',
            'data' => [
                'user_id' => $user->id,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Get all videos for admin
     */
    public function getVideos(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status', '');
        $search = $request->input('search', '');

        $query = Video::with('user', 'category')->withCount('likes');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        $videos = $query->orderBy('created_at', 'desc')
                        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $videos->items(),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'total' => $videos->total()
            ]
        ]);
    }

    /**
     * Approve a video
     */
    public function approveVideo($id)
    {
        $video = Video::findOrFail($id);
        $video->status = 'approved';
        $video->save();

        return response()->json([
            'success' => true,
            'message' => 'Video approved successfully'
        ]);
    }

    /**
     * Reject a video
     */
    public function rejectVideo($id)
    {
        $video = Video::findOrFail($id);
        $video->status = 'rejected';
        $video->save();

        return response()->json([
            'success' => true,
            'message' => 'Video rejected'
        ]);
    }

    /**
     * Delete a video
     */
    public function deleteVideo($id)
    {
        $video = Video::findOrFail($id);
        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully'
        ]);
    }

    /**
     * Get pending videos
     */
    public function getPendingVideos()
    {
        $videos = Video::with('user', 'category')
                       ->where('status', 'pending')
                       ->orderBy('created_at', 'desc')
                       ->get();

        return response()->json([
            'success' => true,
            'data' => $videos
        ]);
    }

    /**
     * Get recent users
     */
    public function getRecentUsers()
    {
        $users = User::orderBy('created_at', 'desc')
                     ->limit(10)
                     ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Create a new category
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string|max:1000'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => \Illuminate\Support\Str::slug($request->name)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ]);
    }

    /**
     * Update a category
     */
    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:1000'
        ]);

        $data = $request->only(['name', 'description']);
        if ($request->has('name')) {
            $data['slug'] = \Illuminate\Support\Str::slug($request->name);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Platform overview for admin dashboard
     */
    public function overview()
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'videos' => [
                'total' => Video::count(),
                'pending' => Video::where('status', 'pending')->count(),
                'approved' => Video::where('status', 'approved')->count(),
                'rejected' => Video::where('status', 'rejected')->count(),
            ],
            'users' => [
                'total' => User::count(),
                'new_today' => User::where('created_at', '>=', $today)->count(),
                'new_this_week' => User::where('created_at', '>=', $thisWeek)->count(),
                'creators' => User::where('role', 'creator')->count(),
            ],
            'engagement' => [
                'total_views' => Video::sum('views_count') ?: 0,
                'total_likes' => DB::table('video_likes')->count(),
                'total_comments' => DB::table('comments')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}

