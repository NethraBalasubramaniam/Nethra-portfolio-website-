-- Schema for the admin panel's content tables.
-- Import this in phpMyAdmin if you ever need to recreate it by hand —
-- admin/db.php also runs these CREATE TABLE IF NOT EXISTS statements
-- automatically on first connection, so a fresh XAMPP install works
-- without a manual import too.

CREATE DATABASE IF NOT EXISTS `nethra-portfolio` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nethra-portfolio`;

-- `cases` holds every case study — both the original in-depth write-ups and
-- anything added through the admin panel. Only the admin panel's simple
-- form (title/tag/blurb/chips/cover image) is editable through the UI;
-- headline/lede/meta/pillars/chapters/metrics carry the full case-detail
-- page content and are edited directly in the database (or phpMyAdmin) —
-- admin-created cases just leave them null/empty and get a simple detail
-- page.
CREATE TABLE IF NOT EXISTS `cases` (
  `id` VARCHAR(191) NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `tag` VARCHAR(60) NOT NULL DEFAULT '',
  `blurb` VARCHAR(400) NOT NULL DEFAULT '',
  `headline` VARCHAR(400) NOT NULL DEFAULT '',
  `lede` TEXT NULL,
  `chips` JSON NULL,
  `meta` JSON NULL,
  `pillars` JSON NULL,
  `chapters` JSON NULL,
  `metrics` JSON NULL,
  `slot` VARCHAR(191) NOT NULL,
  `src` VARCHAR(255) NOT NULL DEFAULT '',
  `ph` VARCHAR(160) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `playground_items` (
  `id` VARCHAR(191) NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `tag` VARCHAR(60) NOT NULL DEFAULT '',
  `description` VARCHAR(300) NOT NULL DEFAULT '',
  `likes` VARCHAR(10) NOT NULL DEFAULT '0',
  `views` VARCHAR(10) NOT NULL DEFAULT '0',
  `slot` VARCHAR(191) NOT NULL,
  `src` VARCHAR(255) NOT NULL DEFAULT '',
  `ph` VARCHAR(160) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
