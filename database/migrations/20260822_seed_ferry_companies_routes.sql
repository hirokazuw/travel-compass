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
