<?php
/**
 * Church Data Import Script
 * Add this file to: church-attendance-reports/includes/import-churches.php
 * 
 * Usage: 
 * 1. Via WP-CLI: wp eval-file wp-content/plugins/church-attendance-reports/includes/import-churches.php
 * 2. Via Admin: Add an import button to your settings page
 */

class CAR_Church_Importer {
    
    private $csv_file;
    private $google_api_key;
    private $imported = 0;
    private $updated = 0;
    private $failed = 0;
    private $geocoding_delay = 0.5; // Delay between geocoding requests (seconds)
    
    public function __construct($csv_file = null) {
        $this->csv_file = $csv_file ?: plugin_dir_path(__FILE__) . '../data/ETND-Churches.csv';
        $this->google_api_key = get_option('car_google_maps_api_key', '');
    }
    
    /**
     * Main import function
     */
    public function import_churches() {
        if (!file_exists($this->csv_file)) {
            $this->log("CSV file not found: {$this->csv_file}");
            return false;
        }
        
        $this->log("Starting church import from CSV...");
        
        // Read CSV
        $handle = fopen($this->csv_file, 'r');
        if (!$handle) {
            $this->log("Failed to open CSV file");
            return false;
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        $this->log("CSV Headers: " . implode(', ', $header));
        
        // Process each row
        while (($data = fgetcsv($handle)) !== FALSE) {
            $this->process_church_row($data);
        }
        
        fclose($handle);
        
        $this->log("Import complete! Imported: {$this->imported}, Updated: {$this->updated}, Failed: {$this->failed}");
        
        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'failed' => $this->failed
        ];
    }
    
    /**
     * Process a single church row from CSV
     */
    private function process_church_row($data) {
        // Map CSV columns to data fields
        $church_data = [
            'name' => trim($data[0]),
            'pastor' => trim($data[1]),
            'church_address' => trim($data[2]),
            'mailing_address' => trim($data[3]),
            'church_phone' => trim($data[4]),
            'pastor_phone' => trim($data[5]),
            'alternate_phone' => trim($data[6]),
            'email' => trim($data[7]),
            'website' => trim($data[8])
        ];
        
        // Skip if no church name
        if (empty($church_data['name'])) {
            return;
        }
        
        $this->log("Processing: {$church_data['name']}");
        
        // Check if church already exists
        $existing_term = get_term_by('name', $church_data['name'], 'church');
        
        if ($existing_term) {
            // Update existing church
            $term_id = $existing_term->term_id;
            $this->log("  - Updating existing church (ID: {$term_id})");
            $this->updated++;
        } else {
            // Create new church
            $result = wp_insert_term($church_data['name'], 'church', [
                'description' => "Church in the East Tennessee District"
            ]);
            
            if (is_wp_error($result)) {
                $this->log("  - ERROR: Failed to create church: " . $result->get_error_message());
                $this->failed++;
                return;
            }
            
            $term_id = $result['term_id'];
            $this->log("  - Created new church (ID: {$term_id})");
            $this->imported++;
        }
        
        // Update church metadata
        $this->update_church_meta($term_id, $church_data);
        
        // Geocode the address
        if (!empty($church_data['church_address'])) {
            $this->geocode_church($term_id, $church_data['church_address']);
        }
    }
    
    /**
     * Update church metadata
     */
    private function update_church_meta($term_id, $data) {
        // Primary contact info
        if (!empty($data['pastor'])) {
            update_term_meta($term_id, 'pastor', $data['pastor']);
            update_term_meta($term_id, 'pastor_name', $data['pastor']); // Duplicate for compatibility
        }
        
        // Addresses
        if (!empty($data['church_address'])) {
            update_term_meta($term_id, 'address', $data['church_address']);
            
            // Parse city and state from address
            if (preg_match('/,\s*([^,]+),\s*TN\s+(\d{5})/', $data['church_address'], $matches)) {
                update_term_meta($term_id, 'city', $matches[1]);
                update_term_meta($term_id, 'state', 'TN');
                update_term_meta($term_id, 'zip', $matches[2]);
            }
        }
        
        if (!empty($data['mailing_address'])) {
            update_term_meta($term_id, 'mailing_address', $data['mailing_address']);
        }
        
        // Phone numbers
        if (!empty($data['church_phone'])) {
            update_term_meta($term_id, 'phone', $data['church_phone']);
        }
        
        if (!empty($data['pastor_phone'])) {
            update_term_meta($term_id, 'pastor_phone', $data['pastor_phone']);
        }
        
        if (!empty($data['alternate_phone'])) {
            update_term_meta($term_id, 'alternate_phone', $data['alternate_phone']);
        }
        
        // Email and website
        if (!empty($data['email'])) {
            update_term_meta($term_id, 'pastor_email', $data['email']);
        }
        
        if (!empty($data['website'])) {
            $website = $data['website'];
            
            // Add http:// if no protocol specified
            if (!preg_match('/^https?:\/\//', $website) && $website !== 'Facebook') {
                $website = 'http://' . $website;
            }
            
            update_term_meta($term_id, 'website', $website);
        }
        
        // Set default service times (can be customized later)
        $default_service_times = "Sunday Morning: 10:00 AM\nSunday Evening: 6:00 PM\nWednesday: 7:00 PM";
        $existing_times = get_term_meta($term_id, 'service_times', true);
        if (empty($existing_times)) {
            update_term_meta($term_id, 'service_times', $default_service_times);
        }
        
        // Determine district region based on city
        $district = $this->determine_district_region($data['church_address']);
        update_term_meta($term_id, 'district_region', $district);
        
        $this->log("  - Updated metadata");
    }
    
    /**
     * Geocode church address
     */
    private function geocode_church($term_id, $address) {
        // Check if already geocoded
        $existing_lat = get_term_meta($term_id, 'latitude', true);
        if (!empty($existing_lat)) {
            $this->log("  - Already geocoded");
            return;
        }
        
        if (empty($this->google_api_key)) {
            $this->log("  - Skipping geocoding (no API key)");
            return;
        }
        
        // Add delay to avoid rate limiting
        if ($this->geocoding_delay > 0) {
            usleep($this->geocoding_delay * 1000000);
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';
        $url .= '?address=' . urlencode($address);
        $url .= '&key=' . $this->google_api_key;
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            $this->log("  - Geocoding failed: " . $response->get_error_message());
            return;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'OK' && isset($data['results'][0])) {
            $location = $data['results'][0]['geometry']['location'];
            update_term_meta($term_id, 'latitude', $location['lat']);
            update_term_meta($term_id, 'longitude', $location['lng']);
            update_term_meta($term_id, 'formatted_address', $data['results'][0]['formatted_address']);
            
            $this->log("  - Geocoded: {$location['lat']}, {$location['lng']}");
        } else {
            $this->log("  - Geocoding failed: " . $data['status']);
        }
    }
    
    /**
     * Determine district region based on location
     */
    private function determine_district_region($address) {
        // Simple region determination based on city
        // This can be customized based on actual district boundaries
        
        $north_cities = ['Knoxville', 'Maryville', 'Oak Ridge', 'Lenoir City', 'Loudon'];
        $south_cities = ['Chattanooga', 'Cleveland', 'Athens', 'Decherd', 'Winchester'];
        $east_cities = ['Kingsport', 'Greenville', 'Newport', 'Elizabethton'];
        $central_cities = ['Murfreesboro', 'Lebanon', 'Nashville', 'Shelbyville', 'Tullahoma'];
        
        foreach ($north_cities as $city) {
            if (stripos($address, $city) !== false) return 'north';
        }
        foreach ($south_cities as $city) {
            if (stripos($address, $city) !== false) return 'south';
        }
        foreach ($east_cities as $city) {
            if (stripos($address, $city) !== false) return 'east';
        }
        foreach ($central_cities as $city) {
            if (stripos($address, $city) !== false) return 'central';
        }
        
        return 'unassigned';
    }
    
    /**
     * Log message
     */
    private function log($message) {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
        } else {
            error_log('[Church Import] ' . $message);
            echo $message . "<br>\n";
        }
    }
}

// WP-CLI Command
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('car import-churches', function($args, $assoc_args) {
        $csv_file = isset($assoc_args['file']) ? $assoc_args['file'] : null;
        $importer = new CAR_Church_Importer($csv_file);
        $result = $importer->import_churches();
        
        if ($result) {
            WP_CLI::success("Import complete! Imported: {$result['imported']}, Updated: {$result['updated']}, Failed: {$result['failed']}");
        } else {
            WP_CLI::error("Import failed!");
        }
    });
}

// Admin AJAX handler
add_action('wp_ajax_car_import_churches', 'car_handle_import_churches');
function car_handle_import_churches() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_ajax_referer('car_import_nonce', 'nonce');
    
    $importer = new CAR_Church_Importer();
    $result = $importer->import_churches();
    
    if ($result) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error('Import failed');
    }
}

// Add import button to settings page
add_action('car_settings_after_form', 'car_add_import_button');
function car_add_import_button() {
    ?>
    <hr>
    <h2>Import Churches from CSV</h2>
    <p>Import or update churches from the ETND-Churches.csv file.</p>
    <button type="button" id="car-import-churches" class="button button-primary">Import Churches</button>
    <div id="car-import-status"></div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#car-import-churches').on('click', function() {
            var button = $(this);
            var status = $('#car-import-status');
            
            button.prop('disabled', true);
            status.html('<p>Importing churches... This may take a few minutes.</p>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'car_import_churches',
                    nonce: '<?php echo wp_create_nonce('car_import_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        status.html('<p style="color: green;">✓ Import complete! Imported: ' + 
                            response.data.imported + ', Updated: ' + response.data.updated + 
                            ', Failed: ' + response.data.failed + '</p>');
                    } else {
                        status.html('<p style="color: red;">✗ Import failed: ' + response.data + '</p>');
                    }
                    button.prop('disabled', false);
                },
                error: function() {
                    status.html('<p style="color: red;">✗ Import failed: Server error</p>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

// If running directly (for testing)
if (defined('DOING_AJAX') && !DOING_AJAX && !defined('WP_CLI')) {
    // Check if we're in WordPress environment
    if (function_exists('wp_insert_term')) {
        $importer = new CAR_Church_Importer();
        $importer->import_churches();
    }
}