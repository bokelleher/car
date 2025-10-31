# Changelog

All notable changes to the Church Attendance Reports plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.5] - 2025-10-31

### Fixed
- Fixed table overflow issues in church admin dashboard
- Corrected Save/Cancel button sizing to prevent text cutoff
- Standardized font sizes across all table columns (removed 12px override on "Submitted At" column)
- Rebalanced column widths for better text display:
  - Increased In-Person, Online, ACL, Total columns from 5% to 7%
  - Increased Discipleship column from 8% to 9%
  - Reduced Submitted At column from 15% to 13%
  - Maintained Edited column at 13% for button display
- Improved button padding and spacing for better fit
- Enhanced input field font consistency to match table text

### Changed
- Updated CSS for church-dashboard.css with improved responsive design
- Better column width distribution across the dashboard table
- Improved button styling with proper min-width constraints
- Enhanced padding on attendance number columns from 8px 2px to 8px 4px
- **Changed license from Proprietary to MIT License** - now open source!

## [1.1.4] - 2025-10-30

### Added
- Initial table overflow protection
- Responsive table wrapper with scroll handling

## [1.1.3] - 2025-10-29

### Added
- Church admin dashboard with inline editing
- Real-time attendance data updates
- CSV export functionality

## [1.1.0] - 2025-09-20

### Added
- Church directory grid view
- Church finder map integration
- Gravity Forms integration for event submissions
- Modern Events Calendar (MEC) integration
- Church event form shortcode
- Geocoding for church addresses

### Changed
- Improved admin bar management
- Enhanced role-based access control
- Updated settings page organization

## [1.0.23] - 2025-07-29

### Added
- Initial release
- Custom post type for attendance reports
- Church taxonomy with metadata
- Role-based user system (District Admin, Church Admin, Church Reporter, Church Viewer)
- Front-end shortcodes for form submission and dashboard viewing
- Admin columns for attendance data
- Report metadata tracking (submitted by, submitted at)
- Duplicate detection for reports

### Features
- `[church_attendance_form]` - Shortcode for attendance submission
- `[church_dashboard_reports]` - Shortcode for church-specific dashboard
- `[district_attendance_summary]` - Shortcode for district-wide reporting
- Settings page for form instructions and report locking
- Dynamic menu switching based on user roles
- Login redirect based on user role
