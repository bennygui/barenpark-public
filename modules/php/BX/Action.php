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

namespace BX\Action;

require_once('DB.php');

// Annotations used by this module:
// @dbcol @dbkey: To work with Actions, database table should have only one @dbkey.

const NTF_CHANGE_PRIVATE_STATE = 'NTF_CHANGE_PRIVATE_STATE';
const NTF_MESSAGE = 'message';
const PRIVATE_STATE_TRANSITION_LOOP = 'privateStateLoop';

// Must be in sort order, smallest wins
const REEVALUATE_NO_CHANGE = 5;
const REEVALUATE_UPDATE = 4;
const REEVALUATE_DELETE_SILENT = 3;
const REEVALUATE_DELETE = 2;
const REEVALUATE_UNDO_SILENT = 1;
const REEVALUATE_UNDO = 0;

trait GameActionsTrait
{
    public function undoLast()
    {
        $playerId = self::getCurrentPlayerId();
        ActionCommandMgr::undoLast($playerId);
    }

    public function undoAll()
    {
        $playerId = self::getCurrentPlayerId();
        ActionCommandMgr::undoAll($playerId);
    }
}

class ActionRowMgrRegister
{
    private static $mgrByKey = [];

    public static function registerMgr(string $key, string $actionMgrId)
    {
        if (array_key_exists($key, self::$mgrByKey)) {
            throw new \BgaSystemException("Key $key is already registered");
        }
        self::$mgrByKey[$key] = new $actionMgrId();
    }

    public static function getMgr(string $key)
    {
        return self::$mgrByKey[$key];
    }

    public static function getAllMgr()
    {
        return self::$mgrByKey;
    }
}

abstract class BaseActionRow extends \BX\DB\BaseRow
{
    private $actionMgr;

    public function setActionMgr(BaseActionRowMgr $actionMgr)
    {
        $this->actionMgr = $actionMgr;
    }

    public function modifyAction()
    {
        $this->actionMgr->modifyAction($this);
    }

    public function newAction()
    {
        $this->actionMgr->newAction($this);
    }

    public function cloneNewAction($newKey)
    {
        $newAction = \BX\Meta\deepClone($this);
        $newAction->setActionMgr($this->actionMgr);
        $newAction->setKeyValue($newKey);
        $newAction->newAction();
        return $newAction;
    }
}

abstract class BaseActionRowMgr
{
    protected $db;
    private $modifiedRowByKey;
    private $newRowByKey;

    public function __construct(string $tableName, string $baseRowClassName)
    {
        $this->modifiedRowByKey = [];
        $this->newRowByKey = [];
        $this->db = \BX\DB\RowMgrRegister::newMgr($tableName, $baseRowClassName);
    }

    public function modifyAction(BaseActionRow $actionRow)
    {
        $this->modifiedRowByKey[$actionRow->keyValue()] = $actionRow;
    }

    public function newAction(BaseActionRow $actionRow)
    {
        $this->newRowByKey[$actionRow->keyValue()] = $actionRow;
    }

    public function saveModifiedActions()
    {
        foreach ($this->modifiedRowByKey as $row) {
            $this->db->updateRow($row);
        }
        foreach ($this->newRowByKey as $row) {
            $this->db->insertRow($row);
        }
    }

    public function clearModifiedActions()
    {
        $this->modifiedRowByKey = [];
        $this->newRowByKey = [];
    }

    public function getAllRowsByKey()
    {
        $rows = $this->db->getAllRowsByKey();
        foreach ($rows as $key => $row) {
            if (array_key_exists($key, $this->modifiedRowByKey)) {
                $rows[$key] = $this->modifiedRowByKey[$key];
            }
            $row->setActionMgr($this);
        }
        foreach ($this->newRowByKey as $key => $row) {
            $rows[$key] = $row;
        }
        return $rows;
    }

    public function getRowByKey($key)
    {
        if (array_key_exists($key, $this->modifiedRowByKey)) {
            return $this->modifiedRowByKey[$key];
        }
        if (array_key_exists($key, $this->newRowByKey)) {
            return $this->newRowByKey[$key];
        }
        $row = $this->db->getRowByKey($key);
        if ($row !== null) {
            $row->setActionMgr($this);
        }
        return $row;
    }
}

abstract class BaseActionCommand
{
    protected $playerId;

    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
    }

    public function getPlayerId()
    {
        return $this->playerId;
    }

    abstract public function do(BaseActionCommandNotifier $notifier);

    abstract public function undo(BaseActionCommandNotifier $notifier);

    public function mustAlwaysUndoAction()
    {
        return false;
    }

    public function reevaluate(?BaseActionCommandNotifier $notifier, array &$args)
    {
        return REEVALUATE_NO_CHANGE;
    }

    public function reevaluateReverseMustUndo($hasDelete, $hasUndo)
    {
        return false;
    }

    public function getReevaluationArgs()
    {
        return [];
    }

    public function getMostRecentActionClassId(string $classId)
    {
        if (get_class($this) == $classId) {
            return $this;
        }
        return null;
    }

    public function getAllActionClassIdInActionOrder(string $classId)
    {
        if (get_class($this) == $classId) {
            return [$this];
        }
        return [];
    }

    public static function getMgr(string $key)
    {
        return ActionRowMgrRegister::getMgr($key);
    }
}

class GroupActionCommand extends BaseActionCommand
{
    protected $actions;

    public function __construct(int $playerId)
    {
        parent::__construct($playerId);
        $this->actions = [];
    }

    public function add(BaseActionCommand $actionCommand)
    {
        $this->actions[] = $actionCommand;
    }

    public function do(BaseActionCommandNotifier $notifier)
    {
        foreach ($this->actions as $action) {
            $action->do($notifier);
        }
    }

    public function undo(BaseActionCommandNotifier $notifier)
    {
        foreach (\array_reverse($this->actions) as $action) {
            $action->undo($notifier);
        }
    }

    public function mustAlwaysUndoAction()
    {
        foreach ($this->actions as $action) {
            if ($action->mustAlwaysUndoAction()) {
                return true;
            }
        }
        return false;
    }

    public function reevaluate(?BaseActionCommandNotifier $notifier, array &$args)
    {
        $combinedEval = [REEVALUATE_NO_CHANGE];
        foreach ($this->actions as $i => $action) {
            $combinedEval[] = $action->reevaluate($notifier, $args);
        }
        sort($combinedEval);
        return $combinedEval[0];
    }

    public function reevaluateReverseMustUndo($hasDelete, $hasUndo)
    {
        foreach ($this->actions as $action) {
            if ($action->reevaluateReverseMustUndo($hasDelete, $hasUndo)) {
                return true;
            }
        }
        return false;
    }

    public function getReevaluationArgs()
    {
        $ret = [];
        foreach ($this->actions as $i => $action) {
            $ret = ActionCommandMgr::mergeReevaluationArgs($ret, $action->getReevaluationArgs());
        }
        return $ret;
    }

    public function getMostRecentActionClassId(string $classId)
    {
        $matching = parent::getMostRecentActionClassId($classId);
        if ($matching !== null) {
            return $matching;
        }
        foreach (\array_reverse($this->actions) as $action) {
            $matching = $action->getMostRecentActionClassId($classId);
            if ($matching !== null) {
                return $matching;
            }
        }
        return null;
    }

    public function getAllActionClassIdInActionOrder(string $classId)
    {
        $matching = parent::getAllActionClassIdInActionOrder($classId);
        foreach ($this->actions as $action) {
            $matching = array_merge($matching, $action->getAllActionClassIdInActionOrder($classId));
        }
        return $matching;
    }
}

class ReevaluateHasUndoneActionCommand extends BaseActionCommand
{
    public function do(BaseActionCommandNotifier $notifier)
    {
    }

    public function undo(BaseActionCommandNotifier $notifier)
    {
    }

    public function mustAlwaysUndoAction()
    {
        return true;
    }
}

class ActionCommandRow extends \BX\DB\BaseRow
{
    private const MAX_ACTION_JSON_SIZE = 2048;

    /** @dbcol @dbkey @dbautoincrement */
    public $actionCommandId;
    /** @dbcol */
    public $actionJson;

    public function setAction(BaseActionCommand $actionCommand)
    {
        $this->subclassId = get_class($actionCommand);
        $this->actionJson = json_encode(\BX\Meta\extractAllPropertyValues($actionCommand));
        if (strlen($this->actionJson) > self::MAX_ACTION_JSON_SIZE) {
            throw new \BgaSystemException('actionJson is too long');
        }
    }

    public function getAction()
    {
        $jsonRow = json_decode($this->actionJson, true);
        return \BX\Meta\rebuildAllPropertyValues($jsonRow);
    }
}

abstract class BaseActionCommandNotifier
{
    private static $game;
    public static function setGame(\Table $game)
    {
        self::$game = $game;
    }

    private $playerId;
    private $privateStateChanged;
    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
        $this->privateStateChanged = false;
    }

    abstract public function notify(string $notifType, string $notifLog, array $notifArgs);
    abstract public function notifyForceMessage(string $notifType, string $notifLog, array $notifArgs);
    abstract public function notifyNoMessage(string $notifType, array $notifArgs);
    abstract public function notifyAllPlayersEmpty();
    public function onNotifierEnd()
    {
        $this->notifyAllPlayersEmpty();
    }

    public function changePrivateState()
    {
        $this->privateStateChanged = true;
    }

    public function hasPrivateStateChanged()
    {
        return $this->privateStateChanged;
    }

    public function getGameStates()
    {
        return self::$game->gamestate->states;
    }

    public function getCurrentGameStateId()
    {
        return self::$game->gamestate->state_id();
    }

    protected function notifyCurrentPlayer(string $notifType, string $notifLog, array $notifArgs)
    {
        self::$game->notifyPlayer($this->playerId, $notifType, $notifLog, $this->processNotifArgs($notifArgs));
    }

    protected function notifyAllPlayers(string $notifType, string $notifLog, array $notifArgs)
    {
        self::$game->notifyAllPlayers($notifType, $notifLog, $this->processNotifArgs($notifArgs));
    }

    private function processNotifArgs(array $notifArgs)
    {
        $playerName = self::$game->loadPlayersBasicInfos()[$this->playerId]['player_name'];
        return json_decode(json_encode(
            array_merge(
                [
                    'playerId' => $this->playerId,
                    'player_id' => $this->playerId,
                    'playerName' => $playerName,
                    'player_name' => $playerName,
                ],
                $notifArgs
            )
        ), true);
    }

    protected function loopState()
    {
        $privateState = ActionRowMgrRegister::getMgr('private_state')->getRowByKey($this->playerId);
        if ($privateState->stateId === null) {
            self::$game->gamestate->nextState(PRIVATE_STATE_TRANSITION_LOOP);
        } else {
            $stateId = $privateState->stateId;
            $states = $this->getGameStates();
            if (!\array_key_exists($stateId, $states)) {
                throw new \BgaSystemException("State {$stateId} does not exists");
            }
            $state = $states[$stateId];
            if (!\array_key_exists('name', $state)) {
                throw new \BgaSystemException('State has no name');
            }
            $stateName = $state['name'];
            $args = [];
            $publicStateId = $this->getCurrentGameStateId();
            $publicState = null;
            if (array_key_exists($publicStateId, $states)) {
                $publicState = $states[$publicStateId];
            }
            $allPrivateFunc = null;
            if ($publicState !== null && \array_key_exists(\BX\PrivateState\KEY_ARGS_ALL_PRIVATE_STATE, $publicState)) {
                $allPrivateFunc = $publicState[\BX\PrivateState\KEY_ARGS_ALL_PRIVATE_STATE];
            }
            if ($allPrivateFunc !== null) {
                $args = array_merge($args, self::$game->$allPrivateFunc($this->playerId));
            }
            if (\array_key_exists('args', $state)) {
                $argFunction = $state['args'];
                $args = array_merge($args, self::$game->$argFunction($this->playerId));
            }
            $args['undoLevel'] = \BX\Action\ActionCommandMgr::count($this->playerId);
            $this->notifyCurrentPlayer(NTF_CHANGE_PRIVATE_STATE, '', [
                'stateId' => $stateId,
                'stateName' => $stateName,
                'stateArgs' => $args,
            ]);
        }
    }
}

class ActionCommandNotifierPrivate extends BaseActionCommandNotifier
{
    public function notify(string $notifType, string $notifLog, array $notifArgs)
    {
        $this->notifyNoMessage($notifType, $notifArgs);
    }

    public function notifyForceMessage(string $notifType, string $notifLog, array $notifArgs)
    {
        $this->notifyCurrentPlayer($notifType, $notifLog, $notifArgs);
    }

    public function notifyNoMessage(string $notifType, array $notifArgs)
    {
        $this->notifyCurrentPlayer($notifType, '', $notifArgs);
    }

    public function notifyAllPlayersEmpty()
    {
        $this->notifyAllPlayers(NTF_MESSAGE, '', []);
    }

    public function onNotifierEnd()
    {
        parent::onNotifierEnd();
        if ($this->hasPrivateStateChanged()) {
            $this->loopState();
        }
    }
}

class ActionCommandNotifierNone extends BaseActionCommandNotifier
{
    public function notify(string $notifType, string $notifLog, array $notifArgs)
    {
    }

    public function notifyNoMessage(string $notifType, array $notifArgs)
    {
    }

    public function notifyForceMessage(string $notifType, string $notifLog, array $notifArgs)
    {
    }

    public function notifyAllPlayersEmpty()
    {
    }
}

class ActionCommandNotifierUndo extends BaseActionCommandNotifier
{
    public function notify(string $notifType, string $notifLog, array $notifArgs)
    {
        $this->notifyNoMessage($notifType, $notifArgs);
    }

    public function notifyNoMessage(string $notifType, array $notifArgs)
    {
        $this->notifyCurrentPlayer($notifType, '', $notifArgs);
    }

    public function notifyForceMessage(string $notifType, string $notifLog, array $notifArgs)
    {
        $this->notifyCurrentPlayer($notifType, $notifLog, $notifArgs);
    }

    public function notifyAllPlayersEmpty()
    {
        $this->notifyAllPlayers(NTF_MESSAGE, '', []);
    }

    public function onNotifierEnd()
    {
        parent::onNotifierEnd();
        if ($this->hasPrivateStateChanged()) {
            $this->loopState();
        }
    }
}

class ActionCommandNotifierPublic extends BaseActionCommandNotifier
{
    public function notify(string $notifType, string $notifLog, array $notifArgs)
    {
        $this->notifyAllPlayers($notifType, $notifLog, $notifArgs);
    }

    public function notifyNoMessage(string $notifType, array $notifArgs)
    {
        $this->notifyAllPlayers($notifType, '', $notifArgs);
    }

    public function notifyForceMessage(string $notifType, string $notifLog, array $notifArgs)
    {
        $this->notifyAllPlayers($notifType, $notifLog, $notifArgs);
    }

    public function notifyAllPlayersEmpty()
    {
        $this->notifyAllPlayers(NTF_MESSAGE, '', []);
    }

    public function onNotifierEnd()
    {
        parent::onNotifierEnd();
        if ($this->hasPrivateStateChanged()) {
            $this->loopState();
        }
    }
}

class ActionCommandCreator
{
    private $playerId;
    private $notifier;
    private $group;

    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
        $this->notifier = new ActionCommandNotifierPrivate($playerId);
        $this->group = new GroupActionCommand($playerId);
    }

    public function add(BaseActionCommand $actionCommand)
    {
        $this->group->add($actionCommand);
        ActionCommandMgr::applyOne($actionCommand, $this->notifier);
    }

    public function save()
    {
        ActionCommandMgr::saveOne($this->group, $this->notifier);
    }
}

class ActionCommandMgr
{
    private static $instance;
    private $db;

    private function __construct()
    {
        $this->db = \BX\DB\RowMgrRegister::newMgr('action_command', ActionCommandRow::class);
    }

    private static function get()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function applyAndSaveOne(BaseActionCommand $actionCommand)
    {
        $notifier = new ActionCommandNotifierPrivate($actionCommand->getPlayerId());
        self::applyOne($actionCommand, $notifier);
        self::saveOne($actionCommand, $notifier);
    }

    public static function applyOne(BaseActionCommand $actionCommand, ActionCommandNotifierPrivate $notifier)
    {
        $actionCommand->do($notifier);
    }

    public static function saveOne(BaseActionCommand $actionCommand, ActionCommandNotifierPrivate $notifier)
    {
        $hasUndoRow = \BX\Action\ActionCommandMgr::getMostRecentRowWithActionClassId($actionCommand->getPlayerId(), \BX\Action\ReevaluateHasUndoneActionCommand::class);
        if ($hasUndoRow !== null) {
            self::get()->db->deleteRow($hasUndoRow);
        }
        $row = self::get()->db->newRow();
        $row->setAction($actionCommand);
        self::get()->db->insertRow($row);
        $notifier->onNotifierEnd();
    }

    public static function apply(int $playerId)
    {
        self::clear();
        $notifier = new ActionCommandNotifierNone($playerId);
        foreach (self::get()->db->getAllRows('action_command_id ASC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $actionCommand->do($notifier);
            }
        }
        $notifier->onNotifierEnd();
    }

    public static function commit(int $playerId)
    {
        self::clear();
        $notifier = new ActionCommandNotifierPublic($playerId);
        foreach (self::get()->db->getAllRows('action_command_id ASC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $actionCommand->do($notifier);
                self::get()->db->deleteRow($row);
            }
        }
        foreach (ActionRowMgrRegister::getAllMgr() as $mgr) {
            $mgr->saveModifiedActions();
        }
        \BX\Action\ActionCommandMgr::apply($playerId);
        $notifier->onNotifierEnd();
    }

    public static function undoLast(int $playerId)
    {
        $notifier = new ActionCommandNotifierUndo($playerId);
        $hasUndone = false;
        foreach (self::get()->db->getAllRows('action_command_id DESC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                if (get_class($actionCommand) == ReevaluateHasUndoneActionCommand::class) {
                    self::get()->db->deleteRow($row);
                    continue;
                }
                if ($hasUndone && !$actionCommand->mustAlwaysUndoAction()) {
                    break;
                }
                $hasUndone = true;
                $actionCommand->undo($notifier);
                self::get()->db->deleteRow($row);
            }
        }
        \BX\Action\ActionCommandMgr::apply($playerId);
        $notifier->onNotifierEnd();
    }

    public static function undoAll(int $playerId)
    {
        $notifier = new ActionCommandNotifierUndo($playerId);
        foreach (self::get()->db->getAllRows('action_command_id DESC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $actionCommand->undo($notifier);
                self::get()->db->deleteRow($row);
            }
        }
        \BX\Action\ActionCommandMgr::apply($playerId);
        $notifier->onNotifierEnd();
    }

    public static function undoAllEndGame(int $playerId)
    {
        $notifier = new ActionCommandNotifierUndo($playerId);
        foreach (self::get()->db->getAllRows('action_command_id DESC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $actionCommand->undo($notifier);
                self::get()->db->deleteRow($row);
            }
        }
    }

    public static function undoUntilAndIncludingFirstMatch(int $playerId, callable $matchFunction)
    {
        $notifier = new ActionCommandNotifierUndo($playerId);
        $foundMatch = false;
        foreach (self::get()->db->getAllRows('action_command_id DESC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                if ($foundMatch) {
                    if (!$actionCommand->mustAlwaysUndoAction()) {
                        break;
                    }
                } else {
                    if ($matchFunction($actionCommand)) {
                        $foundMatch = true;
                    }
                }
                $actionCommand->undo($notifier);
                self::get()->db->deleteRow($row);
            }
        }
        \BX\Action\ActionCommandMgr::apply($playerId);
        $notifier->onNotifierEnd();
    }

    public static function clear()
    {
        foreach (ActionRowMgrRegister::getAllMgr() as $mgr) {
            $mgr->clearModifiedActions();
        }
    }

    public static function count(int $playerId)
    {
        return count(array_filter(self::get()->db->getAllRows(), function ($row) use ($playerId) {
            $actionCommand = $row->getAction();
            return ($actionCommand->getPlayerId() == $playerId && get_class($actionCommand) != ReevaluateHasUndoneActionCommand::class);
        }));
    }

    public static function mergeReevaluationArgs(array $args1, array $args2)
    {
        $ret = $args1;
        foreach ($args2 as $key => $arg) {
            if (array_key_exists($key, $ret)) {
                if (!is_array($arg) || !is_array($ret[$key])) {
                    throw new \BgaSystemException("mergeReevaluationArgs cannot merge args that are not arrays for key $key");
                }
                $ret[$key] = array_merge($ret[$key], $arg);
            } else {
                $ret[$key] = $arg;
            }
        }
        return $ret;
    }

    public static function getReevaluationArgs(int $playerId)
    {
        $ret = [];
        self::clear();
        foreach (self::get()->db->getAllRows('action_command_id ASC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $ret = self::mergeReevaluationArgs($ret, $actionCommand->getReevaluationArgs());
            }
        }
        return $ret;
    }

    public static function reevaluate(int $playerId, array $args)
    {
        self::clear();
        $args['isLastAction'] = false;
        $actionCommands = self::get()->db->getAllRows('action_command_id DESC');
        $mustReevaluate = false;
        $lastUndoIdx = null;
        $lastPlayerIdx = null;
        $hasReevaluteDelete = false;
        $isSilent = true;
        foreach ($actionCommands as $idx => $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() != $playerId) {
                continue;
            }
            $lastPlayerIdx = $idx;
            $args['isLastAction'] = ($idx == 0);
            $eval = $actionCommand->reevaluate(null, $args);
            switch ($eval) {
                case REEVALUATE_NO_CHANGE:
                    break;
                case REEVALUATE_UPDATE:
                    $mustReevaluate = true;
                    break;
                case REEVALUATE_DELETE:
                    $isSilent = false;
                    // no break
                case REEVALUATE_DELETE_SILENT:
                    $mustReevaluate = true;
                    $hasReevaluteDelete = true;
                    break;
                case REEVALUATE_UNDO:
                    $isSilent = false;
                    // no break
                case REEVALUATE_UNDO_SILENT:
                    $mustReevaluate = true;
                    $lastUndoIdx = $idx;
                    break;
                default:
                    throw new \BgaSystemException("Reevaluate returned unknown eval: $eval");
            }
        }
        foreach (array_reverse($actionCommands) as $revIdx => $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() != $playerId) {
                continue;
            }
            $mustUndo = $actionCommand->reevaluateReverseMustUndo($hasReevaluteDelete, $lastUndoIdx !== null);
            if ($mustUndo) {
                $isSilent = false;
                $mustReevaluate = true;
                $idx = count($actionCommands) - $revIdx - 1;
                if ($lastUndoIdx === null || $idx > $lastUndoIdx) {
                    $lastUndoIdx = $idx;
                }
            }
        }
        unset($args['isLastAction']);
        if (!$mustReevaluate) {
            return;
        }
        $notifier = new ActionCommandNotifierUndo($playerId);
        if ($lastUndoIdx === null && $hasReevaluteDelete && !$isSilent) {
            $notifier->notifyForceMessage(NTF_MESSAGE, clienttranslate('The active player choices makes you undo part of your prepared turn'), []);
        }
        if ($lastUndoIdx !== null) {
            if (!$isSilent) {
                if ($lastUndoIdx == $lastPlayerIdx) {
                    $notifier->notifyForceMessage(NTF_MESSAGE, clienttranslate('The active player choices makes you undo all your prepared turn'), []);
                } else {
                    $notifier->notifyForceMessage(NTF_MESSAGE, clienttranslate('The active player choices makes you undo part of your prepared turn'), []);
                }
            }
            $lastLoopUndone = false;
            foreach ($actionCommands as $idx => $row) {
                $actionCommand = $row->getAction();
                if ($actionCommand->getPlayerId() != $playerId) {
                    continue;
                }
                if (($idx <= $lastUndoIdx)
                    ||
                    ($lastLoopUndone && $actionCommand->mustAlwaysUndoAction())
                ) {
                    $actionCommand->undo($notifier);
                    self::get()->db->deleteRow($row);
                    unset($actionCommands[$idx]);
                    $lastLoopUndone = true;
                    continue;
                }
                break;
            }
            $actionCommands = array_values($actionCommands);
        }
        foreach (array_reverse($actionCommands) as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() != $playerId) {
                continue;
            }
            $eval = $actionCommand->reevaluate($notifier, $args);
            switch ($eval) {
                case REEVALUATE_NO_CHANGE:
                    $actionCommand->do(new ActionCommandNotifierNone($playerId));
                    break;
                case REEVALUATE_UPDATE:
                    $row->setAction($actionCommand);
                    self::get()->db->updateRow($row);
                    $actionCommand->do(new ActionCommandNotifierNone($playerId));
                    break;
                case REEVALUATE_DELETE:
                    self::get()->db->deleteRow($row);
                    break;
                case REEVALUATE_UNDO:
                    throw new \BgaSystemException("Reevaluate returned undo at notifier time");
                default:
                    throw new \BgaSystemException("Reevaluate returned unknown eval: $eval");
            }
        }
        $notifier->onNotifierEnd();
        if (!$isSilent) {
            self::applyAndSaveOne(new ReevaluateHasUndoneActionCommand($playerId));
        }
        self::clear();
    }

    public static function getMostRecentActionClassId(int $playerId, string $classId)
    {
        foreach (self::get()->db->getAllRows('action_command_id DESC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $matchingAction = $actionCommand->getMostRecentActionClassId($classId);
                if ($matchingAction !== null) {
                    return $matchingAction;
                }
            }
        }
        return null;
    }

    public static function getMostRecentRowWithActionClassId(int $playerId, string $classId)
    {
        foreach (self::get()->db->getAllRows('action_command_id DESC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $matchingAction = $actionCommand->getMostRecentActionClassId($classId);
                if ($matchingAction !== null) {
                    return $row;
                }
            }
        }
        return null;
    }

    public static function getAllActionClassIdInActionOrder(int $playerId, string $classId)
    {
        $matchingActions = [];
        foreach (self::get()->db->getAllRows('action_command_id ASC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                $matchingActions = array_merge($matchingActions, $actionCommand->getAllActionClassIdInActionOrder($classId));
            }
        }
        return $matchingActions;
    }

    public static function removeOldestActionMatching(int $playerId, callable $matchFunction)
    {
        foreach (self::get()->db->getAllRows('action_command_id ASC') as $row) {
            $actionCommand = $row->getAction();
            if ($actionCommand->getPlayerId() == $playerId) {
                if ($matchFunction($actionCommand)) {
                    self::get()->db->deleteRow($row);
                    return;
                }
            }
        }
    }
}
