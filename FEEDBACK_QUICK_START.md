# SkillSync Feedback System - Quick Installation Guide

## 🎯 Quick Start (3 Easy Steps!)

### Step 1: Create Database Table
1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Select your `skillsync` database
3. Click on the **SQL** tab
4. Copy and paste the following SQL:

```sql
CREATE TABLE IF NOT EXISTS student_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    student_email VARCHAR(100) NOT NULL,
    feedback_type ENUM('concern', 'satisfaction', 'feature_request', 'bug_report', 'ui_improvement', 'general') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    rating INT DEFAULT NULL COMMENT 'Rating from 1-5 for satisfaction feedback',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'reviewed', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    admin_response TEXT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    responded_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES login_credentials(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES login_credentials(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_feedback_type (feedback_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

5. Click **Go** to execute

### Step 2: Test Student Feedback
1. Login as a **student** account
2. Navigate to: `http://localhost/SkillSync/feedback.php`
3. Try submitting different types of feedback:
   - **Concern**: Report an issue
   - **Satisfaction**: Rate your experience (with stars!)
   - **Feature Request**: Suggest improvements
   - **Bug Report**: Report technical problems

### Step 3: Test Admin Panel
1. Login as an **admin** account
2. Navigate to: `http://localhost/SkillSync/admin/view_feedback.php`
3. You should see:
   - ✅ Feedback statistics dashboard
   - ✅ All submitted feedback items
   - ✅ Filter and search options
   - ✅ Ability to respond and update status

## 🎨 What's New in the Feedback System?

### For Students:
✨ **6 Feedback Types**
- 🚨 Concern - Report problems
- 😊 Satisfaction - Share positive feedback with star ratings
- 💡 Feature Request - Suggest new features
- 🐛 Bug Report - Report bugs
- 🎨 UI Improvement - Suggest design changes
- 💬 General - Any other feedback

✨ **Priority Levels**
- Set urgency: Low, Medium, High, or Urgent

✨ **Track Your Feedback**
- See all your previous feedback
- Check status updates
- Read admin responses

### For Admins:
✅ **Comprehensive Dashboard**
- View total feedback count
- See pending items
- Track urgent issues
- Statistics by category

✅ **Advanced Filters**
- Filter by type (concern, satisfaction, etc.)
- Filter by status (pending, reviewed, resolved)
- Filter by priority
- Search by keywords

✅ **Respond to Students**
- Write responses directly
- Update feedback status
- Track response history

## 📋 Quick Test Checklist

- [ ] Database table created successfully
- [ ] Can access student feedback page
- [ ] Can submit feedback as student
- [ ] Feedback appears in student's history
- [ ] Can access admin feedback page
- [ ] Statistics showing correctly
- [ ] Can filter feedback
- [ ] Can respond to feedback as admin
- [ ] Student sees admin response

## 🔧 Troubleshooting

**Problem: "Table doesn't exist" error**
- Solution: Make sure you ran the SQL script in Step 1

**Problem: Feedback not submitting**
- Check browser console for errors (F12)
- Verify you're logged in as a student
- Check database connection

**Problem: Admin page shows no feedback**
- Make sure you submitted feedback as a student first
- Check you're logged in as admin
- Verify database query permissions

**Problem: Can't see feedback statistics on admin dashboard**
- Make sure `admin_dashboard.php` has been updated
- Refresh the page (Ctrl + F5)

## 💡 Usage Tips

### For Students:
1. **Be Specific** - Clear subject lines help admins respond faster
2. **Choose the Right Type** - Use "Concern" for problems, "Satisfaction" for praise
3. **Set Priority Wisely** - Only use "Urgent" for critical issues
4. **Check for Responses** - Admins may ask for more details

### For Admins:
1. **Respond Quickly** - Especially to urgent feedback
2. **Update Status** - Keep students informed of progress
3. **Use Filters** - Focus on pending or urgent items first
4. **Be Clear** - Provide detailed responses
5. **Close Resolved Issues** - Keep the list manageable

## 🎯 Features at a Glance

| Feature | Student | Admin |
|---------|---------|-------|
| Submit Feedback | ✅ | ❌ |
| Rate Satisfaction | ✅ | ❌ |
| View Own Feedback | ✅ | ❌ |
| View All Feedback | ❌ | ✅ |
| Respond to Feedback | ❌ | ✅ |
| Update Status | ❌ | ✅ |
| Filter & Search | ❌ | ✅ |
| Statistics Dashboard | ❌ | ✅ |

## 📞 Need Help?

Check the complete documentation: `FEEDBACK_SYSTEM_DOCUMENTATION.md`

---

**Ready to go!** 🚀 Your feedback system is fully functional and connected to your admin panel.
