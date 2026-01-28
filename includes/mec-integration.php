<?php
/**
 * Modern Events Calendar Integration
 * Syncs churches with MEC venues
 * Add to church-attendance-reports/includes/
 */

// includes/mec-integration.php

class CAR_MEC_Integration {
    
    private $mec_post_type = 'mec-events';
    private $mec_venue_taxonomy = 'mec_location';
    
    public function __construct() {
        // Hook into church save/update
        add_action('created_church', [$this, 'sync_church_to_venue'], 10, 1);
        add_action('edited_church', [$this, 'sync_church_to_venue'], 10, 1);
        add_action('delete_church', [$this, 'delete_venue'], 10, 1);
        
        // Add MEC venue ID field to church taxonomy
        add_action('church_edit_form_fields', [$this, 'add_mec_venue_field'], 15);
        add_action('church_add_form_fields', [$this, 'add_mec_venue_field'], 15);
        
        // CLI command for bulk sync
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('car sync-venues', [$this, 'cli_sync_all_venues']);
        }
        
        // Add Church Admin capabilities for MEC
        add_action('init', [$this, 'add_mec_capabilities'], 20);
        
        // Filter MEC events for church admins
        add_filter('pre_get_posts', [$this, 'filter_events_for_church_admin']);
        
        // Auto-assign venue when church admin creates event
        add_action('save_post_mec-events', [$this, 'auto_assign_venue'], 10, 3);
    }
    
    /**
     * Sync a church to MEC venue
     */
    public function sync_church_to_venue($church_id) {
        $church = get_term($church_id, 'church');
        if (!$church || is_wp_error($church)) {
            return false;
        }
        
        // Get church metadata
        $address = get_term_meta($church_id, 'address', true);
        $phone = get_term_meta($church_id, 'phone', true);
        $website = get_term_meta($church_id, 'website', true);
        $latitude = get_term_meta($church_id, 'latitude', true);
        $longitude = get_term_meta($church_id, 'longitude', true);
        
        // Check if venue already exists
        $mec_venue_id = get_term_meta($church_id, 'mec_venue_id', true);
        
        if ($mec_venue_id) {
            // Update existing venue
            $venue = get_term($mec_venue_id, 'mec_location');
            if (!$venue || is_wp_error($venue)) {
                $mec_venue_id = null; // Reset if venue doesn't exist
            }
        }
        
        // Prepare venue data
        $venue_args = [
            'name' => $church->name,
            'slug' => $church->slug . '-venue',
            'description' => sprintf(
                'Church venue for %s. Pastor: %s',
                $church->name,
                get_term_meta($church_id, 'pastor', true)
            ),
        ];
        
        if ($mec_venue_id) {
            // Update existing
            $result = wp_update_term($mec_venue_id, 'mec_location', $venue_args);
        } else {
            // Create new
            $result = wp_insert_term($church->name, 'mec_location', $venue_args);
        }
        
        if (!is_wp_error($result)) {
            $venue_id = is_array($result) ? $result['term_id'] : $mec_venue_id;
            
            // Update MEC venue metadata
            $this->update_mec_venue_meta($venue_id, [
                'address' => $address,
                'phone' => $phone,
                'website' => $website,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            
            // Store venue ID in church meta
            update_term_meta($church_id, 'mec_venue_id', $venue_id);
            
            return $venue_id;
        }
        
        return false;
    }
    
    /**
     * Update MEC venue metadata
     */
    private function update_mec_venue_meta($venue_id, $data) {
        // MEC stores venue data in a specific format
        $location_data = [
            'address' => $data['address'] ?? '',
            'latitude' => $data['latitude'] ?? '',
            'longitude' => $data['longitude'] ?? '',
            'url' => $data['website'] ?? '',
            'tel' => $data['phone'] ?? '',
            'thumbnail' => '', // Could add church image here
        ];
        
        update_term_meta($venue_id, 'latitude', $data['latitude']);
        update_term_meta($venue_id, 'longitude', $data['longitude']);
        update_term_meta($venue_id, 'address', $data['address']);
        update_term_meta($venue_id, 'url', $data['website']);
        update_term_meta($venue_id, 'tel', $data['phone']);
        
        // MEC specific meta
        update_term_meta($venue_id, 'location', $location_data);
    }
    
    /**
     * Delete MEC venue when church is deleted
     */
    public function delete_venue($church_id) {
        $mec_venue_id = get_term_meta($church_id, 'mec_venue_id', true);
        if ($mec_venue_id) {
            wp_delete_term($mec_venue_id, 'mec_location');
        }
    }
    
    /**
     * Add MEC venue field to church edit form
     */
    public function add_mec_venue_field($term) {
        $venue_id = is_object($term) ? get_term_meta($term->term_id, 'mec_venue_id', true) : '';
        ?>
        <div class="form-field">
            <label for="mec_venue_id">MEC Venue ID</label>
            <input name="mec_venue_id" id="mec_venue_id" type="text" value="<?php echo esc_attr($venue_id); ?>" readonly>
            <p class="description">Automatically synced with Modern Events Calendar</p>
            <?php if ($venue_id): ?>
                <p><a href="<?php echo admin_url('term.php?taxonomy=mec_location&tag_ID=' . $venue_id); ?>" target="_blank">View in MEC</a></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add capabilities for Church Admin role
     */
    public function add_mec_capabilities() {
        $role = get_role('church_admin');
        if ($role) {
            // MEC event capabilities
            $role->add_cap('mec_events');
            $role->add_cap('edit_mec_events');
            $role->add_cap('create_mec_events');
            $role->add_cap('publish_mec_events');
            $role->add_cap('delete_mec_events');
            
            // But not others' events
            $role->remove_cap('edit_others_mec_events');
            $role->remove_cap('delete_others_mec_events');
            
            // Can view but not edit venues (auto-synced)
            $role->add_cap('read_mec_locations');
        }
    }
    
    /**
     * Filter events query for church admins
     */
    public function filter_events_for_church_admin($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'mec-events') {
            return;
        }
        
        $user = wp_get_current_user();
        if (!in_array('church_admin', $user->roles)) {
            return;
        }
        
        // Get user's church
        $church_id = get_user_meta($user->ID, 'assigned_church', true);
        if (!$church_id) {
            return;
        }
        
        // Get MEC venue ID
        $venue_id = get_term_meta($church_id, 'mec_venue_id', true);
        if (!$venue_id) {
            return;
        }
        
        // Filter to show only events at their venue
        $query->set('tax_query', [
            [
                'taxonomy' => 'mec_location',
                'field' => 'term_id',
                'terms' => [$venue_id],
            ]
        ]);
    }
    
    /**
     * Auto-assign venue when church admin creates event
     */
    public function auto_assign_venue($post_id, $post, $update) {
        // Skip on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ($post->post_type !== 'mec-events') {
            return;
        }
        
        // Check user role
        $user = wp_get_current_user();
        if (!in_array('church_admin', $user->roles)) {
            return;
        }
        
        // Get user's church
        $church_id = get_user_meta($user->ID, 'assigned_church', true);
        if (!$church_id) {
            return;
        }
        
        // Get MEC venue ID
        $venue_id = get_term_meta($church_id, 'mec_venue_id', true);
        if (!$venue_id) {
            return;
        }
        
        // Assign venue to event
        wp_set_object_terms($post_id, [$venue_id], 'mec_location');
        
        // Update MEC meta
        $mec_data = get_post_meta($post_id, 'mec', true);
        if (!is_array($mec_data)) {
            $mec_data = [];
        }
        
        $mec_data['location_id'] = $venue_id;
        update_post_meta($post_id, 'mec', $mec_data);
    }
    
    /**
     * CLI command to sync all churches to venues
     */
    public function cli_sync_all_venues($args, $assoc_args) {
        $churches = get_terms([
            'taxonomy' => 'church',
            'hide_empty' => false,
        ]);
        
        $success = 0;
        $failed = 0;
        
        foreach ($churches as $church) {
            WP_CLI::log("Syncing {$church->name}...");
            
            if ($this->sync_church_to_venue($church->term_id)) {
                $success++;
                WP_CLI::success("✓ {$church->name}");
            } else {
                $failed++;
                WP_CLI::warning("✗ Failed to sync {$church->name}");
            }
        }
        
        WP_CLI::success("Sync complete! Success: {$success}, Failed: {$failed}");
    }
}

// Initialize MEC integration
new CAR_MEC_Integration();

/**
 * Shortcode for Church Admin Event Form
 * Uses MEC FES (Frontend Event Submission)
 */
add_shortcode('church_event_form', 'car_render_church_event_form');
function car_render_church_event_form($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to submit events.</p>';
    }
    
    $user = wp_get_current_user();
    if (!in_array('church_admin', $user->roles) && !in_array('administrator', $user->roles)) {
        return '<p>You do not have permission to submit events.</p>';
    }
    
    // Get user's church
    $church_id = get_user_meta($user->ID, 'assigned_church', true);
    if (!$church_id) {
        return '<p>No church assigned to your account.</p>';
    }
    
    $church = get_term($church_id, 'church');
    $venue_id = get_term_meta($church_id, 'mec_venue_id', true);
    
    // Use MEC's FES shortcode with pre-filled venue
    if (shortcode_exists('MEC_fes_form')) {
        return do_shortcode('[MEC_fes_form]');
    } else {
        // Fallback to custom form
        ob_start();
        ?>
        <div class="car-event-form">
            <h3>Submit Event for <?php echo esc_html($church->name); ?></h3>
            <p class="notice">Note: Events will be automatically assigned to your church location.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('car_submit_event', 'car_event_nonce'); ?>
                <input type="hidden" name="venue_id" value="<?php echo esc_attr($venue_id); ?>">
                
                <label>Event Title:
                    <input type="text" name="event_title" required>
                </label>
                
                <label>Event Date:
                    <input type="date" name="event_date" required>
                </label>
                
                <label>Start Time:
                    <input type="time" name="start_time" required>
                </label>
                
                <label>End Time:
                    <input type="time" name="end_time" required>
                </label>
                
                <label>Description:
                    <textarea name="event_description" rows="5"></textarea>
                </label>
                
                <label>Event Type:
                    <select name="event_category">
                        <option value="worship">Worship Service</option>
                        <option value="youth">Youth Event</option>
                        <option value="missions">Missions Event</option>
                        <option value="special">Special Event</option>
                        <option value="conference">Conference/Training</option>
                    </select>
                </label>
                
                <button type="submit">Submit Event</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}