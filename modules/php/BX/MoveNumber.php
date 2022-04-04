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

namespace BX\MoveNumber;

require_once('Action.php');
require_once('BGAGlobal.php');
require_once('DB.php');

// Annotations used by this module:
// @dbmovenumber: When updating a row, update this column with the current move number

trait DBRowMgrTrait
{
    public function onBeforeUpdateRow(\BX\DB\BaseRow $row)
    {
        parent::onBeforeUpdateRow($row);
        $colWithMoveNumber = $this->getColumnsWithMoveNumber();
        if (!empty($colWithMoveNumber)) {
            $move = \BX\BGAGlobal\GlobalMgr::getCurrentMoveNumber();
            foreach ($colWithMoveNumber as $c) {
                $p = $c->property();
                $row->$p = $move;
            }
        }
    }

    public function getColumnsWithMoveNumber()
    {
        $columns = [];
        $meta = new \BX\Meta\Annotation($this->baseRowClassName);
        foreach ($meta->getPropertiesWithAnnotation("@dbmovenumber") as $property) {
            $columns[] = \BX\DB\ColumnProperty::fromProperty($property);
        }
        return $columns;
    }
}

class DBRowMgr extends \BX\DB\RowMgr
{
    use DBRowMgrTrait;
}

trait PlayerLastSeenMoveNumberTrait
{
    /** @dbcol */
    public $lastSeenMoveNumber;
}

trait PlayerMgrLastSeenMoveNumberTrait
{
    public function updatePlayerLastSeenMoveNow(int $playerId)
    {
        $row = $this->db->getRowByKey($playerId);
        $row->lastSeenMoveNumber = \BX\BGAGlobal\GlobalMgr::getCurrentMoveNumber();
        $this->db->updateRow($row);
    }

    public function getLastSeenMoveNumberByPlayerId()
    {
        return array_map(fn ($p) => $p->lastSeenMoveNumber, array_filter($this->getAllRowsByKey(), fn ($p) => $p->lastSeenMoveNumber !== null));
    }
}

trait ActionMgrSavedMoveNumberTrait
{
    public function getSavedMoveNumberByKey()
    {
        $columns = $this->db->getColumnsWithMoveNumber();
        $p = $columns[0]->property();
        return array_map(fn ($a) => $a->$p, array_filter($this->getAllRowsByKey(), fn ($a) => $a->$p !== null));
    }
}

function getSavedMoveNumberForManagers($playerMgrName, ...$mgrNames)
{
    $ret[$playerMgrName] = \BX\Action\ActionRowMgrRegister::getMgr($playerMgrName)->getLastSeenMoveNumberByPlayerId();
    foreach ($mgrNames as $name) {
        $ret[$name] = \BX\Action\ActionRowMgrRegister::getMgr($name)->getSavedMoveNumberByKey();
    }
    return $ret;
}
