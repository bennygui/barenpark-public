<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * barenpark implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * barenpark game statistics description
 *
 */

require_once('modules/php/BP/Globals.php');

$stats_type = [
    // Statistics global to table
    "table" => [],

    // Statistics for each player
    "player" => [
        STATS_PLAYER_SCORE_TOTAL => ["id" => 10, "name" => totranslate("Player Score: Total"), "type" => "int"],
        STATS_PLAYER_SCORE_ANIMAL_HOUSE => ["id" => 11, "name" => totranslate("Player Score: Animal Houses"), "type" => "int"],
        STATS_PLAYER_SCORE_ENCLOSURE => ["id" => 12, "name" => totranslate("Player Score: Enclosures"), "type" => "int"],
        STATS_PLAYER_SCORE_BEAR_STATUE => ["id" => 13, "name" => totranslate("Player Score: Bear Statues"), "type" => "int"],
        STATS_PLAYER_SCORE_ACHIEVEMENT => ["id" => 14, "name" => totranslate("Player Score: Achievements"), "type" => "int"],
        STATS_PLAYER_SCORE_PLAYER_SUPPLY => ["id" => 15, "name" => totranslate("Score left in player supply"), "type" => "int"],
        STATS_PLAYER_NB_PLACED_SHAPE_GREEN => ["id" => 16, "name" => totranslate("Nb Placed Tiles: Green"), "type" => "int"],
        STATS_PLAYER_NB_PLACED_SHAPE_ANIMAL_HOUSE => ["id" => 17, "name" => totranslate("Nb Placed Tiles: Animal Houses"), "type" => "int"],
        STATS_PLAYER_NB_PLACED_SHAPE_ENCLOSURE => ["id" => 18, "name" => totranslate("Nb Placed Tiles: Enclosures"), "type" => "int"],
        STATS_PLAYER_NB_PLACED_SHAPE_BEAR_STATUE => ["id" => 19, "name" => totranslate("Nb Placed Tiles: Bear Statues"), "type" => "int"],
        STATS_PLAYER_NB_ACHIEVEMENT => ["id" => 20, "name" => totranslate("Nb Gained Achievements"), "type" => "int"],
    ],
];
