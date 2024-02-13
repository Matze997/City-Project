<?php

declare(strict_types=1);

namespace matze\item\action;

use pocketmine\item\Item;
use pocketmine\player\Player;

class HeldItemAction extends ItemAction {
    public function __construct(
        Player $player,
        Item $item,
        protected int $slot,
    ){
        parent::__construct($player, $item);
    }

    public function getSlot(): int{
        return $this->slot;
    }
}