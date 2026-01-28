<?php
/**
 * Admin Bar Manager
 * 
 * Manages visibility of the WordPress admin bar based on user roles
 * 
 * @package ChurchAttendanceReports
 * @since 1.1.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CAR_Admin_Bar_Manager {
    
    /**
     * Initialize the admin bar manager
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('after_setup_theme', array($this, 'hide_admin_bar_for_non_admins'));
        add_action('admin_print_styles-profile.php', array($this, 'remove_admin_bar_settings'));
        add_action('admin_print_styles-user-edit.php', array($this, 'remove_admin_bar_settings'));
        add_action('user_register', array($this, 'force_disable_admin_bar'));
        add_action('profile_update', array($this, 'force_disable_admin_bar'));
    }
    
    /**
     * Hide admin bar for non-administrators
     * 
     * Only users with 'manage_options' capability (WordPress Admins) will see the bar
     */
    public function hide_admin_bar_for_non_admins() {
        if (!current_user_can('manage_options')) {
            show_admin_bar(false);
        }
    }
    
    /**
     * Remove admin bar toggle option from user profile pages
     * 
     * Prevents non-administrators from seeing or changing the admin bar setting
     */
    public function remove_admin_bar_settings() {
        if (!current_user_can('manage_options')) {
            ?>
            <style type="text/css">
                .show-admin-bar {
                    display: none !important;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Force disable admin bar in user meta for non-administrators
     * 
     * Ensures admin bar stays hidden even if user tries to enable it
     * 
     * @param int $user_id User ID
     */
    public function force_disable_admin_bar($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        // If user is not an administrator, force admin bar to be hidden
        if (!in_array('administrator', (array) $user->roles)) {
            update_user_meta($user_id, 'show_admin_bar_front', 'false');
        }
    }
    
    /**
     * Check if current user should see admin bar
     * 
     * @return bool True if user should see admin bar
     */
    public function should_show_admin_bar() {
        return current_user_can('manage_options');
    }
    
    /**
     * Alternative: Hide admin bar for specific roles
     * 
     * Call this method instead of hide_admin_bar_for_non_admins() if you need
     * more granular control over which roles can see the admin bar
     * 
     * @param array $hide_for_roles Array of role slugs to hide admin bar for
     */
    public function hide_admin_bar_by_role($hide_for_roles = array()) {
        if (empty($hide_for_roles)) {
            // Default roles to hide admin bar for
            $hide_for_roles = array(
                'church_admin',
                'church_reporter',
                'district_superintendent',
                'subscriber',
                'contributor',
                'author',
                'editor',
            );
        }
        
        $user = wp_get_current_user();
        
        if (!$user) {
            return;
        }
        
        // Check if user has any of the roles that should hide the bar
        $user_roles = (array) $user->roles;
        $hide_bar = !empty(array_intersect($hide_for_roles, $user_roles));
        
        if ($hide_bar) {
            show_admin_bar(false);
        }
    }
}

// Initialize the admin bar manager
new CAR_Admin_Bar_Manager();