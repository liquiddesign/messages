-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `messages_template`;
CREATE TABLE `messages_template` (
  `uuid` varchar(32) COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `description` text COLLATE utf8_czech_ci,
  `subject_en` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `subject_cz` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `cc` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `alias` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `type` enum('incoming','outgoing') COLLATE utf8_czech_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text_en` longtext COLLATE utf8_czech_ci,
  `text_cz` longtext COLLATE utf8_czech_ci,
  `html_en` longtext COLLATE utf8_czech_ci,
  `html_cz` longtext COLLATE utf8_czech_ci,
  `layout` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `messages_template` (`uuid`, `name`, `description`, `subject_en`, `subject_cz`, `email`, `cc`, `alias`, `type`, `active`, `text_en`, `text_cz`, `html_en`, `html_cz`, `layout`) VALUES
('test',	'test',	NULL,	'Subject Test',	'Předmět Test',	'out@lqd.cz',	'out1@lqd.cz',	'OUT',	'outgoing',	1,	NULL,	NULL,	'{$test}EN',	'{$test}CZ',	'test'),
('test_i',	'test_i',	NULL,	'Income',	'Prichod',	'out@lqd.cz',	'out1@lqd.cz',	'OUTovič',	'incoming',	1,	'en text',	'cz text',	'{$test}',	'{$test}',	NULL);

-- 2020-10-20 15:59:39
