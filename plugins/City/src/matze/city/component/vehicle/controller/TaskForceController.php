<?php

declare(strict_types=1);

namespace matze\city\component\vehicle\controller;

use matze\city\component\vehicle\VehicleEntity;
use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\world\particle\DustParticle;

class TaskForceController extends PathfinderController {
    public const SPEED = 0.9;

    protected int $waitTicks = 0;

    public function getDespawnRange(VehicleEntity $vehicle): int{
        return 300;
    }

    public function update(VehicleEntity $vehicle, int $tick): void{
        $vehicle->getWorld()->addParticle($vehicle->getPosition()->up(2), new DustParticle(new Color(0, 0, 255)));

        if($vehicle->getPosition()->distanceSquared($this->target) <= (6 ** 2)) {
            $vehicle->motion(Vector3::zero());
            if(++$this->waitTicks > (20 * 30)) {
                foreach($vehicle->getWorld()->getNearbyEntities($vehicle->getBoundingBox()->expandedCopy(10, 10, 10)) as $entity) {
                    if($entity instanceof VehicleEntity && $entity->isCrashed()) {
                        $entity->flagForDespawn();
                    }
                }

                $newTarget = RoadNetwork::getRoadMarkerConnections($this->target)?->random();
                if($newTarget !== null) {
                    $controller = new EntityController();
                    $controller->targetPosition = $newTarget;
                    $controller->targetId = RoadNetwork::getRoadMarkerConnections($newTarget)?->getId();
                    $vehicle->setController($controller);
                } else {
                    $vehicle->flagForDespawn();
                }
            }
            return;
        }
        parent::update($vehicle, $tick);
    }
}