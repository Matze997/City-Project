<?php

declare(strict_types=1);

namespace matze\item\action;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\player\Player;

class InteractEntityAction extends ItemAction {
    public function __construct(
        Player $player,
        Item             $item,
        protected Entity $entity
    ){
        parent::__construct($player, $item);
    }

    public function getEntity(): Entity{
        return $this->entity;
    }
}