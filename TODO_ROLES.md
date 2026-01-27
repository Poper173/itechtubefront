# TODO: Role-Based Authentication & Dashboards

## Roles Overview

### 👑 Admin
- **Purpose**: Control & moderation
- **Permissions**:
  - Approve/reject videos
  - Delete any video
  - Manage users (ban/role change)
  - View platform statistics
  - Moderate comments
  - Add/manage categories
- **Dashboard**: Twitch-style admin panel

### 🎥 Creator
- **Purpose**: Upload & manage content
- **Permissions**:
  - Upload videos
  - Edit own videos
  - See own analytics
  - Request approval
  - Manage thumbnails & metadata
- **Cannot publish directly** (admin approval required)
- **Dashboard**: Creator studio

### 👀 Viewer (User)
- **Purpose**: Consume content
- **Permissions**:
  - Watch videos
  - Like videos
  - Comment
  - Save watch progress
  - Subscribe to creators
- **Cannot upload** content

---

## Implementation Plan

### Step 1: Database Updates
- Add roles table if needed
- Add role to users table (already exists: enum('user', 'admin', 'creator'))
- Add video status field (pending, approved, rejected)
- Add user status (active, banned)

### Step 2: Authentication Updates
- Update registration to include role selection
- Middleware for role-based access control
- Login redirects to appropriate dashboard based on role

### Step 3: Backend API Updates
- VideoController: Add approval workflow
- UserController: Admin user management
- Middleware: CheckUserRole, IsAdmin, IsCreator

### Step 4: Frontend Dashboards
- Admin Dashboard (Twitch-style)
- Creator Dashboard
- User Dashboard (existing)

### Step 5: API Endpoints
```
Admin:
GET /api/admin/stats              - Platform statistics
GET /api/admin/videos/pending     - Pending videos
POST /api/admin/videos/{id}/approve
POST /api/admin/videos/{id}/reject
GET /api/admin/users              - Manage users
POST /api/admin/users/{id}/ban
POST /api/admin/users/{id}/role

Creator:
GET /api/creator/videos           - Own videos
POST /api/videos                  - Upload (status: pending)
PUT /api/videos/{id}              - Edit own video
GET /api/creator/analytics        - Own analytics
POST /api/videos/{id}/request-approval
```

---

## Files to Modify/Create

### Backend
- `app/Http/Middleware/CheckRole.php` (new)
- `app/Http/Controllers/Api/AdminController.php` (new)
- `app/Http/Controllers/Api/AuthController.php` (update)
- `app/Http/Controllers/Api/VideoController.php` (update)
- `routes/api.php` (update)

### Frontend
- `public/frontend/admin.html` (new - Twitch-style admin)
- `public/frontend/creator.html` (new - Creator dashboard)
- `public/frontend/js/auth.js` (new - Role auth helpers)
- `app.js` (update - Role-based redirects)
- `register.html` (update - Role selection)

