-- Travel Compass
-- Migration: Create ferry master tables
-- Date: 2026-08-22
--
-- Tables:
--   ferry_companies : ferry operator master
--   ferry_routes    : route master

CREATE TABLE IF NOT EXISTS `ferry_companies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `name_ja` VARCHAR(150) DEFAULT NULL,
    `slug` VARCHAR(100) DEFAULT NULL,
    `logo_url` VARCHAR(500) DEFAULT NULL,
    `official_url` VARCHAR(500) DEFAULT NULL,
    `reservation_url` VARCHAR(500) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ferry_companies_slug` (`slug`),
    KEY `idx_ferry_companies_name` (`name`),
    KEY `idx_ferry_companies_active` (`active`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `ferry_routes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,

    `route_name` VARCHAR(200) DEFAULT NULL,

    `departure_port` VARCHAR(150) NOT NULL,
    `departure_prefecture` VARCHAR(100) DEFAULT NULL,

    `arrival_port` VARCHAR(150) NOT NULL,
    `arrival_prefecture` VARCHAR(100) DEFAULT NULL,

    `duration_minutes` INT UNSIGNED DEFAULT NULL,

    `fare_from` INT UNSIGNED DEFAULT NULL,
    `fare_currency` CHAR(3) NOT NULL DEFAULT 'JPY',
    `fare_updated_at` DATE DEFAULT NULL,

    `vehicle_available` TINYINT(1) NOT NULL DEFAULT 0,
    `overnight` TINYINT(1) NOT NULL DEFAULT 0,

    `reservation_url` VARCHAR(500) DEFAULT NULL,
    `timetable_url` VARCHAR(500) DEFAULT NULL,

    `notes` TEXT DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_ferry_routes_company_id` (`company_id`),
    KEY `idx_ferry_routes_departure_port` (`departure_port`),
    KEY `idx_ferry_routes_arrival_port` (`arrival_port`),
    KEY `idx_ferry_routes_active` (`active`),

    CONSTRAINT `fk_ferry_routes_company`
        FOREIGN KEY (`company_id`)
        REFERENCES `ferry_companies` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;