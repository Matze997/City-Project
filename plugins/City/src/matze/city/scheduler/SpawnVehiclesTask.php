<?php

declare(strict_types=1);

namespace matze\city\scheduler;

use matze\city\component\vehicle\VehicleEntity;
use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class SpawnVehiclesTask extends Task {
    public const VEHICLE_MAX_LIMIT = 16;

    public const ATTEMPTS_PER_RUN = 1;

    public function onRun(): void{
        $players = Server::getInstance()->getOnlinePlayers();
        if(count($players) <= 0) {
            return;
        }
        $player = $players[array_rand($players)];
        $vehicles = count(array_filter($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy(200, 365, 200)), function(Entity $entity): bool {
            return $entity instanceof VehicleEntity;
        }));
        if($vehicles >= self::VEHICLE_MAX_LIMIT) {
            return;
        }

        $location = $player->getLocation();
        for($i = 1; $i <= self::ATTEMPTS_PER_RUN; $i++) {
            $position = $location->add(
                random_int(-200, 200),
                0,
                random_int(-200, 200),
            );
            $chunkX = $position->getFloorX() >> 4;
            $chunkZ = $position->getFloorZ() >> 4;
            $connections = RoadNetwork::getConnectionsByChunk($chunkX, $chunkZ);
            if(count($connections) <= 0) {
                continue;
            }
            $connection = $connections[array_rand($connections)];

            if($player->getWorld()->getNearestEntity($position, 20, Player::class) instanceof Player) {
                continue;
            }

            $location = Location::fromObject($connection->getVector3(), $player->getWorld(), 0, 0);
            (new VehicleEntity($location))->spawnToAll();
            break;
        }
    }
}