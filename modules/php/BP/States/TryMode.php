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

namespace BP\State\TryMode;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function enterTryMode()
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'enterTryMode', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $chooseAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\ChooseTileFromPlayerSupplyActionCommand::class);
        $placeAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\PlaceTileInParkActionCommand::class);
        if ($chooseAction !== null && $placeAction === null) {
            $shapeId = $chooseAction->getShapeId();
        } else {
            $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
            $shapeId = $shapeMgr->getPlayerSupplyFirstShapeId($playerId);
        }
        if ($shapeId !== null) {
            $notifier = new \BX\Action\ActionCommandNotifierPrivate($playerId);
            $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_PLAYER_SUPPLY, [
                'shapeId' => $shapeId,
            ]);
        }

        \BX\Action\ActionCommandMgr::applyAndSaveOne(new \BP\EnterTryModeActionCommand($playerId));
    }

    public function argTryModeChooseTile(int $playerId)
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $shapeIds = array_merge(
            array_values(array_filter($shapeMgr->getPlayerSupplyShapeIds($playerId), fn ($id) => !$shapeMgr->isGeneratedShapeId($id))),
            $shapeMgr->getTopChoosableShapeIds([
                \BP\ShapeGreenBase::class,
                \BP\ShapeWhiteAnimalHouseBase::class,
                \BP\ShapeOrangeEnclosureBase::class,
            ]),
        );
        $parkIds = $parkMgr->getTopChoosableParkIds();
        $chooseParkActions = array_merge(
            \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\TryModePlaceParkActionCommand::class),
            \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\PlacePlayerParkActionCommand::class),
        );
        $excludedParkIds = array_map(fn ($action) => $action->getParkId(), $chooseParkActions);
        $parkIds = array_values(array_filter($parkIds, fn ($parkId) => !in_array($parkId, $excludedParkIds)));
        return [
            'choosableShapeIds' => $shapeIds,
            'choosableParkIds' => $parkIds,
        ];
    }

    public function exitTryMode()
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'exitTryMode', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        \BX\ACtion\ActionCommandMgr::undoUntilAndIncludingFirstMatch(
            $playerId,
            fn ($action) => get_class($action) == \BP\EnterTryModeActionCommand::class
        );
    }

    public function tryModeChooseTile(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'tryModeChooseTile', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $creator->add(new \BP\TryModeChooseTileActionCommand($playerId, $shapeId));
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'chooseTile'));
        $creator->save();
    }

    public function tryModeChangeChooseTile(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'tryModeChangeChooseTile', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        \BX\Action\ActionCommandMgr::undoLast($playerId);
        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $creator->add(new \BP\TryModeChooseTileActionCommand($playerId, $shapeId));
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'chooseTile'));
        $creator->save();
    }

    public function argTryModePlaceTile(int $playerId)
    {
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $action = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\TryModeChooseTileActionCommand::class);
        $shapeId = $action->getNewShapeId();
        return array_merge(
            [
                'selectedShapeId' => $shapeId,
                'validPositions' => \BP\ParkValidPosition::mapToUiString($parkMgr->getValidPositions(
                    (new \BP\ParkShapeValidityArgs())->setPlayerId($playerId)->setShapeId($shapeId)->setValidateAdjacency(false)
                )),
                'neighbourPositions' => $parkMgr->getNeighbourPositions($playerId),
            ],
            $this->argTryModeChooseTile($playerId)
        );
    }

    public function tryModePlaceTile(
        int $parkId,
        int $parkTopX,
        int $parkTopY,
        int $parkRotation,
        bool $parkHorizontalFlip,
        bool $parkVerticalFlip
    ) {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'tryModePlaceTile', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $chooseTileAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\TryModeChooseTileActionCommand::class);
        $shapeId = $chooseTileAction->getNewShapeId();

        $creator = new \BX\Action\ActionCommandCreator($playerId);

        // Create action to place tile in park
        $placeAction = new \BP\TryModePlaceTileActionCommand(
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
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId));
        $creator->save();
    }

    public function tryModeChoosePark(int $parkId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'tryModeChoosePark', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $chooseParkActions = array_merge(
            \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\TryModePlaceParkActionCommand::class),
            \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\PlacePlayerParkActionCommand::class),
        );
        if (count(array_filter($chooseParkActions, fn ($action) => $action->getParkId() == $parkId)) > 0) {
            throw new \BgaSystemException("You have already placed park $parkId");
        }

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $creator->add(new \BP\TryModeChooseParkActionCommand($playerId, $parkId));
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'choosePark'));
        $creator->save();
    }

    public function tryModeChangeChoosePark(int $parkId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'tryModeChangeChoosePark', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $chooseParkActions = array_merge(
            \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\TryModePlaceParkActionCommand::class),
            \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\PlacePlayerParkActionCommand::class),
        );
        if (count(array_filter($chooseParkActions, fn ($action) => $action->getParkId() == $parkId)) > 0) {
            throw new \BgaSystemException("You have already placed park $parkId");
        }

        \BX\Action\ActionCommandMgr::undoLast($playerId);
        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $creator->add(new \BP\TryModeChooseParkActionCommand($playerId, $parkId));
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId, 'choosePark'));
        $creator->save();
    }

    public function argTryModePlacePark(int $playerId)
    {
        $chooseParkAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\TryModeChooseParkActionCommand::class);
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $argChoose = $this->argTryModeChooseTile($playerId);
        return array_merge(
            [
                "selectedParkId" => $chooseParkAction->getParkId(),
                "selectedParkDefId" => $parkMgr->getRowByKey($chooseParkAction->getParkId())->parkDefId,
                "newParkValidPositions" => $parkMgr->getNewParkValidPositions($playerId),
                "playerParks" => array_values($parkMgr->getPlayerParks($playerId)),
                'choosableParkIds' => array_values(array_filter($argChoose['choosableParkIds'], fn ($pId) => $pId != $chooseParkAction->getParkId())),
                'choosableShapeIds' => $argChoose['choosableShapeIds'],
            ],
        );
    }

    public function tryModePlacePark(int $posX, int $posY)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'tryModePlacePark', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $chooseParkAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\TryModeChooseParkActionCommand::class);

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $newPlacePark = new \BP\TryModePlaceParkActionCommand($playerId, $chooseParkAction->getParkId(), $posX, $posY);
        $creator->add($newPlacePark);
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId));
        $creator->save();
    }
}
