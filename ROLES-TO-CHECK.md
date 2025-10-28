# Roles Created by Deleted Plugins

When you delete these plugins, User Role Editor will still see these roles (WordPress stores them in database):

## From Fearless Roles Manager:
- `fearless_you_member`
- `fearless_you_subscriber`
- `fearless_you_pending`

## From Fearless You Systems:
- `fearless_you_member` (duplicate)
- `fearless_faculty`
- `fearless_ambassador`

---

## After Deleting Plugins:

1. Install **User Role Editor** plugin
2. Go to Users > User Role Editor
3. You'll see all these roles still there
4. Edit capabilities as needed
5. Delete unused roles

---

## What to Check on Site:

Search for these shortcodes (might be on pages):
- `[fys_member_dashboard]`
- `[fys_faculty_dashboard]`
- `[fys_ambassador_dashboard]`

If found, replace with:
- Regular WordPress page
- HTML links
- LearnDash `[ld_profile]` shortcode
