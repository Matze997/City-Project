<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper;

use matze\city\City;
use matze\city\tool\streetmapper\entity\RoadMarkerEntity;
use matze\city\tool\streetmapper\item\AddLaneSwitchMarkerItem;
use matze\city\tool\streetmapper\item\AddRoadMarkerItem;
use matze\city\tool\streetmapper\item\ClearCurrentStreetEntityItem;
use matze\city\tool\streetmapper\item\RemoveRoadMarkerItem;
use matze\item\ModifiedItemManager;
use pocketmine\entity\Location;
use pocketmine\event\EventPriority;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\Server;

class StreetMapperTool {
    public static function init(): void {
        ModifiedItemManager::register(new AddRoadMarkerItem());
        ModifiedItemManager::register(new ClearCurrentStreetEntityItem());
        ModifiedItemManager::register(new RemoveRoadMarkerItem());
        ModifiedItemManager::register(new AddLaneSwitchMarkerItem());

        RoadNetwork::load();

        Server::getInstance()->getCommandMap()->register(City::getInstance()->getName(), new StreetMapperCommand());
        if(City::$DEBUG) {
            Server::getInstance()->getPluginManager()->registerEvent(ChunkLoadEvent::class, function(ChunkLoadEvent $event): void {
                $connections = RoadNetwork::getConnectionsByChunk($event->getChunkX(), $event->getChunkZ());
                foreach($connections as $connection) {
                    $location = Location::fromObject($connection->getVector3()->add(0.5, 0, 0.5), $event->getWorld(), 0, 0);
                    (new RoadMarkerEntity($location))->spawnToAll();

                    foreach($connection->getAll() as $connected) {
                        if(RoadNetwork::getRoadMarkerConnections($connected) === null) {
                            $location = Location::fromObject($connected, $event->getWorld(), 0, 0);
                            (new RoadMarkerEntity($location))->spawnToAll();
                        }
                    }
                }
            }, EventPriority::NORMAL, City::getInstance());
        }
    }
}