<?php

namespace TheBattleForHill218\Cards;

interface PlayerBattlefieldCard extends PlayerCard, BattlefieldCard
{
    /**
     * @return SupplyOffset[]
     */
    public function getSupplyPattern() : array;

    /**
     * @return AttackOffset[]
     */
    public function getAttackPattern() : array;

    /**
     * @return SupportOffset[]
     */
    public function getSupportPattern() : array;
}
