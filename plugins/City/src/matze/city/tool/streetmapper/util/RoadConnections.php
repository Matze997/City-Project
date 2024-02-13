<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\util;

use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class RoadConnections {
    /** @var Vector3[]  */
    private array $connections = [];
    private int $id;

    public function __construct(
        private Vector3 $vector3,
        array $connections = [],
    ){
        $this->id = RoadNetwork::$id++;
        foreach($connections as $connection) {
            World::getBlockXYZ($connection, $x, $y, $z);
            $this->connections[] = new Vector3($x + 0.5, $y, $z + 0.5);
        }
    }

    public function getVector3(): Vector3{
        return $this->vector3;
    }

    public function getId(): int{
        return $this->id;
    }

    public function toArray(): array {
        return [
            array_map(function(Vector3 $vector3): int {
                return World::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
            }, $this->connections),
        ];
    }

    /**
     * @return Vector3[]
     */
    public function getAll(): array{
        return $this->connections;
    }

    public function add(Vector3 $vector3): void {
        $this->connections[] = $vector3->floor()->add(0.5, 0, 0.5);
    }

    public function remove(Vector3 $vector3): void {
        foreach($this->connections as $key => $connection) {
            if($vector3->equals($connection->floor())) {
                unset($this->connections[$key]);
            }
        }
    }

    public function random(): ?Vector3 {
        if(count($this->connections) <= 0) {
            return null;
        }
        return $this->connections[array_rand($this->connections)];
    }
}