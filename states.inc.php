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
 * states.inc.php
 *
 * barenpark game states description
 *
 */

require_once("modules/php/BP/Globals.php");

$machinestates = [

    // The initial state. Please do not modify.
    1 => [
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => [
            "" => STATE_ENTER_MAIN_PLAY_STATE_ID
        ],
    ],

    // Main states
    STATE_ENTER_MAIN_PLAY_STATE_ID => [
        "name" => STATE_ENTER_MAIN_PLAY_STATE,
        "description" => "",
        "type" => "game",
        "action" => "stPrivateStateEnter",
        "privateStateEnterActiveFunction" => "stChooseMainEnterState",
        "privateStateEnterInactive" => STATE_PRIVATE_INACTIVE_TURN_ID,
        "transitions" => [
            '' => STATE_MAIN_PLAY_STATE_ID
        ],
    ],
    STATE_MAIN_PLAY_STATE_ID => [
        "name" => STATE_MAIN_PLAY_STATE,
        "description" => clienttranslate('${actplayer} must play their turn'),
        "descriptionmyturn" => clienttranslate('${you} must play your turn'),
        "type" => "activeplayer",
        "args" => "argPrivateStateArgs",
        "argsNoPrivateState" => "argMainPlayState",
        "argsAllPrivateState" => "argsAllPrivateState",
        "transitions" => [
            "privateStateLoop" => STATE_MAIN_PLAY_STATE_ID,
            "nextPlayer" => STATE_MAIN_PLAY_STATE_NEXT_PLAYER_ID,
        ],
    ],
    STATE_MAIN_PLAY_STATE_NEXT_PLAYER_ID => [
        "name" => STATE_MAIN_PLAY_STATE_NEXT_PLAYER,
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => [
            "nextPlayer" => STATE_ENTER_MAIN_PLAY_STATE_ID,
            "endGame" => STATE_GAME_END_ID,
        ],
    ],

    // Private states
    STATE_PRIVATE_INACTIVE_TURN_ID => [
        "name" => STATE_PRIVATE_INACTIVE_TURN,
        "description" => clienttranslate('${actplayer} must play their turn'),
        "type" => "privateState",
        "possibleactions" => [
            'enterPlayLoop',
            'enterTryMode',
        ],
        "transitions" => [
            'enterPlayLoop' => STATE_PRIVATE_CHOOSE_TILE_FROM_PLAYER_SUPPLY_ID,
            'enterPlayLoopToPass' => STATE_PRIVATE_PASS_TURN_CHOOSE_FROM_SUPPLY_BOARD_ID,
        ],
    ],
    STATE_PRIVATE_CHOOSE_TILE_FROM_PLAYER_SUPPLY_ID => [
        "name" => STATE_PRIVATE_CHOOSE_TILE_FROM_PLAYER_SUPPLY,
        "description" => clienttranslate('${you} may select a tile from your supply'),
        "descriptionmyturn" => clienttranslate('${you} must select a tile from your supply'),
        "type" => "privateState",
        "args" => "argChooseTileFromPlayerSupply",
        "possibleactions" => [
            'chooseTileFromPlayerSupply',
            'enterTryMode',
        ],
        "transitions" => [
            '' => STATE_PRIVATE_PLACE_TILE_IN_PARK_ID,
        ],
    ],
    STATE_PRIVATE_PLACE_TILE_IN_PARK_ID => [
        "name" => STATE_PRIVATE_PLACE_TILE_IN_PARK,
        "description" => clienttranslate('${you} may place the selected tile in your park'),
        "descriptionmyturn" => clienttranslate('${you} must place the selected tile in your park'),
        "type" => "privateState",
        "args" => "argPlaceTileInPark",
        "possibleactions" => [
            'placeTileInPark',
            'changeChooseTileFromPlayerSupply',
            'enterTryMode',
        ],
        "transitions" => [
            'chooseFromSupplyBoard' => STATE_PRIVATE_CHOOSE_FROM_SUPPLY_BOARD_ID,
            'confirmTurn' => STATE_PRIVATE_CONFIRM_TURN_ID,
        ],
    ],
    STATE_PRIVATE_CHOOSE_FROM_SUPPLY_BOARD_ID => [
        "name" => STATE_PRIVATE_CHOOSE_FROM_SUPPLY_BOARD,
        "description" => clienttranslate('${you} may take new tiles: ${shapeList}'),
        "descriptionmyturn" => clienttranslate('${you} must take new tiles: ${shapeList}'),
        "type" => "privateState",
        "args" => "argChooseFromSupplyBoard",
        "possibleactions" => [
            'chooseShapeFromSupplyBoard',
            'chooseParkFromSupplyBoard',
            'enterTryMode',
        ],
        "transitions" => [
            'chooseFromSupplyBoard' => STATE_PRIVATE_CHOOSE_FROM_SUPPLY_BOARD_ID,
            'placePlayerPark' => STATE_PRIVATE_PLACE_PLAYER_PARK_ID,
            'confirmTurn' => STATE_PRIVATE_CONFIRM_TURN_ID,
        ],
    ],
    STATE_PRIVATE_PLACE_PLAYER_PARK_ID => [
        "name" => STATE_PRIVATE_PLACE_PLAYER_PARK,
        "description" => clienttranslate('${you} may place the new Park Area'),
        "descriptionmyturn" => clienttranslate('${you} must place the new Park Area'),
        "type" => "privateState",
        "args" => "argPlacePlayerPark",
        "possibleactions" => [
            'placePlayerPark',
            'changeChooseParkFromSupplyBoard',
            'changeChooseShapeFromSupplyBoard',
        ],
        "transitions" => [
            'chooseFromSupplyBoard' => STATE_PRIVATE_CHOOSE_FROM_SUPPLY_BOARD_ID,
            'confirmTurn' => STATE_PRIVATE_CONFIRM_TURN_ID,
        ],
    ],
    STATE_PRIVATE_PASS_TURN_CHOOSE_FROM_SUPPLY_BOARD_ID => [
        "name" => STATE_PRIVATE_PASS_TURN_CHOOSE_FROM_SUPPLY_BOARD,
        "description" => clienttranslate('${you} may take a new Green Area tile and pass'),
        "descriptionmyturn" => clienttranslate('${you} must take a new Green Area tile and pass'),
        "type" => "privateState",
        "args" => "argPassTurnChooseFromSupplyBoard",
        "possibleactions" => [
            'chooseShapeFromSupplyBoardAndPass',
            'enterTryMode',
        ],
        "transitions" => [
            'confirmTurn' => STATE_PRIVATE_CONFIRM_TURN_ID,
        ],
    ],
    STATE_PRIVATE_PASS_TURN_NO_SHAPE_ID => [
        "name" => STATE_PRIVATE_PASS_TURN_NO_SHAPE,
        "description" => clienttranslate('${you} must wait for the other players to end their turn'),
        "descriptionmyturn" => clienttranslate('${you} must pass (no Green Area and no placeable tiles)'),
        "type" => "privateState",
        "possibleactions" => [
            'passTurn',
            'enterTryMode',
        ],
        "transitions" => [],
    ],
    STATE_PRIVATE_CONFIRM_TURN_ID => [
        "name" => STATE_PRIVATE_CONFIRM_TURN,
        "description" => clienttranslate('${you} must wait for the other players to end their turn'),
        "descriptionmyturn" => clienttranslate('${you} must confirm your turn'),
        "type" => "privateState",
        "possibleactions" => [
            'confirmTurn',
            'enterTryMode',
        ],
        "transitions" => [],
    ],

    // Try Mode
    STATE_PRIVATE_TRY_MODE_CHOOSE_TILE_ID => [
        "name" => STATE_PRIVATE_TRY_MODE_CHOOSE_TILE,
        "description" => clienttranslate('${you} may select a tile or a park'),
        "type" => "privateState",
        "args" => "argTryModeChooseTile",
        "possibleactions" => [
            'tryModeChooseTile',
            'tryModeChoosePark',
            'exitTryMode',
        ],
        "transitions" => [
            'chooseTile' => STATE_PRIVATE_TRY_MODE_PLACE_TILE_ID,
            'choosePark' => STATE_PRIVATE_TRY_MODE_PLACE_PARK_ID,
        ],
    ],
    STATE_PRIVATE_TRY_MODE_PLACE_TILE_ID => [
        "name" => STATE_PRIVATE_TRY_MODE_PLACE_TILE,
        "description" => clienttranslate('${you} may place a tile'),
        "type" => "privateState",
        "args" => "argTryModePlaceTile",
        "possibleactions" => [
            'tryModePlaceTile',
            'tryModeChangeChooseTile',
            'tryModeChangeChoosePark',
            'exitTryMode',
        ],
        "transitions" => [
            '' => STATE_PRIVATE_TRY_MODE_CHOOSE_TILE_ID,
        ],
    ],
    STATE_PRIVATE_TRY_MODE_PLACE_PARK_ID => [
        "name" => STATE_PRIVATE_TRY_MODE_PLACE_PARK,
        "description" => clienttranslate('${you} may place the new Park Area'),
        "type" => "privateState",
        "args" => "argTryModePlacePark",
        "possibleactions" => [
            'tryModePlacePark',
            'tryModeChangeChooseTile',
            'tryModeChangeChoosePark',
            'exitTryMode',
        ],
        "transitions" => [
            '' => STATE_PRIVATE_TRY_MODE_CHOOSE_TILE_ID,
        ],
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    STATE_GAME_END_ID => [
        "name" => STATE_GAME_END,
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],
];
