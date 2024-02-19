<?php

declare(strict_types=1);

namespace matze\city\component\vehicle\controller;

use matze\city\component\vehicle\VehicleEntity;
use matze\city\tool\streetmapper\pathfinder\Node;
use matze\city\tool\streetmapper\pathfinder\result\PathResult;
use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\math\Vector3;

class PathfinderController extends EntityController {
    public function __construct(
        protected PathResult $result,
        protected Vector3 $target,
    ){
        parent::__construct();
        $this->result->addNode(Node::fromVector3($this->target));
    }

    public function update(VehicleEntity $vehicle, int $tick): void{
        if(!$this->doGenericChecks($vehicle)) {
            return;
        }

        $updateRotation = false;
        $connections = RoadNetwork::getRoadMarkerConnections($vehicle->getPosition());
        if($connections !== null && ($this->targetId === -1 || $connections->getId() === $this->targetId)) {
            $target = $this->result->shiftNode();
            //TODO: Implement lane switching
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