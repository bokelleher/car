<?php
/**
 * Plugin Name: Church Attendance Reports
 * Description: Manages church attendance reports with role-based access control.
 * Version: 1.1.5
 * Author: Bo Kelleher
 */

if (!defined('ABSPATH')) exit;

define('CHURCH_ATTENDANCE_PLUGIN_FILE', __FILE__);
define('CAR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load modular plugin files
require_once plugin_dir_path(__FILE__) . 'includes/roles.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/taxonomy-church.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-user-church-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/report-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-columns.php';
require_once plugin_dir_path(__FILE__) . 'includes/meta-display-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-report-detail.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-district-report.php';
require_once plugin_dir_path(__FILE__) . 'includes/access-control.php';
require_once plugin_dir_path(__FILE__) . 'includes/menu-switch.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-church-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/car-generate-churches.php';
require_once plugin_dir_path(__FILE__) . 'includes/capabilities.php';
require_once plugin_dir_path(__FILE__) . 'includes/church-finder-map.php';
require_once plugin_dir_path(__FILE__) . 'includes/single-church-template.php';
require_once plugin_dir_path(__FILE__) . 'includes/mec-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/church-directory-grid.php';
require_once plugin_dir_path(__FILE__) . 'includes/church-event-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-bar-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-utility-fix-totals.php';

// Import Admin Functions

// Add Church Import Menu
add_action('admin_menu', 'car_add_import_menu');
function car_add_import_menu() {
    add_submenu_page(
        'edit.php?post_type=attendance_report',  // Parent menu (under Attendance Reports)
        'Import Churches',                        // Page title
        'Import Churches',                        // Menu title
        'manage_options',                         // Capability required
        'car-import-churches',                   // Menu slug
        'car_render_import_page'                 // Function to display page
    );
    
    // Add geocoding page (hidden from menu)
    add_submenu_page(
        null,                                     // No parent (hidden)
        'Geocode Churches',
        'Geocode Churches',
        'manage_options',
        'car-geocode-churches',
        'car_render_geocode_page'
    );
}

// Render the import page
function car_render_import_page() {
    ?>
    <div class="wrap">
        <h1>Import Churches from CSV</h1>
        
        <div class="card" style="max-width: 600px; margin: 20px 0;">
            <h2>Step 1: Import Church Data</h2>
            <p>This will import 72 churches from the ETND-Churches.csv file.</p>
            <p>Existing churches will be updated, new ones will be created.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('car_import_action', 'car_import_nonce'); ?>
                <p>
                    <input type="submit" name="run_import" class="button button-primary button-large" value="Import Churches Now">
                </p>
            </form>
            
            <?php
            // Handle import when button is clicked
            if (isset($_POST['run_import']) && check_admin_referer('car_import_action', 'car_import_nonce')) {
                echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-left: 4px solid #00a0d2;">';
                echo '<pre>';
                
                // Include and run the import
                require_once plugin_dir_path(__FILE__) . 'includes/import-churches.php';
                $importer = new CAR_Church_Importer();
                $result = $importer->import_churches();
                
                echo '</pre>';
                echo '</div>';
                
                if ($result) {
                    echo '<div class="notice notice-success"><p><strong>✓ Import Complete!</strong> ';
                    echo "Imported: {$result['imported']}, Updated: {$result['updated']}, Failed: {$result['failed']}</p></div>";
                    
                    echo '<h2>Step 2: Geocode Addresses for Map</h2>';
                    echo '<p>Now geocode the addresses so churches appear on the map:</p>';
                    echo '<p><a href="' . admin_url('edit.php?post_type=attendance_report&page=car-geocode-churches') . '" class="button button-primary">Geocode Churches</a></p>';
                }
            }
            ?>
        </div>
        
        <div class="card" style="max-width: 600px; margin: 20px 0;">
            <h2>Import Information</h2>
            <p><strong>CSV File Location:</strong><br>
            <code>/wp-content/plugins/church-attendance-reports/data/ETND-Churches.csv</code></p>
            
            <p><strong>Data to Import:</strong></p>
            <ul>
                <li>Church Names</li>
                <li>Pastor Names</li>
                <li>Church Addresses (will be geocoded)</li>
                <li>Phone Numbers</li>
                <li>Email Addresses</li>
                <li>Websites</li>
            </ul>
            
            <p><strong>Total Churches:</strong> 72</p>
        </div>
    </div>
    <?php
}

// Render the geocoding page
function car_render_geocode_page() {
    ?>
    <div class="wrap">
        <h1>Geocode Church Addresses</h1>
        
        <?php
        $google_api_key = get_option('car_google_maps_api_key', '');
        
        if (empty($google_api_key)) {
            ?>
            <div class="notice notice-error">
                <p><strong>Google Maps API Key Required!</strong></p>
                <p>Please add your API key in <a href="<?php echo admin_url('options-general.php?page=car_attendance_settings'); ?>">Settings → Church Attendance Settings</a></p>
            </div>
            <?php
            return;
        }
        ?>
        
        <div class="card" style="max-width: 600px; margin: 20px 0;">
            <h2>Geocode All Churches</h2>
            <p>This will add latitude/longitude coordinates to all churches so they appear on the map.</p>
            <p>Churches that already have coordinates will be skipped.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('car_geocode_action', 'car_geocode_nonce'); ?>
                <p>
                    <input type="submit" name="run_geocode" class="button button-primary button-large" value="Start Geocoding">
                </p>
            </form>
            
            <?php
            if (isset($_POST['run_geocode']) && check_admin_referer('car_geocode_action', 'car_geocode_nonce')) {
                echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-left: 4px solid #00a0d2; max-height: 400px; overflow-y: auto;">';
                echo '<pre>';
                
                // Run geocoding
                require_once plugin_dir_path(__FILE__) . 'includes/geocode-churches.php';
                
                echo '</pre>';
                echo '</div>';
                
                echo '<div class="notice notice-success"><p><strong>✓ Geocoding Complete!</strong></p></div>';
                echo '<p><a href="' . home_url() . '" class="button button-primary" target="_blank">View Church Map</a></p>';
            }
            ?>
        </div>
    </div>
    <?php
}

function car_enqueue_assets() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('car-dashboard-charts', plugin_dir_url(__FILE__) . 'assets/js/dashboard-charts.js', ['chartjs'], null, true);
}
add_action('wp_enqueue_scripts', 'car_enqueue_assets');

function etndi_hide_admin_bar_for_non_admins() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $allowed_roles = ['administrator', 'district_admin'];

        // Only show admin bar for allowed roles
        if (!array_intersect($allowed_roles, (array) $user->roles)) {
            show_admin_bar(false);
        }
    }
}