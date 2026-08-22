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
