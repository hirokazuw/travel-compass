-- ================================================================
-- Travel Compass - Ferry Final Consolidated Fix Migration
-- Date: 2026-08-22
-- Includes table creation, base seed, MOL Sunflower URL fix,
-- and added Setouchi routes.
-- Execute this file alone for the complete ferry migration.
-- ================================================================

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

-- ==================== BASE FERRY MASTER DATA ====================
-- ================================================================
-- Travel Compass
-- Ferry companies/routes seed migration
-- Snapshot: 2026-08-22
--
-- Scope:
--   Major scheduled passenger/car-ferry routes in Japan
--   + Japan-Korea
--   + Japan-Taiwan
--   + Japan-China
--
-- Notes:
--   * Not intended to enumerate every small island/local ferry in Japan.
--   * fare_from is left NULL where fares vary heavily by season/class
--     or where a stable minimum was not confidently verified.
--   * Re-runnable: companies use UNIQUE slug; routes use NOT EXISTS.
-- ================================================================

START TRANSACTION;

-- ------------------------------------------------
-- Ferry companies
-- ------------------------------------------------

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Shin Nihonkai Ferry', '新日本海フェリー', 'shinnihonkai-ferry', 'https://www.snf.jp/', 'https://www.snf.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Taiheiyo Ferry', '太平洋フェリー', 'taiheiyo-ferry', 'https://www.taiheiyo-ferry.co.jp/', 'https://www.taiheiyo-ferry.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('MOL Sunflower', '商船三井さんふらわあ', 'mol-sunflower-hokkaido', 'https://www.sunflower.co.jp/', 'https://www.sunflower.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Ocean Tokyu Ferry', 'オーシャン東九フェリー', 'ocean-tokyu-ferry', 'https://www.otf.jp/', 'https://www.otf.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Tokyo Kyusyu Ferry', '東京九州フェリー', 'tokyo-kyusyu-ferry', 'https://tqf.co.jp/', 'https://tqf.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Meimon Taiyo Ferry', '名門大洋フェリー', 'meimon-taiyo-ferry', 'https://www.cityline.co.jp/', 'https://www.cityline.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Hankyu Ferry', '阪九フェリー', 'hankyu-ferry', 'https://www.han9f.co.jp/', 'https://www.han9f.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Ferry Sunflower', 'フェリーさんふらわあ', 'mol-sunflower', 'https://www.ferry-sunflower.co.jp/', 'https://www.ferry-sunflower.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Miyazaki Car Ferry', '宮崎カーフェリー', 'miyazaki-car-ferry', 'https://www.miyazakicarferry.com/', 'https://www.miyazakicarferry.com/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Orange Ferry', 'オレンジフェリー', 'orange-ferry', 'https://www.orange-ferry.co.jp/', 'https://www.orange-ferry.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Jumbo Ferry', 'ジャンボフェリー', 'jumbo-ferry', 'https://ferry.co.jp/', 'https://ferry.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Tsugaru Kaikyo Ferry', '津軽海峡フェリー', 'tsugarukaikyo-ferry', 'https://www.tsugarukaikyo.co.jp/', 'https://www.tsugarukaikyo.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Seikan Ferry', '青函フェリー', 'seikan-ferry', 'https://www.seikan-ferry.co.jp/', 'https://www.seikan-ferry.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Heart Land Ferry', 'ハートランドフェリー', 'heartland-ferry', 'https://heartlandferry.jp/', 'https://heartlandferry.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('A-Line Ferry', 'マルエーフェリー', 'aline-ferry', 'https://www.aline-ferry.com/', 'https://www.aline-ferry.com/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Marix Line', 'マリックスライン', 'marix-line', 'https://marixline.com/', 'https://marixline.com/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('PanStar / Sunstar Line', 'サンスターライン', 'panstar', 'https://panstar.jp/', 'https://panstar.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Camellia Line', 'カメリアライン', 'camellia-line', 'https://www.camellia-line.co.jp/', 'https://www.camellia-line.co.jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Kampu Ferry', '関釜フェリー', 'kampu-ferry', 'https://www.kampuferry.co.jp/', 'https://www.kampuferry.co.jp/yoyaku/jp/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Yaima Line', '商船やいま', 'yaima-line', 'https://yaimaline.com/', 'https://yaimaline.com/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('China-Japan International Ferry', '日中国際フェリー', 'china-japan-ferry', 'https://www.shinganjin.com/', 'https://www.shinganjin.com/', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

-- ------------------------------------------------
-- Ferry routes
-- ------------------------------------------------

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '舞鶴～小樽',
    '舞鶴港', '京都府',
    '小樽港', '北海道',
    1260,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.snf.jp/', 'https://www.snf.jp/', '主要長距離フェリー航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'shinnihonkai-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '舞鶴港'
        AND r.`arrival_port` = '小樽港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '敦賀～苫小牧東',
    '敦賀港', '福井県',
    '苫小牧東港', '北海道',
    1260,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.snf.jp/', 'https://www.snf.jp/', '主要長距離フェリー航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'shinnihonkai-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '敦賀港'
        AND r.`arrival_port` = '苫小牧東港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '新潟～小樽',
    '新潟港', '新潟県',
    '小樽港', '北海道',
    960,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.snf.jp/', 'https://www.snf.jp/', '主要長距離フェリー航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'shinnihonkai-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '新潟港'
        AND r.`arrival_port` = '小樽港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '敦賀～新潟～秋田～苫小牧東',
    '敦賀港', '福井県',
    '苫小牧東港', '北海道',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.snf.jp/', 'https://www.snf.jp/', '寄港便。所要時間は便により異なる', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'shinnihonkai-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '敦賀港'
        AND r.`arrival_port` = '苫小牧東港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '名古屋～仙台～苫小牧',
    '名古屋港', '愛知県',
    '苫小牧港', '北海道',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.taiheiyo-ferry.co.jp/', 'https://www.taiheiyo-ferry.co.jp/', '仙台寄港', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'taiheiyo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '名古屋港'
        AND r.`arrival_port` = '苫小牧港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大洗～苫小牧',
    '大洗港', '茨城県',
    '苫小牧港', '北海道',
    1080,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.sunflower.co.jp/', 'https://www.sunflower.co.jp/', '夕方便・深夜便あり', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'mol-sunflower-hokkaido'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大洗港'
        AND r.`arrival_port` = '苫小牧港'
  );

-- Keep the Hokkaido route on MOL Sunflower's dedicated official site when
-- this re-runnable seed is applied to an existing database.
UPDATE `ferry_routes` r
INNER JOIN `ferry_companies` c ON c.`id` = r.`company_id`
SET r.`reservation_url` = 'https://www.sunflower.co.jp/',
    r.`timetable_url` = 'https://www.sunflower.co.jp/'
WHERE c.`slug` = 'mol-sunflower-hokkaido'
  AND r.`departure_port` = '大洗港'
  AND r.`arrival_port` = '苫小牧港';

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '東京～徳島～新門司',
    '東京港', '東京都',
    '新門司港', '福岡県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.otf.jp/', 'https://www.otf.jp/', '徳島寄港', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'ocean-tokyu-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '東京港'
        AND r.`arrival_port` = '新門司港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '横須賀～新門司',
    '横須賀港', '神奈川県',
    '新門司港', '福岡県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://tqf.co.jp/', 'https://tqf.co.jp/', '主要長距離フェリー航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'tokyo-kyusyu-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '横須賀港'
        AND r.`arrival_port` = '新門司港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大阪南港～新門司',
    '大阪南港', '大阪府',
    '新門司港', '福岡県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.cityline.co.jp/', 'https://www.cityline.co.jp/', '1便・2便あり', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'meimon-taiyo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大阪南港'
        AND r.`arrival_port` = '新門司港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '泉大津～新門司',
    '泉大津港', '大阪府',
    '新門司港', '福岡県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.han9f.co.jp/', 'https://www.han9f.co.jp/', '主要長距離フェリー航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'hankyu-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '泉大津港'
        AND r.`arrival_port` = '新門司港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '神戸～新門司',
    '神戸港', '兵庫県',
    '新門司港', '福岡県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.han9f.co.jp/', 'https://www.han9f.co.jp/', '主要長距離フェリー航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'hankyu-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '神戸港'
        AND r.`arrival_port` = '新門司港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大阪～別府',
    '大阪南港', '大阪府',
    '別府港', '大分県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.ferry-sunflower.co.jp/', 'https://www.ferry-sunflower.co.jp/', '夜行便', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'mol-sunflower'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大阪南港'
        AND r.`arrival_port` = '別府港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '神戸～大分',
    '神戸港', '兵庫県',
    '大分港', '大分県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.ferry-sunflower.co.jp/', 'https://www.ferry-sunflower.co.jp/', '夜行便', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'mol-sunflower'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '神戸港'
        AND r.`arrival_port` = '大分港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大阪～志布志',
    '大阪南港', '大阪府',
    '志布志港', '鹿児島県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.ferry-sunflower.co.jp/', 'https://www.ferry-sunflower.co.jp/', '夜行便', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'mol-sunflower'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大阪南港'
        AND r.`arrival_port` = '志布志港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '神戸～宮崎',
    '神戸港', '兵庫県',
    '宮崎港', '宮崎県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.miyazakicarferry.com/', 'https://www.miyazakicarferry.com/', '夜行便', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'miyazaki-car-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '神戸港'
        AND r.`arrival_port` = '宮崎港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大阪南港～東予',
    '大阪南港', '大阪府',
    '東予港', '愛媛県',
    480,
    7700, 'JPY', '2026-08-22',
    1, 1,
    'https://www.orange-ferry.co.jp/', 'https://www.orange-ferry.co.jp/', '2026年7～9月C期間の最安客室運賃を参考値として登録', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'orange-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大阪南港'
        AND r.`arrival_port` = '東予港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '神戸～高松',
    '神戸港', '兵庫県',
    '高松港', '香川県',
    NULL,
    NULL, 'JPY', NULL,
    1, 0,
    'https://ferry.co.jp/', 'https://ferry.co.jp/', '瀬戸内主要航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'jumbo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '神戸港'
        AND r.`arrival_port` = '高松港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '神戸～小豆島',
    '神戸港', '兵庫県',
    '坂手港（小豆島）', '香川県',
    NULL,
    NULL, 'JPY', NULL,
    1, 0,
    'https://ferry.co.jp/', 'https://ferry.co.jp/', '高松経由便を含む', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'jumbo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '神戸港'
        AND r.`arrival_port` = '坂手港（小豆島）'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '青森～函館',
    '青森港', '青森県',
    '函館港', '北海道',
    220,
    NULL, 'JPY', NULL,
    1, 0,
    'https://www.tsugarukaikyo.co.jp/', 'https://www.tsugarukaikyo.co.jp/service/timetable/hakodate-aomori/', '通常ダイヤは片道約3時間40分', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'tsugarukaikyo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '青森港'
        AND r.`arrival_port` = '函館港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大間～函館',
    '大間港', '青森県',
    '函館港', '北海道',
    90,
    NULL, 'JPY', NULL,
    1, 0,
    'https://www.tsugarukaikyo.co.jp/', 'https://www.tsugarukaikyo.co.jp/service/timetable/hakodate-oma/', '片道約90分', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'tsugarukaikyo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大間港'
        AND r.`arrival_port` = '函館港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '青森～室蘭',
    '青森港', '青森県',
    '室蘭港', '北海道',
    420,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.tsugarukaikyo.co.jp/', 'https://www.tsugarukaikyo.co.jp/service/timetable/muroran-aomori/', '片道約7時間', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'tsugarukaikyo-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '青森港'
        AND r.`arrival_port` = '室蘭港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '青森～函館',
    '青森港', '青森県',
    '函館港', '北海道',
    NULL,
    NULL, 'JPY', NULL,
    1, 0,
    'https://www.seikan-ferry.co.jp/', 'https://www.seikan-ferry.co.jp/', '青函フェリー', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'seikan-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '青森港'
        AND r.`arrival_port` = '函館港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '稚内～利尻（鴛泊）',
    '稚内港', '北海道',
    '鴛泊港（利尻島）', '北海道',
    NULL,
    NULL, 'JPY', NULL,
    1, 0,
    'https://heartlandferry.jp/', 'https://heartlandferry.jp/', '離島航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'heartland-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '稚内港'
        AND r.`arrival_port` = '鴛泊港（利尻島）'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '稚内～礼文（香深）',
    '稚内港', '北海道',
    '香深港（礼文島）', '北海道',
    NULL,
    NULL, 'JPY', NULL,
    1, 0,
    'https://heartlandferry.jp/', 'https://heartlandferry.jp/', '離島航路', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'heartland-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '稚内港'
        AND r.`arrival_port` = '香深港（礼文島）'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '鹿児島～奄美～沖縄',
    '鹿児島新港', '鹿児島県',
    '那覇港', '沖縄県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.aline-ferry.com/', 'https://www.aline-ferry.com/', '奄美群島各港に寄港', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'aline-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '鹿児島新港'
        AND r.`arrival_port` = '那覇港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '鹿児島～奄美～沖縄',
    '鹿児島新港', '鹿児島県',
    '那覇港', '沖縄県',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://marixline.com/', 'https://marixline.com/', '奄美群島各港に寄港', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'marix-line'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '鹿児島新港'
        AND r.`arrival_port` = '那覇港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大阪～釜山',
    '大阪港国際フェリーターミナル', '大阪府',
    '釜山港国際旅客ターミナル', '韓国',
    1020,
    NULL, 'JPY', NULL,
    1, 1,
    'https://panstar.jp/', 'https://panstar.jp/', 'PANSTAR MIRACLE。2026年5月17日以降、大阪発 月・水・金 17:00→翌10:00', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'panstar'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大阪港国際フェリーターミナル'
        AND r.`arrival_port` = '釜山港国際旅客ターミナル'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '博多～釜山',
    '博多港国際ターミナル', '福岡県',
    '釜山港国際旅客ターミナル', '韓国',
    NULL,
    NULL, 'JPY', NULL,
    1, 1,
    'https://www.camellia-line.co.jp/', 'https://www.camellia-line.co.jp/', 'ニューかめりあ。2026年も定期運航', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'camellia-line'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '博多港国際ターミナル'
        AND r.`arrival_port` = '釜山港国際旅客ターミナル'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '下関～釜山',
    '下関港国際ターミナル', '山口県',
    '釜山港国際旅客ターミナル', '韓国',
    735,
    6000, 'JPY', '2026-08-22',
    1, 1,
    'https://www.kampuferry.co.jp/yoyaku/jp/', 'https://www.kampuferry.co.jp/passenger_info/schedule/', '毎日1便。最安値は2026年運賃表の特別区分2等6,000円。諸税・燃油等別途', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'kampu-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '下関港国際ターミナル'
        AND r.`arrival_port` = '釜山港国際旅客ターミナル'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '石垣～基隆',
    '石垣港国際ターミナル', '沖縄県',
    '基隆港', '台湾',
    NULL,
    NULL, 'JPY', NULL,
    0, 1,
    'https://yaimaline.com/', 'https://yaimaline.com/', '2026年運航開始の国際旅客フェリー。天候による変更・欠航あり', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'yaima-line'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '石垣港国際ターミナル'
        AND r.`arrival_port` = '基隆港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '大阪～上海',
    '大阪港', '大阪府',
    '上海港', '中国',
    NULL,
    NULL, 'JPY', NULL,
    0, 1,
    'https://www.shinganjin.com/', 'https://www.shinganjin.com/schedule/', '鑑真号。2026年8月も旅客便あり。神戸・大阪を交互に出港', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'china-japan-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '大阪港'
        AND r.`arrival_port` = '上海港'
  );

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`,
     `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`,
    '神戸～上海',
    '神戸港', '兵庫県',
    '上海港', '中国',
    NULL,
    NULL, 'JPY', NULL,
    0, 1,
    'https://www.shinganjin.com/', 'https://www.shinganjin.com/schedule/', '鑑真号。2026年8月も旅客便あり。神戸・大阪を交互に出港', 1
FROM `ferry_companies` c
WHERE c.`slug` = 'china-japan-ferry'
  AND NOT EXISTS (
      SELECT 1
      FROM `ferry_routes` r
      WHERE r.`company_id` = c.`id`
        AND r.`departure_port` = '神戸港'
        AND r.`arrival_port` = '上海港'
  );

COMMIT;

-- Seed summary:
-- Companies: 21
-- Routes: 32


-- ==================== MOL SUNFLOWER URL FIX ====================
-- Correct MOL Sunflower's Hokkaido route URLs in databases seeded previously.
START TRANSACTION;

UPDATE `ferry_companies`
SET `official_url` = 'https://www.sunflower.co.jp/',
    `reservation_url` = 'https://www.sunflower.co.jp/'
WHERE `slug` = 'mol-sunflower-hokkaido';

UPDATE `ferry_routes` r
SET r.`reservation_url` = 'https://www.sunflower.co.jp/',
    r.`timetable_url` = 'https://www.sunflower.co.jp/'
WHERE (
      (r.`departure_port` = '大洗港' AND r.`arrival_port` = '苫小牧港')
      OR
      (r.`departure_port` = '苫小牧港' AND r.`arrival_port` = '大洗港')
  );

COMMIT;


-- ==================== SETOUCHI ROUTE ADDITIONS ====================
-- Travel Compass
-- Add Setouchi and Shikoku-Kyushu ferry companies/routes.
-- Re-runnable: companies use unique slugs and routes use NOT EXISTS.
START TRANSACTION;

INSERT INTO `ferry_companies`
    (`name`, `name_ja`, `slug`, `official_url`, `reservation_url`, `active`)
VALUES
    ('Setonaikai Kisen', '瀬戸内海汽船', 'setonaikai-kisen', 'https://setonaikaikisen.co.jp/', NULL, 1),
    ('Uwajima Unyu Ferry', '宇和島運輸フェリー', 'uwajima-unyu-ferry', 'https://www.uwajimaunyu.co.jp/', NULL, 1),
    ('Koku94 Ferry', '国道九四フェリー', 'koku94-ferry', 'https://www.koku94.jp/', 'https://reservation.koku94.jp/webyoyaku/WY/WYG0410.aspx', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `name_ja` = VALUES(`name_ja`),
    `official_url` = VALUES(`official_url`),
    `reservation_url` = VALUES(`reservation_url`),
    `active` = 1;

INSERT INTO `ferry_routes`
    (`company_id`, `route_name`,
     `departure_port`, `departure_prefecture`,
     `arrival_port`, `arrival_prefecture`,
     `duration_minutes`, `fare_from`, `fare_currency`, `fare_updated_at`,
     `vehicle_available`, `overnight`,
     `reservation_url`, `timetable_url`, `notes`, `active`)
SELECT
    c.`id`, d.`route_name`,
    d.`departure_port`, d.`departure_prefecture`,
    d.`arrival_port`, d.`arrival_prefecture`,
    d.`duration_minutes`, d.`fare_from`, 'JPY', d.`fare_updated_at`,
    1, d.`overnight`, d.`reservation_url`, d.`timetable_url`, d.`notes`, 1
FROM (
    SELECT 'setonaikai-kisen' AS company_slug, '広島～松山' AS route_name,
           '広島港' AS departure_port, '広島県' AS departure_prefecture,
           '松山観光港' AS arrival_port, '愛媛県' AS arrival_prefecture,
           162 AS duration_minutes, 5800 AS fare_from, DATE('2026-08-22') AS fare_updated_at,
           0 AS overnight, NULL AS reservation_url,
           'https://setonaikaikisen.co.jp/kouro/cruise/' AS timetable_url,
           'クルーズフェリー' AS notes
    UNION ALL SELECT 'setonaikai-kisen', '松山～広島', '松山観光港', '愛媛県', '広島港', '広島県',
           162, 5800, DATE('2026-08-22'), 0, NULL, 'https://setonaikaikisen.co.jp/kouro/cruise/', 'クルーズフェリー'
    UNION ALL SELECT 'setonaikai-kisen', '呉～松山', '呉港', '広島県', '松山観光港', '愛媛県',
           117, 4700, DATE('2026-08-22'), 0, NULL, 'https://setonaikaikisen.co.jp/kouro/cruise/', 'クルーズフェリー'
    UNION ALL SELECT 'setonaikai-kisen', '松山～呉', '松山観光港', '愛媛県', '呉港', '広島県',
           117, 4700, DATE('2026-08-22'), 0, NULL, 'https://setonaikaikisen.co.jp/kouro/cruise/', 'クルーズフェリー'
    UNION ALL SELECT 'uwajima-unyu-ferry', '八幡浜～別府', '八幡浜港', '愛媛県', '別府港', '大分県',
           170, 4900, DATE('2026-08-22'), 1, NULL, 'https://www.uwajimaunyu.co.jp/timetable/', NULL
    UNION ALL SELECT 'uwajima-unyu-ferry', '別府～八幡浜', '別府港', '大分県', '八幡浜港', '愛媛県',
           170, 4900, DATE('2026-08-22'), 1, NULL, 'https://www.uwajimaunyu.co.jp/timetable/', NULL
    UNION ALL SELECT 'uwajima-unyu-ferry', '八幡浜～臼杵', '八幡浜港', '愛媛県', '臼杵港', '大分県',
           145, 3800, DATE('2026-08-22'), 1, NULL, 'https://www.uwajimaunyu.co.jp/timetable/', NULL
    UNION ALL SELECT 'uwajima-unyu-ferry', '臼杵～八幡浜', '臼杵港', '大分県', '八幡浜港', '愛媛県',
           145, 3800, DATE('2026-08-22'), 1, NULL, 'https://www.uwajimaunyu.co.jp/timetable/', NULL
    UNION ALL SELECT 'koku94-ferry', '三崎～佐賀関', '三崎港', '愛媛県', '佐賀関港', '大分県',
           70, NULL, NULL, 0, 'https://reservation.koku94.jp/webyoyaku/WY/WYG0410.aspx', 'https://www.koku94.jp/operation', NULL
    UNION ALL SELECT 'koku94-ferry', '佐賀関～三崎', '佐賀関港', '大分県', '三崎港', '愛媛県',
           70, NULL, NULL, 0, 'https://reservation.koku94.jp/webyoyaku/WY/WYG0410.aspx', 'https://www.koku94.jp/operation', NULL
) AS d
INNER JOIN `ferry_companies` c ON c.`slug` = d.`company_slug`
WHERE NOT EXISTS (
    SELECT 1
    FROM `ferry_routes` r
    WHERE r.`company_id` = c.`id`
      AND r.`departure_port` = d.`departure_port`
      AND r.`arrival_port` = d.`arrival_port`
);

COMMIT;
