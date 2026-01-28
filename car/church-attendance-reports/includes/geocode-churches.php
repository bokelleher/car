<?php
/**
 * Manual Geocoding Script for Churches
 * Run this to geocode all church addresses
 */

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

echo '<div class="wrap">';
echo '<h1>Manual Church Geocoding</h1>';

$google_api_key = get_option('car_google_maps_api_key', '');

if (empty($google_api_key)) {
    echo '<div class="notice notice-error"><p>Google Maps API Key is missing!</p></div>';
    echo '<p>Add it in Settings → Church Attendance Settings</p>';
    echo '</div>';
    return;
}

// Test the API key first
echo '<h2>Testing API Key...</h2>';
$test_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=Nashville+TN&key=' . $google_api_key;
$test_response = wp_remote_get($test_url);

if (!is_wp_error($test_response)) {
    $test_data = json_decode(wp_remote_retrieve_body($test_response), true);
    if ($test_data['status'] === 'OK') {
        echo '<p style="color: green;">✅ API Key is working!</p>';
    } else {
        echo '<p style="color: red;">❌ API Key error: ' . $test_data['status'] . '</p>';
        if (isset($test_data['error_message'])) {
            echo '<p>Error: ' . esc_html($test_data['error_message']) . '</p>';
        }
        echo '</div>';
        return;
    }
}

echo '<h2>Starting Geocoding...</h2>';
echo '<div style="background: #f5f5f5; padding: 15px; max-height: 400px; overflow-y: auto; font-family: monospace;">';

// Get all churches
$churches = get_terms([
    'taxonomy' => 'church',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

$success = 0;
$failed = 0;
$skipped = 0;

foreach ($churches as $church) {
    $address = get_term_meta($church->term_id, 'address', true);
    
    echo "<strong>{$church->name}</strong><br>";
    
    if (empty($address)) {
        echo "⚠️ No address - skipping<br><br>";
        $skipped++;
        continue;
    }
    
    echo "Address: {$address}<br>";
    
    // Check if already geocoded
    $existing_lat = get_term_meta($church->term_id, 'latitude', true);
    if (!empty($existing_lat) && $existing_lat != 'NULL') {
        echo "✓ Already geocoded - skipping<br><br>";
        $skipped++;
        continue;
    }
    
    // Geocode the address
    $geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json';
    $geocode_url .= '?address=' . urlencode($address);
    $geocode_url .= '&key=' . $google_api_key;
    
    $response = wp_remote_get($geocode_url);
    
    if (is_wp_error($response)) {
        echo "❌ Request failed: " . $response->get_error_message() . "<br><br>";
        $failed++;
        continue;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($data['status'] === 'OK' && isset($data['results'][0])) {
        $location = $data['results'][0]['geometry']['location'];
        $lat = $location['lat'];
        $lng = $location['lng'];
        
        // Save coordinates
        update_term_meta($church->term_id, 'latitude', (string)$lat);
        update_term_meta($church->term_id, 'longitude', (string)$lng);
        update_term_meta($church->term_id, 'formatted_address', $data['results'][0]['formatted_address']);
        
        echo "✅ <span style='color: green;'>Success!</span> Lat: {$lat}, Lng: {$lng}<br><br>";
        $success++;
        
        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 seconds
        
    } else {
        echo "❌ Geocoding failed: " . $data['status'];
        if (isset($data['error_message'])) {
            echo " - " . $data['error_message'];
        }
        echo "<br><br>";
        $failed++;
    }
    
    // Flush output
    ob_flush();
    flush();
}

echo '</div>';

echo '<h2>Results</h2>';
echo '<div class="notice notice-info">';
echo "<p><strong>Geocoding Complete!</strong></p>";
echo "<ul>";
echo "<li>✅ Successfully geocoded: <strong>{$success}</strong></li>";
echo "<li>⚠️ Skipped: <strong>{$skipped}</strong></li>";
echo "<li>❌ Failed: <strong>{$failed}</strong></li>";
echo "</ul>";
echo '</div>';

if ($success > 0) {
    echo '<p><a href="' . home_url('/church-directory-finder/') . '" class="button button-primary" target="_blank">View Church Map</a></p>';
}

// Show a few geocoded churches as verification
echo '<h3>Sample Geocoded Churches:</h3>';
echo '<table class="widefat">';
echo '<thead><tr><th>Church</th><th>Address</th><th>Latitude</th><th>Longitude</th></tr></thead>';
echo '<tbody>';

$sample_churches = array_slice($churches, 0, 5);
foreach ($sample_churches as $church) {
    $lat = get_term_meta($church->term_id, 'latitude', true);
    $lng = get_term_meta($church->term_id, 'longitude', true);
    $addr = get_term_meta($church->term_id, 'address', true);
    
    echo '<tr>';
    echo '<td>' . esc_html($church->name) . '</td>';
    echo '<td>' . esc_html($addr) . '</td>';
    echo '<td>' . esc_html($lat) . '</td>';
    echo '<td>' . esc_html($lng) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';

echo '</div>';