CREATE DATABASE IF NOT EXISTS `pup_pick`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `pup_pick`;

CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `student_number` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_student_number` (`student_number`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lost_items` (
    `lost_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `item_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(80) NOT NULL,
    `description` TEXT NOT NULL,
    `color` VARCHAR(50) NOT NULL DEFAULT '',
    `lost_location` VARCHAR(150) NOT NULL,
    `date_lost` DATE DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('open', 'matched', 'returned') NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`lost_item_id`),
    KEY `idx_lost_items_user` (`user_id`),
    KEY `idx_lost_items_status_created` (`status`, `created_at`),
    CONSTRAINT `fk_lost_items_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `found_items` (
    `found_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `item_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(80) NOT NULL,
    `description` TEXT NOT NULL,
    `color` VARCHAR(50) NOT NULL DEFAULT '',
    `found_location` VARCHAR(150) NOT NULL,
    `date_found` DATE DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('open', 'matched', 'returned') NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`found_item_id`),
    KEY `idx_found_items_user` (`user_id`),
    KEY `idx_found_items_status_created` (`status`, `created_at`),
    CONSTRAINT `fk_found_items_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `match_result` (
    `match_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lost_item_id` INT UNSIGNED NOT NULL,
    `found_item_id` INT UNSIGNED NOT NULL,
    `match_score` TINYINT UNSIGNED NOT NULL,
    `match_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`match_id`),
    UNIQUE KEY `uq_match_result_items` (`lost_item_id`, `found_item_id`),
    KEY `idx_match_result_status_created` (`match_status`, `created_at`),
    CONSTRAINT `fk_match_result_lost_item`
        FOREIGN KEY (`lost_item_id`) REFERENCES `lost_items` (`lost_item_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_match_result_found_item`
        FOREIGN KEY (`found_item_id`) REFERENCES `found_items` (`found_item_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `chk_match_result_score` CHECK (`match_score` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `claims` (
    `claim_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lost_item_id` INT UNSIGNED DEFAULT NULL,
    `found_item_id` INT UNSIGNED NOT NULL,
    `claimant_user_id` INT UNSIGNED NOT NULL,
    `claim_message` TEXT NOT NULL,
    `claim_image_path` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`claim_id`),
    KEY `idx_claims_lost_item` (`lost_item_id`),
    KEY `idx_claims_found_item_status` (`found_item_id`, `status`),
    KEY `idx_claims_claimant_created` (`claimant_user_id`, `created_at`),
    CONSTRAINT `fk_claims_lost_item`
        FOREIGN KEY (`lost_item_id`) REFERENCES `lost_items` (`lost_item_id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_claims_found_item`
        FOREIGN KEY (`found_item_id`) REFERENCES `found_items` (`found_item_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_claims_claimant`
        FOREIGN KEY (`claimant_user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_claims_reviewer`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(30) NOT NULL DEFAULT 'system',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    KEY `idx_notifications_user_read_created` (`user_id`, `is_read`, `created_at`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
