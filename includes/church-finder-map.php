<?php
/**
 * Church Finder with Google Maps Integration
 * Add this file to church-attendance-reports/includes/
 */

// includes/church-finder-map.php

class CAR_Church_Finder {
    
    private $google_maps_api_key;
    
    public function __construct() {
        $this->google_maps_api_key = get_option('car_google_maps_api_key', '');
        add_shortcode('church_finder_map', [$this, 'render_church_finder']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_map_assets']);
        add_action('wp_ajax_car_get_churches_json', [$this, 'ajax_get_churches']);
        add_action('wp_ajax_nopriv_car_get_churches_json', [$this, 'ajax_get_churches']);
    }
    
    /**
     * Enqueue Google Maps and custom scripts
     */
    public function enqueue_map_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'church_finder_map')) {
            // Load church-finder.js FIRST so initChurchMap is available
            wp_enqueue_script(
                'car-church-finder',
                plugin_dir_url(__FILE__) . '../assets/js/church-finder.js',
                ['jquery'],
                '1.0.2',
                false  // Load in header, not footer
            );
            
            // Localize script must come after enqueue but before Google Maps
            wp_localize_script('car-church-finder', 'car_finder', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('car_finder_nonce')
            ]);
            
            // Register (not enqueue) Google Maps - we'll load it manually with proper async
            wp_register_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . $this->google_maps_api_key . '&callback=initChurchMap&loading=async',
                ['car-church-finder'],
                null,
                false
            );
            wp_enqueue_script('google-maps');
            
            // Add async and defer attributes to Google Maps script
            add_filter('script_loader_tag', function($tag, $handle) {
                if ('google-maps' === $handle) {
                    return str_replace(' src', ' async defer src', $tag);
                }
                return $tag;
            }, 10, 2);
            
            wp_enqueue_style(
                'car-church-finder',
                plugin_dir_url(__FILE__) . '../assets/css/church-finder.css',
                [],
                '1.0.0'
            );
        }
    }
    
    /**
     * Render the church finder shortcode
     */
    public function render_church_finder($atts) {
        $atts = shortcode_atts([
            'height' => '500px',
            'zoom' => 10,
            'center_lat' => '35.5175',  // Tennessee center
            'center_lng' => '-86.5804',
            'show_list' => 'true',
            'show_filters' => 'true'
        ], $atts);
        
        ob_start();
        ?>
        <div class="car-church-finder-container">
            <?php if ($atts['show_filters'] === 'true'): ?>
            <div class="car-finder-controls">
                <div class="car-search-box">
                    <input type="text" 
                           id="car-address-search" 
                           placeholder="Enter your address or zip code">
                    <button id="car-find-nearby">Find Churches Near Me</button>
                </div>
                
                <div class="car-filters">
                    <label style="display: inline-block; margin-right: 10px;">Search Radius:</label>
                    <input type="number" 
                        id="car-radius-filter" 
                        placeholder="Miles" 
                        min="5" 
                        max="100" 
                        value="25"
                        style="width: 80px;">
                    <span style="margin-left: 5px;">miles</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="car-map-container">
                <div id="car-church-map" 
                     style="height: <?php echo esc_attr($atts['height']); ?>"
                     data-zoom="<?php echo esc_attr($atts['zoom']); ?>"
                     data-center-lat="<?php echo esc_attr($atts['center_lat']); ?>"
                     data-center-lng="<?php echo esc_attr($atts['center_lng']); ?>">
                </div>
                
                <?php if ($atts['show_list'] === 'true'): ?>
                <div class="car-church-list">
                    <h3>Churches Near You</h3>
                    <div id="car-results-list">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX endpoint to get churches as JSON
     */
    public function ajax_get_churches() {
        check_ajax_referer('car_finder_nonce', 'nonce');
        
        $churches = get_terms([
            'taxonomy' => 'church',
            'hide_empty' => false,
        ]);
        
        $church_data = [];
        
        foreach ($churches as $church) {
            $lat = get_term_meta($church->term_id, 'latitude', true);
            $lng = get_term_meta($church->term_id, 'longitude', true);
            
            // Skip churches without coordinates
            if (!$lat || !$lng) {
                continue;
            }
            
            $church_data[] = [
                'id' => $church->term_id,
                'name' => $church->name,
                'slug' => $church->slug,
                'address' => get_term_meta($church->term_id, 'address', true),
                'phone' => get_term_meta($church->term_id, 'phone', true),
                'website' => get_term_meta($church->term_id, 'website', true),
                'pastor_name' => get_term_meta($church->term_id, 'pastor', true),
                'pastor_email' => get_term_meta($church->term_id, 'pastor_email', true),
                'service_times' => get_term_meta($church->term_id, 'service_times', true),
                'latitude' => floatval($lat),
                'longitude' => floatval($lng),
                'district' => get_term_meta($church->term_id, 'district_region', true),
                'url' => get_term_link($church->term_id),
            ];
        }
        
        wp_send_json_success($church_data);
    }
    
    /**
     * Geocode an address using Google Maps API
     */
    public function geocode_address($address) {
        if (empty($this->google_maps_api_key)) {
            return false;
        }
        
        $cache_key = 'car_geocode_' . md5($address);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';
        $url .= '?address=' . urlencode($address);
        $url .= '&key=' . $this->google_maps_api_key;
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'OK' && isset($data['results'][0])) {
            $location = $data['results'][0]['geometry']['location'];
            $result = [
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'formatted_address' => $data['results'][0]['formatted_address'],
            ];
            
            // Cache for 30 days
            set_transient($cache_key, $result, 30 * DAY_IN_SECONDS);
            
            return $result;
        }
        
        return false;
    }
}

// Initialize the church finder
new CAR_Church_Finder();

/**
 * Add geocoding to church save
 */
add_action('edited_church', 'car_geocode_church_on_save', 10, 1);
add_action('created_church', 'car_geocode_church_on_save', 10, 1);

function car_geocode_church_on_save($term_id) {
    $address = get_term_meta($term_id, 'address', true);
    
    if (empty($address)) {
        return;
    }
    
    $finder = new CAR_Church_Finder();
    $coords = $finder->geocode_address($address);
    
    if ($coords) {
        update_term_meta($term_id, 'latitude', $coords['latitude']);
        update_term_meta($term_id, 'longitude', $coords['longitude']);
        update_term_meta($term_id, 'formatted_address', $coords['formatted_address']);
    }
}

/**
 * Add settings for Google Maps API
 */
add_action('car_settings_fields', 'car_add_maps_settings');
function car_add_maps_settings() {
    register_setting('car_settings_group', 'car_google_maps_api_key');
    
    add_settings_field(
        'car_google_maps_api_key',
        'Google Maps API Key',
        'car_google_maps_api_key_callback',
        'car_attendance_settings',
        'car_main_section'
    );
}

function car_google_maps_api_key_callback() {
    $value = esc_attr(get_option('car_google_maps_api_key', ''));
    echo '<input type="text" name="car_google_maps_api_key" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">Required for map functionality and geocoding. <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Get an API key</a></p>';
}