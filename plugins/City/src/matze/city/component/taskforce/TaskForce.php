<?php

declare(strict_types=1);

namespace matze\city\component\taskforce;

use matze\city\component\vehicle\controller\PathfinderController;
use matze\city\component\vehicle\controller\TaskForceController;
use matze\city\component\vehicle\VehicleEntity;
use matze\city\tool\streetmapper\pathfinder\result\PathResult;
use matze\city\tool\streetmapper\pathfinder\StreetPathfinder;
use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\entity\Location;
use pocketmine\world\Position;
use pocketmine\world\World;

class TaskForce {
    public const TYPE_FIRE_DEPARTMENT = "fire_department";

    private static array $triggers = [];

    public static function getTriggers(string $type, int $chunkX, int $chunkZ): int {
        return self::$triggers[$type][World::chunkHash($chunkX, $chunkZ)] ?? 0;
    }

    public static function triggerFireDepartment(Position $position): void {
        $chunkX = $position->getFloorX() >> 4;
        $chunkZ = $position->getFloorZ() >> 4;
        $triggers = self::getTriggers(self::TYPE_FIRE_DEPARTMENT, $chunkX, $chunkZ) + 1;
        self::$triggers[self::TYPE_FIRE_DEPARTMENT][World::chunkHash($chunkX, $chunkZ)] = $triggers;

        if($triggers === 1 || $triggers % 3 === 0) {
            $connection = RoadNetwork::getRandomConnectionInRadius($position, random_int(50, 90));
            if($connection === null) {
                self::$triggers[self::TYPE_FIRE_DEPARTMENT][World::chunkHash($chunkX, $chunkZ)] = 0;
                return;
            }
            StreetPathfinder::findPath(Position::fromObject($connection->getVector3(), $position->getWorld()), $position, function(?PathResult $result) use ($position): void {
                if($result === null) {
                    return;
                }
                $entity = new VehicleEntity(Location::fromObject($result->shiftNode(), $position->getWorld(), 0, 0));
                $entity->setController(new TaskForceController($result, $position));
                $entity->spawnToAll();
            });
        }
    }
}