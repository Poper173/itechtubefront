# iTechTube - Complete Implementation Plan

## 🎯 Priority 1: Fix Video Streaming Issue (CRITICAL)
- [ ] Debug video streaming endpoint
- [ ] Fix file path resolution for streaming
- [ ] Ensure storage disk is properly configured
- [ ] Test video playback from thumbnail click

## 🎯 Priority 2: Role-Based Access Control (RBAC)
### Database Migrations
- [ ] Add `role` column to users table (already exists: user/admin/creator)
- [ ] Add `is_active` column to users table (already exists)
- [ ] Create `channel_name`, `bio`, `subscriber_count` columns

### Middleware
- [ ] Create `RoleMiddleware` for admin/creator/viewer checks
- [ ] Create `AdminMiddleware` for admin-only routes
- [ ] Create `CreatorMiddleware` for creator-only routes

### Routes Protection
- [ ] Protect admin routes with role middleware
- [ ] Protect video upload with creator middleware
- [ ] Update existing routes to use new middleware

## 🎯 Priority 3: User Profile Module
### Backend
- [ ] Update `User` model with profile fields
- [ ] Create `ProfileController` for profile CRUD
- [ ] Add profile photo upload endpoint
- [ ] Create API endpoint for user profile

### Frontend
- [ ] Create `profile.html` page
- [ ] Add profile photo upload UI
- [ ] Add profile editing functionality
- [ ] Display subscriber count

## 🎯 Priority 4: Video Visibility
### Database
- [ ] Add `visibility` column to videos table (public/private)

### Backend
- [ ] Update `Video` model scope for visibility
- [ ] Update video CRUD to handle visibility
- [ ] Filter videos by visibility in listings

### Frontend
- [ ] Add visibility toggle in upload form
- [ ] Add visibility indicator on video cards
- [ ] Hide private videos from public listings

## 🎯 Priority 5: Comments System
### Database Migrations
- [ ] Create `comments` table (id, user_id, video_id, content, parent_id, created_at)
- [ ] Create `comment_likes` table

### Backend
- [ ] Create `Comment` model with relationships
- [ ] Create `CommentController`
- [ ] Implement CRUD for comments
- [ ] Implement nested replies
- [ ] Implement comment likes

### Frontend
- [ ] Add comments section to video page
- [ ] Add comment form
- [ ] Display nested comments
- [ ] Add reply functionality

## 🎯 Priority 6: Subscriptions System
### Database Migrations
- [ ] Create `subscriptions` table (subscriber_id, channel_id, created_at)

### Backend
- [ ] Create `Subscription` model
- [ ] Create `SubscriptionController`
- [ ] Implement subscribe/unsubscribe
- [ ] Get subscriber count
- [ ] Get subscribed channels

### Frontend
- [ ] Add subscribe button on video page
- [ ] Display subscriber count
- [ ] Create subscriptions page

## 🎯 Priority 7: Admin Dashboard
### Backend
- [ ] Create `AdminController`
- [ ] Get all users with pagination
- [ ] Block/unblock users
- [ ] Delete videos
- [ ] Get platform analytics

### Frontend
- [ ] Create `admin.html` page
- [ ] User management table
- [ ] Video management table
- [ ] Analytics display

## 🎯 Priority 8: Password Reset
### Backend
- [ ] Configure Laravel password reset
- [ ] Create `PasswordController`
- [ ] Add email sending configuration
- [ ] Create password reset routes

### Frontend
- [ ] Create `forgot-password.html` page
- [ ] Create `reset-password.html` page
- [ ] Add email input form
- [ ] Add password reset form

## 🎯 Priority 9: Fix Video Streaming (Technical Details)
### Files to Check/Fix
- [ ] `config/filesystems.php` - Ensure public disk is configured
- [ ] `app/Http/Controllers/Api/VideoController::stream()` - Fix path resolution
- [ ] Storage symlink - Ensure `public/storage` exists
- [ ] File permissions - Ensure storage directory is writable
- [ ] Check video file paths in database vs actual files

## 📋 Implementation Order
1. Fix video streaming issue (user can't watch videos)
2. User Profile Module
3. Role-Based Access Control
4. Video Visibility
5. Comments System
6. Subscriptions System
7. Admin Dashboard
8. Password Reset

## 🔐 Security Features to Add
- [ ] CSRF protection for web routes
- [ ] Additional input validation
- [ ] File type validation for uploads
- [ ] Rate limiting for sensitive endpoints

## 📝 Notes
- All endpoints should return consistent JSON responses
- Use Laravel resources for API responses
- Implement proper error handling
- Add comprehensive logging
- Write unit tests for core functionality

Role-Based Access Control (middleware + route protection)
Admin Dashboard (backend + frontend)
Profile API integration
Password Reset
