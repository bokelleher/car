<?php
/**
 * Church Event Submission Form
 * Front-end form for Church Admins to submit events
 * Add to: church-attendance-reports/includes/church-event-form.php
 */

class CAR_Church_Event_Form {
    
    public function __construct() {
        add_shortcode('church_event_form', [$this, 'render_event_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'handle_event_submission']);
    }
    
    /**
     * Enqueue form assets
     */
    public function enqueue_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'church_event_form')) {
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js', ['jquery'], '1.13.18', true);
            wp_enqueue_style('jquery-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css');
        }
    }
    
    /**
     * Render the event form
     */
    public function render_event_form() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to submit events.</p>';
        }
        
        $user = wp_get_current_user();
        $allowed_roles = ['church_admin', 'administrator', 'district_admin'];
        
        if (!array_intersect($allowed_roles, $user->roles)) {
            return '<p>You do not have permission to submit events.</p>';
        }
        
        // Get user's church
        $church_id = get_user_meta($user->ID, 'assigned_church', true);
        if (!$church_id && !current_user_can('administrator')) {
            return '<p>No church assigned to your account. Please contact the district administrator.</p>';
        }
        
        $church = get_term($church_id, 'church');
        $venue_id = get_term_meta($church_id, 'mec_venue_id', true);
        
        // Check if MEC is active
        if (!class_exists('MEC')) {
            return '<p>Event system is not currently available.</p>';
        }
        
        // Display success/error messages
        if (isset($_GET['event_submitted'])) {
            echo '<div class="notice notice-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;">';
            echo '<strong>Success!</strong> Your event has been submitted and will appear on the calendar shortly.';
            echo '</div>';
        }
        
        ob_start();
        ?>
        <div class="car-event-form-container">
            <h2>Submit Church Event</h2>
            <?php if ($church): ?>
            <p class="church-notice">Submitting event for: <strong><?php echo esc_html($church->name); ?></strong></p>
            <?php endif; ?>
            
            <form method="post" action="" class="car-event-form" enctype="multipart/form-data">
                <?php wp_nonce_field('car_submit_event', 'car_event_nonce'); ?>
                <input type="hidden" name="car_venue_id" value="<?php echo esc_attr($venue_id); ?>">
                <input type="hidden" name="car_church_id" value="<?php echo esc_attr($church_id); ?>">
                
                <div class="form-group">
                    <label for="event_title">Event Title <span class="required">*</span></label>
                    <input type="text" name="event_title" id="event_title" required 
                           placeholder="e.g., Sunday Worship Service, Youth Night, Prayer Meeting">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="event_date">Event Date <span class="required">*</span></label>
                        <input type="text" name="event_date" id="event_date" required 
                               placeholder="Select date" autocomplete="off">
                    </div>
                    
                    <div class="form-group half">
                        <label for="recurring">Recurring Event?</label>
                        <select name="recurring" id="recurring">
                            <option value="no">No - One Time Event</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="start_time">Start Time <span class="required">*</span></label>
                        <input type="text" name="start_time" id="start_time" required 
                               placeholder="e.g., 10:00 AM" autocomplete="off">
                    </div>
                    
                    <div class="form-group half">
                        <label for="end_time">End Time <span class="required">*</span></label>
                        <input type="text" name="end_time" id="end_time" required 
                               placeholder="e.g., 11:30 AM" autocomplete="off">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_category">Event Category <span class="required">*</span></label>
                    <select name="event_category" id="event_category" required>
                        <option value="">Select Category</option>
                        <option value="worship">Worship Service</option>
                        <option value="youth">Youth Event</option>
                        <option value="children">Children's Ministry</option>
                        <option value="prayer">Prayer Meeting</option>
                        <option value="bible-study">Bible Study</option>
                        <option value="outreach">Outreach/Evangelism</option>
                        <option value="fellowship">Fellowship Event</option>
                        <option value="special">Special Event</option>
                        <option value="conference">Conference/Training</option>
                        <option value="nmi">NMI (Missions) Event</option>
                        <option value="nyi">NYI (Youth) Event</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="event_description">Event Description</label>
                    <textarea name="event_description" id="event_description" rows="5" 
                              placeholder="Provide details about the event..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contact_name">Contact Person</label>
                    <input type="text" name="contact_name" id="contact_name" 
                           value="<?php echo esc_attr($user->display_name); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" name="contact_email" id="contact_email" 
                               value="<?php echo esc_attr($user->user_email); ?>">
                    </div>
                    
                    <div class="form-group half">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" name="contact_phone" id="contact_phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_image">Event Image (Optional)</label>
                    <input type="file" name="event_image" id="event_image" accept="image/*">
                    <small>Upload an image for your event (JPG, PNG, max 2MB)</small>
                </div>
                
                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" name="district_wide" value="1">
                        This is a district-wide event (all churches invited)
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="submit_event" class="btn-submit">Submit Event</button>
                    <p class="form-note">* Required fields</p>
                </div>
            </form>
        </div>
        
        <style>
        .car-event-form-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .car-event-form h2 {
            color: #007c91;
            margin-bottom: 10px;
        }
        
        .church-notice {
            background: #e8f4f6;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #007c91;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-group.half {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group .required {
            color: #dc3545;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007c91;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .form-group.checkbox label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        
        .form-group.checkbox input {
            width: auto;
            margin-right: 10px;
        }
        
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .btn-submit {
            background: #007c91;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #005f6b;
        }
        
        .form-note {
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .car-event-form-container {
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-group.half {
                width: 100%;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Date picker
            $('#event_date').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                changeMonth: true,
                changeYear: true
            });
            
            // Time pickers
            $('#start_time, #end_time').timepicker({
                timeFormat: 'h:i A',
                interval: 15,
                minTime: '6:00 AM',
                maxTime: '11:00 PM',
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle event submission
     */
    public function handle_event_submission() {
        if (!isset($_POST['submit_event']) || !isset($_POST['car_event_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['car_event_nonce'], 'car_submit_event')) {
            return;
        }
        
        // Validate required fields
        $required = ['event_title', 'event_date', 'start_time', 'end_time', 'event_category'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                return;
            }
        }
        
        // Create event post
        $event_data = [
            'post_title' => sanitize_text_field($_POST['event_title']),
            'post_content' => sanitize_textarea_field($_POST['event_description']),
            'post_type' => 'mec-events',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ];
        
        $event_id = wp_insert_post($event_data);
        
        if ($event_id && !is_wp_error($event_id)) {
            // Assign venue
            if (!empty($_POST['car_venue_id'])) {
                wp_set_object_terms($event_id, intval($_POST['car_venue_id']), 'mec_location');
            }
            
            // Set event meta for MEC
            $event_date = sanitize_text_field($_POST['event_date']);
            $start_time = sanitize_text_field($_POST['start_time']);
            $end_time = sanitize_text_field($_POST['end_time']);
            
            // Convert times to MEC format
            $start_datetime = $event_date . ' ' . date('H:i:s', strtotime($start_time));
            $end_datetime = $event_date . ' ' . date('H:i:s', strtotime($end_time));
            
            // MEC meta structure
            $mec_data = [
                'start' => [
                    'date' => $event_date,
                    'hour' => date('g', strtotime($start_time)),
                    'minutes' => date('i', strtotime($start_time)),
                    'ampm' => date('A', strtotime($start_time))
                ],
                'end' => [
                    'date' => $event_date,
                    'hour' => date('g', strtotime($end_time)),
                    'minutes' => date('i', strtotime($end_time)),
                    'ampm' => date('A', strtotime($end_time))
                ],
                'location_id' => $_POST['car_venue_id'] ?? '',
                'organizer_id' => '',
                'repeat' => [
                    'type' => $_POST['recurring'] === 'weekly' ? 'weekly' : ($_POST['recurring'] === 'monthly' ? 'monthly' : ''),
                    'interval' => 1,
                    'end' => 'never'
                ]
            ];
            
            update_post_meta($event_id, 'mec', $mec_data);
            update_post_meta($event_id, 'mec_start_date', $event_date);
            update_post_meta($event_id, 'mec_end_date', $event_date);
            update_post_meta($event_id, 'mec_start_time_hour', date('g', strtotime($start_time)));
            update_post_meta($event_id, 'mec_start_time_minutes', date('i', strtotime($start_time)));
            update_post_meta($event_id, 'mec_start_time_ampm', strtoupper(date('a', strtotime($start_time))));
            update_post_meta($event_id, 'mec_end_time_hour', date('g', strtotime($end_time)));
            update_post_meta($event_id, 'mec_end_time_minutes', date('i', strtotime($end_time)));
            update_post_meta($event_id, 'mec_end_time_ampm', strtoupper(date('a', strtotime($end_time))));
            
            // Handle image upload
            if (!empty($_FILES['event_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                
                $uploaded_file = $_FILES['event_image'];
                $upload_overrides = ['test_form' => false];
                $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
                
                if ($movefile && !isset($movefile['error'])) {
                    $attachment = [
                        'post_mime_type' => $movefile['type'],
                        'post_title' => sanitize_file_name($uploaded_file['name']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ];
                    
                    $attach_id = wp_insert_attachment($attachment, $movefile['file'], $event_id);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($event_id, $attach_id);
                }
            }
            
            // Redirect with success message
            wp_redirect(add_query_arg('event_submitted', 'true', wp_get_referer()));
            exit;
        }
    }
}

// Initialize the event form
new CAR_Church_Event_Form();