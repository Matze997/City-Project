<?php

declare(strict_types=1);

namespace matze\item\action;

use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class ItemAction {
    protected bool $cancelled = false;

    public function __construct(
        protected Player $player,
        protected Item          $item
    ){}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getItem(): Item{
        return $this->item;
    }

    public function isCancelled(): bool{
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): void{
        $this->cancelled = $cancelled;
    }

    public function cancel(): self {
        $this->cancelled = true;
        return $this;
    }

    public function uncancel(): self {
        $this->cancelled = false;
        return $this;
    }

    public function continue(): self {
        return $this;
    }
}