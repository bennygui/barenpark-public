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

namespace BX\Player;

require_once('Action.php');
require_once('BGAGlobal.php');

class Player extends \BX\Action\BaseActionRow
{
    /** @dbcol @dbkey @ui(id) */
    public $playerId;
    /** @dbcol @dbdefault @ui(score) */
    public $playerScore;
    /** @dbcol @dbdefault @ui(score_aux) */
    public $playerScoreAux;
    /** @dbcol @ui(player_color) */
    public $playerColor;
    /** @dbcol @ui(player_name) */
    public $playerName;
    /** @dbcol @ui(player_canal) */
    public $playerCanal;
    /** @dbcol @ui(player_avatar) */
    public $playerAvatar;
    /** @dbcol @ui(player_no) */
    public $playerNo;
}

class PlayerMgr extends \BX\Action\BaseActionRowMgr
{
    public function __construct(string $playerClassId = '\BX\Player\Player')
    {
        parent::__construct('player', $playerClassId);
    }

    public function setup(array $setupNewGamePlayers, array $colors)
    {
        foreach ($setupNewGamePlayers as $playerId => $setupPlayer) {
            $p = $this->db->newRow();
            $p->playerId = $playerId;
            $p->playerColor = array_shift($colors);
            $p->playerCanal = $setupPlayer['player_canal'];
            $p->playerName = $setupPlayer['player_name'];
            $p->playerAvatar = $setupPlayer['player_avatar'];
            $this->db->insertRow($p);
        }
    }
    
    public function getAllPlayerIds()
    {
        return array_keys($this->getAllRowsByKey());
    }
}

const NTF_UPDATE_PLAYER_SCORE = 'NTF_UPDATE_PLAYER_SCORE';

class UpdatePlayerScoreActionCommand extends \BX\Action\BaseActionCommand
{
    private $scoreToAdd;
    private $undoPlayerScore;

    public function __construct(int $playerId, ?int $scoreToAdd = null)
    {
        parent::__construct($playerId);
        $this->scoreToAdd = $scoreToAdd;
        $this->undoPlayerScore = null;
    }

    public function do(\BX\Action\BaseActionCommandNotifier $notifier, ?int $scoreToAdd = null, ?string $notifLog = null, $additionalNotifParams = [])
    {
        if ($scoreToAdd === null) {
            $scoreToAdd = $this->scoreToAdd;
        }
        if ($scoreToAdd === null || $scoreToAdd == 0) {
            return;
        }
        $playerMgr = self::getMgr('player');
        $player = $playerMgr->getRowByKey($this->playerId);
        $this->undoPlayerScore = $player->playerScore;
        $player->modifyAction();
        $player->playerScore += $scoreToAdd;
        if ($notifLog === null) {
            if ($scoreToAdd > 0) {
                $notifLog = clienttranslate('${player_name} scores ${scorePositive} point(s)');
            } else {
                $notifLog = clienttranslate('${player_name} loses ${scorePositive} point(s)');
            }
        }
        $notifier->notify(
            NTF_UPDATE_PLAYER_SCORE,
            $notifLog,
            array_merge($additionalNotifParams, [
                'score' => $scoreToAdd,
                'scorePositive' => abs($scoreToAdd),
                'playerScore' => $player->playerScore,
            ])
        );
    }

    public function undo(\BX\Action\BaseActionCommandNotifier $notifier)
    {
        if ($this->undoPlayerScore != null) {
            $notifier->notifyNoMessage(NTF_UPDATE_PLAYER_SCORE, [
                'playerScore' => $this->undoPlayerScore
            ]);
        }
    }
}
