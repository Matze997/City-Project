<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\player;

use matze\city\session\Session;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

class StreetMappingSession {
    public static function get(Player $player): StreetMappingSession {
        return Session::get($player)->getStreetMappingSession();
    }

    private int $currentStreetEntity = -1;

    public function __construct(
        private Player $player
    ){}

    public function getPlayer(): Player{
        return $this->player;
    }

    public function clearCurrentStreetEntity(): void {
        $this->currentStreetEntity = -1;
    }

    public function getCurrentStreetEntity(): ?Entity{
        return $this->player->getWorld()->getEntity($this->currentStreetEntity);
    }

    public function setCurrentStreetEntity(Entity $entity): void{
        $this->currentStreetEntity = $entity->getId();
    }

    public function update(int $tick): void {
    }
}