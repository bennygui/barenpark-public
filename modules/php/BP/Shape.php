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

const SHAPE_LOCATION_ID_SUPPLY_BOARD = 0;
const SHAPE_LOCATION_ID_PLAYER_SUPPLY = 1;
const SHAPE_LOCATION_ID_PLAYER_PARK = 2;

abstract class ShapeBase extends \BX\Action\BaseActionRow
{
    /** @dbcol @dbkey */
    public $shapeId;
    /** @dbcol */
    public $shapeDefId;
    /** @dbcol @dbclassid */
    public $classId;
    /** @dbcol */
    public $shapeScore;
    /** @dbcol */
    public $shapeLocationId;
    /** @dbcol */
    public $playerId;
    /** @dbcol */
    public $parkId;
    /** @dbcol */
    public $parkTopX;
    /** @dbcol */
    public $parkTopY;
    /** @dbcol */
    public $parkRotation;
    /** @dbcol */
    public $parkHorizontalFlip;
    /** @dbcol */
    public $parkVerticalFlip;
    /** @dbcol @dbmovenumber */
    public $savedMoveNumber;

    public function __construct()
    {
        $this->shapeLocationId = SHAPE_LOCATION_ID_SUPPLY_BOARD;
    }

    abstract public function generateShapeId();
    abstract public function getShapeNameText();

    public function jsonSerialize()
    {
        $ret = parent::jsonSerialize();
        $ret['baseGridWidth'] = count(static::SHAPE_ARRAY[0]);
        $ret['baseGridHeight'] = count(static::SHAPE_ARRAY);
        $ret['shapeArray'] = static::SHAPE_ARRAY;
        return $ret;
    }

    public function moveToPlayerSupply(int $playerId)
    {
        $this->shapeLocationId = SHAPE_LOCATION_ID_PLAYER_SUPPLY;
        $this->playerId = $playerId;
    }

    public function isInPlayerSupply(int $playerId)
    {
        return ($this->shapeLocationId == SHAPE_LOCATION_ID_PLAYER_SUPPLY &&
            $this->playerId !== null &&
            $this->playerId == $playerId);
    }

    public function isInPlayerPark(int $playerId)
    {
        return ($this->shapeLocationId == SHAPE_LOCATION_ID_PLAYER_PARK &&
            $this->playerId !== null &&
            $this->playerId == $playerId);
    }

    public function isOnSupplyBoard()
    {
        return ($this->shapeLocationId == SHAPE_LOCATION_ID_SUPPLY_BOARD);
    }

    public function placeInPark(
        int $parkId,
        int $parkTopX,
        int $parkTopY,
        int $parkRotation,
        bool $parkHorizontalFlip,
        bool $parkVerticalFlip
    ) {
        $this->shapeLocationId = SHAPE_LOCATION_ID_PLAYER_PARK;
        $this->parkId = $parkId;
        $this->parkTopX = $parkTopX;
        $this->parkTopY = $parkTopY;
        $this->parkRotation = $parkRotation;
        $this->parkHorizontalFlip = $parkHorizontalFlip;
        $this->parkVerticalFlip = $parkVerticalFlip;
    }

    public function getShapeArrayWithParkTransform()
    {
        return $this->getShapeArray($this->parkRotation, $this->parkHorizontalFlip, $this->parkVerticalFlip);
    }

    public function getShapeArray(int $rotation, int $horizontalFlip, int $verticalFlip)
    {
        $shapeArray = static::SHAPE_ARRAY;
        for ($r = 0; $r < $rotation; $r += 90) {
            $shapeArray = $this->rotateArray90($shapeArray);
        }
        $invertFlip = ($rotation == 90 || $rotation == 270);
        $flipH = ($invertFlip ? $verticalFlip : $horizontalFlip);
        $flipV = ($invertFlip ? $horizontalFlip : $verticalFlip);
        if ($flipH) {
            $shapeArray = $this->flipArrayH($shapeArray);
        }
        if ($flipV) {
            $shapeArray = $this->flipArrayV($shapeArray);
        }
        return $shapeArray;
    }

    private function rotateArray90($shapeArray)
    {
        return array_map(
            function ($index) use (&$shapeArray) {
                return array_reverse(array_map(
                    function ($row) use (&$index) {
                        return $row[$index];
                    },
                    $shapeArray
                ));
            },
            array_keys($shapeArray[0])
        );
    }

    private function flipArrayH($shapeArray)
    {
        return array_map(
            function ($a) {
                return array_reverse($a);
            },
            $shapeArray
        );
    }

    private function flipArrayV($shapeArray)
    {
        return array_reverse($shapeArray);
    }
}

interface ShapeAnimalPolar {}
interface ShapeAnimalGobi {}
interface ShapeAnimalKoala {}
interface ShapeAnimalPanda {}

class ShapeBearStatue extends ShapeBase
{
    public const SHAPE_PER_PLAYER = [
        2 => [2, 4, 6, 8, 10, 12, 14, 16],
        3 => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        4 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
    ];
    public const SHAPE_ARRAY = [
        [1],
    ];

    public static function toUIString()
    {
        return 'S';
    }

    public function generateShapeId()
    {
        return 1000 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Bear Statue');
    }
}

abstract class ShapeGreenBase extends ShapeBase
{
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 0;
    }

    public function generateShapeId()
    {
        return null;
    }

    public static function getIconPriority()
    {
        return 0;
    }

    public static function toUIString()
    {
        return 'G';
    }

    public static function getTextPlural()
    {
        return clienttranslate('Green Area(s)');
    }

    public static function getChoosableShapes()
    {
        return [ShapeGreenBase::class];
    }
}

class ShapeGreenToilet extends ShapeGreenBase
{
    public const GENERATE_SHAPE_ID_START = 4000;
    public const SHAPE_PER_PLAYER = 10;
    public const SHAPE_ARRAY = [
        [1],
    ];

    public function getShapeNameText()
    {
        return clienttranslate('Toilet');
    }
}

class ShapeGreenPlayground extends ShapeGreenBase
{
    public const GENERATE_SHAPE_ID_START = 4100;
    public const SHAPE_PER_PLAYER = 10;
    public const SHAPE_ARRAY = [
        [1],
        [1],
    ];

    public function getShapeNameText()
    {
        return clienttranslate('Playground');
    }
}

class ShapeGreenRiver extends ShapeGreenBase
{
    public const GENERATE_SHAPE_ID_START = 4200;
    public const SHAPE_PER_PLAYER = [
        2 => 8,
        3 => 12,
        4 => 16,
    ];
    public const SHAPE_ARRAY = [
        [1, 0],
        [1, 1],
    ];

    public function getShapeNameText()
    {
        return clienttranslate('River');
    }
}

class ShapeGreenFoodStreet extends ShapeGreenBase
{
    public const GENERATE_SHAPE_ID_START = 4300;
    public const SHAPE_PER_PLAYER = [
        2 => 8,
        3 => 12,
        4 => 16,
    ];
    public const SHAPE_ARRAY = [
        [1],
        [1],
        [1],
    ];

    public function getShapeNameText()
    {
        return clienttranslate('Food Street');
    }
}

abstract class ShapeWhiteAnimalHouseBase extends ShapeBase
{
    public const SHAPE_PER_PLAYER = [
        2 => [2, 4, 6],
        3 => [2, 3, 4, 5, 6],
        4 => [1, 2, 3, 4, 5, 6, 7],
    ];

    public static function getIconPriority()
    {
        return 1;
    }

    public static function toUIString()
    {
        return 'W';
    }

    public static function getTextPlural()
    {
        return clienttranslate('Animal House(s)');
    }

    public static function getChoosableShapes()
    {
        return [
            ShapeWhiteAnimalHouseBase::class,
            ShapeGreenBase::class,
        ];
    }
}

class ShapeWhiteAnimalHousePolar extends ShapeWhiteAnimalHouseBase implements ShapeAnimalPolar
{
    public const SHAPE_ARRAY = [
        [1, 1, 1],
        [0, 1, 0],
    ];

    public function generateShapeId()
    {
        return 2200 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Polar Bear Animal House');
    }
}

class ShapeWhiteAnimalHouseGobi extends ShapeWhiteAnimalHouseBase implements ShapeAnimalGobi
{
    public const SHAPE_ARRAY = [
        [1, 1],
        [1, 1],
    ];

    public function generateShapeId()
    {
        return 2000 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Gobi Bear Animal House');
    }
}

class ShapeWhiteAnimalHousePanda extends ShapeWhiteAnimalHouseBase implements ShapeAnimalPanda
{
    public const SHAPE_ARRAY = [
        [0, 1, 1],
        [1, 1, 0],
    ];

    public function generateShapeId()
    {
        return 2300 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Panda Animal House');
    }
}

class ShapeWhiteAnimalHouseKoala extends ShapeWhiteAnimalHouseBase implements ShapeAnimalKoala
{
    public const SHAPE_ARRAY = [
        [1, 1, 1],
        [1, 0, 0],
    ];

    public function generateShapeId()
    {
        return 2100 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Koala Animal House');
    }
}

abstract class ShapeOrangeEnclosureBase extends ShapeBase
{
    public const SHAPE_PER_PLAYER = 1;

    public static function getIconPriority()
    {
        return 2;
    }

    public static function toUIString()
    {
        return 'O';
    }

    public static function getTextPlural()
    {
        return clienttranslate('Enclosure(s)');
    }

    public static function getChoosableShapes()
    {
        return [
            ShapeOrangeEnclosureBase::class,
            ShapeWhiteAnimalHouseBase::class,
            ShapeGreenBase::class,
        ];
    }
}

abstract class ShapeOrangeEnclosurePolarBase extends ShapeOrangeEnclosureBase implements ShapeAnimalPolar
{
    public function generateShapeId()
    {
        return 3200 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Polar Bear Enclosure');
    }
}

class ShapeOrangeEnclosurePolar6 extends ShapeOrangeEnclosurePolarBase
{
    public const SHAPE_ARRAY = [
        [1, 1, 1, 1, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 6;
    }
}

class ShapeOrangeEnclosurePolar7 extends ShapeOrangeEnclosurePolarBase
{
    public const SHAPE_ARRAY = [
        [1, 1, 1],
        [0, 1, 0],
        [0, 1, 0],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 7;
    }
}

class ShapeOrangeEnclosurePolar8 extends ShapeOrangeEnclosurePolarBase
{
    public const SHAPE_ARRAY = [
        [0, 1, 0],
        [1, 1, 1],
        [0, 1, 0],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 8;
    }
}

abstract class ShapeOrangeEnclosureGobiBase extends ShapeOrangeEnclosureBase implements ShapeAnimalGobi
{
    public function generateShapeId()
    {
        return 3000 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Gobi Bear Enclosure');
    }
}

class ShapeOrangeEnclosureGobi6 extends ShapeOrangeEnclosureGobiBase
{
    public const SHAPE_ARRAY = [
        [1, 1],
        [1, 1],
        [1, 0],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 6;
    }
}

class ShapeOrangeEnclosureGobi7 extends ShapeOrangeEnclosureGobiBase
{
    public const SHAPE_ARRAY = [
        [0, 1, 0],
        [1, 1, 1],
        [0, 0, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 7;
    }
}

class ShapeOrangeEnclosureGobi8 extends ShapeOrangeEnclosureGobiBase
{
    public const SHAPE_ARRAY = [
        [1, 0, 0],
        [1, 1, 1],
        [0, 0, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 8;
    }
}

abstract class ShapeOrangeEnclosurePandaBase extends ShapeOrangeEnclosureBase implements ShapeAnimalPanda
{
    public function generateShapeId()
    {
        return 3300 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Panda Enclosure');
    }
}

class ShapeOrangeEnclosurePanda6 extends ShapeOrangeEnclosurePandaBase
{
    public const SHAPE_ARRAY = [
        [0, 0, 1, 0],
        [1, 1, 1, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 6;
    }
}

class ShapeOrangeEnclosurePanda7 extends ShapeOrangeEnclosurePandaBase
{
    public const SHAPE_ARRAY = [
        [0, 1],
        [1, 1],
        [1, 0],
        [1, 0],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 7;
    }
}

class ShapeOrangeEnclosurePanda8 extends ShapeOrangeEnclosurePandaBase
{
    public const SHAPE_ARRAY = [
        [1, 1, 0],
        [0, 1, 1],
        [0, 0, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 8;
    }
}

abstract class ShapeOrangeEnclosureKoalaBase extends ShapeOrangeEnclosureBase implements ShapeAnimalKoala
{
    public function generateShapeId()
    {
        return 3100 + $this->shapeScore;
    }

    public function getShapeNameText()
    {
        return clienttranslate('Koala Enclosure');
    }
}

class ShapeOrangeEnclosureKoala6 extends ShapeOrangeEnclosureKoalaBase
{
    public const SHAPE_ARRAY = [
        [1, 1],
        [0, 1],
        [0, 1],
        [0, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 6;
    }
}

class ShapeOrangeEnclosureKoala7 extends ShapeOrangeEnclosureKoalaBase
{
    public const SHAPE_ARRAY = [
        [1, 1, 1],
        [1, 0, 0],
        [1, 0, 0],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 7;
    }
}

class ShapeOrangeEnclosureKoala8 extends ShapeOrangeEnclosureKoalaBase
{
    public const SHAPE_ARRAY = [
        [1, 1, 1],
        [1, 0, 1],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->shapeScore = 8;
    }
}

class ShapesCount extends \BX\UI\UISerializable
{
    public $classId;
    public $count;

    public function __construct(string $classId, array $shapes)
    {
        $this->classId = $classId;
        $this->count = count($shapes);
    }
}

class ShapeMgr extends \BX\Action\BaseActionRowMgr
{
    use \BX\MoveNumber\ActionMgrSavedMoveNumberTrait;

    private const STARTING_SHAPE_PER_PLAYER_COUNT = [
        2 => [
            ShapeGreenToilet::class,
            ShapeGreenPlayground::class,
        ],
        3 => [
            ShapeGreenToilet::class,
            ShapeGreenPlayground::class,
            ShapeGreenFoodStreet::class,
        ],
        4 => [
            ShapeGreenToilet::class,
            ShapeGreenPlayground::class,
            ShapeGreenPlayground::class,
            ShapeGreenFoodStreet::class,
        ],
    ];
    private const ALL_SHAPE_CLASS_IDS = [
        ShapeBearStatue::class,
        ShapeGreenToilet::class,
        ShapeGreenPlayground::class,
        ShapeGreenRiver::class,
        ShapeGreenFoodStreet::class,
        ShapeWhiteAnimalHousePolar::class,
        ShapeWhiteAnimalHouseGobi::class,
        ShapeWhiteAnimalHousePanda::class,
        ShapeWhiteAnimalHouseKoala::class,
        ShapeOrangeEnclosurePolar6::class,
        ShapeOrangeEnclosurePolar7::class,
        ShapeOrangeEnclosurePolar8::class,
        ShapeOrangeEnclosureGobi6::class,
        ShapeOrangeEnclosureGobi7::class,
        ShapeOrangeEnclosureGobi8::class,
        ShapeOrangeEnclosurePanda6::class,
        ShapeOrangeEnclosurePanda7::class,
        ShapeOrangeEnclosurePanda8::class,
        ShapeOrangeEnclosureKoala6::class,
        ShapeOrangeEnclosureKoala7::class,
        ShapeOrangeEnclosureKoala8::class,
    ];
    private const BASE_NEW_SHAPE_ID = 10000;

    public function __construct()
    {
        parent::__construct('shape', \BP\ShapeBase::class);
    }

    public function setup(array $playerIdArray)
    {
        $playerCount = count($playerIdArray);
        foreach (self::ALL_SHAPE_CLASS_IDS as $classId) {
            if (is_numeric($classId::SHAPE_PER_PLAYER)) {
                for ($i = 0; $i < $classId::SHAPE_PER_PLAYER; ++$i) {
                    $s = $this->db->newRow($classId);
                    $s->shapeId = $s->generateShapeId();
                    if ($s->shapeId === null) {
                        $s->shapeId = $classId::GENERATE_SHAPE_ID_START + $i;
                    }
                    $s->shapeDefId = $s->shapeId;
                    $this->db->insertRow($s);
                }
            } else if (is_array($classId::SHAPE_PER_PLAYER[$playerCount])) {
                foreach ($classId::SHAPE_PER_PLAYER[$playerCount] as $score) {
                    $s = $this->db->newRow($classId);
                    $s->shapeScore = $score;
                    $s->shapeId = $s->generateShapeId();
                    if ($s->shapeId === null) {
                        $s->shapeId = $classId::GENERATE_SHAPE_ID_START + $score;
                    }
                    $s->shapeDefId = $s->shapeId;
                    $this->db->insertRow($s);
                }
            } else {
                for ($i = 0; $i < $classId::SHAPE_PER_PLAYER[$playerCount]; ++$i) {
                    $s = $this->db->newRow($classId);
                    $s->shapeId = $s->generateShapeId();
                    if ($s->shapeId === null) {
                        $s->shapeId = $classId::GENERATE_SHAPE_ID_START + $i;
                    }
                    $s->shapeDefId = $s->shapeId;
                    $this->db->insertRow($s);
                }
            }
        }

        $shapeTypes = self::STARTING_SHAPE_PER_PLAYER_COUNT[$playerCount];
        foreach ($playerIdArray as $playerId) {
            $shape = $this->getFirstSupplyBoardShapeOfType(array_shift($shapeTypes));
            $shape->moveToPlayerSupply($playerId);
            $this->db->updateRow($shape);
        }
    }

    public function getShapeById(int $shapeId)
    {
        return $this->getRowByKey($shapeId);
    }

    public function getAll()
    {
        return $this->getAllRowsByKey();
    }

    public function generateNewShapeId()
    {
        $max = max(array_keys($this->getAll()));
        if ($max < self::BASE_NEW_SHAPE_ID) {
            $max = self::BASE_NEW_SHAPE_ID;
        }
        return $max + 1;
    }

    public function supplyBoardHasShapes()
    {
        return count(array_filter($this->getAll(), fn($shape) => $shape->isOnSupplyBoard())) > 0;
    }

    public function getPlayerSupplyShapeIds(int $playerId)
    {
        return array_map(
            function ($shape) {
                return $shape->shapeId;
            },
            $this->getPlayerSupplyShapes($playerId)
        );
    }

    public function getPlayerSupplyShapes(int $playerId)
    {
        return array_values(array_filter($this->getAll(), function ($shape) use ($playerId) {
            return ($shape->isInPlayerSupply($playerId));
        }));
    }

    public function getPlayerSupplyFirstShapeId(int $playerId)
    {
        $shapeIds = $this->getPlayerSupplyShapeIds($playerId);
        if (count($shapeIds) > 0) {
            return $shapeIds[0];
        }
        return null;
    }

    public function getPlayerSupplyShapesScore(int $playerId)
    {
        return array_sum(array_map(fn($s) => $s->shapeScore, $this->getPlayerSupplyShapes($playerId)));
    }

    public function getPlayerParkShapes(int $playerId)
    {
        return array_values(array_filter($this->getAll(), function ($shape) use ($playerId) {
            return ($shape->isInPlayerPark($playerId));
        }));
    }

    public function getSupplyShapesCount($onlyClassId = null)
    {
        return array_values(
            array_map(
                fn ($classId) => new ShapesCount($classId, $this->getSupplyBoardShapeOfTypeInOrder($classId)),
                array_filter(
                    self::ALL_SHAPE_CLASS_IDS,
                    fn ($classId) => (!is_subclass_of($classId, \BP\ShapeOrangeEnclosureBase::class) && ($onlyClassId === null || $onlyClassId === $classId))
                )
            )
        );
    }

    public function getTopChoosableShapeIds($choosableShapeBaseTypes)
    {
        $classIds = [];
        foreach (array_unique($choosableShapeBaseTypes) as $baseClassId) {
            foreach (self::ALL_SHAPE_CLASS_IDS as $classId) {
                if (is_subclass_of($classId, $baseClassId)) {
                    $classIds[] = $classId;
                }
            }
        }
        $ids = [];
        foreach ($classIds as $classId) {
            $firstShape = $this->getFirstSupplyBoardShapeOfType($classId);
            if ($firstShape !== null) {
                $ids[] = $firstShape->shapeId;
            }
        }
        return $ids;
    }

    public function getSupplyBoardShapeOfTypeInOrder(string $classId)
    {
        $shapes = array_values(array_filter($this->getAll(), function ($shape) use ($classId) {
            return ($classId == $shape->classId && $shape->playerId === null);
        }));
        usort($shapes, function ($s1, $s2) {
            $cmp = ($s2->shapeScore <=> $s1->shapeScore);
            if ($cmp == 0) {
                $cmp = ($s1->shapeId <=> $s2->shapeId);
            }
            return $cmp;
        });
        return $shapes;
    }

    public function getFirstSupplyBoardShapeOfType(string $classId)
    {
        $shapes = $this->getSupplyBoardShapeOfTypeInOrder($classId);
        if (count($shapes) == 0) {
            return null;
        }
        return $shapes[0];
    }
}
