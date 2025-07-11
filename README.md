# Church Attendance Reports

**Version:** 1.0.23  
**Author:** Bo Kelleher  
**Description:** A custom WordPress plugin to manage weekly and monthly church attendance reports with a structured user role system and a multi-tier administrative interface.

---

## 📦 Features

### ✅ Role-Based Access
- **District Admin**: Full access to all churches and reports.
- **Church Admin**: Can manage users and submit reports for their church.
- **Church Reporter**: Can enter attendance data for their church.
- **Church Viewer**: Can only view reports for their assigned church.

### ✅ Admin Pages
- **Attendance Reports**: View all submitted attendance reports.
- **Church Management**: Add, edit, or delete churches with metadata (pastor, city, website).
- **Settings**: Plugin configuration options.

### ✅ Front-End Shortcodes
- `[report_attendance_form]`: Report attendance from the front-end.
- `[church_dashboard]`: View reports for a specific church with inline editing for admins.
- `[district_attendance_report]`: Front-end report view for District Admins.

### ✅ Custom Database Table
- All church data is stored in the `car_churches` custom table (`$wpdb->prefix . 'car_churches'`).

---

## 🔧 Installation

1. Upload the plugin zip via WordPress admin or FTP.
2. Activate the plugin.
3. Use the new “Attendance Reports” menu in the admin sidebar.
4. Navigate to **Settings** for plugin configuration.

---

## 🚧 In Development

- Improved database wiring for front-end views.
- Export tools (CSV download per report list).
- Graphs and analytics for church trends.

---

## 🧪 Debugging

The plugin writes debug logs:
```php
error_log('✅ shortcode-church-dashboard.php was loaded');
```

---

## 📁 File Structure

```
church-attendance-reports/
│
├── includes/
│   ├── roles.php
│   ├── post-types.php
│   ├── taxonomy-church.php
│   ├── admin-reports.php
│   └── ...
├── assets/
│   ├── js/
│   └── css/
├── church-attendance-reports.php
└── README.md
```

---

## 🙋 Support

If you find bugs or want to suggest improvements, please [open an issue](https://github.com/bokelleher/car/issues) or fork and submit a pull request.
