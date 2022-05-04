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

namespace BP\State\PlaceTileInPark;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function argPlaceTileInPark(int $playerId)
    {
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');

        $action = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\ChooseTileFromPlayerSupplyActionCommand::class);
        if ($action !== null) {
            $shapeId = $action->getShapeId();
        } else {
            $shapeId = $shapeMgr->getPlayerSupplyFirstShapeId($playerId);
        }
        return [
            'selectedShapeId' => $shapeId,
            'validPositions' => \BP\ParkValidPosition::mapToUiString($parkMgr->getValidPositions(
                (new \BP\ParkShapeValidityArgs())->setPlayerId($playerId)->setShapeId($shapeId)
            )),
            'neighbourPositions' => $parkMgr->getNeighbourPositions($playerId),
            'shapeIds' => array_values(array_filter($shapeMgr->getPlayerSupplyShapeIds($playerId), fn ($id) => $id != $shapeId)),
        ];
    }


    public function changeChooseTileFromPlayerSupply(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'changeChooseTileFromPlayerSupply', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        \BX\Action\ActionCommandMgr::undoLast($playerId);
        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $creator->add(new \BP\ChooseTileFromPlayerSupplyActionCommand($playerId, $shapeId));
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId));
        $creator->save();
    }

    public function placeTileInPark(
        int $parkId,
        int $parkTopX,
        int $parkTopY,
        int $parkRotation,
        bool $parkHorizontalFlip,
        bool $parkVerticalFlip
    ) {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'placeTileInPark', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $chooseTileAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\ChooseTileFromPlayerSupplyActionCommand::class);
        if ($chooseTileAction !== null) {
            $shapeId = $chooseTileAction->getShapeId();
        } else {
            $shapeId = $shapeMgr->getPlayerSupplyFirstShapeId($playerId);
        }

        $creator = new \BX\Action\ActionCommandCreator($playerId);

        // Create action to place tile in park
        $placeAction = new \BP\PlaceTileInParkActionCommand(
            $playerId,
            $shapeId,
            $parkId,
            $parkTopX,
            $parkTopY,
            $parkRotation,
            $parkHorizontalFlip,
            $parkVerticalFlip
        );
        $creator->add($placeAction);

        // Gain achievement if possible
        $creator->add(new \BP\GainAchievementActionCommand($playerId, count($placeAction->getBearStatueShapeIds())));

        if (count($this->getOverlappedIcons($playerId,  null,  null, $placeAction)) > 0) {
            $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'chooseFromSupplyBoard'));
        } else {
            $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'confirmTurn'));
        }
        $creator->save();
    }
}
