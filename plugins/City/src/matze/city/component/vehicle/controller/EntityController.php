<?php

declare(strict_types=1);

namespace matze\city\component\vehicle\controller;

use matze\city\City;
use matze\city\component\vehicle\VehicleEntity;
use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\tool\streetmapper\util\RoadConnections;
use matze\city\util\VectorUtils;
use pocketmine\color\Color;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\CriticalParticle;
use pocketmine\world\particle\DustParticle;

class EntityController extends Controller {
    protected int $lastConnection = -1;
    protected ?Vector3 $targetPosition;
    protected int $targetId = -1;

    protected ?Vector3 $laneSwitchPoint = null;
    protected bool $switched = false;

    protected Vector3 $motion;

    protected float $speed;

    public function __construct(){
        $this->targetPosition = new Vector3(0, 0, 0);
        $this->motion = new Vector3(0, 0, 0);
    }

    public function getDespawnRange(VehicleEntity $vehicle): int {
        return $vehicle->isCrashed() ? 75 : 200;
    }

    public function getSpeed(): float {
        return $this->speed ??= (0.4 + ((0.4 * random_int(0, 100)) / 100));
    }

    public function update(VehicleEntity $vehicle, int $tick): void{
        if(!$this->doGenericChecks($vehicle)) {
            return;
        }

        if($this->laneSwitchPoint !== null) {
            $vehicle->getWorld()->addParticle($this->laneSwitchPoint, new CriticalParticle());
            $vehicle->getWorld()->addParticle($this->laneSwitchPoint->add(0, 0.5, 0), new CriticalParticle());
            $vehicle->getWorld()->addParticle($this->laneSwitchPoint->add(0, 1, 0), new CriticalParticle());
        }

        $updateRotation = false;
        $position = $vehicle->getPosition();
        $connections = RoadNetwork::getRoadMarkerConnections($position);
        if($connections !== null && ($this->targetId === -1 || $connections->getId() === $this->targetId)) {
            $target = $connections->random();
            if($target === null) {
                return;
            }
            $this->laneSwitchPoint = null;
            switch($connections->getType()) {
                case RoadConnections::TYPE_LANE_CHANGE: {
                    //We don´t need the lane switch "special" mechanic when the road points are near enough
                    if($position->distanceSquared($target) > 225) {//15 Blocks
                        $lane = $connections->getRandomLane();
                        if(!$lane->end->equals($target->floor())) {
                            $this->laneSwitchPoint = VectorUtils::getRandomPositionBetween($lane->start->add(0.5, 0, 0.5), $lane->end->add(0.5, 0, 0.5))->floor();
                        }
                        $this->targetId = RoadNetwork::getRoadMarkerConnections($lane->end)?->getId() ?? -1;
                    }
                    break;
                }
                default: {
                    $this->targetId = RoadNetwork::getRoadMarkerConnections($target)?->getId() ?? -1;
                }
            }
            $this->targetPosition = $target;
            $updateRotation = true;
        }
        $this->followTargetPosition($vehicle, $updateRotation);
    }

    protected function doGenericChecks(VehicleEntity $vehicle): bool {
        $position = $vehicle->getLocation();
        $world = $position->getWorld();
        if($world->getNearestEntity($position, $this->getDespawnRange($vehicle), Player::class) === null) {
            $vehicle->flagForDespawn();
            return false;
        }

        if($vehicle->isCrashed()) {
            if(!$vehicle->isOnFire()){
                $vehicle->flagForDespawn();
            }
            return false;
        }
        return true;
    }

    protected function followTargetPosition(VehicleEntity $vehicle, bool $updateRotation): void {
        $position = $vehicle->getPosition();
        $world = $position->getWorld();
        if($this->targetPosition === null) {
            return;
        }

        if($this->laneSwitchPoint !== null) {
            $distance = $this->laneSwitchPoint->withComponents(null, 0, null)->distanceSquared($position->withComponents(null, 0, null));
            if($distance < 0.25) {
                $this->targetPosition = (RoadNetwork::getConnectionById($this->targetId)?->getVector3() ?? Vector3::zero())->add(0.5, 0, 0.5);
                $this->laneSwitchPoint = null;
                $updateRotation = true;
                $this->switched = false;
            } elseif(!$this->switched && $distance < 100 && !$this->targetPosition->floor()->equals($this->laneSwitchPoint->floor())) {
                $this->targetPosition = $this->laneSwitchPoint->withComponents(null, 0, null);
                $this->switched = true;
                $updateRotation = true;
            }
        }

        if($updateRotation || (Server::getInstance()->getTick() + $vehicle->getId()) % 20 === 0) {
            $vehicle->setRotation(VectorUtils::getYaw($this->targetPosition, $position), 0);
            $dirVec = $vehicle->getDirectionVector()->withComponents(null, 0, null);
            $this->motion = $dirVec->multiply($this->getSpeed());
        } else {
            $dirVec = $vehicle->getDirectionVector()->withComponents(null, 0, null);
        }

        $motion = clone $this->motion;

        $front = $position->addVector($dirVec->multiply(4));
        $entities = array_filter($world->getChunkEntities($front->getFloorX() >> 4, $front->getFloorZ() >> 4), function(Entity $entity) use ($vehicle): bool {
            return $this->isValidEntity($entity, $vehicle);
        });
        if(count($entities) > 0) {
            //TODO: Improve collision check
            $v1 = $front->add(-1, -0.5, -1);
            $v2 = $front->add(1, 2, 1);

            $bb = new AxisAlignedBB(min($v1->x, $v2->x), min($v1->y, $v2->y), min($v1->z, $v2->z), max($v1->x, $v2->x), max($v1->y, $v2->y), max($v1->z, $v2->z));
            $entities = array_filter($world->getCollidingEntities($bb), function(Entity $entity) use ($vehicle): bool {
                return $this->isValidEntity($entity, $vehicle);
            });

            if(City::$DEBUG) {
                $world->addParticle($front->add(0, 2, 0), new DustParticle(new Color(255, 0, 0)));
            }

            if(count($entities) > 0) {
                foreach($entities as $entity) {
                    if(!$entity instanceof VehicleEntity) {
                        $motion = Vector3::zero();
                        break;
                    }
                }
                if(!$motion->equals(Vector3::zero())) {
                    foreach($entities as $entity) {
                        if($vehicle->getId() > $entity->getId()) {
                            $motion = Vector3::zero();
                            break;
                        }
                    }
                }
            }
        }
        $this->move($motion, $vehicle);
    }

    protected function isValidEntity(Entity $entity, VehicleEntity $vehicle): bool {
        if($entity instanceof Player || ($entity instanceof VehicleEntity && $vehicle->getId() !== $entity->getId())) {//TODO: Add NPC´s
            $diff = abs($entity->getLocation()->getY() - $vehicle->getPosition()->getY());
            return $diff < 5;
        }
        return false;
    }
}