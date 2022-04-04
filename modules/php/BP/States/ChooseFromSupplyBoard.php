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

namespace BP\State\ChooseFromSupplyBoard;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function argChooseFromSupplyBoard(int $playerId)
    {
        $icons = $this->getOverlappedIcons($playerId);
        $logs = [];
        $args = [];
        $seenPriority = [];
        $choosableShapeBaseTypes = [];
        foreach ($icons as $icon) {
            $priority = $icon::getIconPriority();
            $number = "number$priority";
            $iconText = "iconText$priority";
            if (!array_key_exists($priority, $seenPriority)) {
                $seenPriority[$priority] = true;
                $logs[] = '${' . $number . '} ${' . $iconText . '}';
                $args[$iconText] = $icon::getTextPlural();
                $args['i18n'][] = $iconText;
                $args[$number] = 1;
                $choosableShapeBaseTypes = array_merge($choosableShapeBaseTypes, $icon::getChoosableShapes());
            } else {
                $args[$number] += 1;
            }
        }
        $choosableShapeIds = \BX\Action\ActionRowMgrRegister::getMgr('shape')->getTopChoosableShapeIds($choosableShapeBaseTypes);
        $choosableParkIds = [];
        if (array_search(\BP\Park::class, $choosableShapeBaseTypes) !== false) {
            $choosableParkIds = \BX\Action\ActionRowMgrRegister::getMgr('park')->getTopChoosableParkIds();
        }
        return [
            'shapeList' => [
                'log' => implode(', ', $logs),
                'args' => $args,
            ],
            'choosableShapeIds' => $choosableShapeIds,
            'choosableParkIds' => $choosableParkIds,
        ];
    }

    public function chooseShapeFromSupplyBoard(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'chooseShapeFromSupplyBoard', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $this->commonChooseShapeFromSupplyBoard($playerId, $shapeId);
    }
    
    public function changeChooseShapeFromSupplyBoard(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'changeChooseShapeFromSupplyBoard', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        \BX\Action\ActionCommandMgr::undoLast($playerId);
        $this->commonChooseShapeFromSupplyBoard($playerId, $shapeId);
    }

    private function commonChooseShapeFromSupplyBoard(int $playerId, int $shapeId)
    {
        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $newChooseAction = new \BP\ChooseShapeFromSupplyBoardActionCommand($playerId, $shapeId);
        $creator->add($newChooseAction);

        $icons = $this->getOverlappedIcons($playerId, $newChooseAction);

        if (count($icons) > 0) {
            $creator->add(new \BX\PrivateState\NextPrivateStateActionCommand($playerId, 'chooseFromSupplyBoard'));
        } else {
            $creator->add(new \BX\PrivateState\NextPrivateStateActionCommand($playerId, 'confirmTurn'));
        }
        $creator->save();
    }

    public function chooseParkFromSupplyBoard(int $parkId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'chooseParkFromSupplyBoard', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $newChooseAction = new \BP\ChooseParkFromSupplyBoardActionCommand($playerId, $parkId);
        $creator->add($newChooseAction);

        // We do not need the icons but we call it to validate
        $this->getOverlappedIcons($playerId, null, $newChooseAction);

        $creator->add(new \BX\PrivateState\NextPrivateStateActionCommand($playerId, 'placePlayerPark'));
        $creator->save();
    }

    public function changeChooseParkFromSupplyBoard(int $parkId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'changeChooseParkFromSupplyBoard', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        \BX\Action\ActionCommandMgr::undoLast($playerId);
        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $newChooseAction = new \BP\ChooseParkFromSupplyBoardActionCommand($playerId, $parkId);
        $creator->add($newChooseAction);

        // We do not need the icons but we call it to validate
        $this->getOverlappedIcons($playerId, null, $newChooseAction);

        $creator->add(new \BX\PrivateState\NextPrivateStateActionCommand($playerId, 'placePlayerPark'));
        $creator->save();
    }

    private function getOverlappedIcons(int $playerId, $newChooseShape = null, $newPlacePark = null, $newPlaceInParkAction = null)
    {
        $placeInParkAction = $newPlaceInParkAction;
        if ($placeInParkAction === null) {
            $placeInParkAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\PlaceTileInParkActionCommand::class);
        }
        $icons = $placeInParkAction->getOverlappedIcons();

        // Match shapes
        $chooseFromSupplyActions = \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\ChooseShapeFromSupplyBoardActionCommand::class);
        if ($newChooseShape !== null) {
            $chooseFromSupplyActions[] = $newChooseShape;
        }

        foreach ($chooseFromSupplyActions as $action) {
            $shape = $action->getShape();
            $choosableShapesForIcons = [];
            foreach ($icons as $iconsIdx => $icon) {
                $choosableShapesForIcons[$iconsIdx] = $icon::getChoosableShapes();
            }
            $found = false;
            while (count($choosableShapesForIcons) > 0) {
                foreach ($choosableShapesForIcons as $iconsIdx => &$baseClassIds) {
                    $baseClassId = array_shift($baseClassIds);
                    if (count($baseClassIds) == 0) {
                        unset($choosableShapesForIcons[$iconsIdx]);
                    }
                    if (is_subclass_of(get_class($shape), $baseClassId)) {
                        $found = true;
                        unset($icons[$iconsIdx]);
                        break;
                    }
                }
                if ($found) {
                    break;
                }
            }
            if (!$found)
                throw new \BgaSystemException("shapeId {$shape->shapeId} is not in the choosable shapes");
        }

        // Match parks
        $placeParkActions = \BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\PlacePlayerParkActionCommand::class);
        if ($newPlacePark !== null) {
            $placeParkActions[] = $newPlacePark;
        }
        foreach ($placeParkActions as $action) {
            $found = false;
            foreach ($icons as $iconsIdx => $icon) {
                if ($icon == \BP\Park::class) {
                    $found = true;
                    unset($icons[$iconsIdx]);
                    break;
                }
            }
            if (!$found)
                throw new \BgaSystemException("parkId {$action->parkId()} cannot be matched in overlapped icons");
        }
        
        // Cannot take shapes if there are none
        if (!\BX\Action\ActionRowMgrRegister::getMgr('shape')->supplyBoardHasShapes()) {
            $icons = array_filter($icons, fn($icon) => $icon == \BP\Park::class);
        }

        // Cannot take parks if you have the maximum number of parks, even if you overlap a park icon
        if (\BX\Action\ActionRowMgrRegister::getMgr('park')->playerHasMaximumParks($playerId)) {
            $icons = array_filter($icons, fn($icon) => $icon != \BP\Park::class);
        }

        return array_values($icons);
    }
}
