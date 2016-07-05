CREATE TABLE `typecho_thinpicture` (
  `hash` CHAR(32) NOT NULL,
  `thin_url` TEXT NULL,
  `thin_width` INT NULL,
  `thin_height` INT NULL,
  `create_time` INT NULL,
  PRIMARY KEY (`hash`)
) DEFAULT CHARSET=%charset%;