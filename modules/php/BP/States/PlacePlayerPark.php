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

namespace BP\State\PlacePlayerPark;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function argPlacePlayerPark(int $playerId)
    {
        $chooseParkAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\ChooseParkFromSupplyBoardActionCommand::class);
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $argChoose = $this->argChooseFromSupplyBoard($playerId);
        return [
            "selectedParkId" => $chooseParkAction->getParkId(),
            "selectedParkDefId" => $parkMgr->getRowByKey($chooseParkAction->getParkId())->parkDefId,
            "newParkValidPositions" => $parkMgr->getNewParkValidPositions($playerId),
            "playerParks" => array_values($parkMgr->getPlayerParks($playerId)),
            'choosableParkIds' => array_values(array_filter($argChoose['choosableParkIds'], fn($pId) => $pId != $chooseParkAction->getParkId())),
            'choosableShapeIds' => $argChoose['choosableShapeIds'],
        ];
    }
    
    public function placePlayerPark(int $posX, int $posY)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'placePlayerPark', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);
        
        $chooseParkAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\ChooseParkFromSupplyBoardActionCommand::class);

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $newPlacePark = new \BP\PlacePlayerParkActionCommand($playerId, $chooseParkAction->getParkId(), $posX, $posY);
        $creator->add($newPlacePark);

        $icons = $this->getOverlappedIcons($playerId, null, $newPlacePark);

        if (count($icons) > 0) {
            $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'chooseFromSupplyBoard'));
        } else {
            $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'confirmTurn'));
        }
        $creator->save();
    }
}
