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

require_once(__DIR__ . '/../BX/Action.php');

trait ShapeActionTrait
{
    private $shapeId;

    public function getShapeId()
    {
        return $this->shapeId;
    }

    public function getShape()
    {
        $shapeMgr = self::getMgr('shape');
        $shape = $shapeMgr->getRowByKey($this->shapeId);
        if ($shape === null) {
            throw new \BgaSystemException("shapeId {$this->shapeId} does not exist");
        }
        return $shape;
    }

    private function validateShapeInPlayerSupply($shape)
    {
        if (!$shape->isInPlayerSupply($this->playerId)) {
            throw new \BgaSystemException("shapeId {$this->shapeId} is not in playerId {$this->playerId} supply");
        }
    }

    private function validateShapeOnSupplyBoard($shape)
    {
        if (!$shape->isOnSupplyBoard()) {
            throw new \BgaSystemException("shapeId {$this->shapeId} is not on supply board");
        }
    }
}

trait ParkActionTrait
{
    private $parkId;

    public function getParkId()
    {
        return $this->parkId;
    }

    public function getPark()
    {
        $parkMgr = self::getMgr('park');
        $park = $parkMgr->getRowByKey($this->parkId);
        if ($park === null) {
            throw new \BgaSystemException("parkId {$this->parkId} does not exist");
        }
        return $park;
    }

    private function validateParkOnSupplyBoardTop($park)
    {
        if (!$park->isOnSupplyBoardTop()) {
            throw new \BgaSystemException("parkid {$this->parkid} is not on top of supply board");
        }
    }
}

class EnterPlayLoopActionCommand extends \BX\PrivateState\JumpPrivateStateActionCommand
{
    use ShapeActionTrait;

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        parent::do($notifier);
        if ($this->shapeId === null) {
            $this->shapeId = $this->getMgr('shape')->getPlayerSupplyFirstShapeId($this->playerId);
        }
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_PLAYER_SUPPLY, [
            'shapeId' => $this->shapeId,
        ]);
        parent::do($notifier);
    }
}

class ChooseTileFromPlayerSupplyActionCommand extends \BX\Action\BaseActionCommand
{
    use ShapeActionTrait;

    public function __construct(int $playerId, int $shapeId)
    {
        parent::__construct($playerId);
        $this->shapeId = $shapeId;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $shape = $this->getShape();
        $this->validateShapeInPlayerSupply($shape);
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_PLAYER_SUPPLY, [
            'shapeId' => $this->shapeId,
        ]);
    }
}

class PlaceTileInParkActionCommand extends \BX\Action\BaseActionCommand
{
    use ShapeActionTrait;

    private $parkId;
    private $parkTopX;
    private $parkTopY;
    private $parkRotation;
    private $parkHorizontalFlip;
    private $parkVerticalFlip;
    private $isValid;
    private $overlappedIcons;
    private $scoreActions;
    private $statueShapeIds;

    public function __construct(
        int $playerId,
        int $shapeId,
        int $parkId,
        int $parkTopX,
        int $parkTopY,
        int $parkRotation,
        bool $parkHorizontalFlip,
        bool $parkVerticalFlip
    ) {
        parent::__construct($playerId);
        $this->shapeId = $shapeId;
        $this->parkId = $parkId;
        $this->parkTopX = $parkTopX;
        $this->parkTopY = $parkTopY;
        $this->parkRotation = $parkRotation;
        $this->parkHorizontalFlip = $parkHorizontalFlip;
        $this->parkVerticalFlip = $parkVerticalFlip;
        $this->overlappedIcons = null;
        $this->scoreActions = [];
        $this->statueShapeIds = [];
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $this->scoreActions = [];
        $this->statueShapeIds = [];
        $this->validate();

        $shape = $this->getShape();
        $shape->modifyAction();
        $shape->placeInPark(
            $this->parkId,
            $this->parkTopX,
            $this->parkTopY,
            $this->parkRotation,
            $this->parkHorizontalFlip,
            $this->parkVerticalFlip
        );
        $this->checkIconsOverlap();
        $notifier->notify(
            NTF_MOVE_SHAPE_TO_PLAYER_PARK,
            clienttranslate('${player_name} places a tile (${shapeName}) in their park ${shapeImage}'),
            [
                'shapeId' => $this->shapeId,
                'parkId' => $this->parkId,
                'parkTopX' => $this->parkTopX,
                'parkTopY' => $this->parkTopY,
                'parkRotation' => $this->parkRotation,
                'parkHorizontalFlip' => $this->parkHorizontalFlip,
                'parkVerticalFlip' => $this->parkVerticalFlip,
                'shapeName' => $shape->getShapeNameText(),
                'shapeImage' => $shape->shapeDefId,
                'i18n' => ['shapeName'],
            ]
        );
        $scoreAction = new \BX\Player\UpdatePlayerScoreActionCommand($this->playerId);
        $scoreAction->do($notifier, $shape->shapeScore, null, ['shapeId' => $this->shapeId]);
        $this->scoreActions[] = $scoreAction;
        $this->fillParks($notifier);
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        foreach ($this->statueShapeIds as $shapeId) {
            $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_SUPPLY_BOARD, [
                'shapeId' => $shapeId,
            ]);
        }
        $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_PLAYER_SUPPLY, [
            'shapeId' => $this->shapeId,
        ]);
        foreach (array_reverse($this->scoreActions) as $scoreAction) {
            $scoreAction->undo($notifier);
        }
    }

    public function reevaluate(?\BX\Action\BaseActionCommandNotifier $notifier, array &$args)
    {
        // Undo as soon as there is a conflict, which should not happen
        // since the selected shape is from the player's supply
        if (array_key_exists('chooseShapeIds', $args)) {
            foreach ($args['chooseShapeIds'] as $shapeId) {
                if ($this->shapeId == $shapeId) {
                    return \BX\Action\REEVALUATE_UNDO;
                }
            }
        }
        // Undo if there is a conflict with a statue since this changes the score
        if (array_key_exists('statueShapeIds', $args)) {
            foreach ($args['statueShapeIds'] as $shapeId) {
                foreach ($this->statueShapeIds as $otherShapeId) {
                    if ($otherShapeId == $shapeId) {
                        return \BX\Action\REEVALUATE_UNDO;
                    }
                }
            }
        }
        return \BX\Action\REEVALUATE_NO_CHANGE;
    }

    public function getReevaluationArgs()
    {
        return [
            'statueShapeIds' => $this->statueShapeIds,
        ];
    }

    public function getOverlappedIcons()
    {
        if ($this->overlappedIcons === null) {
            throw new \BgaSystemException("overlappedIcons is still null");
        }
        return $this->overlappedIcons;
    }

    public function getBearStatueShapeIds()
    {
        return $this->statueShapeIds;
    }

    private function validate()
    {
        if ($this->isValid) {
            return;
        }
        $shape = $this->getShape();
        $this->validateShapeInPlayerSupply($shape);
        $parkMgr = self::getMgr('park');
        if (!$parkMgr->parkExistsForPlayer($this->parkId, $this->playerId)) {
            throw new \BgaSystemException("parkId {$this->parkId} does not exist for playerId {$this->playerId}");
        }
        if (!$parkMgr->isShapePositionValid(
            (new ParkShapeValidityArgs())
                ->setPlayerId($this->playerId)
                ->setShapeId($this->shapeId)
                ->setParkId($this->parkId)
                ->setParkTopX($this->parkTopX)
                ->setParkTopY($this->parkTopY)
                ->setParkRotation($this->parkRotation)
                ->setParkHorizontalFlip($this->parkHorizontalFlip)
                ->setParkVerticalFlip($this->parkVerticalFlip)
        )) {
            throw new \BgaSystemException("Cannot place shapeId {$this->shapeId} in parkId {$this->parkId} with {$this->parkTopX} {$this->parkTopY} {$this->parkRotation} {$this->parkHorizontalFlip} {$this->parkVerticalFlip}");
        }
        $this->isValid = true;
    }

    private function checkIconsOverlap()
    {
        if ($this->overlappedIcons !== null) {
            return;
        }
        $this->overlappedIcons = self::getMgr('park')->getShapeOverlappedIcons($this->playerId, $this->shapeId);
    }

    private function fillParks($notifier)
    {
        $parkMgr = self::getMgr('park');
        $shapeMgr = self::getMgr('shape');
        foreach ($parkMgr->getFilledParkMissingStatueGrid($this->playerId) as $grid) {
            $statue = $shapeMgr->getFirstSupplyBoardShapeOfType(\BP\ShapeBearStatue::class);
            $statue->modifyAction();
            $statue->moveToPlayerSupply($this->playerId);
            $statue->placeInPark(
                $grid->parkId,
                $grid->x,
                $grid->y,
                0,
                false,
                false
            );
            $this->statueShapeIds[] = $statue->shapeId;
            $notifier->notify(
                NTF_MOVE_SHAPE_TO_PLAYER_PARK,
                clienttranslate('${player_name} fills a park and places a Bear Statue in their park ${shapeImage}'),
                [
                    'shapeId' => $statue->shapeId,
                    'parkId' => $grid->parkId,
                    'parkTopX' => $grid->x,
                    'parkTopY' => $grid->y,
                    'parkRotation' => 0,
                    'parkHorizontalFlip' => false,
                    'parkVerticalFlip' => false,
                    'shapeImage' => $statue->shapeDefId,
                ]
            );
            $scoreAction = new \BX\Player\UpdatePlayerScoreActionCommand($this->playerId);
            $scoreAction->do($notifier, $statue->shapeScore, null, ['shapeId' => $statue->shapeId]);
            $this->scoreActions[] = $scoreAction;
        }
    }
}

class ChooseShapeFromSupplyBoardActionCommand extends \BX\Action\BaseActionCommand
{
    use ShapeActionTrait;
    private $validated;
    private $undoSupplyShapesCount;

    public function __construct(int $playerId, int $shapeId)
    {
        parent::__construct($playerId);
        $this->shapeId = $shapeId;
        $this->validated = false;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $shape = $this->getShape();
        $this->validateShapeOnSupplyBoard($shape);
        $shapeMgr = self::getMgr('shape');
        $topShape = $shapeMgr->getFirstSupplyBoardShapeOfType($shape->classId);
        if ($topShape === null)
            throw new \BgaSystemException("There is no top shape");
        if (!$this->validated) {
            if ($topShape->shapeId != $shape->shapeId)
                throw new \BgaSystemException("shapeId {$this->shapeId} is not the top shape on the supply board");
        }

        $this->validated = true;

        $this->undoSupplyShapesCount = $shapeMgr->getSupplyShapesCount(get_class($shape));

        $shape->modifyAction();
        $shape->moveToPlayerSupply($this->playerId);

        $notifier->notify(
            NTF_MOVE_SHAPE_TO_PLAYER_SUPPLY,
            clienttranslate('${player_name} takes a tile (${shapeName}) from the supply board ${shapeImage}'),
            [
                'shapeId' => $this->shapeId,
                'shapeName' => $shape->getShapeNameText(),
                'shapeImage' => $shape->shapeDefId,
                'i18n' => ['shapeName'],
            ]
        );
        $notifier->notifyNoMessage(NTF_UPDATE_SUPPLY_SHAPES_COUNT, [
            'supplyShapesCount' => $shapeMgr->getSupplyShapesCount(get_class($shape)),
        ]);
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_SUPPLY_BOARD, [
            'shapeId' => $this->shapeId,
        ]);
        $notifier->notifyNoMessage(NTF_UPDATE_SUPPLY_SHAPES_COUNT, [
            'supplyShapesCount' => $this->undoSupplyShapesCount,
        ]);
    }

    public function reevaluate(?\BX\Action\BaseActionCommandNotifier $notifier, array &$args)
    {
        // If there is a shape that can be swapped with the shape we are trying to place
        // because it gives no score, we do it. If not, we have to delete it.
        if (!array_key_exists('chooseShapeIds', $args)) {
            $args['chooseShapeIds'] = [];
        }
        if (!array_key_exists('swapShapes', $args)) {
            $args['swapShapes'] = [];
        }
        if (!array_key_exists('returnShapes', $args)) {
            $args['returnShapes'] = [];
        }
        if (!array_key_exists('undoSupplyShapesCount', $args)) {
            $args['undoSupplyShapesCount'] = [];
        }
        if ($notifier === null) {
            foreach ($args['chooseShapeIds'] as $shapeId) {
                if ($this->shapeId == $shapeId) {
                    $shape = $this->getShape();
                    $classId = get_class($shape);
                    if (!is_subclass_of($classId, \BP\ShapeGreenBase::class)) {
                        $args['returnShapes'][$this->shapeId] = true;
                        return \BX\Action\REEVALUATE_DELETE;
                    }
                    $shapeMgr = self::getMgr('shape');
                    $supplyShapes = $shapeMgr->getSupplyBoardShapeOfTypeInOrder($classId);
                    $supplyShapes = array_values(array_filter($supplyShapes, fn ($s) => array_search($s->shapeId, $args['chooseShapeIds']) === false));
                    if (count($supplyShapes) == 0) {
                        $args['returnShapes'][$this->shapeId] = true;
                        return \BX\Action\REEVALUATE_DELETE;
                    }
                    $args['swapShapes'][$this->shapeId] = $supplyShapes[0]->shapeId;
                    $args['undoSupplyShapesCount'][$this->shapeId] = count($supplyShapes);
                    $args['chooseShapeIds'][] = $supplyShapes[0]->shapeId;
                    return \BX\Action\REEVALUATE_UPDATE;
                }
            }
            return \BX\Action\REEVALUATE_NO_CHANGE;
        }
        if (array_key_exists($this->shapeId, $args['swapShapes'])) {
            $notifier->notifyNoMessage(NTF_SWAP_SHAPES, [
                'shapeId1' => $this->shapeId,
                'shapeId2' => $args['swapShapes'][$this->shapeId],
            ]);
            $shape = $this->getShape();
            foreach ($this->undoSupplyShapesCount as $shapeCount) {
                if ($shapeCount->classId == get_class($shape)) {
                    $shapeCount->count = $args['undoSupplyShapesCount'][$this->shapeId];
                    break;
                }
            }
            $this->shapeId = $args['swapShapes'][$this->shapeId];
            return \BX\Action\REEVALUATE_UPDATE;
        }
        if (array_key_exists($this->shapeId, $args['returnShapes'])) {
            $notifier->notifyNoMessage(NTF_MOVE_SHAPE_TO_SUPPLY_BOARD, [
                'shapeId' => $this->shapeId,
            ]);
            return \BX\Action\REEVALUATE_DELETE;
        }
        return \BX\Action\REEVALUATE_NO_CHANGE;
    }

    public function getReevaluationArgs()
    {
        return [
            'chooseShapeIds' => [$this->shapeId],
        ];
    }
}

class ChooseParkFromSupplyBoardActionCommand extends \BX\Action\BaseActionCommand
{
    use ParkActionTrait;
    private $validated;

    public function __construct(int $playerId, int $parkId)
    {
        parent::__construct($playerId);
        $this->parkId = $parkId;
        $this->validated = false;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $park = $this->getPark();
        if (!$this->validated) {
            $this->validateParkOnSupplyBoardTop($park);
        }
        $this->validated = true;
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
    }

    public function reevaluate(?\BX\Action\BaseActionCommandNotifier $notifier, array &$args)
    {
        // Always undo parks to avoid having to deal with this case in the UI
        if (array_key_exists('chooseParkIds', $args) && count($args['chooseParkIds']) > 0) {
            return \BX\Action\REEVALUATE_UNDO;
        }
        return \BX\Action\REEVALUATE_NO_CHANGE;
    }

    public function getReevaluationArgs()
    {
        return [
            'chooseParkIds' => [$this->parkId],
        ];
    }

    public function mustAlwaysUndoAction()
    {
        return true;
    }
}

class PlacePlayerParkActionCommand extends \BX\Action\BaseActionCommand
{
    use ParkActionTrait;

    private $posX;
    private $posY;
    private $playerParksBefore;

    public function __construct(int $playerId, int $parkId, int $posX, int $posY)
    {
        parent::__construct($playerId);
        $this->parkId = $parkId;
        $this->posX = $posX;
        $this->posY = $posY;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $park = $this->getPark();
        $this->validateParkOnSupplyBoardTop($park);
        $this->validatePosition();
        $parkMgr = self::getMgr('park');
        if ($this->playerParksBefore === null) {
            $this->playerParksBefore = \BX\UI\deepCopyToArray(array_values($parkMgr->getPlayerParks($this->playerId)));
        }

        $park->modifyAction();
        $park->moveToPlayerPark($this->playerId, $this->posX, $this->posY);

        $notifier->notify(
            NTF_REPLACE_PLAYER_PARK_AREA,
            clienttranslate('${player_name} places a new Park Area in their park ${parkImage}'),
            [
                'playerParks' => array_values($parkMgr->getPlayerParks($this->playerId)),
                'parkImage' => $park->parkDefId,
            ]
        );
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_REPLACE_PLAYER_PARK_AREA, [
            'playerParks' => $this->playerParksBefore,
        ]);
    }

    public function reevaluate(?\BX\Action\BaseActionCommandNotifier $notifier, array &$args)
    {
        // Always undo parks to avoid having to deal with this case in the UI
        if (array_key_exists('chooseParkIds', $args) && count($args['chooseParkIds']) > 0) {
            return \BX\Action\REEVALUATE_UNDO;
        }
        return \BX\Action\REEVALUATE_NO_CHANGE;
    }

    public function getReevaluationArgs()
    {
        return [
            'chooseParkIds' => [$this->parkId],
        ];
    }

    private function validatePosition()
    {
        $parkMgr = self::getMgr('park');
        foreach ($parkMgr->getNewParkValidPositions($this->playerId) as $pos) {
            if ($pos->posX == $this->posX && $pos->posY == $this->posY) {
                return;
            }
        }
        throw new \BgaSystemException("parkId {$this->parkId} cannot be placed at {$this->posX}/{$this->posY}");
    }
}


class GainAchievementActionCommand extends \BX\Action\BaseActionCommand
{
    private $gainedBearStatueCount;
    private $gainedAchievementIds;
    private $scoreActions;

    public function __construct(int $playerId, int $gainedBearStatueCount)
    {
        parent::__construct($playerId);
        $this->gainedBearStatueCount = $gainedBearStatueCount;
        $this->gainedAchievementIds = [];
        $this->scoreActions = [];
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        if (!$this->gameUsesAchievements()) {
            return;
        }
        $achievementMgr = self::getMgr('achievement');
        $gainedAchievements = $achievementMgr->getPlayerGainedAchievements($this->playerId, $this->gainedBearStatueCount);
        foreach ($gainedAchievements as $achievement) {
            $this->gainAchievement($notifier, $achievement);
        }
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        if (!$this->gameUsesAchievements()) {
            return;
        }
        foreach ($this->gainedAchievementIds as $achievementId) {
            $notifier->notifyNoMessage(NTF_MOVE_ACHIEVEMENT_TO_SUPPLY_BOARD, [
                'achievementId' => $achievementId,
            ]);
        }
        foreach (array_reverse($this->scoreActions) as $scoreAction) {
            $scoreAction->undo($notifier);
        }
    }

    public function getGainedAchievementIds()
    {
        return $this->gainedAchievementIds;
    }

    public function reevaluate(?\BX\Action\BaseActionCommandNotifier $notifier, array &$args)
    {
        // Gaining achievements scores points so we need to undo if there is a conlict
        if (array_key_exists('gainedAchievementIds', $args)) {
            foreach ($args['gainedAchievementIds'] as $gainedId) {
                foreach ($this->gainedAchievementIds as $achievementId) {
                    if ($achievementId == $gainedId) {
                        return \BX\Action\REEVALUATE_UNDO;
                    }
                }
            }
        }
        return \BX\Action\REEVALUATE_NO_CHANGE;
    }

    public function getReevaluationArgs()
    {
        return [
            'gainedAchievementIds' => $this->gainedAchievementIds,
        ];
    }

    private function gameUsesAchievements()
    {
        $achievementMgr = self::getMgr('achievement');
        return $achievementMgr->gameUsesAchievements();
    }

    private function gainAchievement($notifier, $achievement)
    {
        $this->gainedAchievementIds[] = $achievement->achievementId;
        $achievement->modifyAction();
        $achievement->moveToPlayer($this->playerId);
        $notifier->notify(
            NTF_MOVE_ACHIEVEMENT_TO_PLAYER,
            clienttranslate('${player_name} meets the requirements for an achievement (${achievementName}) ${achievementImage}'),
            [
                'achievementId' => $achievement->achievementId,
                'achievementName' => $achievement->achievementName(),
                'achievementImage' => $achievement->achievementId,
                'i18n' => ['achievementName'],
            ]
        );
        $scoreAction = new \BX\Player\UpdatePlayerScoreActionCommand($this->playerId);
        $scoreAction->do($notifier, $achievement->achievementScore, null, ['achievementId' => $achievement->achievementId]);
        $this->scoreActions[] = $scoreAction;
    }
}

class EnterTryModeActionCommand extends \BX\PrivateState\JumpPrivateStateActionCommand
{
    public function __construct(int $playerId)
    {
        parent::__construct($playerId, STATE_PRIVATE_TRY_MODE_CHOOSE_TILE_ID);
    }
}

class TryModeChooseTileActionCommand extends \BX\Action\BaseActionCommand
{
    use ShapeActionTrait;
    private $newShapeId;
    private $isValid;

    public function getNewShapeId()
    {
        return $this->newShapeId;
    }

    public function __construct(int $playerId, int $shapeId)
    {
        parent::__construct($playerId);
        $this->shapeId = $shapeId;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $shape = $this->getShape();
        if (!$this->isValid) {
            if (!$shape->isInPlayerSupply($this->playerId) && !$shape->isOnSupplyBoard()) {
                throw new \BgaSystemException("shapeId {$this->shapeId} is not in playerId {$this->playerId} supply or on supply board");
            }
            $this->isValid = true;
        }
        $shapeMgr = self::getMgr('shape');
        $this->newShapeId = $shapeMgr->generateNewShapeId();
        $newShape = $shape->cloneNewAction($this->newShapeId);
        $notifier->notifyNoMessage(NTF_CREATE_SHAPE, [
            'shape' => $newShape,
        ]);
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_DELETE_SHAPE, [
            'shapeId' => $this->newShapeId,
        ]);
    }

    public function mustAlwaysUndoAction()
    {
        return true;
    }
}

class TryModePlaceTileActionCommand extends \BX\Action\BaseActionCommand
{
    use ShapeActionTrait;

    private $parkId;
    private $parkTopX;
    private $parkTopY;
    private $parkRotation;
    private $parkHorizontalFlip;
    private $parkVerticalFlip;
    private $isValid;

    public function __construct(
        int $playerId,
        int $shapeId,
        int $parkId,
        int $parkTopX,
        int $parkTopY,
        int $parkRotation,
        bool $parkHorizontalFlip,
        bool $parkVerticalFlip
    ) {
        parent::__construct($playerId);
        $this->shapeId = $shapeId;
        $this->parkId = $parkId;
        $this->parkTopX = $parkTopX;
        $this->parkTopY = $parkTopY;
        $this->parkRotation = $parkRotation;
        $this->parkHorizontalFlip = $parkHorizontalFlip;
        $this->parkVerticalFlip = $parkVerticalFlip;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $this->validate();

        $shape = $this->getShape();
        $shape->modifyAction();
        $shape->moveToPlayerSupply($this->playerId);
        $shape->placeInPark(
            $this->parkId,
            $this->parkTopX,
            $this->parkTopY,
            $this->parkRotation,
            $this->parkHorizontalFlip,
            $this->parkVerticalFlip
        );
        $notifier->notifyNoMessage(
            NTF_MOVE_SHAPE_TO_PLAYER_PARK,
            [
                'shapeId' => $this->shapeId,
                'parkId' => $this->parkId,
                'parkTopX' => $this->parkTopX,
                'parkTopY' => $this->parkTopY,
                'parkRotation' => $this->parkRotation,
                'parkHorizontalFlip' => $this->parkHorizontalFlip,
                'parkVerticalFlip' => $this->parkVerticalFlip,
            ]
        );
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_DELETE_SHAPE, [
            'shapeId' => $this->shapeId,
        ]);
    }

    private function validate()
    {
        if ($this->isValid) {
            return;
        }
        // Validate that the shape exist
        $this->getShape();
        $parkMgr = self::getMgr('park');
        if (!$parkMgr->parkExistsForPlayer($this->parkId, $this->playerId)) {
            throw new \BgaSystemException("parkId {$this->parkId} does not exist for playerId {$this->playerId}");
        }
        if (!$parkMgr->isShapePositionValid(
            (new ParkShapeValidityArgs())
                ->setPlayerId($this->playerId)
                ->setShapeId($this->shapeId)
                ->setParkId($this->parkId)
                ->setParkTopX($this->parkTopX)
                ->setParkTopY($this->parkTopY)
                ->setParkRotation($this->parkRotation)
                ->setParkHorizontalFlip($this->parkHorizontalFlip)
                ->setParkVerticalFlip($this->parkVerticalFlip)
                ->setValidateAdjacency(false)
        )) {
            throw new \BgaSystemException("Cannot place shapeId {$this->shapeId} in parkId {$this->parkId} with {$this->parkTopX} {$this->parkTopY} {$this->parkRotation} {$this->parkHorizontalFlip} {$this->parkVerticalFlip}");
        }
        $this->isValid = true;
    }
}

class TryModeChooseParkActionCommand extends \BX\Action\BaseActionCommand
{
    use ParkActionTrait;

    public function __construct(int $playerId, int $parkId)
    {
        parent::__construct($playerId);
        $this->parkId = $parkId;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $park = $this->getPark();
        if (!$park->isVisible()) {
            throw new \BgaSystemException("parkId {$this->parkId} is not visible");
        }
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
    }

    public function reevaluate(?\BX\Action\BaseActionCommandNotifier $notifier, array &$args)
    {
        // Undo if it's the last action and a park is chosen
        if (array_key_exists('isLastAction', $args) && $args['isLastAction']) {
            if (array_key_exists('chooseParkIds', $args)) {
                foreach ($args['chooseParkIds'] as $parkId) {
                    if ($this->parkId == $parkId) {
                        return \BX\Action\REEVALUATE_UNDO_SILENT;
                    }
                }
            }
        }
        return \BX\Action\REEVALUATE_NO_CHANGE;
    }

    public function mustAlwaysUndoAction()
    {
        return true;
    }
}
class TryModePlaceParkActionCommand extends \BX\Action\BaseActionCommand
{
    use ParkActionTrait;

    private $posX;
    private $posY;
    private $playerParksBefore;
    private $newParkId;

    public function getNewParkId()
    {
        return $this->newParkId;
    }

    public function __construct(int $playerId, int $parkId, int $posX, int $posY)
    {
        parent::__construct($playerId);
        $this->parkId = $parkId;
        $this->posX = $posX;
        $this->posY = $posY;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $park = $this->getPark();
        if (!$park->isVisible()) {
            throw new \BgaSystemException("parkId {$this->parkId} is not visible");
        }
        $this->validatePosition();
        $parkMgr = self::getMgr('park');
        if ($this->playerParksBefore === null) {
            $this->playerParksBefore = \BX\UI\deepCopyToArray(array_values($parkMgr->getPlayerParks($this->playerId)));
        }

        $this->newParkId = $parkMgr->generateNewParkId();
        $newPark = $park->cloneNewAction($this->newParkId);
        $newPark->moveToPlayerPark($this->playerId, $this->posX, $this->posY);
        $newPark->supplyPile = null;

        $notifier->notifyNoMessage(NTF_CREATE_PARK, [
            'park' => $newPark,
        ]);
        $notifier->notifyNoMessage(
            NTF_REPLACE_PLAYER_PARK_AREA,
            [
                'playerParks' => array_values($parkMgr->getPlayerParks($this->playerId)),
                'parkImage' => $newPark->parkDefId,
            ]
        );
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_DELETE_PARK, [
            'parkId' => $this->newParkId,
        ]);
        $notifier->notifyNoMessage(NTF_REPLACE_PLAYER_PARK_AREA, [
            'playerParks' => $this->playerParksBefore,
        ]);
    }

    private function validatePosition()
    {
        $parkMgr = self::getMgr('park');
        foreach ($parkMgr->getNewParkValidPositions($this->playerId) as $pos) {
            if ($pos->posX == $this->posX && $pos->posY == $this->posY) {
                return;
            }
        }
        throw new \BgaSystemException("parkId {$this->parkId} cannot be placed at {$this->posX}/{$this->posY}");
    }
}
