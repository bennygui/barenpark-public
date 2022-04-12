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

abstract class AchievementBase extends \BX\Action\BaseActionRow
{
    /** @dbcol @dbkey */
    public $achievementId;
    /** @dbcol @dbclassid */
    public $classId;
    /** @dbcol */
    public $achievementScore;
    /** @dbcol */
    public $supplyPile;
    /** @dbcol */
    public $supplyPileOrder;
    /** @dbcol */
    public $playerId;
    /** @dbcol @dbmovenumber */
    public $savedMoveNumber;

    public function jsonSerialize()
    {
        $ret = parent::jsonSerialize();
        $ret['description'] = $this->description();
        return $ret;
    }

    abstract public function description();

    public function moveToPlayer(int $playerId)
    {
        $this->playerId = $playerId;
    }

    abstract public function playerHasAchievementRequirements(int $playerId, int $gainedBearStatueCount);
    abstract public function achievementName();

    protected static function getMgr(string $key)
    {
        return \BX\Action\ActionRowMgrRegister::getMgr($key);
    }
}

abstract class AchievementAnimalBase extends AchievementBase
{
    public const SCORES = [8, 5, 2];
    private const NB_SHAPES_REQUIREMENT = 3;

    public function playerHasAchievementRequirements(int $playerId, int $gainedBearStatueCount)
    {
        $shapes = self::getMgr('shape')->getPlayerParkShapes($playerId);
        $baseClassId = $this->getShapeAnimalClassId();
        return (count(array_filter($shapes, fn ($s) => is_subclass_of(get_class($s), $baseClassId))) >= self::NB_SHAPES_REQUIREMENT);
    }

    abstract protected function getShapeAnimalClassId();
}

class AchievementAnimalPolar extends AchievementAnimalBase
{
    public const BASE_ID = 1;

    public function achievementName()
    {
        return clienttranslate('3 Polar Bear Tiles');
    }

    protected function getShapeAnimalClassId()
    {
        return \BP\ShapeAnimalPolar::class;
    }

    public function description()
    {
        return clienttranslate('Polar Bears: There are three tiles with polar bears in your park (regardless of size). These tiles need not be adjacent to one another.');
    }
}

class AchievementAnimalGobi extends AchievementAnimalBase
{
    public const BASE_ID = 2;

    public function achievementName()
    {
        return clienttranslate('3 Gobi Bear Tiles');
    }

    protected function getShapeAnimalClassId()
    {
        return \BP\ShapeAnimalGobi::class;
    }

    public function description()
    {
        return clienttranslate('Gobi Bears: There are three tiles with Gobi bears in your park (regardless of size). These tiles need not be adjacent to one another.');
    }
}

class AchievementAnimalKoala extends AchievementAnimalBase
{
    public const BASE_ID = 3;

    public function achievementName()
    {
        return clienttranslate('3 Koala Tiles');
    }

    protected function getShapeAnimalClassId()
    {
        return \BP\ShapeAnimalKoala::class;
    }

    public function description()
    {
        return clienttranslate('Koalas: There are three tiles with koalas in your park (regardless of size). These tiles need not be adjacent to one another.');
    }
}

class AchievementAnimalPanda extends AchievementAnimalBase
{
    public const BASE_ID = 4;

    public function achievementName()
    {
        return clienttranslate('3 Panda Tiles');
    }

    protected function getShapeAnimalClassId()
    {
        return \BP\ShapeAnimalPanda::class;
    }

    public function description()
    {
        return clienttranslate('Pandas: There are three tiles with pandas in your park (regardless of size). These tiles need not be adjacent to one another.');
    }
}

abstract class AchievementShapeGroupBase extends AchievementBase
{
    public const SCORES = [9, 6, 3];

    public function playerHasAchievementRequirements(int $playerId, int $gainedBearStatueCount)
    {
        $baseClassId = $this->getShapeBaseClassId();
        $minAdjacentShapes = $this->getMinimumAdjacentShapes();
        $shapeGroups = self::getMgr('park')->getShapesAdjacentGroups($playerId, $baseClassId);
        return (count(array_filter($shapeGroups, fn ($group) => count($group) >= $minAdjacentShapes)) > 0);
    }

    abstract protected function getShapeBaseClassId();
    abstract protected function getMinimumAdjacentShapes();
}

class AchievementShapeGroupGreen extends AchievementShapeGroupBase
{
    public const BASE_ID = 5;

    public function achievementName()
    {
        return clienttranslate('6 Adjacent Green Tiles');
    }

    protected function getShapeBaseClassId()
    {
        return \BP\ShapeGreenBase::class;
    }
    protected function getMinimumAdjacentShapes()
    {
        return 6;
    }

    public function description()
    {
        return clienttranslate('Green Areas: There is a cluster of six Green Areas in your park (Toilets, Playgrounds, Food Streets, Rivers). Each tile in the cluster must be orthogonally adjacent to another tile in the cluster (not diagonal).');
    }
}

class AchievementShapeGroupOrange extends AchievementShapeGroupBase
{
    public const BASE_ID = 8;

    public function achievementName()
    {
        return clienttranslate('3 Adjacent Enclosures');
    }

    protected function getShapeBaseClassId()
    {
        return \BP\ShapeOrangeEnclosureBase::class;
    }
    protected function getMinimumAdjacentShapes()
    {
        return 3;
    }

    public function description()
    {
        return clienttranslate('Enclosures: All three Enclosures in your park form a cluster, i.e. each Enclosure is orthogonally adjacent to at least one other Enclosure (not diagonal).');
    }
}

abstract class AchievementEndConnectionBase extends AchievementBase
{
    private const NB_CONNECTED = 3;

    public function playerHasAchievementRequirements(int $playerId, int $gainedBearStatueCount)
    {
        $classId = $this->getShapeClassId();
        $length = self::getMgr('park')->getLongestEndConnection($playerId, $classId);
        return ($length >= self::NB_CONNECTED);
    }

    abstract protected function getShapeClassId();
}

class AchievementEndConnectionFoodStreet extends AchievementEndConnectionBase
{
    public const BASE_ID = 6;
    public const SCORES = [9, 6, 3];

    public function achievementName()
    {
        return clienttranslate('3 Contiguous Food Streets');
    }

    protected function getShapeClassId()
    {
        return \BP\ShapeGreenFoodStreet::class;
    }

    public function description()
    {
        return clienttranslate('Long Food Street: There is a (horizontal or vertical) line of three contiguous Food Streets in your park.');
    }
}

class AchievementEndConnectionRiver extends AchievementEndConnectionBase
{
    public const BASE_ID = 7;
    public const SCORES = [10, 7, 4];

    public function achievementName()
    {
        return clienttranslate('3 Contiguous Rivers');
    }

    protected function getShapeClassId()
    {
        return \BP\ShapeGreenRiver::class;
    }

    public function description()
    {
        return clienttranslate('Long River: There are three River tiles in your park forming an uninterrupted watercourse across these tiles.');
    }
}

class AchievementAnimalHouse extends AchievementBase
{
    public const BASE_ID = 9;
    public const SCORES = [8, 5, 2];
    private const NB_SHAPE_TYPE = 4;

    public function achievementName()
    {
        return clienttranslate('4 Different Animal Houses');
    }

    public function playerHasAchievementRequirements(int $playerId, int $gainedBearStatueCount)
    {
        $shapes = self::getMgr('shape')->getPlayerParkShapes($playerId);
        $shapeClassIds = [];
        foreach ($shapes as $shape) {
            $classId = get_class($shape);
            if (!is_subclass_of($classId, \BP\ShapeWhiteAnimalHouseBase::class)) {
                continue;
            }
            $shapeClassIds[$classId] = true;
        }
        return (count($shapeClassIds) >= self::NB_SHAPE_TYPE);
    }

    public function description()
    {
        return clienttranslate('Animal Houses: There is at least one each of the four different shapes of Animal Houses in your park. These tiles need not be adjacent to one another.');
    }
}

class AchievementBearStatue extends AchievementBase
{

    public function achievementName()
    {
        return clienttranslate('2 Bear Statues in a Turn');
    }
    public const BASE_ID = 10;
    public const SCORES = [10, 7, 4];
    private const NB_BEAR_STATUE = 2;

    public function playerHasAchievementRequirements(int $playerId, int $gainedBearStatueCount)
    {
        return ($gainedBearStatueCount >= self::NB_BEAR_STATUE);
    }

    public function description()
    {
        return clienttranslate('Twice Is Nice: You have placed two (or more) Bear Statues in a single turn on the Pits of newly completed Park Areas.');
    }
}

class AchievementMgr extends \BX\Action\BaseActionRowMgr
{
    use \BX\MoveNumber\ActionMgrSavedMoveNumberTrait;

    private const NB_ACHIEVEMENT_PER_GAME = 3;
    private const STARTING_ACHIEVEMENT_PER_PLAYER_COUNT = [
        2 => 2,
        3 => 3,
        4 => 3,
    ];
    private const ALL_ACHIEVEMENT_CLASS_ID = [
        AchievementAnimalPolar::class,
        AchievementAnimalGobi::class,
        AchievementAnimalKoala::class,
        AchievementAnimalPanda::class,
        AchievementShapeGroupGreen::class,
        AchievementEndConnectionFoodStreet::class,
        AchievementEndConnectionRiver::class,
        AchievementShapeGroupOrange::class,
        AchievementAnimalHouse::class,
        AchievementBearStatue::class,
    ];

    public function __construct()
    {
        parent::__construct('achievement', \BP\AchievementBase::class);
    }

    public function setup(bool $gameUsesAchievements, array $playerIdArray)
    {
        if (!$gameUsesAchievements) {
            return;
        }
        $playerCount = count($playerIdArray);
        $classIdIdxArray = array_rand(self::ALL_ACHIEVEMENT_CLASS_ID, self::NB_ACHIEVEMENT_PER_GAME);
        usort($classIdIdxArray, fn ($idx1, $idx2) => self::ALL_ACHIEVEMENT_CLASS_ID[$idx1]::BASE_ID <=> self::ALL_ACHIEVEMENT_CLASS_ID[$idx2]::BASE_ID);
        foreach ($classIdIdxArray as $i => $classIdIdx) {
            $classId = self::ALL_ACHIEVEMENT_CLASS_ID[$classIdIdx];
            for ($scoreIdx = 0; $scoreIdx < self::STARTING_ACHIEVEMENT_PER_PLAYER_COUNT[$playerCount]; ++$scoreIdx) {
                $a = $this->db->newRow($classId);
                $score = $classId::SCORES[$scoreIdx];
                $a->achievementId = sprintf('%d%02d', $classId::BASE_ID, $score);
                $a->achievementScore = $score;
                $a->supplyPile = $i;
                $a->supplyPileOrder = self::STARTING_ACHIEVEMENT_PER_PLAYER_COUNT[$playerCount] - $scoreIdx - 1;
                $this->db->insertRow($a);
            }
        }
    }

    public function getAchievementById(int $achievementId)
    {
        return $this->getRowByKey($achievementId);
    }

    public function getAll()
    {
        return $this->getAllRowsByKey();
    }

    public function getSupplyPilesCount()
    {
        $ret = [];
        foreach ($this->getAll() as $achievement) {
            if (!array_key_exists($achievement->supplyPile, $ret)) {
                $ret[$achievement->supplyPile] = 0;
            }
            if ($achievement->playerId === null) {
                $ret[$achievement->supplyPile] += 1;
            }
        }
        return $ret;
    }

    public function gameUsesAchievements()
    {
        return (count($this->getAll()) > 0);
    }

    public function getTopSupplyPileAchievements()
    {
        $group = [];
        foreach ($this->getAll() as $achievement) {
            if ($achievement->playerId === null) {
                if (!array_key_exists($achievement->classId, $group)) {
                    $group[$achievement->classId] = [];
                }
                $group[$achievement->classId][] = $achievement;
            }
        }
        return array_values(array_map(function ($array) {
            usort($array, fn ($a1, $a2) => $a2->achievementScore <=> $a1->achievementScore);
            return $array[0];
        }, $group));
    }

    public function playerHasAchievementType(int $playerId, \BP\AchievementBase $achievementToCheck)
    {
        foreach ($this->getAll() as $achievement) {
            if ($achievement->playerId == $playerId && $achievement->classId == $achievementToCheck->classId) {
                return true;
            }
        }
        return false;
    }

    public function getPlayerGainedAchievements(int $playerId, int $gainedBearStatueCount)
    {
        if (!$this->gameUsesAchievements()) {
            return [];
        }
        $ret = [];
        foreach ($this->getTopSupplyPileAchievements() as $achievement) {
            if ($this->playerHasAchievementType($playerId, $achievement)) {
                continue;
            }
            if ($achievement->playerHasAchievementRequirements($playerId, $gainedBearStatueCount)) {
                $ret[] = $achievement;
            }
        }
        return $ret;
    }

    public function getSupplyAchievementsCount($classId)
    {
        return count(array_filter($this->getAll(), fn ($a) => $a->playerId === null && get_class($a) == $classId));
    }

    public function debugAssignAchievements($playerIdArray)
    {
        if (!$this->gameUsesAchievements()) {
            return;
        }
        $achievements = array_values(array_filter($this->getAll(), fn ($a) => $a->playerId === null));
        shuffle($achievements);
        foreach ($playerIdArray as $playerId) {
            if (count($achievements) == 0) {
                break;
            }
            $a = array_shift($achievements);
            $a->moveToPlayer($playerId);
            $this->db->updateRow($a);
        }
    }
}
