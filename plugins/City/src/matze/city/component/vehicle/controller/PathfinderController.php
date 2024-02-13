<?php

declare(strict_types=1);

namespace matze\city\component\vehicle\controller;

use matze\city\component\vehicle\VehicleEntity;
use matze\city\tool\streetmapper\pathfinder\Node;
use matze\city\tool\streetmapper\pathfinder\result\PathResult;
use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\HappyVillagerParticle;

class PathfinderController extends EntityController {
    public function __construct(
        protected PathResult $result,
        protected Vector3 $target,
    ){
        parent::__construct();
        $this->result->addNode(Node::fromVector3($this->target));
    }

    public function update(VehicleEntity $vehicle, int $tick): void{
        $position = $vehicle->getLocation();
        $world = $position->getWorld();

        if($world->getNearestEntity($position, $this->getDespawnRange($vehicle), Player::class) === null) {
            $vehicle->flagForDespawn();
            return;
        }
        if($vehicle->isCrashed()) {
            if(!$vehicle->isOnFire()){
                $vehicle->flagForDespawn();
            }
            return;
        }

        $updateRotation = false;
        $connections = RoadNetwork::getRoadMarkerConnections($position);
        if($connections !== null && ($this->targetId === -1 || $connections->getId() === $this->targetId)) {
            $target = $this->result->shiftNode();
            if($target === null) {
                $this->targetPosition = null;
                $vehicle->setMotion(Vector3::zero());
            } elseif($this->lastConnection !== $connections->getId()) {
                $this->lastConnection = $connections->getId();
                $this->targetId = RoadNetwork::getRoadMarkerConnections($target)?->getId() ?? -1;
                $this->targetPosition = $target;
                $updateRotation = true;
            }
        }
        $this->followTargetPosition($vehicle, $updateRotation);
    }
}