-- ================================================================
-- Travel Compass - Airlines Consolidated Fix Migration
-- Date: 2026-08-22
--
-- Execute this file alone for the complete airline migration.
-- This file consolidates:
--   * Japanese major airlines
--   * Japan-serving LCCs
--   * Hybrid carriers
--   * STARLUX Airlines
--   * Major-airline 2026 corrections/additions
--   * New/rebranded airlines discussed for 2026
--
-- Prerequisite:
--   Existing `airlines` table with UNIQUE `iata_code`.
--
-- This migration is designed to be re-runnable.
-- ================================================================

-- ------------------------------------------------
-- 1. Add classification columns only when missing
-- ------------------------------------------------

SET @db_name = DATABASE();

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `airlines` ADD COLUMN `is_lcc` TINYINT(1) NOT NULL DEFAULT 0 AFTER `official_url`',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'airlines'
      AND COLUMN_NAME = 'is_lcc'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `airlines` ADD COLUMN `carrier_type` VARCHAR(20) NOT NULL DEFAULT ''full_service'' AFTER `is_lcc`',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'airlines'
      AND COLUMN_NAME = 'carrier_type'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Existing LCC rows inherit carrier_type=lcc before final UPSERT.
UPDATE `airlines`
SET `carrier_type` = 'lcc'
WHERE `is_lcc` = 1;

-- ------------------------------------------------
-- 2. Consolidated airline UPSERT
-- ------------------------------------------------

INSERT INTO `airlines`
    (`iata_code`, `icao_code`, `name`, `alliance`,
     `ffp_name`, `ffp_currency`, `official_url`,
     `active`, `is_lcc`, `carrier_type`)
VALUES
('JL', 'JAL', 'Japan Airlines', 'ow', 'JAL Mileage Bank', 'miles', 'https://www.jal.co.jp/', 1, 0, 'full_service'),
('NH', 'ANA', 'All Nippon Airways', 'sa', NULL, NULL, 'https://www.ana.co.jp/', 1, 0, 'full_service'),
('BC', NULL, 'Skymark Airlines', NULL, NULL, NULL, 'https://www.skymark.co.jp/', 1, 0, 'full_service'),
('7G', NULL, 'Star Flyer', NULL, NULL, NULL, 'https://www.starflyer.jp/', 1, 0, 'full_service'),
('HD', NULL, 'AIRDO', NULL, NULL, NULL, 'https://www.airdo.jp/', 1, 0, 'full_service'),
('6J', NULL, 'Solaseed Air', NULL, NULL, NULL, 'https://www.solaseedair.jp/', 1, 0, 'full_service'),
('JH', NULL, 'Fuji Dream Airlines', NULL, NULL, NULL, 'https://www.fujidream.co.jp/', 1, 0, 'full_service'),
('NU', NULL, 'Japan Transocean Air', NULL, NULL, NULL, 'https://jta-okinawa.com/', 1, 0, 'full_service'),
('OC', NULL, 'Oriental Air Bridge', NULL, NULL, NULL, 'https://www.orc-air.co.jp/', 1, 0, 'full_service'),
('MM', NULL, 'Peach Aviation', NULL, NULL, NULL, 'https://www.flypeach.com/', 1, 1, 'lcc'),
('GK', NULL, 'Jetstar Japan', NULL, NULL, NULL, 'https://www.jetstar.com/jp/ja/', 1, 1, 'lcc'),
('IJ', NULL, 'SPRING JAPAN', NULL, NULL, NULL, 'https://jp.ch.com/', 1, 1, 'lcc'),
('ZG', NULL, 'ZIPAIR Tokyo', NULL, NULL, NULL, 'https://www.zipair.net/', 1, 1, 'lcc'),
('NQ', NULL, 'AirJapan', NULL, NULL, NULL, 'https://www.flyairjapan.com/', 0, 0, 'hybrid'),
('7C', NULL, 'Jeju Air', NULL, NULL, NULL, 'https://www.jejuair.net/', 1, 1, 'lcc'),
('LJ', NULL, 'Jin Air', NULL, NULL, NULL, 'https://www.jinair.com/', 1, 1, 'lcc'),
('BX', NULL, 'Air Busan', NULL, NULL, NULL, 'https://www.airbusan.com/', 1, 1, 'lcc'),
('RS', NULL, 'Air Seoul', NULL, NULL, NULL, 'https://flyairseoul.com/', 1, 1, 'lcc'),
('TW', NULL, 'T''way Air', NULL, NULL, NULL, 'https://www.twayair.com/', 1, 1, 'lcc'),
('ZE', NULL, 'Eastar Jet', NULL, NULL, NULL, 'https://www.eastarjet.com/', 1, 1, 'lcc'),
('RF', NULL, 'Aero K', NULL, NULL, NULL, 'https://www.aerok.com/', 1, 1, 'lcc'),
('YP', 'APZ', 'Air Premia', NULL, NULL, NULL, 'https://www.airpremia.com/', 1, 0, 'hybrid'),
('WE', 'PTA', 'Parata Air', NULL, NULL, NULL, 'https://www.parataair.com/', 1, 0, 'hybrid'),
('JX', 'SJX', 'STARLUX Airlines', NULL, NULL, NULL, 'https://www.starlux-airlines.com/ja-JP', 1, 0, 'full_service'),
('UO', NULL, 'HK Express', NULL, NULL, NULL, 'https://www.hkexpress.com/', 1, 1, 'lcc'),
('HB', NULL, 'Greater Bay Airlines', NULL, NULL, NULL, 'https://www.greaterbay-airlines.com/', 1, 1, 'lcc'),
('9C', NULL, 'Spring Airlines', NULL, NULL, NULL, 'https://en.ch.com/', 1, 1, 'lcc'),
('IT', NULL, 'Tigerair Taiwan', NULL, NULL, NULL, 'https://www.tigerairtw.com/', 1, 1, 'lcc'),
('3U', 'CSC', 'Sichuan Airlines', NULL, NULL, NULL, 'https://www.sichuanair.com/', 1, 0, 'full_service'),
('NX', 'AMU', 'Air Macau', NULL, NULL, NULL, 'https://www.airmacau.com.mo/', 1, 0, 'full_service'),
('JD', 'CBJ', 'Beijing Capital Airlines', NULL, NULL, NULL, 'https://www.jdair.net/', 1, 0, 'full_service'),
('HX', 'CRK', 'Hong Kong Airlines', NULL, NULL, NULL, 'https://www.hongkongairlines.com/', 1, 0, 'full_service'),
('HO', 'DKH', 'Juneyao Airlines', NULL, NULL, NULL, 'https://www.juneyaoair.com/', 1, 0, 'full_service'),
('GJ', 'CDC', 'Loong Air', NULL, NULL, NULL, 'https://www.loongair.cn/', 1, 0, 'full_service'),
('QW', 'QDA', 'Qingdao Airlines', NULL, NULL, NULL, 'https://www.qdairlines.com/', 1, 0, 'full_service'),
('SC', 'CDG', 'Shandong Airlines', NULL, NULL, NULL, 'https://www.sda.cn/', 1, 0, 'full_service'),
('FM', 'CSH', 'Shanghai Airlines', NULL, 'Eastern Miles', 'points', 'https://www.ceair.com/', 1, 0, 'full_service'),
('GS', 'GCR', 'Tianjin Airlines', NULL, NULL, NULL, 'https://www.tianjin-air.com/', 1, 0, 'full_service'),
('KN', 'CUA', 'China United Airlines', NULL, NULL, NULL, NULL, 1, 1, 'lcc'),
('5J', NULL, 'Cebu Pacific', NULL, NULL, NULL, 'https://www.cebupacificair.com/', 1, 1, 'lcc'),
('Z2', NULL, 'Philippines AirAsia', NULL, NULL, NULL, 'https://www.airasia.com/', 1, 1, 'lcc'),
('TR', NULL, 'Scoot', NULL, NULL, NULL, 'https://www.flyscoot.com/', 1, 1, 'lcc'),
('XJ', NULL, 'Thai AirAsia X', NULL, NULL, NULL, 'https://www.airasia.com/', 1, 1, 'lcc'),
('FD', NULL, 'Thai AirAsia', NULL, NULL, NULL, 'https://www.airasia.com/', 1, 1, 'lcc'),
('VZ', NULL, 'Thai VietJet Air', NULL, NULL, NULL, 'https://www.vietjetair.com/', 1, 1, 'lcc'),
('SL', NULL, 'Thai Lion Air', NULL, NULL, NULL, 'https://www.lionairthai.com/', 1, 1, 'lcc'),
('VJ', NULL, 'VietJet Air', NULL, NULL, NULL, 'https://www.vietjetair.com/', 1, 1, 'lcc'),
('AK', 'AXM', 'AirAsia', NULL, NULL, NULL, 'https://www.airasia.com/', 1, 1, 'lcc'),
('D7', 'XAX', 'AirAsia X', NULL, NULL, NULL, 'https://www.airasia.com/', 1, 1, 'lcc'),
('JQ', 'JST', 'Jetstar Airways', NULL, NULL, NULL, 'https://www.jetstar.com/', 1, 1, 'lcc'),
('OD', 'MXD', 'Batik Air Malaysia', NULL, NULL, NULL, 'https://www.batikair.com.my/', 1, 0, 'full_service'),
('PG', 'BKP', 'Bangkok Airways', NULL, 'FlyerBonus', 'points', 'https://www.bangkokair.com/', 1, 0, 'full_service'),
('PR', 'PAL', 'Philippine Airlines', NULL, 'Mabuhay Miles', 'miles', 'https://www.philippineairlines.com/', 1, 0, 'full_service'),
('OM', 'MGL', 'MIAT Mongolian Airlines', NULL, NULL, NULL, 'https://www.miat.com/', 1, 0, 'full_service'),
('WY', 'OMA', 'Oman Air', 'ow', 'Sindbad', 'miles', 'https://www.omanair.com/', 1, 0, 'full_service'),
('AT', 'RAM', 'Royal Air Maroc', 'ow', 'Safar Flyer', 'miles', 'https://www.royalairmaroc.com/', 1, 0, 'full_service'),
('RX', NULL, 'Riyadh Air', NULL, NULL, NULL, 'https://www.riyadhair.com/', 1, 0, 'full_service'),
('MY', 'MWG', 'AirBorneo', NULL, NULL, NULL, 'https://www.airborneo.com/en', 1, 0, 'full_service'),
('KJ', 'AIH', 'AIRZETA', NULL, NULL, NULL, 'https://www.airzetacargo.com/', 1, 0, 'full_service')
ON DUPLICATE KEY UPDATE
    `icao_code` = COALESCE(VALUES(`icao_code`), `icao_code`),
    `name` = VALUES(`name`),
    `alliance` = COALESCE(VALUES(`alliance`), `alliance`),
    `ffp_name` = COALESCE(VALUES(`ffp_name`), `ffp_name`),
    `ffp_currency` = COALESCE(VALUES(`ffp_currency`), `ffp_currency`),
    `official_url` = COALESCE(VALUES(`official_url`), `official_url`),
    `active` = VALUES(`active`),
    `is_lcc` = VALUES(`is_lcc`),
    `carrier_type` = VALUES(`carrier_type`);

-- ------------------------------------------------
-- 3. 2026 master corrections
-- ------------------------------------------------

-- ITA Airways: replace legacy Alitalia-era metadata.
UPDATE `airlines`
SET
    `name` = 'ITA Airways',
    `alliance` = 'sa',
    `ffp_name` = 'Volare',
    `ffp_currency` = 'points',
    `official_url` = 'https://www.ita-airways.com/',
    `active` = 1,
    `is_lcc` = 0,
    `carrier_type` = 'full_service'
WHERE `iata_code` = 'AZ';

-- Alaska / Hawaiian loyalty and alliance updates discussed for 2026.
UPDATE `airlines`
SET
    `alliance` = 'ow',
    `ffp_name` = 'Atmos Rewards',
    `ffp_currency` = 'points',
    `active` = 1,
    `is_lcc` = 0,
    `carrier_type` = 'full_service'
WHERE `iata_code` IN ('AS', 'HA');

-- China Southern is not treated as a SkyTeam member.
UPDATE `airlines`
SET `alliance` = NULL
WHERE `iata_code` = 'CZ';

-- Suspended alliance memberships are not displayed as active memberships.
UPDATE `airlines`
SET `alliance` = NULL
WHERE `iata_code` IN ('SU', 'S7');

-- Philippine Airlines: retain as non-alliance until full membership is effective.
UPDATE `airlines`
SET `alliance` = NULL
WHERE `iata_code` = 'PR';

-- ------------------------------------------------
-- 4. Final sanity normalization
-- ------------------------------------------------

UPDATE `airlines`
SET `iata_code` = UPPER(`iata_code`)
WHERE `iata_code` IS NOT NULL;

UPDATE `airlines`
SET `icao_code` = UPPER(`icao_code`)
WHERE `icao_code` IS NOT NULL;

-- End of consolidated migration.