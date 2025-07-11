
-- Table: car_churches
CREATE TABLE `car_churches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `pastor_name` VARCHAR(255),
    `pastor_email` VARCHAR(255),
    `address` TEXT,
    `phone_number` VARCHAR(50),
    `website` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: car_users_churches
CREATE TABLE `car_users_churches` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `church_id` INT UNSIGNED NOT NULL,
    `role` ENUM('church_admin', 'church_reporter', 'church_viewer') NOT NULL,
    PRIMARY KEY (`user_id`, `church_id`),
    FOREIGN KEY (`user_id`) REFERENCES `wp_users`(`ID`) ON DELETE CASCADE,
    FOREIGN KEY (`church_id`) REFERENCES `car_churches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: car_attendance_reports
CREATE TABLE `car_attendance_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT UNSIGNED NOT NULL,
    `week_ending` DATE NOT NULL,
    `in_person_attendance` INT UNSIGNED DEFAULT 0,
    `online_attendance` INT UNSIGNED DEFAULT 0,
    `discipleship_attendance` INT UNSIGNED DEFAULT 0,
    `acl_count` INT UNSIGNED DEFAULT 0,
    `submitted_by` BIGINT UNSIGNED,
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (`church_id`, `week_ending`),
    FOREIGN KEY (`church_id`) REFERENCES `car_churches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `wp_users`(`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
