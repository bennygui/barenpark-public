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

namespace BP;

require_once(__DIR__ . '/../BX/Player.php');
require_once(__DIR__ . '/../BX/MoveNumber.php');

class Player extends \BX\Player\Player
{
    /** @dbcol @dbdefault */
    public $parkSideIsFront;

    use \BX\MoveNumber\PlayerLastSeenMoveNumberTrait;
}

class PlayerMgr extends \BX\Player\PlayerMgr
{
    use \BX\MoveNumber\PlayerMgrLastSeenMoveNumberTrait;

    public function __construct()
    {
        parent::__construct(Player::class);
    }

    public function setup(array $setupNewGamePlayers, array $colors)
    {
        parent::setup($setupNewGamePlayers, $colors);
        foreach ($this->db->getAllRows() as $row) {
            $tileSide = [true, false];
            shuffle($tileSide);
            $row->parkSideIsFront = $tileSide[0];
            $this->db->updateRow($row);
        }
    }
}