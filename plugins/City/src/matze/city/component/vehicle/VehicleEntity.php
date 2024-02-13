<?php

declare(strict_types=1);

namespace matze\city\component\vehicle;

use matze\city\component\taskforce\TaskForce;
use matze\city\component\vehicle\controller\Controller;
use matze\city\component\vehicle\controller\EntityController;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\particle\SmokeParticle;

class VehicleEntity extends Entity {
    protected Controller $controller;

    protected bool $crashed = false;

    public function __construct(Location $location, ?CompoundTag $nbt = null){
        parent::__construct($location, $nbt);
        $this->controller = new EntityController();
        $this->setScale(1.5);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(0.5, 0.7);
    }

    protected function getInitialDragMultiplier(): float{
        return 0;
    }

    protected function getInitialGravity(): float{
        return 0;
    }

    public static function getNetworkTypeId(): string{
        return "matadora:car_test";
    }

    public function getController(): Controller{
        return $this->controller;
    }

    public function setController(Controller $controller): void{
        $this->controller = $controller;
    }

    public function isCrashed(): bool{
        return $this->crashed;
    }

    public function onUpdate(int $currentTick): bool{
        $this->controller->update($this, $currentTick);
        if(!$this->crashed) {
            if($this->motion->lengthSquared() > 0) {
                foreach($this->getWorld()->getCollidingEntities($this->getBoundingBox(), $this) as $entity) {
                    $this->crashed = true;
                    $this->setRotation($this->location->yaw + random_int(-30, 30), 0);
                    $this->setOnFire(120);

                    $entity->setMotion($this->getMotion()->add(0, 1, 0));

                    TaskForce::triggerFireDepartment($this->getPosition());
                }
            }
        } else {
            $this->setMotion(Vector3::zero());
            $this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());
        }
        parent::onUpdate($currentTick);
        return true;
    }

    public function canCollideWith(Entity $entity): bool{
        return $entity !== $this && ($entity instanceof self || $entity instanceof Player);
    }

    public function motion(Vector3 $motion): void {
        $this->motion = $motion;
    }

    public function canSaveWithChunk(): bool{
        return false;
    }

    public function attack(EntityDamageEvent $source): void{
        $source->cancel();
    }
}