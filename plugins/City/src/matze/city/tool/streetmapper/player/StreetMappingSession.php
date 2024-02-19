<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\player;

use matze\city\session\Session;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class StreetMappingSession {
    public static function get(Player $player): StreetMappingSession {
        return Session::get($player)->getStreetMappingSession();
    }

    /** @var Vector3[]  */
    private array $laneSwitchSelections = [];
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

    public function clearLaneSwitchSelections(): void {
        $this->laneSwitchSelections = [];
    }

    public function addLaneSwitchSelection(Vector3 $vector3): void {
        $this->laneSwitchSelections[World::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ())] = $vector3->floor();
    }

    public function getLaneSwitchSelections(): array{
        return $this->laneSwitchSelections;
    }

    public function update(int $tick): void {
    }
}