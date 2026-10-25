-- OpenFrame example module: Notes CRUD
-- Import this file, then copy the folder "notes_crud" into /modules.
-- The table name (demo_notes) is hard-coded in index.php.

CREATE TABLE IF NOT EXISTS `demo_notes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(120) NOT NULL,
  `body`       TEXT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional sample rows
INSERT INTO `demo_notes` (`title`, `body`, `created_at`) VALUES
('Welcome to OpenFrame', 'This row was created by schema.sql.', NOW()),
('Second note',          'Delete me from the Notes screen.',   NOW());
