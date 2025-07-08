# Church Attendance Plugin

The **Church Attendance Plugin** is a custom WordPress plugin designed to capture and manage weekly and/or monthly attendance reports submitted by individual churches. It supports a multi-tiered role-based system to ensure proper access and delegation across a district-wide network of churches.

---

## 📌 Features

- **Role-Based Access Control**
  - **District Admin**: View reports from all churches and manage church-level users.
  - **Church Admin**: Manage users within their assigned church (Reporters and Viewers).
  - **Church Reporter**: Submit attendance reports for their assigned church.
  - **Church Viewer**: View attendance data for their church only.

- **Flexible Reporting**
  - Capture **weekly** and/or **monthly** attendance numbers.
  - Categories include in-person attendance, online participation, discipleship, and visitation.

- **Multi-Church Support**
  - Assign churches via a custom taxonomy.
  - Church-specific data views for granular control.

- **Front-End and Admin Interface**
  - Submit reports via front-end shortcodes.
  - Admin views for managing reports, roles, and church assignments.

- **District Reporting Dashboard**
  - District-level summary reports viewable via shortcode.
  - Exportable CSV files for reporting and analysis.
  - Sortable columns by name, date, and attendance totals.

---

## 🚀 Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin via the WordPress Admin Dashboard.
3. Configure user roles and assign churches via the custom taxonomy.

---

## 🔧 Usage

Use the provided shortcodes to embed functionality:

- `[district_attendance_report]`  
  Display a sortable, exportable district summary table (visible to District Admins only).

- `[church_attendance_form]`  
  Embed the front-end form for submitting weekly/monthly attendance (visible to Church Reporters).

---

## 🔐 Permissions

| Role            | Capabilities                                 |
|-----------------|----------------------------------------------|
| District Admin  | View all data, manage churches & users       |
| Church Admin    | Assign/report Church Reporters and Viewers   |
| Church Reporter | Submit attendance forms                      |
| Church Viewer   | View reports for their own church only       |

---

## 📁 Project Structure

```
church-attendance-plugin/
├── includes/
├── templates/
├── shortcode-district-report.php
├── shortcode-church-form.php
├── church-attendance-plugin.php
└── readme.md
```

---

## 🧑‍💻 Author

**Bo Kelleher**  
Eastern Tennessee District, Nazarene Discipleship International  
https://etndi.org

---

## 📄 License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 💬 Feedback & Contributions

For feedback, improvements, or to contribute, feel free to open an issue or pull request on [GitHub](https://github.com/bokelleher/car).
