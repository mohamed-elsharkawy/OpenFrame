-- =====================================================================
--  Front Office — complete database
--  MySQL 8.x / MariaDB 10.4+ (WAMP)
--
--  Import with phpMyAdmin, or:
--      mysql -uroot < database.sql
--
--  Every table has a primary key ID with auto increment.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS frontoffice
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frontoffice;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS guest_visits;
DROP TABLE IF EXISTS user_level_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS user_levels;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- User levels: "entry" (front desk) and "admin"
-- ---------------------------------------------------------------------
CREATE TABLE user_levels (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code    VARCHAR(20)  NOT NULL,
    name_ar VARCHAR(60)  NOT NULL,
    name_en VARCHAR(60)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_levels_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO user_levels (code, name_ar, name_en) VALUES
    ('entry', 'موظف استقبال', 'Entry user'),
    ('admin', 'مسؤول النظام',  'Admin user');

-- ---------------------------------------------------------------------
-- Permissions (checked by the modules through their info.php)
-- ---------------------------------------------------------------------
CREATE TABLE permissions (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code    VARCHAR(50)  NOT NULL,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name_ar, name_en) VALUES
    ('guest_create',        'تسجيل ضيوف جدد',            'Register new guests'),
    ('guest_leave',         'تسجيل مغادرة الضيوف',       'Record guest leave'),
    ('guest_search',        'البحث عن بيانات الضيوف',    'Search guest information'),
    ('report_period',       'تقرير الضيوف لفترة',        'Guests report for a period'),
    ('report_users',        'تقرير التسجيل حسب المستخدم','Guest entry report based on users'),
    ('departments_manage',  'إدارة هيكل الإدارات',       'Manage departments hierarchy'),
    ('users_manage',        'إدارة المستخدمين',          'Manage users');

-- ---------------------------------------------------------------------
-- Which permissions each level holds
-- ---------------------------------------------------------------------
CREATE TABLE user_level_permissions (
    level_id      INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (level_id, permission_id),
    CONSTRAINT fk_ulp_level      FOREIGN KEY (level_id)      REFERENCES user_levels (id) ON DELETE CASCADE,
    CONSTRAINT fk_ulp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO user_level_permissions (level_id, permission_id) VALUES
    (1, 1), (1, 2), (1, 3),   -- entry: register, leave, search
    (2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7);  -- admin: everything

-- ---------------------------------------------------------------------
-- Users
--   admin      / Admin@123
--   reception  / Guest@123
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    level_id      INT UNSIGNED NOT NULL,
    active        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    CONSTRAINT fk_users_level FOREIGN KEY (level_id) REFERENCES user_levels (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, full_name, password_hash, level_id) VALUES
    ('admin',     'System Administrator', '$2y$10$6HfBfkJ7I95XOmI/Tyfah.wt6CYycghddIRSIWME1FY2PAzB2uGhy', 2),
    ('reception', 'Reception Desk',       '$2y$10$YvSNDoPWCWS7iF.F1P81yOtwSF5GuOfB.0iencMn/affrghpQptwK', 1);

-- ---------------------------------------------------------------------
-- Departments hierarchy (parent_id = NULL -> top level)
-- ---------------------------------------------------------------------
CREATE TABLE departments (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    parent_id  INT UNSIGNED NULL DEFAULT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_departments_parent (parent_id),
    CONSTRAINT fk_departments_parent
        FOREIGN KEY (parent_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO departments (id, name, parent_id) VALUES
    (1, 'الإدارة العامة',   NULL),
    (2, 'الموارد البشرية',  1),
    (3, 'تقنية المعلومات',  1),
    (4, 'المالية',          1),
    (5, 'الاستقبال',        NULL);
ALTER TABLE departments AUTO_INCREMENT = 6;

-- ---------------------------------------------------------------------
-- Guest visits (the front office register)
--   id            -> the record number shown on the entry form
--   id_doc_path   -> uploaded ID/passport copy, e.g. uploads/2026/10/18/visit-1.png
--   leave_time    -> NULL while the guest is still inside
-- ---------------------------------------------------------------------
CREATE TABLE guest_visits (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(120) NOT NULL,
    id_number     VARCHAR(40)  NOT NULL,
    purpose       VARCHAR(255) NOT NULL,
    visiting_who  VARCHAR(120) NULL DEFAULT NULL,
    department_id INT UNSIGNED NULL DEFAULT NULL,
    id_doc_path   VARCHAR(255) NULL DEFAULT NULL,
    entry_time    DATETIME     NOT NULL,
    leave_time    DATETIME     NULL DEFAULT NULL,
    entered_by    INT UNSIGNED NULL DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_gv_full_name  (full_name),
    KEY idx_gv_id_number  (id_number),
    KEY idx_gv_entry_time (entry_time),
    KEY idx_gv_leave_time (leave_time),
    KEY idx_gv_department (department_id),
    KEY idx_gv_entered_by (entered_by),
    CONSTRAINT fk_gv_department
        FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE RESTRICT,
    CONSTRAINT fk_gv_user
        FOREIGN KEY (entered_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample visits so reports/grids are not empty (relative dates)
INSERT INTO guest_visits
    (full_name, id_number, purpose, visiting_who, department_id, entry_time, leave_time, entered_by) VALUES
    ('Ahmed Mohammed Ali',  '1012345678', 'Business meeting',   'HR Manager',        2, NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 2 HOUR,          2),
    ('Sara Al-Qahtani',     'AB1234567',  'Collect documents',  'Reception staff',   5, NOW() - INTERVAL 1 HOUR, NULL,                                2),
    ('Khalid Yousef',       '9876543210', 'Official visit',     'General Manager',   1, NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 40 MINUTE,        1);
