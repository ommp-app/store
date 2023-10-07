--
-- Online Module Management Platform
-- 
-- SQL installation file for store module
-- 
-- Author: The OMMP Team
-- Version: 1.0
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Creates the store table
DROP TABLE IF EXISTS `{PREFIX}store_modules`;
CREATE TABLE IF NOT EXISTS `{PREFIX}store_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` int NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `blocked` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates the versions table
DROP TABLE IF EXISTS `{PREFIX}store_versions`;
CREATE TABLE IF NOT EXISTS `{PREFIX}store_versions` (
  `store_id` int NOT NULL,
  `version` int NOT NULL,
  `required` int NOT NULL,
  `timestamp` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;