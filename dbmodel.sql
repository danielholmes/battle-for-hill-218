CREATE TABLE IF NOT EXISTS `playable_card` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL,
  `type` enum('air-strike','artillery','heavy-weapons','infantry','paratroopers','special-forces','tank') NOT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id_order` (`player_id`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `playable_card`
  ADD CONSTRAINT `playable_card_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;


CREATE TABLE IF NOT EXISTS `deck_card` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL,
  `type` enum('artillery','heavy-weapons','infantry','paratroopers','special-forces','tank') NOT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id_order` (`player_id`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `deck_card`
  ADD CONSTRAINT `deck_card_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;


CREATE TABLE IF NOT EXISTS `battlefield_card` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned DEFAULT NULL,
  `type` enum('artillery','heavy-weapons','infantry','paratroopers','special-forces','tank','hill') NOT NULL,
  `x` tinyint(4) NOT NULL,
  `y` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `x_y` (`x`,`y`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `battlefield_card`
  ADD CONSTRAINT `battlefield_card_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE player ADD COLUMN `base_side` enum('1','-1') NOT NULL;
ALTER TABLE player ADD UNIQUE KEY `base_side` (`base_side`);