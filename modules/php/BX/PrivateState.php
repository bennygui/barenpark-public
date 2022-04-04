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

namespace BX\PrivateState;

require_once('Action.php');

const NTF_UNDO_PRIVATE_STATE = 'NTF_UNDO_PRIVATE_STATE';

const KEY_ENTER_ACTIVE = 'privateStateEnterActive';
const KEY_ENTER_ACTIVE_FUNCTION = 'privateStateEnterActiveFunction';
const KEY_ENTER_INACTIVE = 'privateStateEnterInactive';
const KEY_ARGS_NO_PRIVATE_STATE = 'argsNoPrivateState';
const KEY_ARGS_ALL_PRIVATE_STATE = 'argsAllPrivateState';

const PLAYER_ACTIVE_STATUS_ACTIVE = 1;
const PLAYER_ACTIVE_STATUS_INACTIVE = 2;
const PLAYER_ACTIVE_STATUS_ANY = 3;

class NextPrivateStateActionCommand extends \BX\Action\BaseActionCommand
{
    protected $transition;
    protected $prevPrivateStateIdInit;
    protected $prevPrivateStateId;

    public function __construct(int $playerId, string $transition = '')
    {
        parent::__construct($playerId);
        $this->transition = $transition;
        $this->prevPrivateStateIdInit = false;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $privateState = self::getMgr('private_state')->getRowByKey($this->playerId);
        if (!$this->prevPrivateStateIdInit) {
            $this->prevPrivateStateIdInit = true;
            $this->prevPrivateStateId = $privateState->stateId;
        }

        $stateId = $notifier->getCurrentGameStateId();
        if ($privateState->stateId !== null) {
            $stateId = $privateState->stateId;
        }
        $states = $notifier->getGameStates();
        if (!\array_key_exists($stateId, $states)) {
            throw new \BgaSystemException("State {$stateId} does not exists");
        }
        $state = $states[$stateId];
        if (!\array_key_exists('name', $state)) {
            throw new \BgaSystemException('State has no name');
        }
        $stateName = $state['name'];
        if (!\array_key_exists('transitions', $state)) {
            throw new \BgaSystemException('State has no transitions');
        }
        if (!\array_key_exists($this->transition, $state['transitions'])) {
            throw new \BgaSystemException("State $stateName has no transition named _{$this->transition}_ (playerId {$this->playerId})");
        }
        $privateState->modifyAction();
        $privateState->stateId = $state['transitions'][$this->transition];
        $notifier->changePrivateState();
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_UNDO_PRIVATE_STATE, ['stateId' => $this->prevPrivateStateId]);
        $privateState = self::getMgr('private_state')->getRowByKey($this->playerId);
        $privateState->modifyAction();
        $privateState->stateId = $this->prevPrivateStateId;
        $notifier->changePrivateState();
    }

    public function getTransition()
    {
        return $this->transition;
    }
}

class JumpPrivateStateActionCommand extends \BX\Action\BaseActionCommand
{
    protected $jumpPrivateStateId;
    protected $prevPrivateStateIdInit;
    protected $prevPrivateStateId;

    public function __construct(int $playerId, int $jumpPrivateStateId)
    {
        parent::__construct($playerId);
        $this->jumpPrivateStateId = $jumpPrivateStateId;
        $this->prevPrivateStateIdInit = false;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $privateState = self::getMgr('private_state')->getRowByKey($this->playerId);
        if (!$this->prevPrivateStateIdInit) {
            $this->prevPrivateStateIdInit = true;
            $this->prevPrivateStateId = $privateState->stateId;
        }

        $privateState->modifyAction();
        $privateState->stateId = $this->jumpPrivateStateId;
        $notifier->changePrivateState();
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        $notifier->notifyNoMessage(NTF_UNDO_PRIVATE_STATE, ['stateId' => $this->prevPrivateStateId]);
        $privateState = self::getMgr('private_state')->getRowByKey($this->playerId);
        $privateState->modifyAction();
        $privateState->stateId = $this->prevPrivateStateId;
        $notifier->changePrivateState();
    }

    public function getJumpPrivateStateId()
    {
        return $this->jumpPrivateStateId;
    }
}

trait GameActionsTrait
{
    public function stPrivateStateEnter()
    {
        \BX\Action\ActionCommandMgr::clear();
        $activePlayerId = $this->getActivePlayerId();
        $state = $this->gamestate->state();
        if (\array_key_exists(KEY_ENTER_ACTIVE_FUNCTION, $state)) {
            $fct = $state[KEY_ENTER_ACTIVE_FUNCTION];
            $privateStateId = $this->$fct($activePlayerId);
            \BX\Action\ActionRowMgrRegister::getMgr('private_state')->activateStateIfEmpty($activePlayerId, $privateStateId);
        } else if (\array_key_exists(KEY_ENTER_ACTIVE, $state)) {
            $privateStateId = $state[KEY_ENTER_ACTIVE];
            \BX\Action\ActionRowMgrRegister::getMgr('private_state')->activateStateIfEmpty($activePlayerId, $privateStateId);
        }
        if (\array_key_exists(KEY_ENTER_INACTIVE, $state)) {
            $privateStateId = $state[KEY_ENTER_INACTIVE];
            foreach (array_keys($this->loadPlayersBasicInfos()) as $playerId) {
                if ($playerId != $activePlayerId) {
                    \BX\Action\ActionRowMgrRegister::getMgr('private_state')->activateStateIfEmpty($playerId, $privateStateId);
                }
            }
        }
        $this->gamestate->nextState();
    }

    public function argPrivateStateArgs()
    {
        $publicStateId = $this->gamestate->state_id();
        $publicStates = $this->gamestate->states;
        $publicState = null;
        if (array_key_exists($publicStateId, $publicStates)) {
            $publicState = $publicStates[$publicStateId];
        }
        $allPrivateFunc = null;
        if ($publicState !== null && \array_key_exists(KEY_ARGS_ALL_PRIVATE_STATE, $publicState)) {
            $allPrivateFunc = $publicState[KEY_ARGS_ALL_PRIVATE_STATE];
        }
        $ret = ['_private' => []];
        foreach (array_keys($this->loadPlayersBasicInfos()) as $playerId) {
            $ret['_private'][$playerId] = [];
            \BX\Action\ActionCommandMgr::apply($playerId);
            $privateState = \BX\Action\ActionRowMgrRegister::getMgr('private_state')->getRowByKey($playerId);
            if ($privateState->stateId === null) {
                $state = $this->gamestate->state();
                if (array_key_exists(KEY_ARGS_NO_PRIVATE_STATE, $state)) {
                    $func = $state[KEY_ARGS_NO_PRIVATE_STATE];
                    $ret['_private'][$playerId] = $this->$func($playerId);
                }
            } else {
                if (!array_key_exists($privateState->stateId, $this->gamestate->states)) {
                    throw new \BgaSystemException("Invalid private state: {$privateState->stateId}");
                }
                if ($allPrivateFunc !== null) {
                    $ret['_private'][$playerId] = array_merge($ret['_private'][$playerId], $this->$allPrivateFunc($playerId));
                }
                if (array_key_exists('args', $this->gamestate->states[$privateState->stateId])) {
                    $func = $this->gamestate->states[$privateState->stateId]['args'];
                    $ret['_private'][$playerId] = array_merge($ret['_private'][$playerId], $this->$func($playerId));
                }
                $ret['_private'][$playerId]['privateStateId'] = $privateState->stateId;
            }
            $ret['_private'][$playerId]['undoLevel'] = \BX\Action\ActionCommandMgr::count($playerId);
        }
        return $ret;
    }

    public function privateStateCheckAction($playerId, $actionName, $playerActiveStatus)
    {
        $privateState = \BX\Action\ActionRowMgrRegister::getMgr('private_state')->getRowByKey($playerId);
        $stateId = $privateState->stateId;
        if ($stateId === null) {
            $stateId = $this->gamestate->state_id();
        }
        $states = $this->gamestate->states;
        if (!\array_key_exists($stateId, $states)) {
            throw new \BgaSystemException("State {$stateId} does not exists");
        }
        $state = $states[$stateId];
        if (
            !\array_key_exists('possibleactions', $state)
            || array_search($actionName, $state['possibleactions']) === false
        ) {
            throw new \BgaUserException(clienttranslate('This action is not possible at this time'));
        }
        switch ($playerActiveStatus) {
            case PLAYER_ACTIVE_STATUS_ACTIVE:
                if (!$this->gamestate->isPlayerActive($playerId)) {
                    throw new \BgaUserException(clienttranslate("This action is not possible because it's not your turn"));
                }
                break;
            case PLAYER_ACTIVE_STATUS_INACTIVE:
                if ($this->gamestate->isPlayerActive($playerId)) {
                    throw new \BgaUserException(clienttranslate("This action is not possible when it's your turn"));
                }
                break;
            case PLAYER_ACTIVE_STATUS_ANY:
                break;
            default:
                throw new \BgaSystemException("playerActiveStatus ${$playerActiveStatus} invalid");
        }
    }
}

class PrivateState extends \BX\Action\BaseActionRow
{
    /** @dbcol @dbkey */
    public $playerId;
    /** @dbcol */
    public $stateId;
}

class PrivateStateMgr extends \BX\Action\BaseActionRowMgr
{
    public function __construct()
    {
        parent::__construct('private_state', PrivateState::class);
    }

    public function setup(array $playerIdArray)
    {
        foreach ($playerIdArray as $playerId) {
            $p = $this->db->newRow();
            $p->playerId = $playerId;
            $this->db->insertRow($p);
        }
    }

    public function currentStateId(int $playerId)
    {
        $row = $this->getRowByKey($playerId);
        return $row->stateId;
    }

    public function activateStateIfEmpty(int $playerId, int $privateStateId)
    {
        $row = $this->db->getRowByKey($playerId);
        if ($row->stateId !== null) {
            return;
        }
        $row->stateId = $privateStateId;
        $this->db->updateRow($row);
    }

    public function clearPlayerState(int $playerId)
    {
        $row = $this->db->getRowByKey($playerId);
        $row->stateId = null;
        $this->db->updateRow($row);
    }

    public function clearPlayerStateIfEqual(int $playerId, int $privateStateId)
    {
        $row = $this->db->getRowByKey($playerId);
        if ($row->stateId !== null && $row->stateId != $privateStateId) {
            return;
        }
        $row->stateId = null;
        $this->db->updateRow($row);
    }
}
