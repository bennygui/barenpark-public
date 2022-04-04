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

namespace BP\State\PassTurnChooseFromSupplyBoard;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function argPassTurnChooseFromSupplyBoard(int $playerId)
    {
        $choosableShapeIds = \BX\Action\ActionRowMgrRegister::getMgr('shape')->getTopChoosableShapeIds([\BP\ShapeGreenBase::class]);
        return [
            'choosableShapeIds' => $choosableShapeIds,
        ];
    }

    public function chooseShapeFromSupplyBoardAndPass(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'chooseShapeFromSupplyBoardAndPass', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $newChooseAction = new \BP\ChooseShapeFromSupplyBoardActionCommand($playerId, $shapeId);
        $creator->add($newChooseAction);
        $creator->add(new \BX\PrivateState\NextPrivateStateActionCommand($playerId, 'confirmTurn'));
        $creator->save();
    }
}
