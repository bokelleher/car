<?php
/**
 * Single Church Page Template Handler
 * Creates a beautiful single page for each church
 */

class CAR_Single_Church_Template {
    
    public function __construct() {
        add_filter('template_include', [$this, 'church_template'], 99);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    /**
     * Use our custom template for church taxonomy pages
     */
    public function church_template($template) {
        if (is_tax('church')) {
            // Check if theme has a custom template
            $theme_template = locate_template(['taxonomy-church.php']);
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Use our plugin template
            return $this->render_church_page();
        }
        return $template;
    }
    
    /**
     * Enqueue styles for single church pages
     */
    public function enqueue_styles() {
        if (is_tax('church')) {
            wp_enqueue_style(
                'car-single-church',
                plugin_dir_url(__FILE__) . '../assets/css/single-church.css',
                [],
                '1.0.0'
            );
        }
    }
    
    /**
     * Render the church page
     */
    public function render_church_page() {
        $term = get_queried_object();
        
        if (!$term || !isset($term->term_id)) {
            return get_template_directory() . '/404.php';
        }
        
        // Get all church metadata
        $pastor_name = get_term_meta($term->term_id, 'pastor', true) ?: get_term_meta($term->term_id, 'pastor_name', true);
        $pastor_email = get_term_meta($term->term_id, 'pastor_email', true);
        $address = get_term_meta($term->term_id, 'address', true);
        $phone = get_term_meta($term->term_id, 'phone', true);
        $website = get_term_meta($term->term_id, 'website', true);
        $district = get_term_meta($term->term_id, 'district_region', true);
        $latitude = get_term_meta($term->term_id, 'latitude', true);
        $longitude = get_term_meta($term->term_id, 'longitude', true);
        
        // Get city from address if available
        $city = '';
        if ($address) {
            $address_parts = explode(',', $address);
            if (count($address_parts) >= 2) {
                $city = trim($address_parts[1]);
            }
        }
        
        // Start output buffering
        ob_start();
        
        get_header();
        ?>
        
        <div class="car-single-church-page">
            <div class="car-single-church-card">
                
                <!-- Church Name -->
                <h1 class="car-church-name"><?php echo esc_html($term->name); ?></h1>
                
                <!-- City Pill -->
                <?php if ($city): ?>
                    <span class="car-city-pill"><?php echo esc_html($city); ?></span>
                <?php endif; ?>
                
                <!-- Divider -->
                <hr class="car-divider">
                
                <!-- Church Info -->
                <div class="car-church-details">
                    
                    <?php if ($pastor_name): ?>
                    <div class="car-detail-row">
                        <strong>Pastor:</strong> <?php echo esc_html($pastor_name); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($address): ?>
                    <div class="car-detail-row">
                        <strong>Address:</strong><br>
                        <?php echo nl2br(esc_html($address)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($phone): ?>
                    <div class="car-detail-row">
                        <strong>Phone:</strong> <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pastor_email): ?>
                    <div class="car-detail-row">
                        <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($pastor_email); ?>"><?php echo esc_html($pastor_email); ?></a>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Action Buttons -->
                <div class="car-action-buttons">
                    <?php if ($website): ?>
                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="car-btn car-btn-teal">
                        Visit Website
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($latitude && $longitude): ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr($latitude); ?>,<?php echo esc_attr($longitude); ?>" 
                       target="_blank" 
                       rel="noopener"
                       class="car-btn car-btn-green">
                        Get Directions
                    </a>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <!-- Map Section -->
            <?php if ($latitude && $longitude): ?>
            <div class="car-map-section">
                <div id="car-single-church-map" 
                     data-lat="<?php echo esc_attr($latitude); ?>"
                     data-lng="<?php echo esc_attr($longitude); ?>"
                     data-name="<?php echo esc_attr($term->name); ?>">
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Back Button -->
            <div class="car-back-section">
                <?php
                // Determine back link based on referrer
                $back_url = home_url('/church-directory/');
                $back_text = '← Back to Directory';
                
                // Check if came from church finder
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $referrer = $_SERVER['HTTP_REFERER'];
                    if (strpos($referrer, 'church-finder') !== false) {
                        $back_url = home_url('/church-finder/');
                        $back_text = '← Back to Church Finder';
                    }
                }
                ?>
                <a href="<?php echo esc_url($back_url); ?>" class="car-back-link">
                    <?php echo esc_html($back_text); ?>
                </a>
            </div>
            
        </div>
        
        <?php if ($latitude && $longitude): ?>
        <script>
        // Initialize map for single church page
        function initSingleChurchMap() {
            const mapElement = document.getElementById('car-single-church-map');
            if (!mapElement) return;
            
            const lat = parseFloat(mapElement.dataset.lat);
            const lng = parseFloat(mapElement.dataset.lng);
            const name = mapElement.dataset.name;
            
            const map = new google.maps.Map(mapElement, {
                center: { lat: lat, lng: lng },
                zoom: 16,  // Better zoom level for individual church
                styles: [
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    }
                ]
            });
            
            new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: name
            });
        }
        
        // Load Google Maps if not already loaded
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo esc_js(get_option('car_google_maps_api_key', '')); ?>&callback=initSingleChurchMap&loading=async';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        } else {
            initSingleChurchMap();
        }
        </script>
        <?php endif; ?>
        
        <?php
        get_footer();
        
        $content = ob_get_clean();
        echo $content;
        
        // Return empty string to prevent template_include from trying to load another template
        return '';
    }
}

// Initialize
new CAR_Single_Church_Template();