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
use TheBattleForHill218\Functional as HF;
use TheBattleForHill218\Battlefield\Battlefield;
use TheBattleForHill218\Battlefield\CardPlacement;
use TheBattleForHill218\Battlefield\Position;
use TheBattleForHill218\Cards\AirStrikeCard;
use TheBattleForHill218\Cards\CardFactory;
use TheBattleForHill218\Cards\HillCard;
use TheBattleForHill218\Cards\PlayerBattlefieldCard;
use TheBattleForHill218\Cards\PlayerCard;
use TheBattleForHill218\Hill218Setup;
use TheBattleForHill218\SQLHelper;

class BattleForHillDhau extends Table
{
    const DOWNWARD_PLAYER_COLOR = '6f0f11';

    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]);
    }

    /**
     * @return string
     */
    protected function getGameName()
    {
        return "battleforhilldhau";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        $this->setupPlayers($players);
        $this->setupStats();
        $this->setupBattlefield();
        foreach (array_keys($players) as $playerId) {
            $this->setupPlayerCards($playerId);
        }
        $this->activeNextPlayer();
        $this->gamestate->setAllPlayersMultiactive();
    }

    private function setupStats()
    {
        $this->initStat('player', 'num_defeated_cards', 0);
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
        $playerNumbers = range(1, count($players));
        shuffle($playerNumbers);
        shuffle($colors);

        $i = 0;
        foreach ($players as $player_id => $player) {
            $color = $colors[$i];
            self::DbQuery(SQLHelper::insert('player', [
                'player_id' => $player_id,
                'player_color' => $color,
                'player_canal' => $player['player_canal'],
                'player_name' => $player['player_name'],
                'player_avatar' => $player['player_avatar']
            ]));
            $i++;
        }

        $this->reattributeColorsBasedOnPreferences($players, $colors);
        $this->reloadPlayersBasicInfos();
    }

    private function setupBattlefield()
    {
        self::DBQuery(SQLHelper::insert('battlefield_card', [
            'type' => 'hill',
            'player_id' => null,
            'x' => 0,
            'y' => 0
        ]));
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
                    function (PlayerCard $card, $i) {
                        return [
                            'player_id' => $card->getPlayerId(),
                            'type' => $card->getTypeKey(),
                            'order' => $i
                        ];
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
        $allHandCards = HF\group_to_lists(
            self::getObjectListFromDB('SELECT id, player_id, type FROM playable_card ORDER BY `order` ASC'),
            function (array $card) {
                return (int) $card['player_id'];
            }
        );

        // Decks
        $allDeckSizes = F\map(
            F\map(
                HF\group_to_lists(
                    self::getObjectListFromDB('SELECT COUNT(id) as size, player_id FROM deck_card GROUP BY player_id'),
                    function (array $count) {
                        return (int) $count['player_id'];
                    }
                ),
                function (array $counts) {
                    return F\head($counts);
                }
            ),
            function (array $count) {
                return (int) $count['size'];
            }
        );

        // Default values
        $players = F\map(
            $this->getCollectionFromDb('SELECT player_id id, player_score score, player_color color FROM player'),
            function (array $rawPlayer) {
                return array_merge(
                    $rawPlayer,
                    ['id' => (int) $rawPlayer['id'], 'score' => (int) $rawPlayer['score']]
                );
            }
        );
        $playerIds = F\pluck($players, 'id');
        foreach ($playerIds as $playerId) {
            if (!isset($allHandCards[$playerId])) {
                $allHandCards[$playerId] = [];
            }
            if (!isset($allDeckSizes[$playerId])) {
                $allDeckSizes[$playerId] = 0;
            }
        }

        // Players
        $currentPlayerId = (int) self::getCurrentPlayerId();
        $_this = $this;
        $players = F\map(
            $players,
            function (array $player) use ($_this, $allHandCards, $allDeckSizes, $currentPlayerId) {;
                $handCards = $allHandCards[$player['id']];
                $deckSize = $allDeckSizes[$player['id']];

                if ($player['id'] === $currentPlayerId) {
                    return array_merge($player, $_this->getMyPlayerData($deckSize, $handCards));
                }

                return array_merge($player, $_this->getPublicPlayerData($deckSize, $handCards));
            }
        );

        return [
            'players' => $players,
            'battlefield' => $this->getBattlefieldDatas()
        ];
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
            [
                'cards' => F\map(
                    $cards,
                    function (array $card) {
                        return ['id' => (int) $card['id'], 'type' => $card['type']];
                    }
                )
            ]
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
                function ($type) {
                    return $type === 'air-strike';
                }
            )
        );
        return [
            'numAirStrikes' => $numAirStrikes,
            'numCards' => count($cards),
            'deckSize' => $deckSize
        ];
    }

    /**
     * @return array
     */
    private function getBattlefieldDatas()
    {
        $players = self::loadPlayersBasicInfos();
        return F\map(
            self::getObjectListFromDB('SELECT player_id AS playerId, type, x, y FROM battlefield_card'),
            function (array $card) use ($players) {
                $playerId = $card['playerId'];
                $playerColor = null;
                if ($playerId !== null) {
                    $playerId = (int) $playerId;
                    $playerColor = $players[$playerId]['player_color'];
                }
                return [
                    'playerId' => $playerId,
                    'playerColor' => $playerColor,
                    'type' => $card['type'],
                    'x' => (int) $card['x'],
                    'y' => (int) $card['y']
                ];
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
        $numDeckCards = self::getIntUniqueValueFromDB("SELECT COUNT(id) FROM deck_card");
        $numPlayableCards = self::getIntUniqueValueFromDB("SELECT COUNT(id) FROM playable_card");
        $totalCards = $numDeckCards + $numPlayableCards;

        $percent = (Hill218Setup::getTotalDeckSize() - $totalCards) / Hill218Setup::getTotalDeckSize();
        return (int) round(100 * $percent);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////
    /**
     * @return Battlefield
     */
    private function loadBattlefield()
    {
        $downwardPlayerColor = self::DOWNWARD_PLAYER_COLOR;
        $downwardPlayerId = self::getUniqueValueFromDB(
            "SELECT player_id FROM player WHERE player_color = '{$downwardPlayerColor}'"
        );
        if ($downwardPlayerId === null) {
            throw new RuntimeException('Downwards player not found');
        }
        return new Battlefield(
            (int) $downwardPlayerId,
            F\map(
                self::getObjectListFromDB('SELECT * FROM battlefield_card'),
                ['BattleForHillDhau', 'parseCardPlacement']
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
     * @return int
     */
    private static function getIntUniqueValueFromDB($sql)
    {
        return (int) self::getUniqueValueFromDB($sql);
    }

    private function updatePlayerScores()
    {
        self::DbQuery(
<<<SQL
            UPDATE player p 
            SET player_score = (SELECT COUNT(b.id) FROM battlefield_card b WHERE b.player_id = p.player_id)
SQL
        );
        self::notifyAllPlayers(
            'newScores',
            '',
            F\map(
                $this->getCollectionFromDb('SELECT player_id, player_score FROM player', true),
                function ($value) {
                    return (int) $value;
                }
            )
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////
    /**
     * @param array $cardIds
     * @throws BgaUserException
     */
    public function returnToDeck(array $cardIds)
    {
        $this->checkAction('returnToDeck');

        $this->performReturnToDeck($this->getCurrentPlayerId(), $cardIds);
    }

    /**
     * @param array $cardIds
     * @throws BgaUserException
     */
    private function performReturnToDeck($playerId, array $cardIds)
    {
        $numCards = Hill218Setup::NUMBER_OF_INITIAL_CARDS_TO_RETURN;
        if (count($cardIds) !== $numCards) {
            throw new BgaUserException(sprintf('Exactly %d cards required', $numCards));
        }

        // Remove cards from hand
        $idList = join(', ', $cardIds);
        $returnCards = self::getObjectListFromDB(
            "SELECT id, type FROM playable_card WHERE player_id = {$playerId} AND id IN ({$idList})"
        );
        if (count($returnCards) !== Hill218Setup::NUMBER_OF_INITIAL_CARDS_TO_RETURN) {
            throw new BgaUserException("Card no longer playable");
        }
        self::DBQuery("DELETE FROM playable_card WHERE id IN ({$idList})");

        // Put removed cards into deck
        self::DBQuery(
            "UPDATE deck_card SET `order` = `order` + {$numCards} WHERE player_id = {$playerId} ORDER BY `order` DESC"
        );
        self::DBQuery(SQLHelper::insertAll(
            'deck_card',
            F\map($returnCards, function (array $handCard, $i) use ($playerId) {
                return [
                    'type' => $handCard['type'],
                    'order' => $i,
                    'player_id' => $playerId
                ];
            })
        ));

        $this->notifyPlayer(
            'all',
            'returnedToDeck',
            clienttranslate('${playerName} returned ${numCards} cards to their deck'),
            [
                'numCards' => $numCards,
                'playerName' => $this->getCurrentPlayerName(),
                'playerColor' => $this->getCurrentPlayerColor(),
                'playerId' => $playerId
            ]
        );

        $this->giveExtraTime($playerId);
        $this->gamestate->setPlayerNonMultiactive($playerId, 'allReturned');
    }

    /**
     * @param int $cardId
     * @param int $x
     * @param int $y
     * @throws BgaUserException
     */
    public function playCard($cardId, $x, $y)
    {
        $this->checkAction('playCard');

        $playerId = (int) $this->getCurrentPlayerId();
        $position = new Position($x, $y);
        $card = null;
        try {
            $card = self::parsePlayableCard(
                self::getNonEmptyObjectFromDB(
                    "SELECT * FROM playable_card WHERE id = {$cardId} AND player_id = {$playerId}"
                )
            );
        } catch (BgaSystemException $e) {
            throw new BgaUserException('Card no longer playable');
        }

        if ($card instanceof AirStrikeCard) {
            $this->playAirStrikeCard($card, $cardId, $position);
            return;
        }

        $this->playBattlefieldCard($card, $cardId, $position);
    }

    /**
     * @param AirStrikeCard $card
     * @param int $cardId
     * @param Position $position
     * @throws BgaUserException
     */
    private function playAirStrikeCard(AirStrikeCard $card, $cardId, Position $position)
    {
        $foundInPosition = self::getObjectListFromDB(
            "SELECT id, player_id FROM battlefield_card WHERE x = {$position->getX()} AND y = {$position->getY()}"
        );
        if (empty($foundInPosition)) {
            throw new BgaUserException("Position {$position->getX()},{$position->getY()} doesn't have opponent card");
        }
        $cardInPosition = $foundInPosition[0];
        if ((int) $cardInPosition['player_id'] === $card->getPlayerId()) {
            throw new BgaUserException("Can't Air Strike your own card");
        }
        if ($cardInPosition['player_id'] === null) {
            throw new BgaUserException("Can't Air Strike hill");
        }

        // Remove from battlefield and playable
        self::DbQuery("DELETE FROM playable_card WHERE id = {$cardId}");
        self::DbQuery("DELETE FROM battlefield_card WHERE id = {$cardInPosition['id']}");
        $this->incStat(1, 'num_defeated_cards', $card->getPlayerId());

        $this->updatePlayerScores();

        // Notifications
        $players = self::loadPlayersBasicInfos();
        $player = $players[$card->getPlayerId()];
        $this->notifyAllPlayers(
            'playedAirStrike',
            '${playerName} played an air strike card at ${x},${y}',
            [
                'playerId' => $card->getPlayerId(),
                'playerName' => $player['player_name'],
                'x' => $position->getX(),
                'y' => $position->getY()
            ]
        );
        $this->notifyPlayer(
            $card->getPlayerId(),
            'iPlayedAirStrike',
            '',
            [
                'cardId' => $cardId,
                'x' => $position->getX(),
                'y' => $position->getY()
            ]
        );

        $this->giveExtraTime($card->getPlayerId());
        $this->gamestate->nextState('noAttackAvailable');
    }

    /**
     * @param PlayerBattlefieldCard $card
     * @param int $cardId
     * @param Position $position
     * @throws BgaUserException
     */
    private function playBattlefieldCard(PlayerBattlefieldCard $card, $cardId, Position $position)
    {
        // Check if valid position
        $battlefield = $this->loadBattlefield();
        $possiblePositions = $card->getPossiblePlacements($battlefield);
        if (!F\contains($possiblePositions, $position, false)) {
            throw new BgaUserException('That position isn\'t allowed');
        }

        // Move from playable to battlefield
        self::DbQuery("DELETE FROM playable_card WHERE id = {$cardId}");
        self::DbQuery(
            SQLHelper::insert(
                'battlefield_card',
                [
                    'type' => $card->getTypeKey(),
                    'player_id' => $card->getPlayerId(),
                    'x' => $position->getX(),
                    'y' => $position->getY()
                ]
            )
        );

        // Notifications
        $players = self::loadPlayersBasicInfos();
        $player = $players[$card->getPlayerId()];
        $this->notifyAllPlayers(
            'placedCard',
            '${playerName} placed a ${typeName} card at ${x},${y}',
            [
                'playerId' => $card->getPlayerId(),
                'playerName' => $player['player_name'],
                'playerColor' => $player['player_color'],
                'typeName' => $card->getTypeName(),
                'typeKey' => $card->getTypeKey(),
                'x' => $position->getX(),
                'y' => $position->getY()
            ]
        );
        $this->notifyPlayer(
            $card->getPlayerId(),
            'iPlacedCard',
            '',
            [
                'cardId' => $cardId,
                'x' => $position->getX(),
                'y' => $position->getY()
            ]
        );

        // Check if occupying base
        $opponentBasePosition = $battlefield->getOpponentBasePosition($card->getPlayerId());
        if ($opponentBasePosition == $position) {
            self::DbQuery("UPDATE player SET player_score = 10 WHERE player_id = {$card->getPlayerId()}");
            self::DbQuery("UPDATE player SET player_score = 0 WHERE player_id != {$card->getPlayerId()}");
            $this->gamestate->nextState('occupyEnemyBase');
            return;
        }

        $updatedBattlefield = $this->loadBattlefield();
        $this->updatePlayerScores();
        $this->giveExtraTime($card->getPlayerId());
        if ($updatedBattlefield->hasAttackablePlacement($position)) {
            $this->gamestate->nextState('attackAvailable');
            return;
        }
        $this->gamestate->nextState('noAttackAvailable');
    }

    /**
     * @param int $x
     * @param int $y
     * @throws BgaUserException
     */
    public function chooseAttack($x, $y)
    {
        $playerId = (int) $this->getActivePlayerId();
        $attackPosition = new Position($x, $y);
        $battlefield = $this->loadBattlefield();
        $fromPosition = $this->getChooseAttackFromPosition($playerId);
        $isAttackablePosition = F\some(
            $battlefield->getAttackablePlacements($fromPosition),
            function (CardPlacement $p) use ($attackPosition) {
                return $p->getPosition() == $attackPosition;
            }
        );

        if (!$isAttackablePosition) {
            throw new BgaUserException('Position not attackable');
        }

        $this->giveExtraTime($playerId);

        $this->performChooseAttack($playerId, $fromPosition, $attackPosition);
    }

    /**
     * @param int $playerId
     * @param Position $fromPosition
     * @param Position $attackPosition
     */
    private function performChooseAttack($playerId, Position $fromPosition, Position $attackPosition)
    {
        self::DbQuery(
            "DELETE FROM battlefield_card WHERE x = {$attackPosition->getX()} AND y = {$attackPosition->getY()} LIMIT 1"
        );
        $this->incStat(1, 'num_defeated_cards', $playerId);
        $this->updatePlayerScores();

        $this->notifyAllPlayers(
            'cardAttacked',
            '${playerName} attacked ${x},${y}',
            [
                'playerName' => self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = {$playerId}"),
                'x' => $attackPosition->getX(),
                'y' => $attackPosition->getY(),
                'fromX' => $fromPosition->getX(),
                'fromY' => $fromPosition->getY()
            ]
        );

        $this->gamestate->nextState('attackChosen');
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////
    /**
     * @return array
     */
    public function argPlayCard()
    {
        $playerId = (int) $this->getActivePlayerId();
        $playableCards = self::getObjectListFromDB("SELECT * from playable_card WHERE player_id = {$playerId}");
        $battlefield = $this->loadBattlefield();
        return [
            '_private' => [
                'active' => array_combine(
                    F\pluck($playableCards, 'id'),
                    F\map(
                        F\map($playableCards, ['BattleForHillDhau', 'parsePlayableCard']),
                        function (PlayerCard $card) use ($battlefield) {
                            return F\map(
                                $card->getPossiblePlacements($battlefield),
                                function (Position $position) {
                                    return ['x' => $position->getX(), 'y' => $position->getY()];
                                }
                            );
                        }
                    )
                )
            ]
        ];
    }

    /**
     * @return array
     * @throws BgaSystemException
     */
    public function argChooseAttack()
    {
        $battlefield = $this->loadBattlefield();
        $fromPosition = $this->getChooseAttackFromPosition((int) $this->getActivePlayerId());

        return [
            '_private' => [
                'active' => F\map(
                    $battlefield->getAttackablePlacements($fromPosition),
                    function (CardPlacement $p) {
                        return ['x' => $p->getPosition()->getX(), 'y' => $p->getPosition()->getY()];
                    }
                )
            ]
        ];
    }

    /**
     * @param int $playerId
     * @return Position
     * @throws BgaSystemException
     */
    private function getChooseAttackFromPosition($playerId)
    {
        // TODO: placed_at timestamp would be better
        $mostRecentList = self::getObjectListFromDB(
            "SELECT x, y, player_id FROM battlefield_card ORDER BY id DESC LIMIT 1"
        );
        if (empty($mostRecentList)) {
            throw new BgaSystemException('Choosing attack when no battlefield card');
        }
        $mostRecent = $mostRecentList[0];
        if ((int) $mostRecent['player_id'] !== $playerId) {
            throw new BgaSystemException(
                'Choosing attack when active player hasn\'t placed most recent card: ' . json_encode($mostRecent)
            );
        }

        return new Position((int) $mostRecent['x'], (int) $mostRecent['y']);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    public function stDrawCards()
    {
        $playerId = (int) $this->getActivePlayerId();
        $numCards = 2;
        $playerNumber = self::getIntUniqueValueFromDB("SELECT player_no from player WHERE player_id = {$playerId}");
        if ($playerNumber === 1) {
            $deckSize = self::getIntUniqueValueFromDB("SELECT COUNT(id) FROM deck_card WHERE player_id = {$playerId}");
            if ($deckSize === Hill218Setup::getPlayerDeckSizeAfterInitialReturn()) {
                $numCards = 1;
            }
        }

        $drawnDeck = self::getObjectListFromDB(
            "SELECT id, type FROM deck_card WHERE player_id = {$playerId} ORDER BY `order` DESC LIMIT {$numCards}"
        );
        $numDrawn = count($drawnDeck);
        if ($numDrawn === 0) {
            $this->gamestate->nextState('cardsDrawn');
            return;
        }

        // Remove drawn from the deck
        $drawnIds = F\pluck($drawnDeck, 'id');
        $drawnIdsList = join(', ', $drawnIds);
        self::DBQuery("DELETE FROM deck_card WHERE id IN ({$drawnIdsList})");

        // Put drawn cards into hand
        // Leaves holes in `order`, but is more efficient this way and doesn't matter
        $maxOrder = self::getIntUniqueValueFromDB(
            "SELECT MAX(`order`) FROM playable_card WHERE player_id = {$playerId}"
        );
        self::DBQuery(SQLHelper::insertAll(
            'playable_card',
            F\map($drawnDeck, function (array $card, $i) use ($playerId, $maxOrder) {
                return [
                    'type' => $card['type'],
                    'order' => $maxOrder + $i + 1,
                    'player_id' => $playerId
                ];
            })
        ));
        $drawnPlayable = self::getObjectListFromDB(
            "SELECT id, type FROM playable_card WHERE player_id = {$playerId} ORDER BY id DESC LIMIT {$numDrawn}"
        );

        $players = self::loadPlayersBasicInfos();
        $playerColor = $players[$playerId]['player_color'];
        $this->notifyPlayer(
            $playerId,
            'myCardsDrawn',
            '',
            ['cards' => $drawnPlayable, 'playerColor' => $playerColor]
        );

        self::notifyAllPlayers(
            'newDeckCount',
            '',
            [
                'playerId' => $playerId,
                'count' => self::getIntUniqueValueFromDB("SELECT COUNT(id) FROM deck_card WHERE player_id = {$playerId}")
            ]
        );

        $drawMessage = '${playerName} has drawn ${numCards} card';
        if ($numDrawn > 1) {
            $drawMessage .= 's';
        }
        $this->notifyAllPlayers(
            'cardsDrawn',
            clienttranslate($drawMessage),
            [
                'numCards' => $numDrawn,
                'playerName' => $players[$playerId]['player_name'],
                'playerId' => $playerId,
                'playerColor' => $playerColor
            ]
        );

        self::DbQuery("UPDATE player SET turn_plays_remaining = {$numDrawn} WHERE player_id = {$playerId}");

        $this->gamestate->nextState('cardsDrawn');
    }

    public function stNextPlay()
    {
        $playerId = (int) $this->getActivePlayerId();

        $haveDeckCards = (boolean) self::getUniqueValueFromDB('SELECT COUNT(id) FROM deck_card LIMIT 1');
        $havePlayableCards = (boolean) self::getUniqueValueFromDB('SELECT COUNT(id) FROM playable_card LIMIT 1');
        if (!$haveDeckCards && !$havePlayableCards) {
            $this->gamestate->nextState('noCardsLeft');
            return;
        }

        $activePlayerHasCards = (boolean) self::getUniqueValueFromDB(
            "SELECT COUNT(id) FROM playable_card WHERE player_id = {$playerId} LIMIT 1"
        );
        self::DbQuery(
            "UPDATE player SET turn_plays_remaining = turn_plays_remaining - 1 WHERE player_id = {$playerId}"
        );
        $remaining = self::getIntUniqueValueFromDB(
            "SELECT turn_plays_remaining FROM player WHERE player_id = {$playerId}"
        );
        if ($remaining <= 0 || !$activePlayerHasCards) {
            $opponentPlayerId = F\first(
                array_keys(self::loadPlayersBasicInfos()),
                function ($checkId) use ($playerId) {
                    return $checkId !== $playerId;
                }
            );
            self::DbQuery("UPDATE player SET turn_plays_remaining = 2 WHERE player_id = {$opponentPlayerId}");
            $this->gamestate->changeActivePlayer($opponentPlayerId);
            $this->gamestate->nextState('nextPlayer');
            return;
        }

        $this->gamestate->nextState('playAgain');
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
    public function zombieTurn($state, $activePlayerId)
    {
        switch ($state['name']) {
            case 'returnToDeck':
                $this->performReturnToDeck(
                    $activePlayerId,
                    F\map(
                        self::getObjectListFromDB(
                            "SELECT id FROM playable_card WHERE player_id = ${activePlayerId} AND type != \"air-strike\" ORDER BY RAND() LIMIT 2"
                        ),
                        function(array $row) {
                            return (int) $row['id'];
                        }
                    )
                );
                break;
            case 'playCard':
                self::DbQuery("DELETE FROM playable_card WHERE player_id = {$activePlayerId} LIMIT 1");
                $this->gamestate->nextState('noAttackAvailable');
                break;
            case 'chooseAttack':
                $battlefield = $this->loadBattlefield();
                $fromPosition = $this->getChooseAttackFromPosition($activePlayerId);
                $battlefield->getAttackablePlacements($fromPosition);
                $possiblePlacements = $battlefield->getAttackablePlacements($fromPosition);
                $this->performChooseAttack($activePlayerId, $fromPosition, $possiblePlacements[0]->getPosition());
                break;
            default:
                throw new BgaSystemException("Unknown state for zombie {$state['name']}");
        }
        //$this->gamestate->updateMultiactiveOrNextState( '' );
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
    public function upgradeTableDb($from_version)
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
