<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!is_admin() || !isset($_GET['generate_churches'])) return;

    $term = wp_insert_term(
        'Athens',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. David W. Reynolds');
        update_term_meta($term_id, 'address', '1908 West Madison Ave, Athens, TN 37303');
        update_term_meta($term_id, 'phone', '(423) 790-5183');
        update_term_meta($term_id, 'website', 'www.athensnazarenechurch.org');
    }

    $term = wp_insert_term(
        'Barfield',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Thomas Crummer');
        update_term_meta($term_id, 'address', '2349 S. Church St, Murfreesboro, TN 37310');
        update_term_meta($term_id, 'phone', '(615) 489-6947');
        update_term_meta($term_id, 'website', 'barfieldnaz.org');
    }

    $term = wp_insert_term(
        'Beulah Chapel',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Revs. John & Karen Porter');
        update_term_meta($term_id, 'address', '270 Country Road 231, Niota, TN 37826');
        update_term_meta($term_id, 'phone', 'John: (423) 333-5278 - Karen: (423) 333-9981');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Blackman Community',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Lisa Hathcock');
        update_term_meta($term_id, 'address', '315 John R. Rice Blvd, Murfreesboro, TN 37128');
        update_term_meta($term_id, 'phone', '(615) 838-6453');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Brownington',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. William A. Brunosky');
        update_term_meta($term_id, 'address', '5864 Mansford Road, Winchester, TN 37398');
        update_term_meta($term_id, 'phone', '(931) 967-9064');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Chattanooga Calvary',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. W. Preston Brown');
        update_term_meta($term_id, 'address', '4400 Bonny Oaks Dr, Chattanooga, TN 37416');
        update_term_meta($term_id, 'phone', '(423) 892-5771');
        update_term_meta($term_id, 'website', 'http://www.nazarene.ch/calvary/');
    }

    $term = wp_insert_term(
        'Chattanooga East Ridge',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Charles D. Knight');
        update_term_meta($term_id, 'address', '5821 Ringgold Rd, Chattanooga, TN 37412');
        update_term_meta($term_id, 'phone', '(423) 364-8828');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Chattanooga First',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Eric V. Johnson');
        update_term_meta($term_id, 'address', '5455 North Terrace, Chattanooga, TN 37411');
        update_term_meta($term_id, 'phone', '(423) 893-7756');
        update_term_meta($term_id, 'website', 'www.chattanooganazarene.org');
    }

    $term = wp_insert_term(
        'Chattanooga Grace',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Stephen Deese');
        update_term_meta($term_id, 'address', '6310 Dayton Blvd, Hixson, TN 37343');
        update_term_meta($term_id, 'phone', '(423) 842-5919');
        update_term_meta($term_id, 'website', 'chattanoogagrace.com');
    }

    $term = wp_insert_term(
        'Cleveland',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. B. J. Miller, Jr.');
        update_term_meta($term_id, 'address', '114 Stuart Road NE #332, Cleveland, TN 37312-5084');
        update_term_meta($term_id, 'phone', '(423) 472-7371');
        update_term_meta($term_id, 'website', 'www.clevelandnazarene.org');
    }

    $term = wp_insert_term(
        'Cleveland New Hope',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Daniel P. Ammons');
        update_term_meta($term_id, 'address', '4514 Waterlevel Hwy, Cleveland, TN 37311');
        update_term_meta($term_id, 'phone', '(423) 584-5536');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Cookeville',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Stephen T. Morrison');
        update_term_meta($term_id, 'address', '360 S. Old Kentucky Road, Cookeville, TN 38501');
        update_term_meta($term_id, 'phone', '(931) 526-4371');
        update_term_meta($term_id, 'website', 'www.cookevillenazarene.org');
    }

    $term = wp_insert_term(
        'Cowan',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. John Barker');
        update_term_meta($term_id, 'address', '221 Cumberland St. W, Cowan, TN 37318');
        update_term_meta($term_id, 'phone', '(423) 750-1537');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Cross Style',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Stephen Manley');
        update_term_meta($term_id, 'address', '104 Trinity Dr, Unknown City, TN');
        update_term_meta($term_id, 'phone', '-');
        update_term_meta($term_id, 'website', '-');
    }

    $term = wp_insert_term(
        'Sweetwater',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Stacy Dockery');
        update_term_meta($term_id, 'address', '247 Old Hwy. 11, Sweetwater, TN 37874');
        update_term_meta($term_id, 'phone', '(423) 337-6486');
        update_term_meta($term_id, 'website', 'www.sweetwaternazarene.org');
    }

    $term = wp_insert_term(
        'The Church House',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Jacob Ward');
        update_term_meta($term_id, 'address', '136 South Aster Ave, Chattanooga, TN 37419');
        update_term_meta($term_id, 'phone', '(423) 821-2332');
        update_term_meta($term_id, 'website', 'https://thechurch.house/');
    }

    $term = wp_insert_term(
        'Tullahoma First',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Jeffrey Scott Roberts');
        update_term_meta($term_id, 'address', '102 S. Polk St, Tullahoma, TN 37388');
        update_term_meta($term_id, 'phone', '(931) 455-5008');
        update_term_meta($term_id, 'website', 'tullahomafirstnaz.com');
    }

    $term = wp_insert_term(
        'Tullahoma Westside',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Robert Lee McBee');
        update_term_meta($term_id, 'address', '123 Westside Dr, Tullahoma, TN 37388');
        update_term_meta($term_id, 'phone', '(931) 455-6382');
        update_term_meta($term_id, 'website', 'www.westsidenazarene.org');
    }

    $term = wp_insert_term(
        'Warren Chapel',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Phillip W. Bowles');
        update_term_meta($term_id, 'address', '2096 Warren Chapel Rd, Gruelti-Laager, TN 37339');
        update_term_meta($term_id, 'phone', '(931) 265-0048');
        update_term_meta($term_id, 'website', 'www.warrenchapel.org');
    }

    $term = wp_insert_term(
        'Wartburg Community',
        'church'
    );
    if (!is_wp_error($term)) {
        $term_id = $term['term_id'];
        update_term_meta($term_id, 'pastor', 'Rev. Sam Wood (Interim)');
        update_term_meta($term_id, 'address', '620 St, Wartburg, TN 37887');
        update_term_meta($term_id, 'phone', '(931) 445-6607 or (931) 879-5193');
        update_term_meta($term_id, 'website', '-');
    }

    echo '✅ Churches added successfully.';
    exit;
});