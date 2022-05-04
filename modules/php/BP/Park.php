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
require_once('Shape.php');

const PARK_SIZE = 4;
const EXTRA_PLACEMENT_SIZE = 3;
const PARK_COUNT_WITH_ENTRANCE = 4;
const PARK_COUNT_WITHOUT_ENTRANCE = 16 - PARK_COUNT_WITH_ENTRANCE;
const PARK_NB_PILE = 2;
const PARKS_PER_PILE = 6;
const PLAYER_MAXIMUM_NUMBER_OF_PARKS = 4;

class Park extends \BX\Action\BaseActionRow
{
    public const PARK_ICONS = [
        // X, Y
        // 4 inital parks
        // Japan/Danmark
        0 => [
            [2, 0, \BP\ShapeGreenBase::class],
            [0, 1, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 1, \BP\ShapeGreenBase::class],
            [1, 2, \BP\Park::class],
            [2, 2, \BP\ShapeBearStatue::class],
            [0, 3, \BP\ShapeGreenBase::class],
            [3, 3, \BP\ShapeWhiteAnimalHouseBase::class],
        ],
        // Czech Republic/US
        1 => [
            [0, 0, \BP\ShapeGreenBase::class],
            [3, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [1, 1, \BP\ShapeGreenBase::class],
            [0, 2, \BP\Park::class],
            [1, 2, \BP\ShapeBearStatue::class],
            [2, 2, \BP\ShapeGreenBase::class],
            [1, 3, \BP\ShapeWhiteAnimalHouseBase::class],
        ],
        // Switzerland/Poland
        2 => [
            [0, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 0, \BP\ShapeGreenBase::class],
            [1, 1, \BP\ShapeBearStatue::class],
            [2, 1, \BP\ShapeGreenBase::class],
            [0, 2, \BP\ShapeGreenBase::class],
            [3, 2, \BP\Park::class],
            [2, 3, \BP\ShapeWhiteAnimalHouseBase::class],
        ],
        // Russia/France
        3 => [
            [0, 0, \BP\Park::class],
            [2, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [1, 1, \BP\ShapeGreenBase::class],
            [2, 1, \BP\ShapeBearStatue::class],
            [2, 2, \BP\ShapeGreenBase::class],
            [0, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 3, \BP\ShapeGreenBase::class],
        ],
        // 12 other parks, sorted from top,left by: empty, bear, green, ...
        4 => [
            [2, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 0, \BP\ShapeBearStatue::class],
            [0, 1, \BP\Park::class],
            [3, 1, \BP\ShapeGreenBase::class],
            [1, 2, \BP\ShapeOrangeEnclosureBase::class],
            [0, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 3, \BP\ShapeGreenBase::class],
        ],
        5 => [
            [1, 0, \BP\ShapeGreenBase::class],
            [3, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [0, 1, \BP\Park::class],
            [2, 1, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 2, \BP\ShapeGreenBase::class],
            [1, 3, \BP\ShapeOrangeEnclosureBase::class],
            [3, 3, \BP\ShapeBearStatue::class],
        ],
        6 => [
            [1, 0, \BP\ShapeGreenBase::class],
            [2, 0, \BP\ShapeBearStatue::class],
            [3, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [0, 1, \BP\ShapeGreenBase::class],
            [2, 2, \BP\ShapeOrangeEnclosureBase::class],
            [0, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 3, \BP\Park::class],
        ],
        7 => [
            [1, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 0, \BP\ShapeGreenBase::class],
            [2, 1, \BP\Park::class],
            [0, 2, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 2, \BP\ShapeGreenBase::class],
            [0, 3, \BP\ShapeBearStatue::class],
            [2, 3, \BP\ShapeOrangeEnclosureBase::class],
        ],
        8 => [
            [1, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 0, \BP\ShapeGreenBase::class],
            [0, 1, \BP\ShapeGreenBase::class],
            [2, 1, \BP\Park::class],
            [3, 1, \BP\ShapeBearStatue::class],
            [3, 2, \BP\ShapeWhiteAnimalHouseBase::class],
            [0, 3, \BP\ShapeOrangeEnclosureBase::class],
        ],
        9 => [
            [1, 0, \BP\ShapeOrangeEnclosureBase::class],
            [3, 0, \BP\Park::class],
            [2, 1, \BP\ShapeGreenBase::class],
            [1, 2, \BP\ShapeGreenBase::class],
            [3, 2, \BP\ShapeBearStatue::class],
            [0, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 3, \BP\ShapeWhiteAnimalHouseBase::class],
        ],
        10 => [
            [0, 0, \BP\ShapeBearStatue::class],
            [1, 0, \BP\Park::class],
            [3, 0, \BP\ShapeOrangeEnclosureBase::class],
            [0, 1, \BP\ShapeGreenBase::class],
            [2, 2, \BP\ShapeGreenBase::class],
            [0, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 3, \BP\ShapeWhiteAnimalHouseBase::class],
        ],
        11 => [
            [0, 0, \BP\ShapeGreenBase::class],
            [3, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [0, 1, \BP\ShapeBearStatue::class],
            [1, 1, \BP\ShapeOrangeEnclosureBase::class],
            [3, 2, \BP\ShapeGreenBase::class],
            [0, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [2, 3, \BP\Park::class],
        ],
        12 => [
            [0, 0, \BP\ShapeGreenBase::class],
            [2, 0, \BP\ShapeOrangeEnclosureBase::class],
            [3, 1, \BP\ShapeWhiteAnimalHouseBase::class],
            [0, 2, \BP\ShapeBearStatue::class],
            [1, 2, \BP\Park::class],
            [0, 3, \BP\ShapeGreenBase::class],
            [3, 3, \BP\ShapeWhiteAnimalHouseBase::class],
        ],
        13 => [
            [0, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 0, \BP\ShapeGreenBase::class],
            [2, 1, \BP\ShapeOrangeEnclosureBase::class],
            [0, 2, \BP\Park::class],
            [1, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [2, 3, \BP\ShapeBearStatue::class],
            [3, 3, \BP\ShapeGreenBase::class],
        ],
        14 => [
            [0, 0, \BP\ShapeWhiteAnimalHouseBase::class],
            [1, 0, \BP\ShapeBearStatue::class],
            [3, 0, \BP\ShapeGreenBase::class],
            [1, 1, \BP\Park::class],
            [0, 2, \BP\ShapeGreenBase::class],
            [1, 3, \BP\ShapeWhiteAnimalHouseBase::class],
            [3, 3, \BP\ShapeOrangeEnclosureBase::class],
        ],
        15 => [
            [0, 0, \BP\ShapeOrangeEnclosureBase::class],
            [3, 0, \BP\Park::class],
            [2, 1, \BP\ShapeWhiteAnimalHouseBase::class],
            [1, 2, \BP\ShapeWhiteAnimalHouseBase::class],
            [0, 3, \BP\ShapeGreenBase::class],
            [1, 3, \BP\ShapeBearStatue::class],
            [2, 3, \BP\ShapeGreenBase::class],
        ],
    ];

    /** @dbcol @dbkey */
    public $parkId;
    /** @dbcol */
    public $parkDefId;
    /** @dbcol */
    public $supplyPile;
    /** @dbcol */
    public $supplyPileOrder;
    /** @dbcol @dbdefault */
    public $isSupplyPileTop;
    /** @dbcol */
    public $playerId;
    /** @dbcol */
    public $posX;
    /** @dbcol */
    public $posY;
    /** @dbcol @dbmovenumber */
    public $savedMoveNumber;

    public function jsonSerialize()
    {
        $ret = parent::jsonSerialize();
        $ret['supplyPileCount'] = 0;
        if ($this->supplyPileOrder !== null) {
            $ret['supplyPileCount'] = PARKS_PER_PILE - $this->supplyPileOrder;
        }
        return $ret;
    }

    public function isVisible()
    {
        return ($this->playerId !== null || $this->isSupplyPileTop);
    }

    public static function getIconPriority()
    {
        return 3;
    }

    public static function toUIString()
    {
        return 'P';
    }

    public static function getTextPlural()
    {
        return clienttranslate('Park Area(s)');
    }

    public static function getChoosableShapes()
    {
        return [Park::class];
    }

    public function isOnSupplyBoardTop()
    {
        return ($this->playerId === null && $this->isSupplyPileTop);
    }

    public function moveToPlayerPark(int $playerId, int $posX, int $posY)
    {
        $this->isSupplyPileTop = false;
        $this->playerId = $playerId;
        $this->posX = $posX;
        $this->posY = $posY;
    }
}

class ParkNewValidPosition extends \BX\UI\UISerializable
{
    public $posX;
    public $posY;

    public function __construct(int $posX, int $posY)
    {
        $this->posX = $posX;
        $this->posY = $posY;
    }

    public function isEqual($other)
    {
        return ($this->posX == $other->posX && $this->posY == $other->posY);
    }
}

class ParkValidPosition extends \BX\UI\UISerializable
{
    public $shapeId;
    public $parkId;
    public $parkTopX;
    public $parkTopY;
    public $parkRotation;
    public $parkHorizontalFlip;
    public $parkVerticalFlip;
    public $overlappedIcons;
    public $statueShapeIds;

    public function __construct(
        int $shapeId,
        int $parkId,
        int $parkTopX,
        int $parkTopY,
        int $parkRotation,
        bool $parkHorizontalFlip,
        bool $parkVerticalFlip,
        array $overlappedIcons,
        array $statueShapeIds
    ) {
        $this->shapeId = $shapeId;
        $this->parkId = $parkId;
        $this->parkTopX = $parkTopX;
        $this->parkTopY = $parkTopY;
        $this->parkRotation = $parkRotation;
        $this->parkHorizontalFlip = $parkHorizontalFlip;
        $this->parkVerticalFlip = $parkVerticalFlip;
        $this->overlappedIcons = $overlappedIcons;
        $this->statueShapeIds = $statueShapeIds;
    }

    public function toUIString()
    {
        $flipH = $this->parkHorizontalFlip ? '1' : '0';
        $flipV = $this->parkVerticalFlip ? '1' : '0';
        $str = "{$this->shapeId}|{$this->parkId}|{$this->parkTopX}|{$this->parkTopY}|{$this->parkRotation}|{$flipH}|{$flipV}|";
        $str .= implode(',', array_map(fn ($icon) => $icon::toUIString(), $this->overlappedIcons));
        $str .= '|';
        $str .= implode(',', $this->statueShapeIds);
        return $str;
    }

    public static function mapToUiString(array $validPositions)
    {
        return implode(';', array_map(fn ($vp) => $vp->toUIString(), $validPositions));
    }

    public function jsonSerialize()
    {
        throw new \BgaSystemException('Do not serialize ParkValidPosition!');
    }
}

class ParkShapeValidityArgs
{
    public $playerId;
    public $shapeId;
    public $parkId;
    public $parkTopX;
    public $parkTopY;
    public $parkRotation;
    public $parkHorizontalFlip;
    public $parkVerticalFlip;
    public $validateAdjacency;
    public $isPitVariantActive;
    public $isLastTurn;
    public $allowBearStatueOverlap;

    public $overlappedIcons;
    public $filledParkCount;
    public $shapeArray;
    public $isValid;

    public function __construct(?ParkShapeValidityArgs $args = null)
    {
        $this->validateAdjacency = true;
        $this->allowBearStatueOverlap = false;
        $this->overlappedIcons = [];
        $this->shapeArray = null;
        $this->isValid = null;
        if ($args !== null) {
            $this->playerId = $args->playerId;
            $this->shapeId = $args->shapeId;
            $this->parkId = $args->parkId;
            $this->parkTopX = $args->parkTopX;
            $this->parkTopY = $args->parkTopY;
            $this->parkRotation = $args->parkRotation;
            $this->parkHorizontalFlip = $args->parkHorizontalFlip;
            $this->parkVerticalFlip = $args->parkVerticalFlip;
            $this->validateAdjacency = $args->validateAdjacency;
            $this->isPitVariantActive = $args->isPitVariantActive;
            $this->isLastTurn = $args->isLastTurn;
            $this->allowBearStatueOverlap = $args->allowBearStatueOverlap;
            $this->shapeArray = $args->shapeArray;
        }
    }

    public function setPlayerId(int $playerId)
    {
        $this->playerId = $playerId;
        return $this;
    }

    public function setShapeId(int $shapeId)
    {
        $this->shapeId = $shapeId;
        return $this;
    }

    public function setParkId(int $parkId)
    {
        $this->parkId = $parkId;
        return $this;
    }

    public function setParkTopX(int $parkTopX)
    {
        $this->parkTopX = $parkTopX;
        return $this;
    }

    public function setParkTopY(int $parkTopY)
    {
        $this->parkTopY = $parkTopY;
        return $this;
    }

    public function setParkRotation(int $parkRotation)
    {
        $this->parkRotation = $parkRotation;
        return $this;
    }

    public function setParkHorizontalFlip(bool $parkHorizontalFlip)
    {
        $this->parkHorizontalFlip = $parkHorizontalFlip;
        return $this;
    }

    public function setParkVerticalFlip(bool $parkVerticalFlip)
    {
        $this->parkVerticalFlip = $parkVerticalFlip;
        return $this;
    }

    public function setValidateAdjacency(bool $validateAdjacency)
    {
        $this->validateAdjacency = $validateAdjacency;
        return $this;
    }

    public function setAllowBearStatueOverlap(bool $allowBearStatueOverlap)
    {
        $this->allowBearStatueOverlap = $allowBearStatueOverlap;
        return $this;
    }

    public function isPitVariantActive()
    {
        if ($this->isPitVariantActive === null) {
            $this->isPitVariantActive = (\BX\BGAGlobal\GlobalMgr::getGlobal(GAME_OPTION_VARIANT_PIT_ID) == GAME_OPTION_VARIANT_PIT_VALUE_ON);
        }
        return $this->isPitVariantActive;
    }

    public function isLastTurn()
    {
        if ($this->isLastTurn === null) {
            $this->isLastTurn = false;
            $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
            $playerMgr = \BX\Action\ActionRowMgrRegister::getMgr('player');
            if ($parkMgr->atLeastOnePlayerParksAreFull($playerMgr->getAllPlayerIds())) {
                $this->isLastTurn = true;
            }
        }
        return $this->isLastTurn;
    }

    public function fillCache()
    {
        // For performance
        $this->isPitVariantActive();
        $this->isLastTurn();
    }

    public function shapeArrayKey()
    {
        return json_encode($this->shapeArray);
    }
}

class ParkGrid
{
    public $parkId;
    public $x;
    public $y;
    public $iconClassId;
    public $shapeId;

    public function __construct(int $parkId, int $x, int $y)
    {
        $this->parkId = $parkId;
        $this->x = $x;
        $this->y = $y;
    }

    public function canPlaceShape(bool $allowBearStatueOverlap)
    {
        if ($this->containsShape()) {
            return false;
        }
        if ($allowBearStatueOverlap) {
            return true;
        }
        return (!$this->isBearStatueIcon());
    }

    public function containsShape()
    {
        return ($this->shapeId !== null);
    }

    public function isBearStatueIcon()
    {
        return ($this->iconClassId == \BP\ShapeBearStatue::class);
    }

    public function hasIcon()
    {
        return ($this->iconClassId !== null);
    }
}

class ParkNeighbor
{
    public $upParkId;
    public $downParkId;
    public $leftParkId;
    public $rightParkId;
}

class GlobalPlayerPark
{
    private const ORTHO_DIRECTIONS = [
        [0, -1],
        [0, 1],
        [-1, 0],
        [1, 0]
    ];
    private $playerId;
    private $parkNeighbors;
    private $parkGrid;
    private $hasShapes;

    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
        $this->hasShapes = false;

        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $parks = $parkMgr->getPlayerParks($this->playerId);
        $this->buildNeighbors($parks);
        $this->buildParkGrid($parks);
        $this->placeShapesInGrid();
    }

    public function getValidPositions(ParkShapeValidityArgs $args)
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $shape = $shapeMgr->getShapeById($args->shapeId);
        if ($shape === null) {
            throw new \BgaSystemException("shapeId {$args->shapeId} does not exist");
        }
        $statueShapes = $shapeMgr->getSupplyBoardShapeOfTypeInOrder(\BP\ShapeBearStatue::class);
        $validPositions = [];
        // For performance
        $args->fillCache();
        foreach (array_keys($this->parkGrid) as $parkId) {
            $startX = 0;
            if ($this->parkNeedsExtraPlacement($parkId)) {
                $startX = -1 * $shape->getShapeExtraPlacementSize();
            }
            foreach (range($startX, PARK_SIZE - 1) as $x) {
                foreach (range(0, PARK_SIZE - 1) as $y) {
                    $checkedArgs = [];
                    foreach (VALID_ROTATIONS as $rotation) {
                        foreach ([true, false] as $flipH) {
                            foreach ([true, false] as $flipV) {
                                $isValidArgs = (new ParkShapeValidityArgs($args))
                                    ->setParkId($parkId)
                                    ->setParkTopX($x)
                                    ->setParkTopY($y)
                                    ->setParkRotation($rotation)
                                    ->setParkHorizontalFlip($flipH)
                                    ->setParkVerticalFlip($flipV);
                                $isPosValid = $this->isShapePositionValid($isValidArgs, $checkedArgs);
                                $checkedArgs[$isValidArgs->shapeArrayKey()] = $isValidArgs;
                                if ($isPosValid) {
                                    $statueShapeIds = [];
                                    foreach ($statueShapes as $statue) {
                                        if (count($statueShapeIds) >= $isValidArgs->filledParkCount) {
                                            break;
                                        }
                                        $statueShapeIds[] = $statue->shapeId;
                                    }
                                    $validPositions[] = new ParkValidPosition(
                                        $isValidArgs->shapeId,
                                        $parkId,
                                        $x,
                                        $y,
                                        $rotation,
                                        $flipH,
                                        $flipV,
                                        $isValidArgs->overlappedIcons,
                                        $statueShapeIds
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        return $validPositions;
    }

    public function getNeighbourPositions()
    {
        $positions = [];
        $extraPlacementParkId = null;
        foreach (array_keys($this->parkGrid) as $parkId) {
            if ($this->parkNeedsExtraPlacement($parkId)) {
                $extraPlacementParkId = $parkId;
            }
            $positions[$parkId] = [];
            foreach (range(0, PARK_SIZE - 1) as $x) {
                $positions[$parkId][$x] = [];
                foreach (range(0, PARK_SIZE - 1) as $y) {
                    $positions[$parkId][$x][$y]['up'] = $this->getParkGridAt($parkId, $x, $y - 1);
                    $positions[$parkId][$x][$y]['down'] = $this->getParkGridAt($parkId, $x, $y + 1);
                    $positions[$parkId][$x][$y]['left'] = $this->getParkGridAt($parkId, $x - 1, $y);
                    $positions[$parkId][$x][$y]['right'] = $this->getParkGridAt($parkId, $x + 1, $y);
                }
            }
        }
        if ($extraPlacementParkId !== null) {
            // Extra grid
            foreach (range(-1 * EXTRA_PLACEMENT_SIZE, -1) as $x) {
                $positions[$extraPlacementParkId][$x] = [];
                foreach (range(0, PARK_SIZE - 1) as $y) {
                    $positions[$extraPlacementParkId][$x][$y]['up'] = $this->getParkGridAt($extraPlacementParkId, $x, $y - 1);
                    if ($positions[$extraPlacementParkId][$x][$y]['up'] === null && ($y - 1) >= 0) {
                        $positions[$extraPlacementParkId][$x][$y]['up'] = new ParkGrid($extraPlacementParkId, $x, $y - 1);
                    }
                    $positions[$extraPlacementParkId][$x][$y]['down'] = $this->getParkGridAt($extraPlacementParkId, $x, $y + 1);
                    if ($positions[$extraPlacementParkId][$x][$y]['down'] === null) {
                        $positions[$extraPlacementParkId][$x][$y]['down'] = new ParkGrid($extraPlacementParkId, $x, $y + 1);
                    }
                    $positions[$extraPlacementParkId][$x][$y]['left'] = $this->getParkGridAt($extraPlacementParkId, $x - 1, $y);
                    if ($positions[$extraPlacementParkId][$x][$y]['left'] === null && ($x - 1) >= -1 * EXTRA_PLACEMENT_SIZE) {
                        $positions[$extraPlacementParkId][$x][$y]['left'] = new ParkGrid($extraPlacementParkId, $x - 1, $y);
                    }
                    $positions[$extraPlacementParkId][$x][$y]['right'] = $this->getParkGridAt($extraPlacementParkId, $x + 1, $y);
                    if ($positions[$extraPlacementParkId][$x][$y]['right'] === null) {
                        $positions[$extraPlacementParkId][$x][$y]['right'] = new ParkGrid($extraPlacementParkId, $x + 1, $y);
                    }
                }
            }
            // From right park to extra grid
            foreach (range(0, PARK_SIZE - 1) as $y) {
                $positions[$extraPlacementParkId][0][$y]['left'] = new ParkGrid($extraPlacementParkId, -1, $y);
            }
            // From down park to extra grid 
            $downParkId = $this->getParkGridAt($parkId, -1, PARK_SIZE)->parkId;
            foreach (range(PARK_SIZE - EXTRA_PLACEMENT_SIZE, PARK_SIZE - 1) as $x) {
                $positions[$downParkId][$x][0]['up'] = new ParkGrid($extraPlacementParkId, $x - PARK_SIZE, PARK_SIZE - 1);
            }
        }
        return $positions;
    }

    private function parkNeedsExtraPlacement($parkId)
    {
        // Detect this park shape:
        //     __
        //  __|__|<-- This one
        // |__|__|
        if (
            $this->getParkGridAt($parkId, 0, PARK_SIZE) !== null
            && $this->getParkGridAt($parkId, -1, PARK_SIZE) !== null
            && $this->getParkGridAt($parkId, -1, PARK_SIZE - 1) === null
        ) {
            return true;
        }
        return false;
    }

    public function isShapePositionValid(ParkShapeValidityArgs &$args, array $checkedArgs = [])
    {
        $args->filledParkCount = 0;
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $shape = $shapeMgr->getShapeById($args->shapeId);
        if ($shape === null) {
            throw new \BgaSystemException("shapeId {$args->shapeId} does not exist");
        }
        $args->shapeArray = $shape->getShapeArray($args->parkRotation, $args->parkHorizontalFlip, $args->parkVerticalFlip);
        if (count($checkedArgs) > 0) {
            $shapeArrayKey = $args->shapeArrayKey();
            if (array_key_exists($shapeArrayKey, $checkedArgs)) {
                $args->overlappedIcons = $checkedArgs[$shapeArrayKey]->overlappedIcons;
                $args->filledParkCount = $checkedArgs[$shapeArrayKey]->filledParkCount;
                $args->isValid = $checkedArgs[$shapeArrayKey]->isValid;
                return $args->isValid;
            }
        }
        $valid = true;
        $emptyValid = false;
        if (!$this->hasShapes) {
            $emptyValid = true;
        }
        $parkFilledGridCount = [];
        $parkBearStatueFilled = [];
        $shapeCoveredGrids = 0;
        $coversAtLeastOneEmptyBearStatue = false;
        $this->foreachGrid(
            $args->shapeArray,
            $args->parkId,
            $args->parkTopX,
            $args->parkTopY,
            function ($grid, $used) use (&$valid, &$emptyValid, &$parkFilledGridCount, &$parkBearStatueFilled, &$shapeCoveredGrids, &$coversAtLeastOneEmptyBearStatue, $args) {
                if ($used && ($grid === null || !$grid->canPlaceShape($args->allowBearStatueOverlap))) {
                    $valid = false;
                    // Normally we would return false here to stop the loop but we
                    // must check if the park is filled for the pit variant
                    if (!$args->isPitVariantActive()) {
                        return false;
                    }
                    if ($grid === null) {
                        return false;
                    }
                    if (!$grid->canPlaceShape(true)) {
                        return false;
                    }
                }
                if ($used && $grid !== null) {
                    $shapeCoveredGrids += 1;
                    if ($grid->isBearStatueIcon()) {
                        $parkBearStatueFilled[$grid->parkId] = true;
                        if (!$grid->containsShape()) {
                            $coversAtLeastOneEmptyBearStatue = true;
                        }
                    } else {
                        if (!array_key_exists($grid->parkId, $parkFilledGridCount)) {
                            $parkFilledGridCount[$grid->parkId] = 0;
                        }
                        $parkFilledGridCount[$grid->parkId] += 1;
                    }
                }
                if ($used && $grid !== null && $grid->hasIcon() && !$grid->isBearStatueIcon()) {
                    $args->overlappedIcons[] = $grid->iconClassId;
                }
                if (!$emptyValid && $used && $grid !== null && $this->hasShapes) {
                    foreach (self::ORTHO_DIRECTIONS as $xy) {
                        $neighbor = $this->getParkGridAt($grid->parkId, $grid->x + $xy[0], $grid->y + $xy[1]);
                        if ($neighbor !== null && $neighbor->shapeId !== null && $neighbor->shapeId != $args->shapeId) {
                            $emptyValid = true;
                            break;
                        }
                    }
                }
            }
        );
        foreach (array_keys($parkFilledGridCount) as $filledParkId) {
            foreach ($this->parkGrid[$filledParkId] as $row) {
                foreach ($row as $grid) {
                    if ($grid->containsShape()) {
                        if ($grid->isBearStatueIcon()) {
                            $parkBearStatueFilled[$filledParkId] = true;
                        } else {
                            $parkFilledGridCount[$filledParkId] += 1;
                        }
                    }
                }
            }
            if (!array_key_exists($filledParkId, $parkBearStatueFilled) && $parkFilledGridCount[$filledParkId] == PARK_SIZE * PARK_SIZE - 1) {
                $args->filledParkCount += 1;
            }
        }
        usort($args->overlappedIcons, fn ($i1, $i2) => $i1::getIconPriority() <=> $i2::getIconPriority());
        if (!$args->validateAdjacency) {
            $emptyValid = true;
        }
        if (!$valid && $coversAtLeastOneEmptyBearStatue) {
            if ($args->isPitVariantActive() && !$args->allowBearStatueOverlap) {
                if ($args->isLastTurn() || ($this->countCoveredGrids() + $shapeCoveredGrids) == (PARK_SIZE * PARK_SIZE * PLAYER_MAXIMUM_NUMBER_OF_PARKS)) {
                    // In this case, we must check if the shape fits if we allow the bear statue to overlap
                    $args = (new ParkShapeValidityArgs($args))->setAllowBearStatueOverlap(true);
                    return $this->isShapePositionValid($args);
                }
            }
        }
        $args->isValid = ($valid && $emptyValid);
        return $args->isValid;
    }

    public function getShapeOverlappedIcons($shapeId)
    {
        $icons = [];
        foreach ($this->parkGrid as $parkGrid) {
            foreach ($parkGrid as $row) {
                foreach ($row as $grid) {
                    if ($grid->shapeId == $shapeId && $grid->hasIcon() && !$grid->isBearStatueIcon()) {
                        $icons[] = $grid->iconClassId;
                    }
                }
            }
        }
        return $icons;
    }

    public function getFilledParkMissingStatueGrid()
    {
        $statueGrids = [];
        foreach ($this->parkGrid as $parkGrid) {
            $filledGridCount = 0;
            $statueGrid = null;
            foreach ($parkGrid as $row) {
                foreach ($row as $grid) {
                    if ($grid->containsShape()) {
                        if (!$grid->isBearStatueIcon()) {
                            ++$filledGridCount;
                        }
                    } else {
                        if ($grid->isBearStatueIcon()) {
                            $statueGrid = $grid;
                        }
                    }
                }
            }
            if ($filledGridCount == (PARK_SIZE * PARK_SIZE - 1) && $statueGrid !== null) {
                $statueGrids[] = $statueGrid;
            }
        }
        return $statueGrids;
    }

    public function parksAreFull()
    {
        foreach ($this->parkGrid as $parkGrid) {
            foreach ($parkGrid as $row) {
                foreach ($row as $grid) {
                    if (!$grid->containsShape()) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function countCoveredGrids()
    {
        $count = 0;
        foreach ($this->parkGrid as $parkGrid) {
            foreach ($parkGrid as $row) {
                foreach ($row as $grid) {
                    if ($grid->containsShape()) {
                        ++$count;
                    }
                }
            }
        }
        return $count;
    }

    public function getShapesAdjacentGroups(string $baseClassId)
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $seenGrids = [];
        $adjacentGroups = [];
        foreach ($this->parkGrid as $parkGrid) {
            foreach ($parkGrid as $row) {
                foreach ($row as $grid) {
                    $group = $this->buildAdjacentGroupFromGrid($grid, $baseClassId, $seenGrids, $shapeMgr);
                    if (count($group) > 0) {
                        $adjacentGroups[] = array_keys($group);
                    }
                }
            }
        }
        return $adjacentGroups;
    }

    public function getLongestEndConnection(string $classId)
    {
        $isEndConnection = function ($grid) {
            $sameShapeDirection = null;
            foreach (self::ORTHO_DIRECTIONS as $xy) {
                $neighbor = $this->getParkGridAt($grid->parkId, $grid->x + $xy[0], $grid->y + $xy[1]);
                if ($neighbor === null || !$neighbor->containsShape() || $neighbor->shapeId != $grid->shapeId) {
                    continue;
                }
                if ($sameShapeDirection !== null) {
                    return null;
                }
                $sameShapeDirection = $xy;
            }
            if ($sameShapeDirection === null) {
                return null;
            }
            return self::invertDirection($sameShapeDirection);
        };
        $connectedShapeIds = [];
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        foreach ($this->parkGrid as $parkGrid) {
            foreach ($parkGrid as $row) {
                foreach ($row as $grid) {
                    if (!$grid->containsShape()) {
                        continue;
                    }
                    $shape = $shapeMgr->getShapeById($grid->shapeId);
                    if (get_class($shape) != $classId) {
                        continue;
                    }
                    $end = $isEndConnection($grid);
                    if ($end === null) {
                        continue;
                    }
                    $neighbor = $this->getParkGridAt($grid->parkId, $grid->x + $end[0], $grid->y + $end[1]);
                    if ($neighbor === null || !$neighbor->containsShape()) {
                        continue;
                    }
                    $neighborShape = $shapeMgr->getShapeById($neighbor->shapeId);
                    if (get_class($neighborShape) != $classId) {
                        continue;
                    }
                    $neighborEnd = $isEndConnection($neighbor);
                    if ($neighborEnd === null) {
                        continue;
                    }
                    if ($end != self::invertDirection($neighborEnd)) {
                        continue;
                    }

                    if (!array_key_exists($grid->shapeId, $connectedShapeIds)) {
                        $connectedShapeIds[$grid->shapeId] = [];
                    }
                    $connectedShapeIds[$grid->shapeId][] = $neighbor->shapeId;
                    if (!array_key_exists($neighbor->shapeId, $connectedShapeIds)) {
                        $connectedShapeIds[$neighbor->shapeId] = [];
                    }
                    $connectedShapeIds[$neighbor->shapeId][] = $grid->shapeId;
                }
            }
        }
        $followConnection = function ($from, $connectedShapeIds, &$seenShapeIds) use (&$followConnection) {
            if (array_key_exists($from, $seenShapeIds)) {
                return;
            }
            $seenShapeIds[$from] = true;
            foreach ($connectedShapeIds[$from] as $to) {
                $followConnection($to, $connectedShapeIds, $seenShapeIds);
            }
        };
        $maxLength = 0;
        foreach ($connectedShapeIds as $from => $toArray) {
            $seenShapeIds = [];
            $followConnection($from, $connectedShapeIds, $seenShapeIds);
            $maxLength = max($maxLength, count($seenShapeIds));
        }
        return $maxLength;
    }

    private static function invertDirection(array $dir)
    {
        $dir[0] *= -1;
        $dir[1] *= -1;
        return $dir;
    }

    private function buildAdjacentGroupFromGrid($grid, $baseClassId, &$seenGrids, $shapeMgr)
    {
        if (!$grid->containsShape()) {
            return [];
        }
        $gridId = spl_object_id($grid);
        if (array_key_exists($gridId, $seenGrids)) {
            return [];
        }
        $seenGrids[$gridId] = true;
        $shape = $shapeMgr->getShapeById($grid->shapeId);
        if (!is_subclass_of(get_class($shape), $baseClassId)) {
            return [];
        }
        $group = [];
        $group[$grid->shapeId] = true;
        foreach (self::ORTHO_DIRECTIONS as $xy) {
            $neighbor = $this->getParkGridAt($grid->parkId, $grid->x + $xy[0], $grid->y + $xy[1]);
            if ($neighbor !== null) {
                $group += $this->buildAdjacentGroupFromGrid($neighbor, $baseClassId, $seenGrids, $shapeMgr);
            }
        }
        return $group;
    }

    private function buildNeighbors(array $parks)
    {
        $this->parkNeighbors = [];
        foreach ($parks as $park) {
            $neighbor = new ParkNeighbor();
            $this->parkNeighbors[$park->parkId] = $neighbor;
            foreach ($parks as $parkNeighbor) {
                if ($park->posX == $parkNeighbor->posX + 0 && $park->posY == $parkNeighbor->posY - 1) {
                    $neighbor->upParkId = $parkNeighbor->parkId;
                } else if ($park->posX == $parkNeighbor->posX + 0 && $park->posY == $parkNeighbor->posY + 1) {
                    $neighbor->downParkId = $parkNeighbor->parkId;
                } else if ($park->posX == $parkNeighbor->posX + 1 && $park->posY == $parkNeighbor->posY + 0) {
                    $neighbor->leftParkId = $parkNeighbor->parkId;
                } else if ($park->posX == $parkNeighbor->posX - 1 && $park->posY == $parkNeighbor->posY + 0) {
                    $neighbor->rightParkId = $parkNeighbor->parkId;
                }
            }
        }
    }

    private function buildParkGrid(array $parks)
    {
        $this->parkGrid = [];
        foreach ($parks as $park) {
            $this->parkGrid[$park->parkId] = [];
            foreach (range(0, PARK_SIZE - 1) as $x) {
                $this->parkGrid[$park->parkId][$x] = [];
                foreach (range(0, PARK_SIZE - 1) as $y) {
                    $grid = new ParkGrid($park->parkId, $x, $y);
                    foreach (Park::PARK_ICONS[$park->parkDefId] as $iconGrid) {
                        if ($iconGrid[0] == $x && $iconGrid[1] == $y) {
                            $grid->iconClassId = $iconGrid[2];
                            break;
                        }
                    }
                    $this->parkGrid[$park->parkId][$x][$y] = $grid;
                }
            }
        }
    }

    private function placeShapesInGrid()
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $shapes = $shapeMgr->getPlayerParkShapes($this->playerId);
        if (count($shapes) > 0) {
            $this->hasShapes = true;
        }
        foreach ($shapes as $shape) {
            $shapeArray = $shape->getShapeArrayWithParkTransform();
            $this->foreachGrid($shapeArray, $shape->parkId, $shape->parkTopX, $shape->parkTopY, function ($grid, $used) use ($shape) {
                if ($used) {
                    if ($grid === null) {
                        throw new \BgaSystemException("parkId {$shape->parkId} cannot contain shapeId {$shape->spapeId} at ({$shape->parkTopX}, {$shape->parkTopY})");
                    }
                    $grid->shapeId = $shape->shapeId;
                }
            });
        }
    }

    private function foreachGrid(array $shapeArray, int $parkId, int $parkTopX, int $parkTopY, callable $callback)
    {
        foreach ($shapeArray as $y => $row) {
            foreach ($row as $x => $used) {
                $grid = $this->getParkGridAt($parkId, $parkTopX + $x, $parkTopY + $y);
                if ($callback($grid, $used) === false) {
                    return;
                }
            }
        }
    }

    private function getParkGridAt(int $parkId, int $x, int $y)
    {
        if (!array_key_exists($parkId, $this->parkGrid)) {
            throw new \BgaSystemException("parkId $parkId does not exists");
        }
        return $this->getParkGridAtRecursive($parkId, $x, $y, []);
    }

    private function getParkGridAtRecursive(int $parkId, int $x, int $y, $visitedParkId)
    {
        if (array_search($parkId, $visitedParkId) !== false) {
            return null;
        }

        if ($x >= 0 && $x < PARK_SIZE && $y >= 0 && $y < PARK_SIZE) {
            return $this->parkGrid[$parkId][$x][$y];
        }

        if ($this->parkNeighbors[$parkId]->leftParkId !== null) {
            $grid = $this->getParkGridAtRecursive(
                $this->parkNeighbors[$parkId]->leftParkId,
                $x + PARK_SIZE,
                $y,
                array_merge($visitedParkId, [$parkId])
            );
            if ($grid !== null) {
                return $grid;
            }
        }

        if ($this->parkNeighbors[$parkId]->rightParkId !== null) {
            $grid = $this->getParkGridAtRecursive(
                $this->parkNeighbors[$parkId]->rightParkId,
                $x - PARK_SIZE,
                $y,
                array_merge($visitedParkId, [$parkId])
            );
            if ($grid !== null) {
                return $grid;
            }
        }

        if ($this->parkNeighbors[$parkId]->downParkId !== null) {
            $grid = $this->getParkGridAtRecursive(
                $this->parkNeighbors[$parkId]->downParkId,
                $x,
                $y - PARK_SIZE,
                array_merge($visitedParkId, [$parkId])
            );
            if ($grid !== null) {
                return $grid;
            }
        }

        if ($this->parkNeighbors[$parkId]->upParkId !== null) {
            $grid = $this->getParkGridAtRecursive(
                $this->parkNeighbors[$parkId]->upParkId,
                $x,
                $y + PARK_SIZE,
                array_merge($visitedParkId, [$parkId])
            );
            if ($grid !== null) {
                return $grid;
            }
        }

        return null;
    }
}

class ParkMgr extends \BX\Action\BaseActionRowMgr
{
    use \BX\MoveNumber\ActionMgrSavedMoveNumberTrait;
    private const BASE_NEW_PARK_ID = 10000;

    public function __construct()
    {
        parent::__construct('park', Park::class);
    }

    public function setup(array $playerIdArray)
    {
        $parkWithEntrance = range(0, PARK_COUNT_WITH_ENTRANCE - 1);
        shuffle($parkWithEntrance);
        foreach ($playerIdArray as $playerId) {
            $park = $this->db->newRow();
            $park->parkId = array_shift($parkWithEntrance);
            $park->parkDefId = $park->parkId;
            $park->playerId = $playerId;
            $park->posX = 0;
            $park->posY = 0;
            $this->db->insertRow($park);
        }

        $parkWithoutEntrance = range(PARK_COUNT_WITH_ENTRANCE, PARK_COUNT_WITH_ENTRANCE + PARK_COUNT_WITHOUT_ENTRANCE - 1);
        shuffle($parkWithoutEntrance);
        $supplyPile = 0;
        $supplyPileOrder = 0;
        foreach ($parkWithoutEntrance as $parkId) {
            $park = $this->db->newRow();
            $park->parkId = $parkId;
            $park->parkDefId = $park->parkId;
            $park->supplyPile = $supplyPile;
            $park->supplyPileOrder = $supplyPileOrder;
            if ($supplyPileOrder == 0) {
                $park->isSupplyPileTop = true;
            }
            $this->db->insertRow($park);
            ++$supplyPile;
            if ($supplyPile >= PARK_NB_PILE) {
                $supplyPile = 0;
                ++$supplyPileOrder;
            }
        }
    }

    public function getAllVisible()
    {
        return array_filter($this->getAllRowsByKey(), function ($park) {
            return $park->isVisible();
        });
    }

    public function generateNewParkId()
    {
        $max = max(array_keys($this->getAllRowsByKey()));
        if ($max < self::BASE_NEW_PARK_ID) {
            $max = self::BASE_NEW_PARK_ID;
        }
        return $max + 1;
    }

    public function getSupplyPilesCount()
    {
        $supplyPileCount = [0, 0];
        foreach ($this->getAllRowsByKey() as $park) {
            if ($park->playerId === null && $park->supplyPile !== null) {
                $supplyPileCount[$park->supplyPile] += 1;
            }
        }
        return $supplyPileCount;
    }

    public function getPlayerParks(int $playerId)
    {
        return array_filter($this->getAllRowsByKey(), function ($park) use ($playerId) {
            return $park->playerId == $playerId;
        });
    }

    public function playerHasMaximumParks(int $playerId)
    {
        return (count($this->getPlayerParks($playerId)) >= PLAYER_MAXIMUM_NUMBER_OF_PARKS);
    }

    public function playerParksAreFull(int $playerId)
    {
        return ($this->playerHasMaximumParks($playerId)
            && (new GlobalPlayerPark($playerId))->parksAreFull()
        );
    }

    public function atLeastOnePlayerParksAreFull(array $playerIdArray)
    {
        foreach ($playerIdArray as $playerId) {
            if ($this->playerParksAreFull($playerId)) {
                return true;
            }
        }
        return false;
    }

    public function getGameProgression(array $playerIdArray)
    {
        $maxCoveredGrids = 0;
        foreach ($playerIdArray as $playerId) {
            $maxCoveredGrids = max($maxCoveredGrids, (new GlobalPlayerPark($playerId))->countCoveredGrids());
        }
        return floor($maxCoveredGrids * 100 / (PARK_SIZE * PARK_SIZE * PLAYER_MAXIMUM_NUMBER_OF_PARKS));
    }

    public function parkExistsForPlayer($parkId, $playerId)
    {
        $park = $this->getRowByKey($parkId);
        if ($park === null) {
            return false;
        }
        return ($park->playerId == $playerId);
    }

    public function getTopChoosableParkIds()
    {
        $ids = [];
        foreach ($this->getAllRowsByKey() as $park) {
            if ($park->isSupplyPileTop) {
                $ids[] = $park->parkId;
            }
        }
        return $ids;
    }

    public function getValidPositions(ParkShapeValidityArgs $args)
    {
        return (new GlobalPlayerPark($args->playerId))->getValidPositions($args);
    }

    public function getNeighbourPositions(int $playerId)
    {
        return (new GlobalPlayerPark($playerId))->getNeighbourPositions();
    }

    public function isShapePositionValid(ParkShapeValidityArgs $args)
    {
        return (new GlobalPlayerPark($args->playerId))->isShapePositionValid($args);
    }

    public function getShapeOverlappedIcons(int $playerId, int $shapeId)
    {
        return (new GlobalPlayerPark($playerId))->getShapeOverlappedIcons($shapeId);
    }

    public function getFilledParkMissingStatueGrid(int $playerId)
    {
        return (new GlobalPlayerPark($playerId))->getFilledParkMissingStatueGrid();
    }

    public function getShapesAdjacentGroups(int $playerId, string $baseClassId)
    {
        return (new GlobalPlayerPark($playerId))->getShapesAdjacentGroups($baseClassId);
    }

    public function getLongestEndConnection(int $playerId, string $classId)
    {
        return (new GlobalPlayerPark($playerId))->getLongestEndConnection($classId);
    }

    public function getNewParkValidPositions(int $playerId)
    {
        $parks = $this->getPlayerParks($playerId);
        $newValidPos = [];
        foreach ($parks as $park) {
            $newValidPos[] = new ParkNewValidPosition($park->posX + 0, $park->posY + 1);
            $newValidPos[] = new ParkNewValidPosition($park->posX + 0, $park->posY - 1);
            $newValidPos[] = new ParkNewValidPosition($park->posX + 1, $park->posY + 0);
            $newValidPos[] = new ParkNewValidPosition($park->posX - 1, $park->posY + 0);
        }

        $newValidPos = array_filter($newValidPos, fn ($p) => $p->posY >= 0);
        foreach ($parks as $park) {
            $newValidPos = array_filter($newValidPos, fn ($p) => !$p->isEqual($park));
        }

        return array_values($newValidPos);
    }

    public function revealSupplyPileTop()
    {
        $parksByPile = [[], []];
        foreach ($this->getAllRowsByKey() as $park) {
            if ($park->playerId !== null || $park->supplyPile === null) {
                continue;
            }
            $parksByPile[$park->supplyPile][] = $park;
        }
        $topParks = [];
        foreach ($parksByPile as $pile => &$parks) {
            usort($parks, function ($p1, $p2) {
                $cmp = $p2->isSupplyPileTop <=> $p1->isSupplyPileTop;
                if ($cmp != 0) return $cmp;
                return $p1->supplyPileOrder <=> $p2->supplyPileOrder;
            });
            if (count($parks) > 0) {
                if (!$parks[0]->isSupplyPileTop) {
                    $parks[0]->isSupplyPileTop = true;
                    $this->db->updateRow($parks[0]);
                }
                $topParks[] = $parks[0];
            }
        }
        return $topParks;
    }

    public function debugAssignParks($playerIdArray)
    {
        $loopCount = 0;
        foreach ($playerIdArray as $playerId) {
            while (!$this->playerHasMaximumParks($playerId)) {
                $topPark = null;
                foreach ($this->getPlayerParks($playerId) as $park) {
                    if ($topPark === null || $park->posY > $topPark->posY) {
                        $topPark = $park;
                    }
                }
                $parkIds = $this->getTopChoosableParkIds();
                shuffle($parkIds);
                $park = $this->getRowByKey($parkIds[0]);
                $park->moveToPlayerPark($playerId, $topPark->posX, $topPark->posY + 1);
                $this->db->updateRow($park);
                $this->revealSupplyPileTop();
                ++$loopCount;
                if ($loopCount >= 100) {
                    die("DEBUG: Loop too long");
                }
            }
        }
    }

    public function debugAssignShapes($playerIdArray)
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        foreach ($playerIdArray as $playerId) {
            for ($i = 0; $i < 15; ++$i) {
                $shapeIds = $shapeMgr->getTopChoosableShapeIds([\BP\ShapeGreenBase::class, \BP\ShapeWhiteAnimalHouseBase::class, \BP\ShapeOrangeEnclosureBase::class]);
                shuffle($shapeIds);
                $validPositions = $this->getValidPositions((new \BP\ParkShapeValidityArgs())->setPlayerId($playerId)->setShapeId($shapeIds[0]));
                shuffle($validPositions);
                if (count($validPositions) == 0) {
                    continue;
                }
                $shape = $shapeMgr->getRowByKey($shapeIds[0]);
                $shapeMgr->modifyAction($shape);
                $shape->moveToPlayerSupply($playerId);
                $shape->placeInPark(
                    $validPositions[0]->parkId,
                    $validPositions[0]->parkTopX,
                    $validPositions[0]->parkTopY,
                    $validPositions[0]->parkRotation,
                    $validPositions[0]->parkHorizontalFlip,
                    $validPositions[0]->parkVerticalFlip
                );
            }
        }
        foreach ($shapeMgr->getAll() as $shape) {
            if ($shape->playerId !== null) {
                continue;
            }
            $shapeMgr->modifyAction($shape);
            $shape->moveToPlayerSupply($playerIdArray[0]);
        }
        $shapeMgr->saveModifiedActions();
        $shapeMgr->clearModifiedActions();
    }

    public function debugFillWithToilets($playerIdArray)
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        foreach ($playerIdArray as $playerId) {
            $shapeToilet = $shapeMgr->getRowByKey('4000');
            foreach (array_values($this->getPlayerParks($playerId)) as $parkIdx => $park) {
                for ($x = 0; $x < PARK_SIZE; ++$x) {
                    for ($y = 0; $y < PARK_SIZE; ++$y) {
                        // Leave first row empty
                        if ($parkIdx == 0 && $y == 0) {
                            continue;
                        }
                        if ($this->isShapePositionValid((new ParkShapeValidityArgs())
                                ->setPlayerId($playerId)
                                ->setShapeId($shapeToilet->shapeId)
                                ->setParkId($park->parkId)
                                ->setParkTopX($x)
                                ->setParkTopY($y)
                                ->setParkRotation(0)
                                ->setParkHorizontalFlip(false)
                                ->setParkVerticalFlip(false)
                                ->setValidateAdjacency(false)
                        )) {
                            $newShape = $shapeToilet->cloneNewAction($shapeMgr->generateNewShapeId());
                            $newShape->moveToPlayerSupply($playerId);
                            $newShape->placeInPark(
                                $park->parkId,
                                $x,
                                $y,
                                0,
                                false,
                                false
                            );
                        } else {
                            if ($parkIdx != 0) {
                                $statue = $shapeMgr->getFirstSupplyBoardShapeOfType(\BP\ShapeBearStatue::class);
                                $statue->modifyAction();
                                $statue->moveToPlayerSupply($playerId);
                                $statue->placeInPark(
                                    $park->parkId,
                                    $x,
                                    $y,
                                    0,
                                    false,
                                    false
                                );
                            }
                        }
                    }
                }
            }
        }
        $shapeMgr->saveModifiedActions();
        $shapeMgr->clearModifiedActions();
    }
}
