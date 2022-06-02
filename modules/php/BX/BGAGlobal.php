<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * theisleofcats implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

namespace BX\BGAGlobal;

require_once('DB.php');

const GLOBAL_ACTIVE_STATE_ID = 1;
const GLOBAL_ACTIVE_PLAYER_ID = 2;
const GLOBAL_NEXT_MOVE_NUMBER = 3;
const GLOBAL_PLAYER_TURN_NUMBER = 6;
const GLOBAL_GAME_PROGRESSION = 7;
const GLOBAL_INITIAL_REFLECTION_TIME = 8;
const GLOBAL_ADDITIONAL_REFLECTION_TIME = 8;
const GLOBAL_REFLEXTION_TIME_PROFILE = 200;
const GLOBAL_RANKING_MODE = 201;
const GLOBAL_GAMESTATE_GAME_LANG = \GAMESTATE_GAME_LANG;
const GLOBAL_GAMESTATE_GAMEVERSION = \GAMESTATE_GAMEVERSION;
const GLOBAL_GAMESTATE_GAME_RESULT_NEUTRALIZED = \GAMESTATE_GAME_RESULT_NEUTRALIZED;
const GLOBAL_GAMESTATE_NEUTRALIZED_PLAYER_ID = \GAMESTATE_NEUTRALIZED_PLAYER_ID;
const GLOBAL_GAMESTATE_UNDO_MOVES_STORED = \GAMESTATE_UNDO_MOVES_STORED;
const GLOBAL_GAMESTATE_UNDO_MOVES_PLAYER = \GAMESTATE_UNDO_MOVES_PLAYER;
const GLOBAL_GAMESTATE_LOCK_TIMESTAMP = \GAMESTATE_LOCK_TIMESTAMP;

class GlobalRow extends \BX\DB\BaseRow
{
    /** @dbcol @dbkey */
    public $globalId;
    /** @dbcol */
    public $globalValue;
}

class GlobalMgr
{
    private static $instance;
    private $db;

    private function __construct()
    {
        $this->db = \BX\DB\RowMgrRegister::newMgr('global', GlobalRow::class);
    }

    static private function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new GlobalMgr();
        }
        return self::$instance;
    }

    static public function getGlobal(int $globalId)
    {
        $row = self::getInstance()->db->getRowByKey($globalId);
        if ($row === null) {
            return null;
        }
        return $row->globalValue;
    }

    static public function getNextMoveNumber()
    {
        return self::getGlobal(GLOBAL_NEXT_MOVE_NUMBER);
    }

    static public function getCurrentMoveNumber()
    {
        return self::getNextMoveNumber() - 1;
    }
}
