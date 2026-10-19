USE `pup_pick`;

START TRANSACTION;

INSERT IGNORE INTO `users`
    (`full_name`, `student_number`, `email`, `password_hash`, `role`)
VALUES
    ('Demo User 01', 'DEMO-0001', 'demo01@example.test', '$2y$10$W4wBByGWkZjlkzPaLOqpE.tjT6j4CXxHCfRDJPsg.mtdKbSUdzdzK', 'student'),
    ('Demo User 02', 'DEMO-0002', 'demo02@example.test', '$2y$10$W4wBByGWkZjlkzPaLOqpE.tjT6j4CXxHCfRDJPsg.mtdKbSUdzdzK', 'student'),
    ('Demo User 03', 'DEMO-0003', 'demo03@example.test', '$2y$10$W4wBByGWkZjlkzPaLOqpE.tjT6j4CXxHCfRDJPsg.mtdKbSUdzdzK', 'student'),
    ('Demo User 04', 'DEMO-0004', 'demo04@example.test', '$2y$10$W4wBByGWkZjlkzPaLOqpE.tjT6j4CXxHCfRDJPsg.mtdKbSUdzdzK', 'student'),
    ('Demo User 05', 'DEMO-0005', 'demo05@example.test', '$2y$10$W4wBByGWkZjlkzPaLOqpE.tjT6j4CXxHCfRDJPsg.mtdKbSUdzdzK', 'student'),
    ('Demo User 06', 'DEMO-0006', 'demo06@example.test', '$2y$10$W4wBByGWkZjlkzPaLOqpE.tjT6j4CXxHCfRDJPsg.mtdKbSUdzdzK', 'student');

INSERT INTO `lost_items`
    (`user_id`, `item_name`, `category`, `description`, `color`, `lost_location`, `date_lost`, `status`)
SELECT u.`user_id`, d.`item_name`, d.`category`, d.`description`, d.`color`, d.`location`, d.`item_date`, d.`status`
FROM (
    SELECT 'demo01@example.test' AS email, 'Demo Lost 01 - Blue notebook' AS item_name, 'Books / School supplies' AS category, 'DEMO SEED: Blue notebook with a plain cover and handwritten lecture notes.' AS description, 'Blue' AS color, 'Main library' AS location, '2026-10-01' AS item_date, 'open' AS status
    UNION ALL SELECT 'demo02@example.test', 'Demo Lost 02 - Black umbrella', 'Accessories', 'DEMO SEED: Compact black folding umbrella with a curved handle.', 'Black', 'Main building lobby', '2026-10-02', 'open'
    UNION ALL SELECT 'demo03@example.test', 'Demo Lost 03 - Silver water bottle', 'Other', 'DEMO SEED: Silver metal water bottle with a small sticker near the bottom.', 'Silver', 'Canteen', '2026-10-03', 'open'
    UNION ALL SELECT 'demo04@example.test', 'Demo Lost 04 - Red wallet', 'Wallet / ID', 'DEMO SEED: Small red wallet with several cards and a zipper pocket.', 'Red', 'Gym entrance', '2026-10-04', 'open'
    UNION ALL SELECT 'demo05@example.test', 'Demo Lost 05 - Black headphones', 'Electronics', 'DEMO SEED: Over-ear black headphones in a soft carrying pouch.', 'Black', 'Library second floor', '2026-10-05', 'open'
    UNION ALL SELECT 'demo06@example.test', 'Demo Lost 06 - Green pencil case', 'Books / School supplies', 'DEMO SEED: Green fabric pencil case containing pens and pencils.', 'Green', 'Room 204', '2026-10-06', 'open'
    UNION ALL SELECT 'demo01@example.test', 'Demo Lost 07 - Gray jacket', 'Clothing', 'DEMO SEED: Lightweight gray jacket with two front pockets.', 'Gray', 'Auditorium', '2026-10-07', 'open'
    UNION ALL SELECT 'demo02@example.test', 'Demo Lost 08 - House keys', 'Keys', 'DEMO SEED: Two silver keys attached to a round blue keychain.', 'Silver', 'Parking area', '2026-10-08', 'open'
    UNION ALL SELECT 'demo03@example.test', 'Demo Lost 09 - Brown backpack', 'Bag', 'DEMO SEED: Brown backpack with a water bottle pocket on each side.', 'Brown', 'Computer laboratory', '2026-10-09', 'open'
    UNION ALL SELECT 'demo04@example.test', 'Demo Lost 10 - USB drive', 'Electronics', 'DEMO SEED: Small black USB flash drive with a metal cap.', 'Black', 'Room 310', '2026-10-10', 'open'
    UNION ALL SELECT 'demo05@example.test', 'Demo Lost 11 - Yellow ID holder', 'Wallet / ID', 'DEMO SEED: Yellow plastic ID holder on a short lanyard.', 'Yellow', 'Registrar hallway', '2026-10-11', 'open'
    UNION ALL SELECT 'demo06@example.test', 'Demo Lost 12 - White phone charger', 'Electronics', 'DEMO SEED: White wall charger with a short charging cable.', 'White', 'Study area', '2026-10-12', 'open'
    UNION ALL SELECT 'demo01@example.test', 'Demo Lost 13 - Maroon cap', 'Clothing', 'DEMO SEED: Maroon baseball cap with a plain front panel.', 'Maroon', 'Covered court', '2026-10-13', 'open'
    UNION ALL SELECT 'demo02@example.test', 'Demo Lost 14 - Black eyeglasses', 'Accessories', 'DEMO SEED: Black rectangular eyeglasses in a hard case.', 'Black', 'Science building', '2026-10-14', 'open'
    UNION ALL SELECT 'demo03@example.test', 'Demo Lost 15 - Orange tumbler', 'Other', 'DEMO SEED: Orange insulated tumbler with a screw-on lid.', 'Orange', 'Cafeteria', '2026-10-15', 'open'
    UNION ALL SELECT 'demo04@example.test', 'Demo Lost 16 - Calculator', 'Electronics', 'DEMO SEED: Scientific calculator with a dark protective cover.', 'Black', 'Room 112', '2026-10-09', 'matched'
    UNION ALL SELECT 'demo05@example.test', 'Demo Lost 17 - Blue scarf', 'Clothing', 'DEMO SEED: Soft blue scarf folded into a small rectangle.', 'Blue', 'Auditorium seats', '2026-10-08', 'returned'
    UNION ALL SELECT 'demo06@example.test', 'Demo Lost 18 - Flash drive', 'Electronics', 'DEMO SEED: Red USB flash drive with a small metal loop.', 'Red', 'Computer laboratory', '2026-10-07', 'open'
    UNION ALL SELECT 'demo01@example.test', 'Demo Lost 19 - Sketchbook', 'Books / School supplies', 'DEMO SEED: Small sketchbook with a black spiral binding.', 'Black', 'Art room', '2026-10-06', 'open'
    UNION ALL SELECT 'demo02@example.test', 'Demo Lost 20 - Sports bottle', 'Other', 'DEMO SEED: Clear reusable sports bottle with a blue lid.', 'Clear', 'Covered court', '2026-10-05', 'open'
) AS d
JOIN `users` u ON u.`email` = d.`email`
WHERE NOT EXISTS (
    SELECT 1 FROM `lost_items` existing
    WHERE existing.`user_id` = u.`user_id` AND existing.`item_name` = d.`item_name`
);

INSERT INTO `found_items`
    (`user_id`, `item_name`, `category`, `description`, `color`, `found_location`, `date_found`, `status`)
SELECT u.`user_id`, d.`item_name`, d.`category`, d.`description`, d.`color`, d.`location`, d.`item_date`, d.`status`
FROM (
    SELECT 'demo02@example.test' AS email, 'Demo Found 01 - Blue notebook' AS item_name, 'Books / School supplies' AS category, 'DEMO SEED: Found a blue notebook with a plain cover and lecture notes.' AS description, 'Blue' AS color, 'Main library' AS location, '2026-10-01' AS item_date, 'open' AS status
    UNION ALL SELECT 'demo03@example.test', 'Demo Found 02 - Black umbrella', 'Accessories', 'DEMO SEED: Found a compact black folding umbrella with a curved handle.', 'Black', 'Main building lobby', '2026-10-02', 'open'
    UNION ALL SELECT 'demo04@example.test', 'Demo Found 03 - Silver water bottle', 'Other', 'DEMO SEED: Found a silver metal water bottle with a small sticker.', 'Silver', 'Canteen', '2026-10-03', 'open'
    UNION ALL SELECT 'demo05@example.test', 'Demo Found 04 - Red wallet', 'Wallet / ID', 'DEMO SEED: Found a small red wallet with cards and a zipper pocket.', 'Red', 'Gym entrance', '2026-10-04', 'open'
    UNION ALL SELECT 'demo06@example.test', 'Demo Found 05 - Black headphones', 'Electronics', 'DEMO SEED: Found over-ear black headphones inside a soft pouch.', 'Black', 'Library second floor', '2026-10-05', 'open'
    UNION ALL SELECT 'demo01@example.test', 'Demo Found 06 - Green pencil case', 'Books / School supplies', 'DEMO SEED: Found a green fabric pencil case with pens and pencils.', 'Green', 'Room 204', '2026-10-06', 'open'
    UNION ALL SELECT 'demo02@example.test', 'Demo Found 07 - Gray jacket', 'Clothing', 'DEMO SEED: Found a lightweight gray jacket with front pockets.', 'Gray', 'Auditorium', '2026-10-07', 'open'
    UNION ALL SELECT 'demo03@example.test', 'Demo Found 08 - House keys', 'Keys', 'DEMO SEED: Found two silver keys with a round blue keychain.', 'Silver', 'Parking area', '2026-10-08', 'open'
    UNION ALL SELECT 'demo04@example.test', 'Demo Found 09 - Brown backpack', 'Bag', 'DEMO SEED: Found a brown backpack with two side bottle pockets.', 'Brown', 'Computer laboratory', '2026-10-09', 'open'
    UNION ALL SELECT 'demo05@example.test', 'Demo Found 10 - USB drive', 'Electronics', 'DEMO SEED: Found a small black USB flash drive with a metal cap.', 'Black', 'Room 310', '2026-10-10', 'open'
    UNION ALL SELECT 'demo06@example.test', 'Demo Found 11 - Yellow ID holder', 'Wallet / ID', 'DEMO SEED: Found a yellow plastic ID holder on a short lanyard.', 'Yellow', 'Registrar hallway', '2026-10-11', 'open'
    UNION ALL SELECT 'demo01@example.test', 'Demo Found 12 - White phone charger', 'Electronics', 'DEMO SEED: Found a white wall charger with a short cable.', 'White', 'Study area', '2026-10-12', 'open'
    UNION ALL SELECT 'demo02@example.test', 'Demo Found 13 - Maroon cap', 'Clothing', 'DEMO SEED: Found a maroon baseball cap with a plain front panel.', 'Maroon', 'Covered court', '2026-10-13', 'open'
    UNION ALL SELECT 'demo03@example.test', 'Demo Found 14 - Black eyeglasses', 'Accessories', 'DEMO SEED: Found black rectangular eyeglasses in a hard case.', 'Black', 'Science building', '2026-10-14', 'open'
    UNION ALL SELECT 'demo04@example.test', 'Demo Found 15 - Orange tumbler', 'Other', 'DEMO SEED: Found an orange insulated tumbler with a screw-on lid.', 'Orange', 'Cafeteria', '2026-10-15', 'open'
    UNION ALL SELECT 'demo05@example.test', 'Demo Found 16 - Calculator', 'Electronics', 'DEMO SEED: Found a scientific calculator with a dark protective cover.', 'Black', 'Room 112', '2026-10-09', 'matched'
    UNION ALL SELECT 'demo06@example.test', 'Demo Found 17 - Blue scarf', 'Clothing', 'DEMO SEED: Found a soft blue scarf folded into a small rectangle.', 'Blue', 'Auditorium seats', '2026-10-08', 'returned'
    UNION ALL SELECT 'demo01@example.test', 'Demo Found 18 - Flash drive', 'Electronics', 'DEMO SEED: Found a red USB flash drive with a small metal loop.', 'Red', 'Computer laboratory', '2026-10-07', 'open'
    UNION ALL SELECT 'demo02@example.test', 'Demo Found 19 - Sketchbook', 'Books / School supplies', 'DEMO SEED: Found a small sketchbook with black spiral binding.', 'Black', 'Art room', '2026-10-06', 'open'
    UNION ALL SELECT 'demo03@example.test', 'Demo Found 20 - Sports bottle', 'Other', 'DEMO SEED: Found a clear reusable sports bottle with a blue lid.', 'Clear', 'Covered court', '2026-10-05', 'open'
) AS d
JOIN `users` u ON u.`email` = d.`email`
WHERE NOT EXISTS (
    SELECT 1 FROM `found_items` existing
    WHERE existing.`user_id` = u.`user_id` AND existing.`item_name` = d.`item_name`
);

INSERT INTO `match_result` (`lost_item_id`, `found_item_id`, `match_score`, `match_status`)
SELECT l.`lost_item_id`, f.`found_item_id`, d.`score`, 'pending'
FROM (
    SELECT 'Demo Lost 01 - Blue notebook' AS lost_name, 'Demo Found 01 - Blue notebook' AS found_name, 95 AS score
    UNION ALL SELECT 'Demo Lost 02 - Black umbrella', 'Demo Found 02 - Black umbrella', 92
    UNION ALL SELECT 'Demo Lost 03 - Silver water bottle', 'Demo Found 03 - Silver water bottle', 89
    UNION ALL SELECT 'Demo Lost 04 - Red wallet', 'Demo Found 04 - Red wallet', 87
) AS d
JOIN `lost_items` l ON l.`item_name` = d.`lost_name`
JOIN `found_items` f ON f.`item_name` = d.`found_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `match_result` existing
    WHERE existing.`lost_item_id` = l.`lost_item_id`
      AND existing.`found_item_id` = f.`found_item_id`
);

INSERT INTO `claims`
    (`lost_item_id`, `found_item_id`, `claimant_user_id`, `claim_message`, `status`, `reviewed_by`)
SELECT l.`lost_item_id`, f.`found_item_id`, u.`user_id`, d.`message`, d.`status`, NULL
FROM (
    SELECT 'demo01@example.test' AS email, 'Demo Lost 01 - Blue notebook' AS lost_name, 'Demo Found 01 - Blue notebook' AS found_name, '[DEMO-SEED] The cover and class notes match my notebook.' AS message, 'pending' AS status
    UNION ALL SELECT 'demo02@example.test', 'Demo Lost 02 - Black umbrella', 'Demo Found 02 - Black umbrella', '[DEMO-SEED] I can identify the handle and folding mechanism.', 'approved'
    UNION ALL SELECT 'demo03@example.test', 'Demo Lost 03 - Silver water bottle', 'Demo Found 03 - Silver water bottle', '[DEMO-SEED] I can describe the sticker on the bottle.', 'rejected'
    UNION ALL SELECT 'demo04@example.test', 'Demo Lost 04 - Red wallet', 'Demo Found 04 - Red wallet', '[DEMO-SEED] I can verify the wallet contents with the administrator.', 'completed'
    UNION ALL SELECT 'demo05@example.test', 'Demo Lost 05 - Black headphones', 'Demo Found 05 - Black headphones', '[DEMO-SEED] I can identify the headphones and carrying pouch.', 'pending'
    UNION ALL SELECT 'demo06@example.test', 'Demo Lost 06 - Green pencil case', 'Demo Found 06 - Green pencil case', '[DEMO-SEED] I can describe the pens inside the case.', 'rejected'
) AS d
JOIN `users` u ON u.`email` = d.`email`
JOIN `lost_items` l ON l.`item_name` = d.`lost_name`
JOIN `found_items` f ON f.`item_name` = d.`found_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `claims` existing
    WHERE existing.`claimant_user_id` = u.`user_id`
      AND existing.`found_item_id` = f.`found_item_id`
      AND existing.`claim_message` = d.`message`
);

INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`)
SELECT u.`user_id`, d.`title`, d.`message`, d.`type`
FROM (
    SELECT 'demo01@example.test' AS email, '[DEMO] Possible item match' AS title, 'A demo found-item report may match your notebook.' AS message, 'match' AS type
    UNION ALL SELECT 'demo02@example.test', '[DEMO] Claim update', 'Your sample umbrella claim is approved.', 'claim'
    UNION ALL SELECT 'demo03@example.test', '[DEMO] New report', 'A demo item report was added to the community list.', 'system'
    UNION ALL SELECT 'demo04@example.test', '[DEMO] Claim completed', 'Your sample wallet claim has been marked completed.', 'claim'
) AS d
JOIN `users` u ON u.`email` = d.`email`
WHERE NOT EXISTS (
    SELECT 1 FROM `notifications` existing
    WHERE existing.`user_id` = u.`user_id`
      AND existing.`title` = d.`title`
      AND existing.`message` = d.`message`
);

COMMIT;

SELECT 'users' AS `table_name`, COUNT(*) AS `sample_rows` FROM `users` WHERE `email` LIKE 'demo0%@example.test'
UNION ALL SELECT 'lost_items', COUNT(*) FROM `lost_items` WHERE `item_name` LIKE 'Demo Lost %'
UNION ALL SELECT 'found_items', COUNT(*) FROM `found_items` WHERE `item_name` LIKE 'Demo Found %'
UNION ALL SELECT 'claims', COUNT(*) FROM `claims` WHERE `claim_message` LIKE '[DEMO-SEED]%'
UNION ALL SELECT 'match_result', COUNT(*) FROM `match_result` mr JOIN `lost_items` li ON li.`lost_item_id` = mr.`lost_item_id` WHERE li.`item_name` LIKE 'Demo Lost %'
UNION ALL SELECT 'notifications', COUNT(*) FROM `notifications` WHERE `title` LIKE '[DEMO]%'
UNION ALL SELECT 'TOTAL', (
    (SELECT COUNT(*) FROM `users` WHERE `email` LIKE 'demo0%@example.test')
    + (SELECT COUNT(*) FROM `lost_items` WHERE `item_name` LIKE 'Demo Lost %')
    + (SELECT COUNT(*) FROM `found_items` WHERE `item_name` LIKE 'Demo Found %')
    + (SELECT COUNT(*) FROM `claims` WHERE `claim_message` LIKE '[DEMO-SEED]%')
    + (SELECT COUNT(*) FROM `match_result` mr JOIN `lost_items` li ON li.`lost_item_id` = mr.`lost_item_id` WHERE li.`item_name` LIKE 'Demo Lost %')
    + (SELECT COUNT(*) FROM `notifications` WHERE `title` LIKE '[DEMO]%')
);
