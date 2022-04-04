
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- barenpark implementation : © Guillaume Benny bennygui@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- side of the bottom part of the main park of the player (with 'barenpark' in multiple languages)
ALTER TABLE `player` ADD `park_side_is_front` boolean NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD `last_seen_move_number` int(10) NULL;

-- Shapes are green areas, animal houses (white), enclosures (orange), bear statues
CREATE TABLE IF NOT EXISTS `shape` (
  -- unique id with no meaning 
  `shape_id` smallint(5) unsigned NOT NULL,
  -- id of the type of shape
  `shape_def_id` smallint(5) unsigned NOT NULL,
  -- green areas, animal houses (white), enclosures (orange), bear statues
  `class_id` varchar(256) NOT NULL,
  -- score for shape
  `shape_score` smallint(5) unsigned NOT NULL,
  -- supply board, player supply, player park 
  `shape_location_id` smallint(5) unsigned NOT NULL,
  -- player that has this shape, null if location is supply board
  `player_id` int(10) unsigned NULL,
  -- on which park the shape is located, null if location is not player park
  `park_id` smallint(5) unsigned NULL,
  -- top corner in the park, null if location is not park
  `park_top_x` smallint(5) unsigned NULL,
  -- top corner in the park, null if location is not park
  `park_top_y` smallint(5) unsigned NULL,
  -- rotation (0, 90, 180, 270) in the park, null if location is not park
  `park_rotation` smallint(5) unsigned NULL,
  -- flipped horizontal if true, null if location is not park
  `park_horizontal_flip` boolean NULL,
  -- flipped vertical if true, null if location is not park
  `park_vertical_flip` boolean NULL,
  -- Number to know which shapes where played since last turn
  `saved_move_number` int(10) unsigned NULL,
  PRIMARY KEY (`shape_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Parks are 4x4 areas to put shapes on
CREATE TABLE IF NOT EXISTS `park` (
  -- unique id: Each park in the game will have a unique id
  `park_id` smallint(5) unsigned NOT NULL, 
  -- id of the type of park
  `park_def_id` smallint(5) unsigned NOT NULL,
  -- in which pile in the supply, null if not in supply
  `supply_pile` smallint(5) unsigned NULL,
  -- order in the supply pile, null if not in supply
  `supply_pile_order` smallint(5) unsigned NULL,
  -- is top of supply pile
  `is_supply_pile_top` boolean NOT NULL DEFAULT 0,
  -- player that has this tile, null if still in supply
  `player_id` int(10) unsigned NULL,
  -- position of the park: (0,0) is the inital park. null if still in supply
  `pos_x` smallint(5) NULL,
  -- position of the park: (0,0) is the inital park. y cannot be negative. null if still in supply
  `pos_y` smallint(5) unsigned NULL,
  -- Number to know which parks where played since last turn
  `saved_move_number` int(10) unsigned NULL,
  PRIMARY KEY (`park_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Achievements are won by the first player that meet the condition
CREATE TABLE IF NOT EXISTS `achievement` (
  -- unique id: Each achievement in the game will have a unique id
  `achievement_id` smallint(5) unsigned NOT NULL, 
  -- Type of achievement (number of shapes, shape placement, ...)
  `class_id` varchar(256) NOT NULL,
  -- score for achievement
  `achievement_score` smallint(5) unsigned NOT NULL,
  -- in which pile in the supply
  `supply_pile` smallint(5) unsigned NOT NULL,
  -- order in the supply pile
  `supply_pile_order` smallint(5) unsigned NOT NULL,
  -- player that has this achievement, null if still in supply
  `player_id` int(10) unsigned NULL,
  -- Number to know which parks where played since last turn
  `saved_move_number` int(10) unsigned NULL,
  PRIMARY KEY (`achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Actions that are still private to a player and that can be undone
CREATE TABLE IF NOT EXISTS `action_command` (
  -- unique id with no meaning 
  `action_command_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  -- json version of the class
  `action_json` varchar(2048) NOT NULL,
  PRIMARY KEY (`action_command_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Private state of the player
CREATE TABLE IF NOT EXISTS `private_state` (
  -- player id
  `player_id` int(10) unsigned NOT NULL,
  `state_id` smallint(5) unsigned NULL,
  PRIMARY KEY (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;