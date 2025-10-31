<?php
/**
 * Gravity Forms Integration
 * Custom autocomplete for church text field
 * Form ID: 1, Field ID: 21
 */

if (!defined('ABSPATH')) exit;

// Enqueue jQuery UI Autocomplete
add_action('wp_enqueue_scripts', 'car_enqueue_autocomplete_assets');
function car_enqueue_autocomplete_assets() {
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}

// Add script directly to footer
add_action('wp_footer', 'car_add_autocomplete_script_direct');
function car_add_autocomplete_script_direct() {
    ?>
    <script type="text/javascript">
    console.log('CAR: Autocomplete script loading for Form 1, Field 21');
    
    jQuery(document).ready(function($) {
        console.log('CAR: jQuery ready');
        
        var attempts = 0;
        var maxAttempts = 10;
        
        var pollingInterval = setInterval(function() {
            attempts++;
            
            // Look for field input_1_21
            var field = $('#input_1_21');
            
            if (field.length > 0) {
                console.log('CAR: ✓ Field found! Setting up autocomplete...');
                
                clearInterval(pollingInterval);
                
                // Set up autocomplete
                field.autocomplete({
                    source: function(request, response) {
                        console.log('CAR: Searching for:', request.term);
                        
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            dataType: 'json',
                            data: {
                                action: 'car_search_churches',
                                term: request.term,
                                nonce: '<?php echo wp_create_nonce('car_search_churches'); ?>'
                            },
                            success: function(data) {
                                console.log('CAR: ✓ Received', data.length, 'churches');
                                response(data);
                            },
                            error: function(xhr, status, error) {
                                console.error('CAR: ✗ AJAX ERROR:', error);
                                response([]);
                            }
                        });
                    },
                    minLength: 2,
                    delay: 300,
                    select: function(event, ui) {
                        console.log('CAR: ✓ Selected:', ui.item.label);
                        $(this).val(ui.item.label);
                        return false;
                    }
                });
                
                console.log('CAR: ✓ Autocomplete initialized successfully!');
                
            } else if (attempts >= maxAttempts) {
                clearInterval(pollingInterval);
                console.error('CAR: ✗ Field #input_1_21 not found after', maxAttempts, 'attempts');
            }
        }, 500);
    });
    </script>
    <?php
}

// AJAX handler to search churches
add_action('wp_ajax_car_search_churches', 'car_search_churches_ajax');
add_action('wp_ajax_nopriv_car_search_churches', 'car_search_churches_ajax');

function car_search_churches_ajax() {
    // Verify nonce
    if (!check_ajax_referer('car_search_churches', 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
    
    // Get all churches
    $churches = get_terms(array(
        'taxonomy' => 'church',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    $results = array();
    
    if (!is_wp_error($churches) && !empty($churches)) {
        foreach ($churches as $church) {
            // Filter by search term (case insensitive)
            if (empty($term) || stripos($church->name, $term) !== false) {
                $results[] = array(
                    'label' => $church->name,
                    'value' => $church->name
                );
            }
        }
    }
    
    wp_send_json($results);
}

// Validate church field on form submission
add_filter('gform_field_validation', 'car_validate_church_field', 10, 4);
function car_validate_church_field($result, $value, $form, $field) {
    // Form ID 1, Field ID 21
    if ($form['id'] == 1 && $field->id == 21 && !empty($value)) {
        $church = get_term_by('name', $value, 'church');
        
        if (!$church) {
            $result['is_valid'] = false;
            $result['message'] = 'Please select a valid church from the autocomplete suggestions.';
        }
    }
    
    return $result;
}

/**
 * Disable duplicate detection for access request form
 * This allows users to resubmit if needed
 */
add_filter('gform_duplicate_message_1', 'car_disable_duplicate_message');
function car_disable_duplicate_message($message) {
    // Return empty to disable duplicate detection
    return '';
}

// Alternative: Completely bypass duplicate checking for Form 1
add_filter('gform_entry_is_spam_1', '__return_false', 10, 3);

// Reset honeypot detection for Form 1 to prevent false positives
add_filter('gform_form_args_1', 'car_reset_form_args');
function car_reset_form_args($form_args) {
    // Disable duplicate detection by modifying form args
    $form_args['enableHoneypot'] = false;
    return $form_args;
}

// Most effective: Remove duplicate detection entirely for Form 1
add_filter('gform_validation_1', 'car_remove_duplicate_validation');
function car_remove_duplicate_validation($validation_result) {
    // Get the form object
    $form = $validation_result['form'];
    
    // Remove any duplicate-related validation errors
    foreach ($form['fields'] as &$field) {
        if (!empty($field->failed_validation) && 
            (strpos($field->validation_message, 'already submitted') !== false ||
             strpos($field->validation_message, 'duplicate') !== false)) {
            $field->failed_validation = false;
            $field->validation_message = '';
        }
    }
    
    // Mark as valid if only duplicate errors existed
    $has_errors = false;
    foreach ($form['fields'] as $field) {
        if (!empty($field->failed_validation)) {
            $has_errors = true;
            break;
        }
    }
    
    $validation_result['is_valid'] = !$has_errors;
    $validation_result['form'] = $form;
    
    return $validation_result;
}