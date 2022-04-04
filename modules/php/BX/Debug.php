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

namespace BX\Debug;

trait GameStatesTrait
{
    private function debugLoadBugInternal(callable $getSqlFct)
    {
        $studioPlayerId = self::getCurrentPlayerId();
        $playerIdArray = self::getObjectListFromDb("SELECT player_id FROM player", true);

        $sql = [];
        foreach ($playerIdArray as $pId) {
            // All games can keep this SQL
            $sql[] = "UPDATE player SET player_id=$studioPlayerId WHERE player_id=$pId";
            $sql[] = "UPDATE global SET global_value=$studioPlayerId WHERE global_value=$pId";
            $sql[] = "UPDATE stats SET stats_player_id=$studioPlayerId WHERE stats_player_id=$pId";

            // Add game-specific SQL update the tables for your game
            $sql = array_merge($sql, $getSqlFct($studioPlayerId, $pId));

            // This could be improved, it assumes you had sequential studio accounts before loading
            // e.g., quietmint0, quietmint1, quietmint2, etc.
            $studioPlayerId++;
        }
        $this->notifyAllPlayers('message', 'DONE', []);

        foreach ($sql as $q) {
            self::DbQuery($q);
        }
        self::reloadPlayersBasicInfos();
    }

    private function debugGetSqlForActionCommand($studioPlayerId, $replacePlayerId)
    {
        return [
            "UPDATE action_command SET action_json = REPLACE(action_json, '\"playerId\":$replacePlayerId', '\"playerId\":$studioPlayerId') WHERE action_json like '\"playerId\":$replacePlayerId'"
        ];
    }

    private function debugGetSqlForPrivateState($studioPlayerId, $replacePlayerId)
    {
        return [
            "UPDATE private_state SET player_id = $studioPlayerId WHERE player_id = $replacePlayerId"
        ];
    }
}
