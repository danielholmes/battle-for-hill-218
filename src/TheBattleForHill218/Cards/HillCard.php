<?php

namespace TheBattleForHill218\Cards;

class HillCard implements BattlefieldCard
{
    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getTypeKey() : string
    {
        return 'hill';
    }

    /**
     * @inheritdoc
     */
    public function getTypeName() : string
    {
        return clienttranslate('Hill');
    }
}
