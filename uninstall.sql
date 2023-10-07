--
-- Online Module Management Platform
-- 
-- SQL uninstallation file for example module
-- 
-- Author: The OMMP Team
-- Version: 1.0
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Delete the counter table
DROP TABLE IF EXISTS `{PREFIX}store_modules`;
DROP TABLE IF EXISTS `{PREFIX}store_versions`;
COMMIT;
