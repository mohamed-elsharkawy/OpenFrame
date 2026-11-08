-- OpenFrame — Login module schema
-- Import this file into your MySQL database before using the login module.
--
--   mysql -h 127.0.0.1 -u root -p openframe < docs/examples/login/schema.sql
--
-- Default credentials (inserted below):
--   Username: superadmin   Password: admin123   Level: superadmin
--   Username: admin        Password: admin123   Level: admin
--   Username: user         Password: user123    Level: user

CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(60)  NOT NULL UNIQUE,
  `full_name`     VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `level`         VARCHAR(20)  NOT NULL DEFAULT 'user',
  `created_at`    DATETIME NULL,
  `updated_at`    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default users (passwords hashed with PHP's password_hash)
INSERT INTO `users` (`username`, `full_name`, `password_hash`, `level`, `created_at`, `updated_at`) VALUES
('superadmin', 'Super Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', NOW(), NOW()),
('admin',      'Administrator',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      NOW(), NOW()),
('user',       'Regular User',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',       NOW(), NOW());
