# Database Analysis: Role Usage

## ✅ ROLES IN USE (18 Total Users - KEEP THESE)

### fearless_you_member
- **Users**: 9 users (IDs: 8, 12, 19, 39, 56, 59, 68, 78, 79)
- **Capabilities**: 6 capabilities
  - read
  - access_fearless_you_content
  - view_membership_dashboard
  - participate_in_community
  - access_monthly_trainings
  - download_resources

### fearless_faculty
- **Users**: 4 users (IDs: 3831, 5372, 9932, 13153)
- **Capabilities**: 7 capabilities
  - read
  - access_fearless_you_content
  - teach_courses
  - create_content
  - moderate_discussions
  - view_faculty_dashboard
  - access_faculty_resources

### fearless_ambassador
- **Users**: 5 users (IDs: 3632, 4131, 9142, 16706, 165129)
- **Capabilities**: 7 capabilities
  - read
  - access_fearless_you_content
  - promote_fearless_living
  - access_ambassador_resources
  - view_ambassador_dashboard
  - participate_in_community
  - refer_members

---

## ❌ UNUSED ROLES (0 Users - CAN DELETE)

### fearless_you_subscriber
- **Users**: 0
- **Status**: Registered but never assigned to anyone
- **Action**: Delete via User Role Editor after plugin removal

### fearless_you_pending
- **Users**: 0
- **Status**: Registered but never assigned to anyone
- **Action**: Delete via User Role Editor after plugin removal

---

## What User Role Editor Will Show

After deleting the plugins, all 5 roles remain in the `wp_user_roles` option. User Role Editor will:
- ✅ Auto-detect all 5 roles
- ✅ Show all capabilities for each role
- ✅ Allow you to edit/delete/manage them
- ✅ No data loss - 18 users keep their assigned roles
- ✅ You can safely delete the 2 unused roles (fearless_you_subscriber, fearless_you_pending)
