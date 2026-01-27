# Unified Dashboard Implementation

## Goal
Create a single dashboard.html that works for both regular users and creators, showing/hiding features based on user role.

## Tasks

### Step 1: Update app.js with Role Detection Helpers
- [ ] Add `getUserRole()` helper function
- [ ] Add `isCreator()` helper function
- [ ] Add `isAdmin()` helper function
- [ ] Modify dashboard initialization to use role-based logic

### Step 2: Modify dashboard.html
- [ ] Add role detection on page load
- [ ] Add CSS classes for creator-only sections (`.creator-only`, `.user-only`)
- [ ] Add JavaScript to show/hide sections based on role
- [ ] Update navigation based on role
- [ ] Add creator-specific analytics section (for creators)

### Step 3: Test the Implementation
- [ ] Test as regular user - upload section should be hidden
- [ ] Test as creator - all features visible
- [ ] Verify navigation works correctly

## Implementation Details

### Role-Based Features

**Regular User (role: user):**
- Can browse and watch videos
- Can create/edit playlists
- Can like videos
- Can view watch history
- Cannot upload videos

**Creator (role: creator):**
- All user features
- Can upload videos (local, server, import)
- Can scan and import server videos
- Creator analytics/stats
- Video management

## Files to Modify
- `public/frontend/js/app.js` - Add role detection helpers
- `public/frontend/dashboard.html` - Add role-based visibility logic

