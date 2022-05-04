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

namespace BP\State\ChooseTileFromPlayerSupply;

require_once(__DIR__ . '/../../BX/Action.php');
require_once(__DIR__ . '/../Action.php');

trait GameStatesTrait
{
    public function argChooseTileFromPlayerSupply(int $playerId)
    {
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        return [
            'shapeIds' => $shapeMgr->getPlayerSupplyShapeIds($playerId),
        ];
    }

    public function chooseTileFromPlayerSupply(int $shapeId)
    {
        $playerId = $this->getCurrentPlayerId();
        \BX\Action\ActionCommandMgr::apply($playerId);
        $this->privateStateCheckAction($playerId, 'chooseTileFromPlayerSupply', \BX\PrivateState\PLAYER_ACTIVE_STATUS_ANY);

        $creator = new \BX\Action\ActionCommandCreator($playerId);
        $creator->add(new \BP\ChooseTileFromPlayerSupplyActionCommand($playerId, $shapeId));
        $creator->add(new \BP\NextPrivateStateActionCommand($playerId));
        $creator->save();
    }
}
