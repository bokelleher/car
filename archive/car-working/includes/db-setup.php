<?php
// includes/db-setup.php

function car_create_plugin_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $churches_table = $wpdb->prefix . 'car_churches';
    $users_churches_table = $wpdb->prefix . 'car_users_churches';
    $attendance_table = $wpdb->prefix . 'car_attendance';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("
        CREATE TABLE $churches_table (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            pastor_name VARCHAR(255),
            pastor_email VARCHAR(255),
            address TEXT,
            phone_number VARCHAR(50),
            website VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;
    ");

    dbDelta("
        CREATE TABLE $users_churches_table (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            church_id INT UNSIGNED NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_church (user_id, church_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
            FOREIGN KEY (church_id) REFERENCES $churches_table(id) ON DELETE CASCADE
        ) $charset_collate;
    ");

    dbDelta("
        CREATE TABLE $attendance_table (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            church_id INT UNSIGNED NOT NULL,
            report_date DATE NOT NULL,
            in_person INT UNSIGNED DEFAULT 0,
            online INT UNSIGNED DEFAULT 0,
            discipleship INT UNSIGNED DEFAULT 0,
            acl INT UNSIGNED DEFAULT 0,
            submitted_by BIGINT UNSIGNED,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (church_id) REFERENCES $churches_table(id) ON DELETE CASCADE,
            FOREIGN KEY (submitted_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL,
            UNIQUE KEY unique_church_date (church_id, report_date)
        ) $charset_collate;
    ");
}
