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

namespace BP\State\Main;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function enterPlayLoop()
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'enterPlayLoop', \BX\PrivateState\PLAYER_ACTIVE_STATUS_INACTIVE);

        \BX\Action\ActionCommandMgr::applyAndSaveOne(new \BP\EnterPlayLoopActionCommand($playerId, $this->stChooseMainEnterState($playerId)));
    }

    public function stNextPlayer()
    {
        $this->activeNextPlayer();
        $playerId = $this->getActivePlayerId();
        // Remove inactive state so that the player cannot undo up to that point when its their turn
        \BX\Action\ActionCommandMgr::apply($playerId);
        if (\BX\Action\ActionRowMgrRegister::getMgr('private_state')->currentStateId($playerId) != STATE_PRIVATE_INACTIVE_TURN_ID) {
            \BX\Action\ActionCommandMgr::removeOldestActionMatching($playerId, function ($action) {
                return (get_class($action) == \BP\EnterPlayLoopActionCommand::class);
            });
        }

        \BX\Action\ActionRowMgrRegister::getMgr('private_state')->clearPlayerStateIfEqual($playerId, STATE_PRIVATE_INACTIVE_TURN_ID);
        
        \BX\Action\ActionCommandMgr::clear();

        // If current player park is full, game ends
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        if ($parkMgr->playerParksAreFull($playerId)) {
            $this->endGame();
            return;
        }

        // If supply board is empty and no one can place shapes, game ends
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        if (!$shapeMgr->supplyBoardHasGreenShapes()) {
            if (!$this->atLeastOnePlayerCanPlaceShapes()) {
                $this->endGame();
                return;
            }
        }

        // Send notification to display that it's the last turn
        if ($parkMgr->atLeastOnePlayerParksAreFull(array_keys($this->loadPlayersBasicInfos()))) {
            $notifier = new \BX\Action\ActionCommandNotifierPublic($playerId);
            $notifier->notifyNoMessage(NTF_DISPLAY_LAST_TURN, []);
        }
        $this->gamestate->nextState('nextPlayer');
    }

    public function stChooseMainEnterState($playerId)
    {
        \BX\Action\ActionCommandMgr::clear();
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        if (!$this->playerCanPlaceShapes($playerId)) {
            if ($shapeMgr->supplyBoardHasGreenShapes()) {
                return STATE_PRIVATE_PASS_TURN_CHOOSE_FROM_SUPPLY_BOARD_ID;
            } else {
                return STATE_PRIVATE_PASS_TURN_NO_SHAPE_ID;
            }
        }
        // Only one shape, select it
        if (count($shapeMgr->getPlayerSupplyShapes($playerId)) == 1) {
            return STATE_PRIVATE_PLACE_TILE_IN_PARK_ID;
        }
        return STATE_PRIVATE_CHOOSE_TILE_FROM_PLAYER_SUPPLY_ID;
    }

    public function argsAllPrivateState(int $playerId)
    {
        $enterPlayLoopAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\EnterPlayLoopActionCommand::class);
        $enterTryModeAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BP\EnterTryModeActionCommand::class);
        $hasUndoAction = \BX\Action\ActionCommandMgr::getMostRecentActionClassId($playerId, \BX\Action\ReevaluateHasUndoneActionCommand::class);

        $currentTurnShapeIds = [];
        foreach (\BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\PlaceTileInParkActionCommand::class) as $action) {
            $currentTurnShapeIds[] = $action->getShapeId();
            $currentTurnShapeIds = array_merge($currentTurnShapeIds, $action->getBearStatueShapeIds());
        }
        foreach (\BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\ChooseShapeFromSupplyBoardActionCommand::class) as $action) {
            $currentTurnShapeIds[] = $action->getShapeId();
        }
        foreach (\BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\TryModePlaceTileActionCommand::class) as $action) {
            $currentTurnShapeIds[] = $action->getShapeId();
        }

        $currentTurnParkIds = [];
        foreach (\BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\PlacePlayerParkActionCommand::class) as $action) {
            $currentTurnParkIds[] = $action->getParkId();
        }
        foreach (\BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\TryModePlaceParkActionCommand::class) as $action) {
            $currentTurnParkIds[] = $action->getNewParkId();
        }

        $currentTurnAchievementIds = [];
        foreach (\BX\Action\ActionCommandMgr::getAllActionClassIdInActionOrder($playerId, \BP\GainAchievementActionCommand::class) as $action) {
            $currentTurnAchievementIds = array_merge($currentTurnAchievementIds, $action->getGainedAchievementIds());
        }

        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        return [
            'isInPrepareMode' => ($enterPlayLoopAction !== null),
            'playerParksAreFull' => $parkMgr->playerParksAreFull($playerId),
            'isInTryMode' => ($enterTryModeAction !== null),
            'currentTurnShapeIds' => $currentTurnShapeIds,
            'currentTurnParkIds' => $currentTurnParkIds,
            'currentTurnAchievementIds' => $currentTurnAchievementIds,
            'hasUndoAction' => ($hasUndoAction !== null),
        ];
    }

    private function endGame()
    {
        $this->endGameImplementation();
        $this->gamestate->nextState('endGame');
    }

    private function endGameImplementation()
    {
        \BX\Action\ActionCommandMgr::clear();
        foreach (array_keys($this->loadPlayersBasicInfos()) as $playerId) {
            \BX\Action\ActionCommandMgr::undoAllEndGame($playerId);
        }
        \BX\Action\ActionCommandMgr::clear();
        // Update tie breaker
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $playerMgr = \BX\Action\ActionRowMgrRegister::getMgr('player');
        $achievementMgr = \BX\Action\ActionRowMgrRegister::getMgr('achievement');
        foreach ($playerMgr->getAllRowsByKey() as $playerId => $player) {
            $playerMgr->modifyAction($player);
            $player->playerScoreAux = $shapeMgr->getPlayerSupplyShapesScore($playerId);
        }
        $playerMgr->saveModifiedActions();
        $playerMgr->clearModifiedActions();

        // Update stats
        foreach ($playerMgr->getAllRowsByKey() as $playerId => $player) {
            $shapeScores = [
                \BP\ShapeGreenBase::class => [],
                \BP\ShapeWhiteAnimalHouseBase::class => [],
                \BP\ShapeOrangeEnclosureBase::class => [],
                \BP\ShapeBearStatue::class => [],
            ];
            foreach ($shapeMgr->getPlayerParkShapes($playerId) as $shape) {
                foreach (array_keys($shapeScores) as $baseClassId) {
                    $shapeClassId = get_class($shape);
                    if ($shapeClassId == $baseClassId || is_subclass_of($shapeClassId, $baseClassId)) {
                        $shapeScores[$baseClassId][] = $shape->shapeScore;
                        break;
                    }
                }
            }
            self::setStat($player->playerScore, STATS_PLAYER_SCORE_TOTAL, $playerId);
            self::setStat(array_sum($shapeScores[\BP\ShapeWhiteAnimalHouseBase::class]), STATS_PLAYER_SCORE_ANIMAL_HOUSE, $playerId);
            self::setStat(array_sum($shapeScores[\BP\ShapeOrangeEnclosureBase::class]), STATS_PLAYER_SCORE_ENCLOSURE, $playerId);
            self::setStat(array_sum($shapeScores[\BP\ShapeBearStatue::class]), STATS_PLAYER_SCORE_BEAR_STATUE, $playerId);
            if ($achievementMgr->gameUsesAchievements()) {
                self::setStat(
                    array_sum(array_map(fn($a) => $a->achievementScore, array_filter($achievementMgr->getAll(), fn($a) => $a->playerId !== null && $a->playerId == $playerId))),
                    STATS_PLAYER_SCORE_ACHIEVEMENT,
                    $playerId
                );
            }
            self::setStat($player->playerScoreAux, STATS_PLAYER_SCORE_PLAYER_SUPPLY, $playerId);
            self::setStat(count($shapeScores[\BP\ShapeGreenBase::class]), STATS_PLAYER_NB_PLACED_SHAPE_GREEN, $playerId);
            self::setStat(count($shapeScores[\BP\ShapeWhiteAnimalHouseBase::class]), STATS_PLAYER_NB_PLACED_SHAPE_ANIMAL_HOUSE, $playerId);
            self::setStat(count($shapeScores[\BP\ShapeOrangeEnclosureBase::class]), STATS_PLAYER_NB_PLACED_SHAPE_ENCLOSURE, $playerId);
            self::setStat(count($shapeScores[\BP\ShapeBearStatue::class]), STATS_PLAYER_NB_PLACED_SHAPE_BEAR_STATUE, $playerId);
            if ($achievementMgr->gameUsesAchievements()) {
                self::setStat(
                    count(array_filter($achievementMgr->getAll(), fn($a) => $a->playerId !== null && $a->playerId == $playerId)),
                    STATS_PLAYER_NB_ACHIEVEMENT,
                    $playerId
                );
            }
        }
    }

    private function atLeastOnePlayerCanPlaceShapes()
    {
        foreach (array_keys($this->loadPlayersBasicInfos()) as $playerId) {
            if ($this->playerCanPlaceShapes($playerId)) {
                return true;
            }
        }
        return false;
    }

    private function playerCanPlaceShapes(int $playerId)
    {
        // Player must take a single shape and pass if he has no shape in its supply
        $playerSupplyShapes = \BX\Action\ActionRowMgrRegister::getMgr('shape')->getPlayerSupplyShapes($playerId);
        if (count($playerSupplyShapes) == 0) {
            return false;
        }

        // Player must take a single shape and pass if he cannot place is shapes
        $canPlaceShape = false;
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        foreach ($playerSupplyShapes as $shape) {
            if (count($parkMgr->getValidPositions((new \BP\ParkShapeValidityArgs())->setPlayerId($playerId)->setShapeId($shape->shapeId)))) {
                $canPlaceShape = true;
                break;
            }
        }
        if (!$canPlaceShape) {
            return false;
        }
        return true;
    }
}
