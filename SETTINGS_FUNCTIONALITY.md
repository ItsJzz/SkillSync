# Settings Page Functionality

## Overview
The Settings page allows users to manage their account settings including profile information, password, theme preference, and notification settings.

## Files Created/Modified

### 1. **settings.php** (Modified)
The main settings page with the following features:
- ✅ Load current user data from database
- ✅ Display success/error messages
- ✅ Form pre-filled with user's current information
- ✅ Modern green/yellow themed UI
- ✅ Client-side validation
- ✅ Auto-hide alerts after 5 seconds

### 2. **update_settings.php** (New)
Backend script that handles form submission:
- ✅ Session validation
- ✅ Input validation (name, email, password)
- ✅ Email uniqueness check
- ✅ Password hashing (if changed)
- ✅ Database update
- ✅ Success/error message handling
- ✅ Redirect back to settings page

### 3. **add_settings_columns.sql** (New)
SQL script to add required columns to the database:
- `theme_preference` VARCHAR(10) - Stores 'light' or 'dark'
- `email_notifications` TINYINT(1) - Stores 0 or 1

## Database Setup

Run the following SQL to add the required columns:

```sql
-- Run this in phpMyAdmin or MySQL command line
source add_settings_columns.sql;
```

Or manually:

```sql
ALTER TABLE login_credentials 
ADD COLUMN IF NOT EXISTS theme_preference VARCHAR(10) DEFAULT 'light',
ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) DEFAULT 1;

UPDATE login_credentials 
SET theme_preference = 'light' 
WHERE theme_preference IS NULL;

UPDATE login_credentials 
SET email_notifications = 1 
WHERE email_notifications IS NULL;
```

## Features

### 1. **User Profile Management**
- Update full name
- Update email address
- Email uniqueness validation

### 2. **Password Management**
- Change password (optional)
- Password hashing for security
- Minimum 6 characters validation
- Confirmation prompt before changing

### 3. **Theme Preference**
- Light Mode ☀️
- Dark Mode 🌙
- Saved to database for future implementation

### 4. **Email Notifications**
- Toggle checkbox to enable/disable
- Receive notifications for new quizzes and activities

### 5. **User Experience**
- Form pre-filled with current data
- Success message when settings saved
- Error messages for validation issues
- Auto-hide alerts after 5 seconds
- Smooth animations and transitions
- Responsive design

## Form Validation

### Client-Side (JavaScript)
- Password minimum length (6 characters)
- Confirmation prompt when changing password

### Server-Side (PHP)
- Required field validation (name, email)
- Email format validation
- Email uniqueness check
- Password hashing
- SQL injection prevention (prepared statements)

## Security Features

1. **Session Management**
   - User must be logged in to access settings
   - Session validation on every request

2. **Password Security**
   - Passwords hashed using `password_hash()`
   - Original password never stored in plain text

3. **SQL Injection Prevention**
   - Prepared statements used throughout
   - Parameter binding for all queries

4. **XSS Prevention**
   - `htmlspecialchars()` used for all output
   - Sanitized user inputs

5. **Email Validation**
   - Format validation
   - Uniqueness check to prevent conflicts

## Usage Flow

1. User clicks "Settings" in sidebar
2. `settings.php` loads current user data
3. Form displays with pre-filled information
4. User makes changes and clicks "Save Changes"
5. `update_settings.php` validates and saves changes
6. User redirected back with success/error message

## Testing Checklist

- [ ] Load settings page and verify data is pre-filled
- [ ] Update name and save - verify success message
- [ ] Update email and save - verify success message
- [ ] Try duplicate email - verify error message
- [ ] Change password - verify confirmation prompt
- [ ] Leave password blank - verify it doesn't change
- [ ] Change theme preference - verify it saves
- [ ] Toggle notifications - verify it saves
- [ ] Check sidebar shows updated username
- [ ] Verify alerts auto-hide after 5 seconds

## Error Messages

- "Full name and email are required!" - Missing required fields
- "Invalid email format!" - Email doesn't match valid format
- "Email address is already in use by another account!" - Duplicate email
- "Failed to update settings. Please try again." - Database error

## Success Message

- "Settings updated successfully!" - All changes saved

## Next Steps (Optional Enhancements)

1. **Profile Picture Upload**
   - Add file upload functionality
   - Store image path in database
   - Display in sidebar

2. **Theme Implementation**
   - Create dark mode CSS
   - Load theme based on user preference
   - Toggle theme dynamically

3. **Email Verification**
   - Send verification email when email changes
   - Require confirmation before updating

4. **Password Strength Meter**
   - Visual indicator of password strength
   - Requirements display (uppercase, numbers, etc.)

5. **Two-Factor Authentication**
   - Add 2FA option for extra security
   - Phone/email verification

## Troubleshooting

### Settings not saving?
- Check database columns exist (run add_settings_columns.sql)
- Verify db_connect.php is working
- Check PHP error logs

### Email validation failing?
- Ensure email format is valid
- Check if email already exists in database

### Password not changing?
- Verify password is at least 6 characters
- Check if you're clicking through the confirmation prompt

### Alerts not showing?
- Check session messages are being set
- Verify JavaScript is not blocked
- Check browser console for errors

## File Structure

```
SkillSync/
├── settings.php              # Settings page (modified)
├── update_settings.php       # Form handler (new)
├── add_settings_columns.sql  # Database update (new)
├── SETTINGS_FUNCTIONALITY.md # This file (new)
└── db_connect.php           # Database connection (existing)
```

## Support

For issues or questions:
1. Check database connection
2. Verify all columns exist in login_credentials table
3. Check PHP error logs
4. Verify session is working properly
