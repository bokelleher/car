# Church Attendance Reports

**Version:** 1.1.5  
**Author:** Bo Kelleher  
**Description:** A comprehensive WordPress plugin to manage weekly and monthly church attendance reports with a structured user role system and multi-tier administrative interface.

---

## 📦 Features

### ✅ Role-Based Access Control
- **District Admin**: Full access to all churches and reports across the district
- **Church Admin**: Can manage users and submit/edit reports for their assigned church
- **Church Reporter**: Can enter attendance data for their assigned church
- **Church Viewer**: Read-only access to view reports for their assigned church

### ✅ Admin Dashboard
- **Attendance Reports**: View, sort, and filter all submitted attendance reports
- **Church Management**: Add, edit, or delete churches with comprehensive metadata
- **Settings**: Configurable form instructions and report locking options
- **User Management**: Assign users to specific churches with role-based permissions

### ✅ Front-End Shortcodes
- `[church_attendance_form]` - Submit attendance reports from the front-end
- `[church_dashboard_reports]` - View and edit reports with inline editing for church admins
- `[district_attendance_summary]` - District-wide report view with CSV export
- `[church_directory]` - Display church directory in grid format
- `[church_finder_map]` - Interactive map of all churches with geocoding

### ✅ Data Tracking
- Track weekly attendance metrics:
  - In-Person Attendance
  - Online Attendance
  - Discipleship Attendance
  - Accountability Care List (ACL)
- Automatic submission metadata (who submitted, when submitted)
- Edit tracking with timestamp and user logging
- Duplicate detection to prevent multiple reports for same week/church

### ✅ Advanced Features
- **Inline Editing**: Church admins can edit attendance numbers directly in the dashboard
- **CSV Export**: Export district reports for analysis
- **Sorting & Filtering**: Sort by any column, filter by date range
- **Responsive Design**: Mobile-friendly tables and forms
- **Chart.js Integration**: Visual graphs of attendance trends
- **Modern Events Calendar Integration**: Sync church events
- **Gravity Forms Integration**: Advanced form handling for events

---

## 🔧 Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via WordPress admin
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Attendance Reports** in the admin sidebar
4. Configure settings under **Settings → Church Attendance Settings**
5. Create churches via the **Churches** taxonomy
6. Assign users to churches and roles via the user profile pages

---

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher

### Optional Integrations
- Modern Events Calendar (MEC) plugin for event management
- Gravity Forms for advanced form handling

---

## 🎯 Usage

### For Church Reporters
1. Log in to your WordPress account
2. Navigate to the attendance form page (using `[church_attendance_form]` shortcode)
3. Fill in attendance numbers for the week
4. Submit the report

### For Church Admins
1. Access the church dashboard (using `[church_dashboard_reports]` shortcode)
2. View all submitted reports for your church
3. Click on any attendance number to edit inline
4. Save or cancel changes
5. Export data as needed

### For District Admins
1. Access the district dashboard (using `[district_attendance_summary]` shortcode)
2. View reports from all churches
3. Filter by date range or church
4. Export comprehensive reports to CSV
5. Manage users and church assignments via WordPress admin

---

## 🛠️ Development

### File Structure
```
church-attendance-reports/
│
├── assets/
│   ├── css/
│   │   ├── church-dashboard.css
│   │   ├── church-directory.css
│   │   ├── church-finder.css
│   │   └── single-church.css
│   └── js/
│       ├── dashboard-charts.js
│       └── church-finder.js
│
├── includes/
│   ├── access-control.php
│   ├── admin-columns.php
│   ├── admin-settings.php
│   ├── admin-user-church-list.php
│   ├── ajax-handlers.php
│   ├── capabilities.php
│   ├── post-types.php
│   ├── report-meta.php
│   ├── roles.php
│   ├── shortcode-church-dashboard.php
│   ├── shortcode-district-report.php
│   ├── shortcode-form.php
│   ├── taxonomy-church.php
│   └── ... (additional files)
│
├── data/
│   └── ETND-Churches.csv
│
├── church-attendance-reports.php
├── CHANGELOG.md
└── README.md
```

### Key Components

**Post Type**: `attendance_report`
- Stores individual weekly attendance submissions
- Custom capabilities for role-based access

**Taxonomy**: `church`
- Organizes reports by church
- Stores church metadata (pastor, address, phone, website, etc.)

**User Roles**:
- `district_admin` - Full system access
- `church_admin` - Church-level management
- `church_reporter` - Data entry only
- `church_viewer` - Read-only access

---

## 🐛 Known Issues

None currently reported for version 1.1.5

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

---

## 🤝 Contributing

This is a private plugin developed for church district management. If you find bugs or have feature requests, please contact the author.

---

## 📄 License

Proprietary - All Rights Reserved  
© 2025 Bo Kelleher

---

## 👤 Author

**Bo Kelleher**  
Plugin developed for church attendance tracking and reporting.

---

## 🙏 Acknowledgments

- Chart.js for data visualization
- DataTables for advanced table functionality
- WordPress community for best practices

---

## 📞 Support

For support inquiries, please contact the plugin author directly.
