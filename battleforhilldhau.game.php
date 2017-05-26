<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * BattleForHillDhau implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * battleforhilldhau.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */

require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');

$prevAutoloads = spl_autoload_functions();
require_once(__DIR__ . '/vendor/autoload.php');
foreach ($prevAutoloads as $prevAutoload) {
    spl_autoload_register($prevAutoload, true, false);
}

use Functional as F;
use TheBattleForHill218\Cards\PlayerCard;
use TheBattleForHill218\Hill218Setup;
use TheBattleForHill218\SQLHelper;


class BattleForHillDhau extends Table
{
	public function __construct()
	{
        parent::__construct();

        self::initGameStateLabels(array());
	}

    /**
     * @return string
     */
    protected function getGameName( )
    {
        return "battleforhilldhau";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        $this->setupPlayers($players);
        // TODO: Setup stats here
        $this->setupBattlefield();
        foreach (array_keys($players) as $playerId) {
            $this->setupPlayerCards($playerId);
        }
        $this->gamestate->setAllPlayersMultiactive();
    }

    /**
     * @param array $players
     */
    private function setupPlayers(array $players)
    {
        if (count($players) !== 2) {
            throw new InvalidArgumentException('Can only work with 2 players');
        }

        $infos = self::getGameInfosForGame($this->getGameName());
        $colors = $infos['player_colors'];
        $directions = array('-1', '1');

        $i = 0;
        foreach ($players as $player_id => $player)
        {
            self::DbQuery(SQLHelper::insert('player', array(
                'player_id' => $player_id,
                'player_color' => $colors[$i],
                'player_canal' => $player['player_canal'],
                'player_name' => $player['player_name'],
                'player_avatar' => $player['player_avatar'],
                'base_side' => $directions[$i]
            )));
            $i++;
        }

        self::reattributeColorsBasedOnPreferences($players, $colors);
        self::reloadPlayersBasicInfos();
    }

    /**
     *
     */
    private function setupBattlefield()
    {
        self::DBQuery(SQLHelper::insert('battlefield_card', array(
            'type' => 'hill',
            'player_id' => null,
            'x' => 0,
            'y' => 0
        )));
    }

    /**
     * @param int $playerId
     */
    private function setupPlayerCards($playerId)
    {
        list($hand, $deck) = Hill218Setup::getPlayerStartingCards($playerId);
        $this->saveCards('hand_card', $hand);
        $this->saveCards('deck_card', $deck);
    }

    /**
     * @param string $table
     * @param PlayerCard[] $cards
     */
    private function saveCards($table, array $cards)
    {
        self::DBQuery(
            SQLHelper::insertAll(
                $table,
                F\map(
                    $cards,
                    function(PlayerCard $card, $i) {
                        return array(
                            'player_id' => $card->getPlayerId(),
                            'type' => $card->getTypeKey(),
                            'order' => $i
                        );
                    }
                )
            )
        );
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
        // Players
        $myPlayerId = (int) self::getCurrentPlayerId();
        $players = self::getCollectionFromDb('SELECT player_id id, player_score score, player_color color FROM player');
        $opponentPlayerId = F\first(
            F\map(array_keys($players), function($id) { return (int) $id; }),
            function($playerId) use ($myPlayerId) { return $playerId !== $myPlayerId; }
        );

        // Hands
        $handCardsByPlayerId = F\group(
            self::getObjectListFromDB('SELECT id, player_id, type FROM hand_card ORDER BY `order` ASC'),
            function(array $card) { return (int) $card['player_id']; }
        );
        $myHand = $handCardsByPlayerId[$myPlayerId];
        $opponentHand = $handCardsByPlayerId[$opponentPlayerId];
        $opponentNumAirStrikes = count(
            F\filter(
                F\pluck($opponentHand, 'type'),
                function($type) { return $type === 'air-strike'; }
            )
        );

        // Decks
        $rawDeckCounts = self::getObjectListFromDB('SELECT COUNT(id) as size, player_id FROM deck_card GROUP BY player_id');
        $deckSizes = F\map(
            F\map(
                F\group($rawDeckCounts, function(array $count) { return (int) $count['player_id']; }),
                function(array $counts) { return F\head($counts); }
            ),
            function(array $count) { return (int) $count['size']; }
        );

        // Battlefield
        $battlefield = F\map(
            self::getObjectListFromDB('SELECT player_id, type, x, y FROM battlefield_card'),
            function(array $card) {
                return array(
                    'playerId' => (int) $card['player_id'],
                    'type' => $card['type'],
                    'x' => (int) $card['x'],
                    'y' => (int) $card['y']
                );
            }
        );

        return array(
            'players' => $players,
            'me' => array(
                'color' => $players[$myPlayerId]['color'],
                'hand' => F\map(
                    array_values($myHand),
                    function(array $card) { return array('id' => (int) $card['id'], 'type' => $card['type']); }
                ),
                'deckSize' => $deckSizes[$myPlayerId]
            ),
            'opponent' => array(
                'color' => $players[$opponentPlayerId]['color'],
                'numAirStrikes' => $opponentNumAirStrikes,
                'handSize' => count($opponentHand),
                'deckSize' => $deckSizes[$opponentPlayerId]
            ),
            'battlefield' => $battlefield
        );
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
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in battleforhilldhau.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
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
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
