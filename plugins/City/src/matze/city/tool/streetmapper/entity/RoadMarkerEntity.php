<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\entity;

use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\tool\streetmapper\util\RoadConnections;
use matze\city\util\VectorUtils;
use pocketmine\color\Color;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\DustParticle;

class RoadMarkerEntity extends Entity {
    private array $connections = [];

    public function __construct(Location $location, ?CompoundTag $nbt = null){
        parent::__construct($location, $nbt);
        $this->setScale(0.5);
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(1, 1);
    }

    protected function getInitialDragMultiplier(): float{
        return 0.0;
    }

    protected function getInitialGravity(): float{
        return 0.0;
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::GOAT;
    }

    public function onUpdate(int $currentTick): bool{
        $connections = RoadNetwork::getRoadMarkerConnections($this->getPosition());
        if($connections === null) {
            return true;
        }
        $this->setNameTag("§r§l".(match ($connections->getType()) {
            RoadConnections::TYPE_LANE_CHANGE => "§1",
            default => "§a"
        }).count($connections->getAll()).TextFormat::EOL."§r§l§a".count($connections->getParentConnections()));

        $player = $this->getWorld()->getNearestEntity($this->getPosition(), 20, Player::class);
        if($player instanceof Player) {
            $start = $this->getPosition();
            $world = $this->getWorld();
            $particle = new DustParticle(new Color(($this->getId() * 3) % 255, ($this->getId() * 5) % 255, ($this->getId() * 11) % 255));
            if(count($this->connections) <= 0) {
                foreach($connections->getAll() as $target) {
                    foreach(VectorUtils::getPositionsBetween($start, $target) as $vector3) {
                        $this->connections[] = $vector3->add(0, 0.3, 0);
                    }
                }
            } else {
                $vector3 = array_shift($this->connections);
                $world->addParticle($vector3->add(0, 0.3, 0), $particle);
            }
        }

        $changedProperties = $this->getDirtyNetworkData();
        if(count($changedProperties) > 0){
            $this->sendData(null, $changedProperties);
            $this->getNetworkProperties()->clearDirtyProperties();
        }
        return true;
    }

    public function canSaveWithChunk(): bool{
        return false;
    }

    public function attack(EntityDamageEvent $source): void{
        if($source instanceof EntityDamageByEntityEvent) {
            $entity = $source->getDamager();
            if($entity instanceof Player && $entity->isSneaking()) {
                $entity->flagForDespawn();
            }
        }
        $source->cancel();
        parent::attack($source);
    }
}