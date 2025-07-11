## [1.0.24] - 2025-07-11
### Added
- Nonce field and verification added to church taxonomy meta forms for CSRF protection.
- Capability checks (`manage_terms`) added before saving taxonomy meta.
- Improved handling of shortcode-based forms with better permission validation.
- Restored missing admin menu and settings page visibility in WP Admin.

### Fixed
- Chart.js canvas ID mismatch corrected in frontend dashboard.
- Corrected plugin file structure to ensure proper activation and version recognition.
- Eliminated duplicate plugin header declarations and orphaned plugin artifacts.

### Changed
- Reorganized plugin initialization order for more reliable menu/page registration.
- Updated plugin version in main file to reflect new release.
