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

namespace BP\Debug;

require_once(__DIR__ . '/../BX/Action.php');
require_once(__DIR__ . '/../BX/Debug.php');

trait GameStatesTrait
{
    use \BX\Debug\GameStatesTrait;

    public function debugLoadBug()
    {
        $this->debugLoadBugInternal(function ($studioPlayerId, $replacePlayerId) {
            return array_merge(
                $this->debugGetSqlForActionCommand($studioPlayerId, $replacePlayerId),
                $this->debugGetSqlForPrivateState($studioPlayerId, $replacePlayerId),
                [
                    "UPDATE shape SET player_id = $studioPlayerId WHERE player_id = $replacePlayerId",
                    "UPDATE park SET player_id = $studioPlayerId WHERE player_id = $replacePlayerId",
                    "UPDATE achievement SET player_id = $studioPlayerId WHERE player_id = $replacePlayerId",
                ],
            );
        });
    }

    public function debugAssignParks()
    {
        $playersInfos = $this->loadPlayersBasicInfos();
        $playerIdArray = array_keys($playersInfos);
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $parkMgr->debugAssignParks($playerIdArray);
        $this->notifyAllPlayers('message', 'DONE', []);
    }

    public function debugAssignShapes()
    {
        $playersInfos = $this->loadPlayersBasicInfos();
        $playerIdArray = array_keys($playersInfos);
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $parkMgr->debugAssignParks($playerIdArray);
        $parkMgr->debugAssignShapes($playerIdArray);
        $this->notifyAllPlayers('message', 'DONE', []);
    }

    public function debugAssignAchievements()
    {
        $playersInfos = $this->loadPlayersBasicInfos();
        $playerIdArray = array_keys($playersInfos);
        $achievementMgr = \BX\Action\ActionRowMgrRegister::getMgr('achievement');
        $achievementMgr->debugAssignAchievements($playerIdArray);
        $this->notifyAllPlayers('message', 'DONE', []);
    }

    public function debugToilets()
    {
        $playersInfos = $this->loadPlayersBasicInfos();
        $playerIdArray = array_keys($playersInfos);
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $parkMgr->debugAssignParks($playerIdArray);
        $parkMgr->debugFillWithToilets($playerIdArray);
        $this->notifyAllPlayers('message', 'DONE', []);
    }

    public function debugEndGame()
    {
        $this->endGameImplementation();
        $this->gamestate->jumpToState(STATE_GAME_END_ID);
    }
}
