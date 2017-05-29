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

        $this->initGameStateLabels(array());
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
        $this->activeNextPlayer();
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

        $this->reattributeColorsBasedOnPreferences($players, $colors);
        $this->reloadPlayersBasicInfos();
    }

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

        // Decks
        $rawDeckCounts = self::getObjectListFromDB('SELECT COUNT(id) as size, player_id FROM deck_card GROUP BY player_id');
        $deckSizes = F\map(
            F\map(
                F\group($rawDeckCounts, function(array $count) { return (int) $count['player_id']; }),
                function(array $counts) { return F\head($counts); }
            ),
            function(array $count) { return (int) $count['size']; }
        );

        return array(
            'players' => $players,
            'me' => $this->getMeDatas(
                $players[$myPlayerId],
                $handCardsByPlayerId[$myPlayerId],
                $deckSizes[$myPlayerId]
            ),
            'opponent' => $this->getOpponentDatas(
                $players[$opponentPlayerId],
                $handCardsByPlayerId[$opponentPlayerId],
                $deckSizes[$opponentPlayerId]
            ),
            'battlefield' => $this->getBattlefieldDatas()
        );
    }

    /**
     * @param array $player
     * @param array $hand
     * @param int $deckSize
     * @return array
     */
    private function getMeDatas(array $player, array $hand, $deckSize)
    {
        return array(
            'color' => $player['color'],
            'hand' => F\map(
                array_values($hand),
                function(array $card) { return array('id' => (int) $card['id'], 'type' => $card['type']); }
            ),
            'deckSize' => $deckSize
        );
    }

    /**
     * @param array $player
     * @param array $hand
     * @param int $deckSize
     * @return array
     */
    private function getOpponentDatas(array $player, array $hand, $deckSize)
    {
        $numAirStrikes = count(
            F\filter(
                F\pluck($hand, 'type'),
                function($type) { return $type === 'air-strike'; }
            )
        );
        return array(
            'color' => $player['color'],
            'numAirStrikes' => $numAirStrikes,
            'handSize' => count($hand),
            'deckSize' => $deckSize
        );
    }

    /**
     * @return array
     */
    private function getBattlefieldDatas()
    {
        return F\map(
            self::getObjectListFromDB('SELECT player_id AS playerId, type, x, y FROM battlefield_card'),
            function(array $card) {
                return array(
                    'playerId' => $card['playerId'] !== null ? (int) $card['playerId'] : null,
                    'type' => $card['type'],
                    'x' => (int) $card['x'],
                    'y' => (int) $card['y']
                );
            }
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
    public function returnToDeck(array $cardIds)
    {
        $this->checkAction('returnToDeck');

        $playerId = $this->getCurrentPlayerId();

        // Remove cards from hand
        $idList = join(', ', $cardIds);
        $returnCards = self::getObjectListFromDB("SELECT id, type FROM hand_card WHERE player_id = {$playerId} AND id IN ({$idList})");
        $returnIds = F\pluck($returnCards, 'id');
        $returnIdsList = join(', ', $returnIds);
        self::DBQuery("DELETE FROM hand_card WHERE id IN ({$returnIdsList})");
        $numCards = count($returnIds);

        // Remove replacements from the deck
        $replacements = self::getObjectListFromDB("SELECT id, type FROM deck_card WHERE player_id = {$playerId} ORDER BY `order` DESC LIMIT {$numCards}");
        $replacementIds = F\pluck($replacements, 'id');
        $replacementIdsList = join(', ', $replacementIds);
        self::DBQuery("DELETE FROM deck_card WHERE id IN ({$replacementIdsList})");

        // Put removed cards into deck
        self::DBQuery("UPDATE deck_card SET `order` = `order` + {$numCards} WHERE player_id = {$playerId} ORDER BY `order` DESC");
        self::DBQuery(SQLHelper::insertAll(
            'deck_card',
            F\map($returnCards, function(array $handCard, $i) use ($playerId) {
                return array(
                    'type' => $handCard['type'],
                    'order' => $i,
                    'player_id' => $playerId
                );
            })
        ));

        // Put replacement cards into hand
        // Leaves holes in `order`, but is more efficient this way
        $maxOrder = (int) self::getUniqueValueFromDB("SELECT MAX(`order`) FROM hand_card WHERE player_id = {$playerId}");
        self::DBQuery(SQLHelper::insertAll(
            'hand_card',
            F\map($replacements, function(array $replacement, $i) use ($playerId, $maxOrder) {
                return array(
                    'type' => $replacement['type'],
                    'order' => $maxOrder + $i + 1,
                    'player_id' => $playerId
                );
            })
        ));

        $opponentPlayerId = (int) self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_id != {$playerId}");
        $this->notifyPlayer(
            $playerId,
            'returnedToDeck',
            clienttranslate("You returned {$numCards} to your deck"),
            array('oldIds' => $cardIds, 'replacements' => $replacements)
        );
        $this->notifyPlayer(
            $opponentPlayerId,
            'opponentReturnedToDeck',
            clienttranslate("Opponent returned {$numCards} to their deck"),
            array('numCards' => $numCards)
        );
        // TODO: Sub in name for opponent

        $this->gamestate->setPlayerNonMultiactive($playerId, 'allReturned');
    }

    
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
