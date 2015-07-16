-- Recent additions to add after db init (.gz)

ALTER TABLE `coins` ADD `hasmasternodes` TINYINT(1) NOT NULL DEFAULT '0' AFTER `hassubmitblock`;

