# Implementation Plan for Video Player Enhancements

## Overview
This plan outlines the implementation of YouTube-style features for the video streaming platform.

---

## Features to Implement

### 1. Like Button with Heart Icons
**UI Changes:**
- Replace thumbs up icon with heart emoji (♡ = unliked, ❤️ = liked)
- Red filled heart when liked, outline heart when not liked
- Position: Keep in the same location

**Backend:** Already implemented (VideoLike model exists)

**Frontend:** Update `video.html` like button

### 2. Share Button with Arrow for WhatsApp
**UI Changes:**
- Add share icon with arrow (📤 or ➤)
- Add WhatsApp sharing option in share modal
- Update share modal to include WhatsApp

### 3. Playback Controls (10s Forward/Backward)
**UI Changes:**
- Add ⏪ button for 10 seconds backward
- Add ⏩ button for 10 seconds forward
- Position: In video player controls area

### 4. Video Duration Display
**UI Changes:**
- Show current time (e.g., 2:35)
- Show total duration (e.g., 10:42)
- Format: MM:SS or HH:MM:SS

### 5. Download Button for Registered Users
**Backend Changes:**
- Add download endpoint in VideoController
- Endpoint: GET /api/videos/{video}/download

**Frontend Changes:**
- Show download button only for authenticated users
- Button icon: ⬇️ or 📥

### 6. Subscribe Feature with Real-time Updates
**Database Changes:**
- Create subscriptions table
- Migration: Create user_subscriptions table

**Backend Changes:**
- Create Subscription model
- Create SubscriptionController
- Add routes for subscribe/unsubscribe/check subscription

**Frontend Changes:**
- Subscribe button (Subscribe → Subscribed)
- Real-time count of subscribers
- Show subscriber count on user profile

---

## Files to Create/Modify

### New Files to Create:
1. `database/migrations/XXXX_XX_XX_create_subscriptions_table.php` - Subscription migration
2. `app/Models/Subscription.php` - Subscription model
3. `app/Http/Controllers/Api/SubscriptionController.php` - Subscription controller

### Files to Modify:
1. `public/frontend/video.html` - Main video player page
2. `public/frontend/js/app.js` - Add new functions for download, subscribe, playback
3. `routes/api.php` - Add subscription routes
4. `app/Models/User.php` - Add subscription relationships
5. `app/Http/Resources/VideoResource.php` - Include subscriber count

---

## Implementation Order

### Phase 1: UI Updates (Frontend)
1. Update like button with heart icons in `video.html`
2. Update share button with arrow icon
3. Add WhatsApp share option
4. Add 10s forward/backward buttons
5. Add duration display
6. Add download button (for logged in users)

### Phase 2: Backend Subscriptions
1. Create Subscription model
2. Create migration
3. Create SubscriptionController
4. Add API routes

### Phase 3: Frontend Subscription Logic
1. Add subscribe toggle function
2. Add subscriber count display
3. Update video resource to include subscriber count
4. Test real-time subscription updates

### Phase 4: Download Feature
1. Add download endpoint in VideoController
2. Add download function in app.js
3. Connect download button

---

## Success Criteria

✅ Heart icons for like (♡ → ❤️)  
✅ WhatsApp share option available  
✅ 10 second skip buttons working  
✅ Duration display shows current/total time  
✅ Download button visible for logged-in users  
✅ Subscribe button toggles state  
✅ Subscriber count updates in real-time  

---

Last Updated: 2024
Status: Planning Complete

