# Changelog

All notable changes to the Church Attendance Reports plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.6] - 2026-01-28

### Added
- Added a configurable API key field for SMS attendance reminders in **Attendance Reports → Settings**.
- Consolidated all plugin settings under **Attendance Reports → Settings** for easier configuration.
- Removed direct references to third‑party services from code and documentation to improve privacy.

### Changed
- Removed built‑in ClearStream and Google Maps API keys; administrators must now supply their own keys via settings.

### Fixed
- Resolved cross‑browser issues causing the church finder map to fail in Safari and Brave.
- Ensured global map callback is registered properly and removed unsupported `loading=async` parameter.

## [1.3.5] - 2026-01-27

### Added
- Improved cross‑browser compatibility for the Church Finder map by explicitly assigning the global JavaScript callback.

### Changed
- Removed `loading=async` parameter from the Google Maps script for wider browser support.

### Fixed
- Addressed an issue where the Church Finder map would not load in Safari or Brave browsers.

## [1.3.4] - 2026-01-27

### Added
- Added new SMS API key field to the plugin settings to facilitate flexible attendance reminders.

### Changed
- Updated the geocode page link to point to the new **Attendance Reports → Settings** page.
- Removed duplicate code handling Google Maps API keys to prevent fatal errors.

### Fixed
- Fixed a fatal error caused by duplicate function declarations in map and API key handling.
- Resolved issues with the Google Maps script not loading by removing duplicate loading and the `loading=async` parameter.

## [1.3.3] - 2026-01-27

### Added
- Introduced a dedicated **Settings** sub‑menu under **Attendance Reports** for all plugin configurations.
- Added Google Maps API key configuration field to the settings page.

### Changed
- Migrated existing settings from **General → Church Attendance** to **Attendance Reports → Settings**.
- Removed legacy ClearStream and Google Maps configuration code.

### Fixed
- Corrected duplicate function declarations for ClearStream and Google Maps API settings.

## [1.3.2] - 2026-01-26

### Added
- Added support for manually managing the **My Account** link in WordPress menus.

### Changed
- Updated My Account page button styling to match the site design.
- Removed automatic menu injection of the **My Account** link.

## [1.3.1] - 2026-01-26

### Added
- Automatically creates the `/my-account/` page upon plugin activation.
- Added polished, card‑style CSS for the **My Account** page.

### Changed
- Improved styling of the account management page for consistency with dashboard elements.

## [1.3.0] - 2026-01-26

### Added
- Added `[car_my_account]` shortcode to render a user account management page allowing users to update email and mobile phone numbers and view church affiliation.
- Introduced role‑aware menu injection to display the **My Account** link for logged‑in users.

### Changed
- Enhanced attendance form header to display the church name, greeting, and last report date.
- Implemented date restrictions on attendance submission forms to prevent future dates.

## [1.2.0] - 2026-01-26

### Added
- Included direct report link in weekly SMS reminders for quick access.

### Changed
- Adjusted reminder messages and improved ClearStream integration.

## [1.1.9] - 2026-01-25

### Added
- Moved the attendance reminders admin page into the **Attendance Reports** menu.

## [1.1.8] - 2026-01-25

### Added
- Added weekly cron scheduling for attendance reminders.
- Provided admin page to send test SMS and run reminders manually.

### Changed
- Registered a weekly cron schedule to ensure reminders are sent reliably.

## [1.1.7] - 2026-01-25

### Added
- Personalized attendance form header showing the church name and last report date.

### Changed
- Removed the duplicate "Report Attendance" heading on the form.

## [1.1.6] - 2026-01-24

### Added
- Added CSV export functionality with a new "Updated At" column on the church dashboard.
- Implemented weekly SMS reminders using ClearStream with logic to notify reporters and admins based on missing reports.
- Added a missing reports list for district administrators to identify churches with overdue reports.
- Introduced a yellow warning banner on dashboards when no reports have been submitted in 30 days.
- Extended user profiles with a mobile phone field to store numbers for reminders.
- Added ClearStream integration for sending attendance reminders (with administrator-supplied API key).
- Updated attendance form labels and totals, and prevented future dates in submissions.

### Changed
- Improved dashboard UI and report sorting logic.

### Fixed
- Addressed issues with CSV export and sorting.

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