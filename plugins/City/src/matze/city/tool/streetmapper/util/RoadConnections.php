<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\util;

use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class RoadConnections {
    public const TYPE_NORMAL_CONNECTION = 0;
    public const TYPE_LANE_CHANGE = 1;

    /** @var Vector3[]  */
    private array $connections = [];
    /** @var Vector3[]  */
    private array $parentConnections = [];

    /** @var Vector3[]  */
    private array $lanes = [];

    private int $id;

    public function __construct(
        private Vector3 $vector3,
        array $connections = [],
        private int $type = self::TYPE_NORMAL_CONNECTION,
        array $lanes = [],
    ){
        $this->id = RoadNetwork::$id++;
        foreach($connections as $connection) {
            World::getBlockXYZ($connection, $x, $y, $z);
            $this->connections[] = new Vector3($x + 0.5, $y, $z + 0.5);
        }

        foreach($lanes as $start => $end) {
            World::getBlockXYZ($start, $sX, $sY, $sZ);
            World::getBlockXYZ($end, $eX, $eY, $eZ);
            $this->addLane(new Vector3($sX, $sY, $sZ), new Vector3($eX, $eY, $eZ));
        }
    }

    public function getVector3(): Vector3{
        return $this->vector3;
    }

    public function getId(): int{
        return $this->id;
    }

    public function getType(): int{
        return $this->type;
    }

    public function setType(int $type): void{
        $this->type = $type;
    }

    public function addLane(Vector3 $from, Vector3 $to): void {
        $this->lanes[World::blockHash($from->getFloorX(), $from->getFloorY(), $from->getFloorZ())] = $to;
    }

    /**
     * @return Vector3[]
     */
    public function getLanes(): array{
        return $this->lanes;
    }

    public function getLane(Vector3 $target): ?Lane {
        $target = $target->floor();
        foreach($this->lanes as $hash => $vector3) {
            if($vector3->floor()->equals($target)) {
                World::getBlockXYZ($hash, $x, $y, $z);
                return new Lane(new Vector3($x, $y, $z), $target);
            }
        }
        return null;
    }

    public function getRandomLane(): Lane {
        if(count($this->lanes) <= 0) {
            throw new \RuntimeException("Road has to have at least one lane!");
        }
        $key = array_rand($this->lanes);
        World::getBlockXYZ($key, $x, $y, $z);
        return new Lane(new Vector3($x, $y, $z), $this->lanes[$key]);
    }

    /**
     * @return Vector3[]
     */
    public function getAll(): array{
        return $this->connections;
    }

    public function add(Vector3 $vector3): void {
        $this->remove($vector3);//Simple but stupid hack to avoid duplications
        $this->connections[] = $vector3->floor()->add(0.5, 0, 0.5);
    }

    public function remove(Vector3 $vector3): void {
        $vector3 = $vector3->floor();
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

    public function getParentConnections(): array{
        return $this->parentConnections;
    }

    public function calculateParentConnections(): void {
        $this->parentConnections = [];
        $vector3 = $this->vector3->floor();
        foreach(RoadNetwork::getAll() as $connections) {
            foreach($connections->getAll() as $connection) {
                $connection = $connection->floor();
                if($connection->equals($vector3)) {
                    $this->parentConnections[] = $connection;
                }
            }
        }
    }

    public function toArray(): array {
        $lanes = [];
        foreach($this->lanes as $hash => $vector3) {
            $lanes[$hash] = World::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
        }
        return [
            array_map(function(Vector3 $vector3): int {
                return World::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
            }, $this->connections),
            $this->getType(),
            $lanes,
        ];
    }
}