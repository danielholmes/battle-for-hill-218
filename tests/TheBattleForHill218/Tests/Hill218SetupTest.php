<?php

namespace TheBattleForHill218\Tests;

use PHPUnit\Framework\TestCase;
use TheBattleForHill218\Cards\AirStrikeCard;
use TheBattleForHill218\Cards\PlayerCard;
use TheBattleForHill218\Hill218Setup;
use Functional as F;

class Hill218SetupTest extends TestCase
{
    public function testStartingCards()
    {
        list($hand, $deck) = Hill218Setup::getPlayerStartingCards(123);
        assertThat($hand, arrayWithSize(7));
        assertThat(array_keys($hand), equalTo(range(0, 6)));
        assertThat($deck, arrayWithSize(19));
        assertThat(array_keys($deck), equalTo(range(0, 18)));
        assertThat(
            F\filter(
                $hand,
                function (PlayerCard $card) {
                    return $card instanceof AirStrikeCard;
                }
            ),
            arrayWithSize(2)
        );
    }

    public function testGetStartingDeckSize()
    {
        assertThat(Hill218Setup::getPlayerDeckSizeAfterInitialReturn(), equalTo(21));
    }
}
