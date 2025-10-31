<?php
/**
 * Modern Events Calendar Integration - Simplified
 * Syncs churches with MEC venues
 * Add to church-attendance-reports/includes/
 */

// includes/mec-integration.php

class CAR_MEC_Integration {
    
    private $venue_taxonomy = 'mec_location';
    private $organizer_taxonomy = 'mec_organizer';
    private $event_post_type = 'mec-events';
    
    public function __construct() {
        // Only initialize if MEC is active
        if (!$this->is_mec_active()) {
            return;
        }
        
        // Hook into church save/update
        add_action('created_church', [$this, 'sync_church_to_venue'], 10, 1);
        add_action('edited_church', [$this, 'sync_church_to_venue'], 10, 1);
        add_action('delete_church', [$this, 'delete_venue'], 10, 1);
        
        // Add admin menu for sync
        add_action('admin_menu', [$this, 'add_sync_menu']);
        
        // Add Church Admin capabilities for MEC
        add_action('init', [$this, 'add_mec_capabilities'], 20);
        
        // Auto-assign venue when church admin creates event
        add_action('save_post_mec-events', [$this, 'auto_assign_venue'], 10, 3);
    }
    
    /**
     * Check if MEC is active
     */
    private function is_mec_active() {
        return class_exists('MEC') || defined('MEC_VERSION');
    }
    
    /**
     * Add sync menu
     */
    public function add_sync_menu() {
        add_submenu_page(
            'edit.php?post_type=attendance_report',
            'Sync Churches to MEC',
            'MEC Venue Sync',
            'manage_options',
            'car-mec-sync',
            [$this, 'render_sync_page']
        );
    }
    
    /**
     * Render sync page
     */
    public function render_sync_page() {
        ?>
        <div class="wrap">
            <h1>Sync Churches to MEC Venues</h1>
            
            <?php
            // Check if MEC is active
            if (!$this->is_mec_active()) {
                ?>
                <div class="notice notice-error">
                    <p><strong>Modern Events Calendar is not active!</strong></p>
                    <p>Please install and activate MEC to use this feature.</p>
                </div>
                <?php
                return;
            }
            ?>
            
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>Venue Synchronization</h2>
                <p>This will sync all churches from your Church taxonomy to MEC venue locations.</p>
                <p>Churches will be created as venues with their address, phone, and pastor information.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('car_mec_sync', 'car_mec_sync_nonce'); ?>
                    
                    <h3>Sync Options</h3>
                    <p>
                        <label>
                            <input type="checkbox" name="update_existing" value="1" checked>
                            Update existing venues if they already exist
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="create_organizers" value="1" checked>
                            Also create pastors as event organizers
                        </label>
                    </p>
                    
                    <p>
                        <input type="submit" name="sync_venues" class="button button-primary" value="Sync All Churches to MEC">
                    </p>
                </form>
                
                <?php
                // Handle sync request
                if (isset($_POST['sync_venues']) && check_admin_referer('car_mec_sync', 'car_mec_sync_nonce')) {
                    $this->run_sync();
                }
                ?>
            </div>
            
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2>Current Status</h2>
                <?php
                $churches = get_terms(['taxonomy' => 'church', 'hide_empty' => false]);
                $venues = get_terms(['taxonomy' => 'mec_location', 'hide_empty' => false]);
                ?>
                <p>Total Churches: <strong><?php echo count($churches); ?></strong></p>
                <p>Total MEC Venues: <strong><?php echo count($venues); ?></strong></p>
                
                <?php if (count($churches) > count($venues)): ?>
                <p style="color: orange;">⚠️ You have more churches than venues. Run sync to create missing venues.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Run the sync process
     */
    private function run_sync() {
        $update_existing = isset($_POST['update_existing']);
        $create_organizers = isset($_POST['create_organizers']);
        
        echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; max-height: 400px; overflow-y: auto;">';
        echo '<h3>Sync Progress:</h3>';
        echo '<pre>';
        
        $churches = get_terms([
            'taxonomy' => 'church',
            'hide_empty' => false,
            'orderby' => 'name'
        ]);
        
        $synced = 0;
        $failed = 0;
        
        foreach ($churches as $church) {
            echo "Processing: {$church->name}\n";
            
            $venue_id = $this->sync_church_to_venue($church->term_id, $update_existing);
            
            if ($venue_id) {
                echo "  ✅ Venue created/updated (ID: {$venue_id})\n";
                
                if ($create_organizers) {
                    $organizer_id = $this->create_organizer_for_church($church->term_id);
                    if ($organizer_id) {
                        echo "  ✅ Organizer created (ID: {$organizer_id})\n";
                    }
                }
                
                $synced++;
            } else {
                echo "  ❌ Failed to sync\n";
                $failed++;
            }
            
            echo "\n";
            ob_flush();
            flush();
        }
        
        echo '</pre>';
        echo "<h3>✅ Sync Complete!</h3>";
        echo "<p>Successfully synced: {$synced} churches</p>";
        if ($failed > 0) {
            echo "<p>Failed: {$failed} churches</p>";
        }
        echo '</div>';
    }
    
    /**
     * Sync a church to MEC venue
     */
    public function sync_church_to_venue($church_id, $update_existing = true) {
        $church = get_term($church_id, 'church');
        if (!$church || is_wp_error($church)) {
            return false;
        }
        
        // Get church metadata
        $address = get_term_meta($church_id, 'address', true);
        $phone = get_term_meta($church_id, 'phone', true);
        $website = get_term_meta($church_id, 'website', true);
        $pastor = get_term_meta($church_id, 'pastor', true);
        $latitude = get_term_meta($church_id, 'latitude', true);
        $longitude = get_term_meta($church_id, 'longitude', true);
        
        // Check if venue already exists
        $existing_venue = get_term_by('name', $church->name, 'mec_location');
        
        if ($existing_venue && !$update_existing) {
            return $existing_venue->term_id;
        }
        
        // Prepare venue args
        $venue_args = [
            'description' => sprintf('Church venue for %s', $church->name),
            'slug' => $church->slug
        ];
        
        if ($existing_venue) {
            // Update existing
            $result = wp_update_term($existing_venue->term_id, 'mec_location', $venue_args);
            $venue_id = $existing_venue->term_id;
        } else {
            // Create new
            $result = wp_insert_term($church->name, 'mec_location', $venue_args);
            if (!is_wp_error($result)) {
                $venue_id = $result['term_id'];
            } else {
                return false;
            }
        }
        
        // Update MEC venue metadata
        if ($venue_id) {
            update_term_meta($venue_id, 'address', $address);
            update_term_meta($venue_id, 'latitude', $latitude);
            update_term_meta($venue_id, 'longitude', $longitude);
            update_term_meta($venue_id, 'url', $website);
            update_term_meta($venue_id, 'tel', $phone);
            update_term_meta($venue_id, 'thumbnail', '');
            
            // Store venue ID in church meta for reference
            update_term_meta($church_id, 'mec_venue_id', $venue_id);
            
            // Also store in MEC format
            $location = [
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'url' => $website,
                'tel' => $phone,
                'thumbnail' => ''
            ];
            update_term_meta($venue_id, 'location', $location);
        }
        
        return $venue_id;
    }
    
    /**
     * Create organizer for church
     */
    private function create_organizer_for_church($church_id) {
        $church = get_term($church_id, 'church');
        if (!$church || is_wp_error($church)) {
            return false;
        }
        
        $pastor = get_term_meta($church_id, 'pastor', true);
        $email = get_term_meta($church_id, 'pastor_email', true);
        $phone = get_term_meta($church_id, 'phone', true);
        
        if (!$pastor) {
            return false;
        }
        
        // Check if organizer already exists
        $existing = get_term_by('name', $pastor, 'mec_organizer');
        if ($existing) {
            return $existing->term_id;
        }
        
        // Create organizer
        $result = wp_insert_term($pastor, 'mec_organizer', [
            'description' => sprintf('Pastor at %s', $church->name)
        ]);
        
        if (!is_wp_error($result)) {
            $organizer_id = $result['term_id'];
            
            // Add organizer meta
            update_term_meta($organizer_id, 'email', $email);
            update_term_meta($organizer_id, 'tel', $phone);
            update_term_meta($organizer_id, 'url', '');
            
            // Store organizer ID in church meta
            update_term_meta($church_id, 'mec_organizer_id', $organizer_id);
            
            return $organizer_id;
        }
        
        return false;
    }
    
    /**
     * Delete MEC venue when church is deleted
     */
    public function delete_venue($church_id) {
        $venue_id = get_term_meta($church_id, 'mec_venue_id', true);
        if ($venue_id) {
            wp_delete_term($venue_id, 'mec_location');
        }
        
        $organizer_id = get_term_meta($church_id, 'mec_organizer_id', true);
        if ($organizer_id) {
            wp_delete_term($organizer_id, 'mec_organizer');
        }
    }
    
    /**
     * Add capabilities for Church Admin role
     */
    public function add_mec_capabilities() {
        $role = get_role('church_admin');
        if ($role) {
            // MEC event capabilities
            $role->add_cap('read');
            $role->add_cap('edit_posts');
            $role->add_cap('edit_mec_events');
            $role->add_cap('create_mec_events');
            $role->add_cap('publish_mec_events');
            $role->add_cap('delete_mec_events');
            $role->add_cap('upload_files');
            
            // But not others' events
            $role->remove_cap('edit_others_posts');
            $role->remove_cap('edit_others_mec_events');
            $role->remove_cap('delete_others_mec_events');
        }
        
        // Also for church reporter (view only)
        $reporter = get_role('church_reporter');
        if ($reporter) {
            $reporter->add_cap('read');
            $reporter->add_cap('read_mec_events');
        }
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
            // Try to create venue if it doesn't exist
            $venue_id = $this->sync_church_to_venue($church_id);
        }
        
        if (!$venue_id) {
            return;
        }
        
        // Assign venue to event
        wp_set_object_terms($post_id, intval($venue_id), 'mec_location');
        
        // Also get organizer if exists
        $organizer_id = get_term_meta($church_id, 'mec_organizer_id', true);
        if ($organizer_id) {
            wp_set_object_terms($post_id, intval($organizer_id), 'mec_organizer');
        }
    }
}

// Initialize MEC integration
new CAR_MEC_Integration();