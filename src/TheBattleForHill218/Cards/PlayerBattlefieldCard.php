<?php

namespace TheBattleForHill218\Cards;

interface PlayerBattlefieldCard extends PlayerCard, BattlefieldCard
{
    /**
     * @return SupplyOffset[]
     */
    public function getSupplyPattern();

    public function getAttackPattern();

    /**
     * @return SupportOffset[]
     */
    public function getSupportPattern();
}
