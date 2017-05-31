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

require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

$prevAutoloads = spl_autoload_functions();
require_once(__DIR__ . '/vendor/autoload.php');
foreach ($prevAutoloads as $prevAutoload) {
    spl_autoload_register($prevAutoload, true, false);
}

use Functional as F;
use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\CardPlacement;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Cards\BattlefieldCard;
use TheBattleForHill218\Cards\CardFactory;
use TheBattleForHill218\Cards\HillCard;
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

        $i = 0;
        foreach ($players as $player_id => $player) {
            $color = $colors[$i];
            self::DbQuery(SQLHelper::insert('player', array(
                'player_id' => $player_id,
                'player_color' => $color,
                'player_canal' => $player['player_canal'],
                'player_name' => $player['player_name'],
                'player_avatar' => $player['player_avatar'],
                'base_side' => $color === '04237b' ? '1' : '-1'
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
        $this->saveCards('playable_card', $hand);
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

    /**
     * Gather all informations about current game situation (visible by the current player).
     * The method is called each time the game interface is displayed to a player, ie:
     *  - when the game starts
     *  - when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        // Hands
        $allHandCards = F\group(
            self::getObjectListFromDB('SELECT id, player_id, type FROM playable_card ORDER BY `order` ASC'),
            function(array $card) { return (int) $card['player_id']; }
        );

        // Decks
        $allDeckSizes = F\map(
            F\map(
                F\group(
                    self::getObjectListFromDB('SELECT COUNT(id) as size, player_id FROM deck_card GROUP BY player_id'),
                    function(array $count) { return (int) $count['player_id']; }
                ),
                function(array $counts) { return F\head($counts); }
            ),
            function(array $count) { return (int) $count['size']; }
        );

        // Players
        $currentPlayerId = (int) self::getCurrentPlayerId();
        $_this = $this;
        $players = F\map(
            self::getCollectionFromDb('SELECT player_id id, player_score score, player_color color FROM player'),
            function(array $player) use ($_this, $allHandCards, $allDeckSizes, $currentPlayerId) {
                $player = array_merge($player, array('id' => (int) $player['id'], 'score' => (int) $player['score']));
                $handCards = array_values($allHandCards[$player['id']]);
                $deckSize = $allDeckSizes[$player['id']];

                if ($player['id'] === $currentPlayerId) {
                    return array_merge($player, $_this->getMyPlayerData($deckSize, $handCards));
                }

                return array_merge($player, $_this->getPublicPlayerData($deckSize, $handCards));
            }
        );

        return array(
            'players' => $players,
            'battlefield' => $this->getBattlefieldDatas()
        );
    }

    /**
     * @param int $deckSize
     * @param array $cards
     * @return array
     */
    public function getMyPlayerData($deckSize, array $cards)
    {
        return array_merge(
            $this->getPublicPlayerData($deckSize, $cards),
            array(
                'cards' => F\map(
                    $cards,
                    function(array $card) { return array('id' => (int) $card['id'], 'type' => $card['type']); }
                )
            )
        );
    }

    /**
     * @param int $deckSize
     * @param array $cards
     * @return array
     */
    public function getPublicPlayerData($deckSize, array $cards)
    {
        $numAirStrikes = count(
            F\filter(
                F\pluck($cards, 'type'),
                function($type) { return $type === 'air-strike'; }
            )
        );
        return array(
            'numAirStrikes' => $numAirStrikes,
            'numCards' => count($cards),
            'deckSize' => $deckSize
        );
    }

    /**
     * @return array
     */
    private function getBattlefieldDatas()
    {
        $players = self::loadPlayersBasicInfos();
        return F\map(
            self::getObjectListFromDB('SELECT player_id AS playerId, type, x, y FROM battlefield_card'),
            function(array $card) use ($players) {
                $playerId = $card['playerId'];
                $playerColor = null;
                if ($playerId !== null) {
                    $playerId = (int) $playerId;
                    $playerColor = $players[$playerId]['player_color'];
                }
                return array(
                    'playerId' => $playerId,
                    'playerColor' => $playerColor,
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
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////
    /**
     * @return Battlefield
     */
    private function loadBattlefield()
    {
        $downwardPlayerId = self::getUniqueValueFromDB('SELECT player_id FROM player WHERE base_side = "-1"');
        if ($downwardPlayerId === null) {
            throw new RuntimeException('Downwards player not found');
        }
        return new Battlefield(
            (int) $downwardPlayerId,
            F\map(
                self::getObjectListFromDB('SELECT * FROM battlefield_card'),
                array('BattleForHillDhau', 'parseCardPlacement')
            )
        );
    }

    /**
     * @param array $raw
     * @return PlayerCard
     */
    public static function parsePlayableCard(array $raw)
    {
        return CardFactory::createFromTypeKey($raw['type'], (int) $raw['player_id']);
    }

    /**
     * @param array $raw
     * @return CardPlacement
     */
    public static function parseCardPlacement(array $raw)
    {
        $position = new Position((int) $raw['x'], (int) $raw['y']);
        switch ($raw['type']) {
            case 'hill':
                return new CardPlacement(new HillCard(), $position);
            default:
                return new CardPlacement(
                    CardFactory::createBattlefieldFromTypeKey($raw['type'], (int) $raw['player_id']),
                    $position
                );
        }
    }

    /**
     * @param string $sql
     * @return array
     */
    private function getIntCollectionFromDB($sql)
    {
        return F\map($this->getCollectionFromDB($sql), function($value) { return (int) $value; });
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////
    /**
     * @param array $cardIds
     * @throws BgaSystemException
     */
    public function returnToDeck(array $cardIds)
    {
        $this->checkAction('returnToDeck');

        $numCards = Hill218Setup::NUMBER_OF_INITIAL_CARDS_TO_RETURN;
        if (count($cardIds) !== $numCards) {
            throw new BgaSystemException(sprintf('Exactly %d cards required', $numCards));
        }

        $playerId = $this->getCurrentPlayerId();

        // Remove cards from hand
        $idList = join(', ', $cardIds);
        $returnCards = self::getObjectListFromDB("SELECT id, type FROM playable_card WHERE player_id = {$playerId} AND id IN ({$idList})");
        if (count($returnCards) !== Hill218Setup::NUMBER_OF_INITIAL_CARDS_TO_RETURN) {
            throw new BgaSystemException("Couldn\'t find given card ids {$idList}");
        }
        self::DBQuery("DELETE FROM playable_card WHERE id IN ({$idList})");

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

        $this->notifyPlayer(
            'all',
            'returnedToDeck',
            clienttranslate('${playerName} returned ${numCards} cards to their deck'),
            array(
                'numCards' => $numCards,
                'playerName' => $this->getCurrentPlayerName(),
                'playerColor' => $this->getCurrentPlayerColor(),
                'playerId' => $playerId
            )
        );

        $this->gamestate->setPlayerNonMultiactive($playerId, 'allReturned');
    }

    /**
     * @param int $cardId
     * @param int $x
     * @param int $y
     * @throws BgaSystemException
     */
    public function playCard($cardId, $x, $y)
    {
        $this->checkAction('playCard');

        $playerId = (int) $this->getCurrentPlayerId();
        $position = new Position($x, $y);
        $card = self::parsePlayableCard(
            self::getNonEmptyObjectFromDB("SELECT * FROM playable_card WHERE id = {$cardId} AND player_id = {$playerId}")
        );

        // Check if valid position
        $battlefield = $this->loadBattlefield();
        $possiblePositions = $card->getPossiblePlacements($battlefield);
        if (!F\contains($possiblePositions, $position, false)) {
            throw new BgaSystemException('That position isn\'t allowed');
        }

        // Move from playable to battlefield
        self::DbQuery("DELETE FROM playable_card WHERE id = {$cardId}");
        self::DbQuery(
            SQLHelper::insert(
                'battlefield_card',
                array(
                    'type' => $card->getTypeKey(),
                    'player_id' => $playerId,
                    'x' => $position->getX(),
                    'y' => $position->getY()
                )
            )
        );

        // Notifications
        $players = self::loadPlayersBasicInfos();
        $player = $players[$playerId];
        $this->notifyAllPlayers(
            'placedCard',
            '${playerName} placed a ${typeName} card at ${x},${y}',
            array(
                'playerName' => $player['player_name'],
                'playerColor' => $player['player_color'],
                'typeName' => $card->getTypeName(),
                'typeKey' => $card->getTypeKey(),
                'x' => $x,
                'y' => $y
            )
        );
        $this->notifyPlayer(
            $playerId,
            'iPlacedCard',
            '',
            array(
                'cardId' => $cardId,
                'x' => $x,
                'y' => $y
            )
        );

        $this->gamestate->nextState('noAttackAvailable');
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////
    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */
    public function argPlayCard()
    {
        $currentPlayerId = (int) $this->getCurrentPlayerId();
        if ((int) $this->getActivePlayerId() !== $currentPlayerId) {
            return array();
        }

        $playableCards = self::getObjectListFromDB("SELECT * from playable_card WHERE player_id = {$currentPlayerId}");
        $battlefield = $this->loadBattlefield();
        return array_combine(
            F\pluck($playableCards, 'id'),
            F\map(
                F\map($playableCards, array('BattleForHillDhau', 'parsePlayableCard')),
                function(PlayerCard $card) use ($battlefield) {
                    return F\map(
                        $card->getPossiblePlacements($battlefield),
                        function(Position $position) {
                            return array('x' => $position->getX(), 'y' => $position->getY());
                        }
                    );
                }
            )
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    public function stDrawCards()
    {
        $playerId = (int) $this->getActivePlayerId();
        $numCards = 1; // TODO: Change based on turn

        $drawn = self::getObjectListFromDB("SELECT id, type FROM deck_card WHERE player_id = {$playerId} ORDER BY `order` DESC LIMIT {$numCards}");
        if (count($drawn) === 0) {
            $this->gamestate->nextState('cardsDrawn');
            return;
        }

        // Remove drawn from the deck
        $drawnIds = F\pluck($drawn, 'id');
        $drawnIdsList = join(', ', $drawnIds);
        self::DBQuery("DELETE FROM deck_card WHERE id IN ({$drawnIdsList})");

        // Put drawn cards into hand
        // Leaves holes in `order`, but is more efficient this way and doesn't matter
        $maxOrder = (int) self::getUniqueValueFromDB("SELECT MAX(`order`) FROM playable_card WHERE player_id = {$playerId}");
        self::DBQuery(SQLHelper::insertAll(
            'playable_card',
            F\map($drawn, function(array $card, $i) use ($playerId, $maxOrder) {
                return array(
                    'type' => $card['type'],
                    'order' => $maxOrder + $i + 1,
                    'player_id' => $playerId
                );
            })
        ));

        $players = self::loadPlayersBasicInfos();
        $playerColor = $players[$playerId]['player_color'];
        $playerName = $players[$playerId]['player_name'];
        $this->notifyPlayer(
            $playerId,
            'myCardsDrawn',
            '',
            array('cards' => $drawn, 'playerColor' => $playerColor)
        );
        $drawMessage = '${playerName} has drawn ${numCards} card';
        if ($numCards > 1) {
            $drawMessage .= 's';
        }
        $this->notifyAllPlayers(
            'cardsDrawn',
            clienttranslate($drawMessage),
            array(
                'numCards' => count($drawn),
                'playerName' => $playerName,
                'playerId' => $playerId,
                'playerColor' => $playerColor
            )
        );

        $this->gamestate->nextState('cardsDrawn');
    }

    public function stNextPlay()
    {
        $playerId = (int) $this->getActivePlayerId();

        $playerIdsWithDeckCard = $this->getIntCollectionFromDB('SELECT DISTINCT player_id FROM deck_card');
        $playerIdsWithPlayableCard = $this->getIntCollectionFromDB('SELECT DISTINCT player_id FROM playable_card');
        if (empty($playerIdsWithDeckCard) && empty($playerIdsWithPlayableCard)) {
            $this->gamestate->nextState('noCardsLeft');
            return;
        }

        $players = self::loadPlayersBasicInfos();
        $opponentPlayerId = F\first(
            array_keys($players),
            function($checkId) use ($playerId) { return $checkId !== $playerId; }
        );

        $this->gamestate->changeActivePlayer($opponentPlayerId);
        $this->gamestate->nextState('nextPlayer');
    }

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
