<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper;

use matze\city\City;
use matze\city\tool\streetmapper\util\RoadConnections;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\world\World;

class RoadNetwork {
    public const VERSION = "1.3.0";

    /** @var RoadConnections[][]  */
    private static array $connections = [];
    /** @var RoadConnections[]  */
    private static array $idConnectionsCache = [];

    public static int $id = 0;

    public static function load(): void {
        $file = new Config(City::getDataPath()."road_network.json");
        $version = $file->get("version", "Unknown");
        switch($version) {
            case "1.0.0": {
                foreach($file->get("connections", []) as $chunkHash => $connections) {
                    foreach($connections as $hash => $connection) {
                        World::getBlockXYZ($hash, $x, $y, $z);
                        self::$connections[$chunkHash][$hash] = new RoadConnections(new Vector3($x, $y, $z), $connection);
                    }
                }
                break;
            }
            case "1.1.0": {
                foreach($file->get("connections", []) as $chunkHash => $connections) {
                    foreach($connections as $hash => $data) {
                        World::getBlockXYZ($hash, $x, $y, $z);
                        self::$connections[$chunkHash][$hash] = new RoadConnections(new Vector3($x, $y, $z), $data[0]);
                    }
                }
                break;
            }
            case "1.2.0": {
                foreach($file->get("connections", []) as $chunkHash => $connections) {
                    foreach($connections as $hash => $data) {
                        World::getBlockXYZ($hash, $x, $y, $z);
                        self::$connections[$chunkHash][$hash] = new RoadConnections(new Vector3($x, $y, $z), $data[0], $data[1]);
                    }
                }
                break;
            }
            case "1.3.0": {
                foreach($file->get("connections", []) as $chunkHash => $connections) {
                    foreach($connections as $hash => $data) {
                        World::getBlockXYZ($hash, $x, $y, $z);
                        self::$connections[$chunkHash][$hash] = new RoadConnections(new Vector3($x, $y, $z), $data[0], $data[1], $data[2]);
                    }
                }
                break;
            }
            default: {
                City::getInstance()->getLogger()->warning("Could not load road network. Version ".$version." does not exist!");
                return;
            }
        }
        foreach(self::getAll() as $connections) {
            $connections->calculateParentConnections();
        }
    }

    public static function save(): void {
        $file = new Config(City::getDataPath()."road_network.json");
        $file->setAll([]);
        $file->set("version", self::VERSION);

        $connections = [];
        foreach(self::$connections as $chunkHash => $connection) {
            foreach($connection as $hash => $data) {
                $connections[$chunkHash][$hash] = $data->toArray();
            }
        }
        $file->set("connections", $connections);
        $file->save();

        City::getInstance()->getLogger()->info("Successfully saved road network on version ".self::VERSION.".");
    }

    /**
     * @return RoadConnections[]
     */
    public static function getAll(): array{
        $connections = [];
        foreach(self::$connections as $chunkHash => $list) {
            foreach($list as $hash => $connection) {
                $connections[] = $connection;
            }
        }
        return $connections;
    }

    /**
     * @return RoadConnections[]
     */
    public static function getConnectionsByChunk(int $chunkX, int $chunkZ): array {
        return self::$connections[World::chunkHash($chunkX, $chunkZ)] ?? [];
    }

    public static function getConnectionById(int $id): ?RoadConnections {
        if(isset(self::$idConnectionsCache[$id])) {
            return self::$idConnectionsCache[$id];
        }
        foreach(self::getAll() as $connections) {
            if($connections->getId() === $id) {
                return (self::$idConnectionsCache[$id] = $connections);
            }
        }
        return null;
    }

    public static function getRoadMarkerConnections(Vector3 $vector3): ?RoadConnections {
        $x = $vector3->getFloorX();
        $z = $vector3->getFloorZ();
        $hash = World::blockHash($x, $vector3->getFloorY(), $z);
        $chunkHash = World::chunkHash($x >> 4, $z >> 4);
        return self::$connections[$chunkHash][$hash] ?? null;
    }

    public static function addRoadMarker(Vector3 $start, Vector3 $target, int $type = RoadConnections::TYPE_NORMAL_CONNECTION): void {
        $x = $start->getFloorX();
        $z = $start->getFloorZ();
        $hash = World::blockHash($x, $start->getFloorY(), $z);
        $chunkHash = World::chunkHash($x >> 4, $z >> 4);

        $connections = self::$connections[$chunkHash][$hash] ??= new RoadConnections($start->floor(), [], $type);
        $connections->add($target);

        foreach(self::getAll() as $connections) {
            $connections->calculateParentConnections();
        }
    }

    public static function removeRoadMarker(Vector3 $vector3): void {
        $vector3 = $vector3->floor();
        foreach(self::$connections as $chunkHash => $connection) {
            foreach($connection as $hash => $data) {
                foreach($data->getAll() as $target) {
                    if($target->floor()->equals($vector3)) {
                        $data->remove($vector3);
                    }
                }
            }
        }

        $x = $vector3->getFloorX();
        $z = $vector3->getFloorZ();
        $hash = World::blockHash($x, $vector3->getFloorY(), $z);
        $chunkHash = World::chunkHash($x >> 4, $z >> 4);
        unset(self::$connections[$chunkHash][$hash]);

        foreach(self::getAll() as $connections) {
            $connections->calculateParentConnections();
        }
    }

    public static function getRandomConnectionInRadius(Vector3 $center, int $radius): ?RoadConnections {
        $baseDegree = random_int(0, 360);
        for($i = 0; $i <= 360; $i++) {
            $degree = ($baseDegree + $i) % 360;

            $vector3 = $center->add(sin($degree) * $radius, 0, cos($degree) * $radius);
            $connections = self::getConnectionsByChunk($vector3->getFloorX() >> 4, $vector3->getFloorZ() >> 4);
            if(count($connections) <= 0) {
                continue;
            }
            return $connections[array_rand($connections)];
        }
        return null;
    }
}