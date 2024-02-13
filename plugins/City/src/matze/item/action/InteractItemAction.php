<?php

declare(strict_types=1);

namespace matze\item\action;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;

class InteractItemAction extends ItemAction {
    private static array $playerCooldowns = [];

    public function __construct(
        Player $player,
        Item             $item,
        protected ?Block $block = null,
        protected ?int   $action = null,
        protected ?int   $face = null,
    ){
        parent::__construct($player, $item);
    }

    public function getAction(): ?int{
        return $this->action;
    }

    public function getBlock(): ?Block{
        return $this->block;
    }

    public function getFace(): ?int{
        return $this->face;
    }

    public function hasCooldown(): bool {
        $tick = self::$playerCooldowns[$this->getItem()->getName().self::class] ?? 0;
        return Server::getInstance()->getTick() <= $tick;
    }

    public function resetCooldown(int $cooldown): self{
        self::$playerCooldowns[$this->getItem()->getName().self::class] = Server::getInstance()->getTick() + $cooldown;
        return $this;
    }
}