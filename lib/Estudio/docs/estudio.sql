SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

-- CREATE SCHEMA IF NOT EXISTS `estudio` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
-- use estudio;

CREATE TABLE IF NOT EXISTS `estudio_page` (
  `id` BIGINT(19) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` VARCHAR(150) NOT NULL,
  `language` VARCHAR(5) NOT NULL,
  `title` VARCHAR(250) NULL DEFAULT NULL,
  `formats` VARCHAR(250) NULL DEFAULT NULL,
  `script` VARCHAR(250) NULL DEFAULT NULL,
  `stylesheet` VARCHAR(250) NULL DEFAULT NULL,
  `multiview` TINYINT(1) NULL DEFAULT NULL,
  `created` datetime(6) NOT NULL,
  `modified` datetime(6) NOT NULL,
  `published` datetime(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx__page__url` (`url` DESC),
  INDEX `idx__page__modified` (`modified` DESC),
  INDEX `idx__page__published` (`published` DESC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE TABLE IF NOT EXISTS `estudio_content` (
  `page` BIGINT(19) UNSIGNED NOT NULL,
  `id` INT(10) UNSIGNED NOT NULL COMMENT 'auto-increment',
  `slot` VARCHAR(45) NOT NULL DEFAULT 'body',
  `priority` INT(11) NOT NULL DEFAULT 1,
  `class` VARCHAR(90) NULL DEFAULT NULL,
  `method` VARCHAR(45) NULL DEFAULT NULL,
  `params` TEXT NULL DEFAULT NULL,
  `content` BLOB NULL DEFAULT NULL,
  `prepare` TINYINT(1) NULL DEFAULT NULL,
  `modified` datetime(6) NOT NULL,
  `published` datetime(6) NULL DEFAULT NULL,
  PRIMARY KEY (`page`, `id`),
  INDEX `idx__content__modified` (`modified` DESC),
  CONSTRAINT `fk_content_page`
    FOREIGN KEY (`page`)
    REFERENCES `estudio_page` (`id`)
    ON DELETE cascade
    ON UPDATE cascade)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE TABLE IF NOT EXISTS `estudio_credential` (
  `assign` VARCHAR(100) NOT NULL,
  `role` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
  `id` INT(10) UNSIGNED NOT NULL COMMENT 'auto-increment',
  `require` TINYINT(1) NULL DEFAULT NULL,
  `certificate` VARCHAR(100) NULL DEFAULT NULL,
  `http` VARCHAR(100) NULL DEFAULT NULL,
  `group` VARCHAR(100) NULL DEFAULT NULL,
  `user` VARCHAR(100) NULL DEFAULT NULL,
  `ip` VARCHAR(100) NULL DEFAULT NULL,
  `modified` datetime(6) NOT NULL,
  PRIMARY KEY (`assign`, `role`, `id`),
  INDEX `idx__credential__modified` (`modified` DESC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


/*
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(19) unsigned | NO   | PRI | NULL    | auto_increment |
| url        | varchar(150)        | NO   | MUL | NULL    |                |
| language   | varchar(5)          | NO   |     | NULL    |                |
| title      | varchar(250)        | YES  |     | NULL    |                |
| formats    | varchar(250)        | YES  |     | NULL    |                |
| script     | varchar(250)        | YES  |     | NULL    |                |
| stylesheet | varchar(250)        | YES  |     | NULL    |                |
| multiview  | tinyint(1)          | YES  |     | NULL    |                |
| created    | datetime(6)            | NO   |     | NULL    |                |
| modified   | datetime(6)            | NO   | MUL | NULL    |                |
| published  | datetime(6)            | YES  | MUL | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
*/
replace into estudio_page(id,url,language,title,created,modified,published) values (1,'/','pt','Olá',now(6),now(6),now(6));

/*
+-----------+---------------------+------+-----+---------+-------+
| Field     | Type                | Null | Key | Default | Extra |
+-----------+---------------------+------+-----+---------+-------+
| page      | bigint(19) unsigned | NO   | PRI | NULL    |       |
| id        | int(10) unsigned    | NO   | PRI | NULL    |       |
| slot      | varchar(45)         | NO   |     | body    |       |
| priority  | int(11)             | NO   |     | 1       |       |
| class     | varchar(90)         | YES  |     | NULL    |       |
| method    | varchar(45)         | YES  |     | NULL    |       |
| params    | text                | YES  |     | NULL    |       |
| content   | blob                | YES  |     | NULL    |       |
| prepare   | tinyint(1)          | YES  |     | NULL    |       |
| modified  | datetime(6)            | NO   | MUL | NULL    |       |
| published | datetime(6)            | YES  |     | NULL    |       |
+-----------+---------------------+------+-----+---------+-------+
*/
replace into estudio_content(page,id,slot,priority,content,modified,published) 
values (1,1,'body',0,'<p>Olá mundo!!!</p>',now(6),now(6));






SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
