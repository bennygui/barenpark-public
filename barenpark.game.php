<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * barenpark implementation : © Guillaume Benny bennygui@gmail.com
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * barenpark.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');
require_once("modules/php/BX/DB.php");
require_once("modules/php/BX/Action.php");
require_once("modules/php/BX/PrivateState.php");
require_once("modules/php/BX/UI.php");
require_once("modules/php/BX/MoveNumber.php");
require_once("modules/php/BP/Globals.php");
require_once("modules/php/BP/Player.php");
require_once("modules/php/BP/Shape.php");
require_once("modules/php/BP/Park.php");
require_once("modules/php/BP/Achievement.php");
require_once("modules/php/BP/Action.php");
require_once("modules/php/BP/States/Main.php");
require_once("modules/php/BP/States/ChooseTileFromPlayerSupply.php");
require_once("modules/php/BP/States/PlaceTileInPark.php");
require_once("modules/php/BP/States/ChooseFromSupplyBoard.php");
require_once("modules/php/BP/States/PassTurnChooseFromSupplyBoard.php");
require_once("modules/php/BP/States/PlacePlayerPark.php");
require_once("modules/php/BP/States/ConfirmTurn.php");
require_once("modules/php/BP/States/TryMode.php");

require_once("modules/php/BP/Debug.php");

\BX\DB\RowMgrRegister::registerClassId(\BX\MoveNumber\DBRowMgr::class);
\BX\Action\ActionRowMgrRegister::registerMgr('player', \BP\PlayerMgr::class);
\BX\Action\ActionRowMgrRegister::registerMgr('private_state', \BX\PrivateState\PrivateStateMgr::class);
\BX\Action\ActionRowMgrRegister::registerMgr('shape', \BP\ShapeMgr::class);
\BX\Action\ActionRowMgrRegister::registerMgr('park', \BP\ParkMgr::class);
\BX\Action\ActionRowMgrRegister::registerMgr('achievement', \BP\AchievementMgr::class);

class barenpark extends Table
{
    use BX\Action\GameActionsTrait;
    use BX\PrivateState\GameActionsTrait;
    use BP\State\Main\GameStatesTrait;
    use BP\State\ChooseTileFromPlayerSupply\GameStatesTrait;
    use BP\State\PlaceTileInPark\GameStatesTrait;
    use BP\State\ChooseFromSupplyBoard\GameStatesTrait;
    use BP\State\PassTurnChooseFromSupplyBoard\GameStatesTrait;
    use BP\State\PlacePlayerPark\GameStatesTrait;
    use BP\State\ConfirmTurn\GameStatesTrait;
    use BP\State\TryMode\GameStatesTrait;

    use BP\Debug\GameStatesTrait;

    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        \BX\Action\BaseActionCommandNotifier::setGame($this);

        self::initGameStateLabels([
            GAME_OPTION_ACHIEVEMENT => GAME_OPTION_ACHIEVEMENT_ID,
            GAME_OPTION_VARIANT_PIT => GAME_OPTION_VARIANT_PIT_ID,
            GAME_OPTION_HIDE_SCORE => GAME_OPTION_HIDE_SCORE_ID,
        ]);
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "barenpark";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        $gameinfos = self::getGameinfos();

        \BX\Action\ActionRowMgrRegister::getMgr('player')->setup($players, $gameinfos['player_colors']);

        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        $gameUsesAchievements = ($this->getGameStateValue(GAME_OPTION_ACHIEVEMENT) == GAME_OPTION_ACHIEVEMENT_VALUE_ON);

        $playersInfos = $this->loadPlayersBasicInfos();
        $playerIdArray = array_keys($playersInfos);
        usort($playerIdArray, function ($p1, $p2) use (&$playersInfos) {
            return ($playersInfos[$p1]['player_no'] <=> $playersInfos[$p2]['player_no']);
        });
        \BX\Action\ActionRowMgrRegister::getMgr('private_state')->setup($playerIdArray);
        \BX\Action\ActionRowMgrRegister::getMgr('shape')->setup($playerIdArray);
        \BX\Action\ActionRowMgrRegister::getMgr('park')->setup($playerIdArray);
        \BX\Action\ActionRowMgrRegister::getMgr('achievement')->setup($gameUsesAchievements, $playerIdArray);

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = [];

        $playerId = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
        \BX\Action\ActionCommandMgr::apply($playerId);

        $playerMgr = \BX\Action\ActionRowMgrRegister::getMgr('player');
        $shapeMgr = \BX\Action\ActionRowMgrRegister::getMgr('shape');
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        $achievementMgr = \BX\Action\ActionRowMgrRegister::getMgr('achievement');
        $result['players'] = \BX\UI\deepCopyToArray($playerMgr->getAllRowsByKey());
        $result['shapes'] = $shapeMgr->getAll();
        $result['parks'] = $parkMgr->getAllVisible();
        $result['achievements'] = $achievementMgr->getAll();
        $result['supplyAchievementCount'] = $achievementMgr->getSupplyPilesCount();
        $result['supplyPilesCount'] = $parkMgr->getSupplyPilesCount();
        $result['supplyShapesCount'] = $shapeMgr->getSupplyShapesCount();
        $result['isLastTurn'] = $parkMgr->atLeastOnePlayerParksAreFull(array_keys($this->loadPlayersBasicInfos()));
        $result['savedMoveNumber'] = \BX\MoveNumber\getSavedMoveNumberForManagers('player', 'shape', 'park', 'achievement');
        $result['hideScore'] = (\BX\BGAGlobal\GlobalMgr::getGlobal(GAME_OPTION_HIDE_SCORE_ID) == GAME_OPTION_HIDE_SCORE_VALUE_HIDE);

        return $result;
    }

    public function argMainPlayState($playerId)
    {
        return [];
    }

    public function currentPlayerId()
    {
        return $this->getCurrentPlayerId();
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $parkMgr = \BX\Action\ActionRowMgrRegister::getMgr('park');
        return $parkMgr->getGameProgression(array_keys($this->loadPlayersBasicInfos()));
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */
    function zombieTurn($state, $playerId)
    {
        // STATE_MAIN_PLAY_STATE is the only state possible
        $this->undoAll($playerId);
        $this->gamestate->nextState('nextPlayer');
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    function upgradeTableDb($from_version)
    {
        if ($from_version <= 2204291308) {
            $sql = "ALTER TABLE `shape` MODIFY `park_top_x` smallint(5) NULL;";
            self::applyDbUpgradeToAllDB($sql);
        }

    }
}
