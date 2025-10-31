<?php
/**
 * Church Directory Grid Display
 * Add to: church-attendance-reports/includes/church-directory-grid.php
 */

class CAR_Church_Directory {
    
    public function __construct() {
        add_shortcode('church_directory', [$this, 'render_directory']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_car_filter_churches', [$this, 'ajax_filter_churches']);
        add_action('wp_ajax_nopriv_car_filter_churches', [$this, 'ajax_filter_churches']);
    }
    
    /**
     * Enqueue directory assets
     */
    public function enqueue_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'church_directory')) {
            wp_enqueue_style(
                'car-directory-style',
                plugin_dir_url(__FILE__) . '../assets/css/church-directory.css',
                [],
                '1.0.0'
            );
            
            wp_enqueue_script(
                'car-directory-script',
                plugin_dir_url(__FILE__) . '../assets/js/church-directory.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            wp_localize_script('car-directory-script', 'car_directory', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('car_directory_nonce')
            ]);
        }
    }
    
    /**
     * Render the directory shortcode
     */
    public function render_directory($atts) {
        $atts = shortcode_atts([
            'columns' => 3,
            'show_search' => 'true',
            'show_map_link' => 'true'
        ], $atts);
        
        // Get all churches
        $churches = get_terms([
            'taxonomy' => 'church',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        ob_start();
        ?>
        <div class="car-directory-container">
            
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="car-directory-controls">
                <div class="car-directory-search">
                    <input type="text" 
                           id="car-church-search" 
                           placeholder="Search churches, pastors, or cities..."
                           autocomplete="off">
                    <span class="search-icon">🔍</span>
                </div>
                
                <div class="car-directory-stats">
                    <span id="church-count"><?php echo count($churches); ?></span> churches found
                    <?php if ($atts['show_map_link'] === 'true'): ?>
                    <a href="/church-finder/" class="map-link">View Map →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="car-directory-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                <?php foreach ($churches as $church): 
                    $pastor = get_term_meta($church->term_id, 'pastor', true);
                    $address = get_term_meta($church->term_id, 'address', true);
                    $phone = get_term_meta($church->term_id, 'phone', true);
                    $website = get_term_meta($church->term_id, 'website', true);
                    $email = get_term_meta($church->term_id, 'pastor_email', true);
                    
                    // Extract city from address
                    $city = '';
                    if (preg_match('/,\s*([^,]+),\s*TN/', $address, $matches)) {
                        $city = trim($matches[1]);
                    }
                ?>
                <div class="car-church-card" 
                     data-name="<?php echo esc_attr(strtolower($church->name)); ?>"
                     data-pastor="<?php echo esc_attr(strtolower($pastor)); ?>"
                     data-city="<?php echo esc_attr(strtolower($city)); ?>">
                    
                    <div class="church-card-header">
                        <h3><a href="<?php echo esc_url(get_term_link($church->term_id)); ?>" class="church-name-link"><?php echo esc_html($church->name); ?></a></h3>
                        <?php if ($city): ?>
                        <span class="church-city"><?php echo esc_html($city); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="church-card-body">
                        <?php if ($pastor): ?>
                        <p class="church-pastor">
                            <strong>Pastor:</strong> <?php echo esc_html($pastor); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($address): ?>
                        <p class="church-address">
                            <strong>Address:</strong><br>
                            <?php echo esc_html($address); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($phone): ?>
                        <p class="church-phone">
                            <strong>Phone:</strong> 
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($email): ?>
                        <p class="church-email">
                            <strong>Email:</strong> 
                            <a href="mailto:<?php echo esc_attr($email); ?>">
                                <?php echo esc_html($email); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="church-card-footer">
                        <?php if ($website && $website !== 'Facebook'): ?>
                        <a href="<?php echo esc_url($website); ?>" 
                           target="_blank" 
                           class="btn-website">
                            Visit Website
                        </a>
                        <?php elseif ($website === 'Facebook'): ?>
                        <span class="btn-facebook">Find on Facebook</span>
                        <?php endif; ?>
                        
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($address); ?>" 
                           target="_blank" 
                           class="btn-directions">
                            Get Directions
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="car-no-results" style="display: none;">
                <p>No churches found matching your search.</p>
                <button id="clear-search">Clear Search</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for filtering churches
     */
    public function ajax_filter_churches() {
        check_ajax_referer('car_directory_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $args = [
            'taxonomy' => 'church',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ];
        
        if ($search) {
            $args['name__like'] = $search;
        }
        
        $churches = get_terms($args);
        
        // Additional filtering
        if ($search) {
            $churches = array_filter($churches, function($church) use ($search) {
                $pastor = get_term_meta($church->term_id, 'pastor', true);
                $address = get_term_meta($church->term_id, 'address', true);
                $searchable = $church->name . ' ' . $pastor . ' ' . $address;
                if (!stripos($searchable, $search)) {
                    return false;
                }
                return true;
            });
        }
        
        wp_send_json_success([
            'count' => count($churches),
            'churches' => array_values($churches)
        ]);
    }
}

// Initialize the directory
new CAR_Church_Directory();