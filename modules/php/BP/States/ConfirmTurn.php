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

namespace BP\State\ConfirmTurn;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function confirmTurn()
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'confirmTurn', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ACTIVE);

        // Undo other players actions
        $reevalArgs = \BX\Action\ActionCommandMgr::getReevaluationArgs($playerId);
        foreach (array_keys($this->loadPlayersBasicInfos()) as $reevalPlayerId) {
            if ($reevalPlayerId == $playerId) {
                continue;
            }
            \BX\Action\ActionCommandMgr::reevaluate($reevalPlayerId, $reevalArgs);
        }

        \BX\Action\ActionCommandMgr::commit($playerId);

        $this->notifyReplaceSupplyBoardParks($playerId);
        $this->updateLastMove($playerId);

        \BX\Action\ActionRowMgrRegister::getMgr('private_state')->clearPlayerState($playerId);
        $this->giveExtraTime($playerId);

        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        foreach (array_keys($this->loadPlayersBasicInfos()) as $notifPlayerId) {
            \BX\Action\ActionCommandMgr::apply($notifPlayerId);
            $notifier = new \BX\Action\ActionCommandNotifierPrivate($notifPlayerId);
            $notifier->notifyNoMessage(NTF_UPDATE_SUPPLY_SHAPES_COUNT, [
                'supplyShapesCount' => $shapeMgr->getSupplyShapesCount(),
            ]);
        }
        \BX\Action\ActionCommandMgr::clear();

        $this->gamestate->nextState('nextPlayer');
    }

    public function passTurn()
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'passTurn', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ACTIVE);

        $this->updateLastMove($playerId);
        \BX\Action\ActionRowMgrRegister::getMgr('private_state')->clearPlayerState($playerId);
        $this->giveExtraTime($playerId);

        $this->gamestate->nextState('nextPlayer');
    }

    private function notifyReplaceSupplyBoardParks($playerId)
    {
        $notifier = new \BX\Action\ActionCommandNotifierPublic($playerId);
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $topParks = $parkMgr->revealSupplyPileTop();
        $notifier->notifyNoMessage(
            NTF_REPLACE_SUPPLY_BOARD_PARKS,
            [
                'parks' => $topParks,
                'supplyPilesCount' => $parkMgr->getSupplyPilesCount(),
            ]
        );
    }

    private function updateLastMove($playerId)
    {
        $playerMgr = \BX\Action\ActionRowMgrRegister::getMgr('player');
        $playerMgr->updatePlayerLastSeenMoveNow($playerId);

        $notifier = new \BX\Action\ActionCommandNotifierPublic($playerId);
        $notifier->notifyNoMessage(
            NTF_UPDATE_SAVED_MOVE_NUMBER,
            [
                'savedMoveNumber' => \BX\MoveNumber\getSavedMoveNumberForManagers('player', 'shape', 'park', 'achievement'),
            ]
        );
    }
}
