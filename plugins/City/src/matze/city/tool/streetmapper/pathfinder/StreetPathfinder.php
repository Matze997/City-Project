<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\pathfinder;

use matze\city\tool\streetmapper\pathfinder\result\PathResult;
use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\util\VectorUtils;
use pmmp\thread\ThreadSafeArray;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\Position;
use pocketmine\world\World;

class StreetPathfinder extends AsyncTask {
    private const SIDES = [
        [0, 1],
        [1, 0],
        [0, -1],
        [-1, 0],
    ];

    private ThreadSafeArray $chunks;

    public string $world;

    private int $start;
    private int $target;

    public ?string $chunk;

    public function __construct(
        Vector3 $startVector,
        Vector3 $targetVector,
        World $world,
        $onCompletion,
        private float $timeout = 10.0,
        private float $width = 1.0,
        private float $height = 1.0,
    ){
        $this->chunks = new ThreadSafeArray();
        $this->world = $world->getFolderName();
        $this->start = World::blockHash($startVector->getFloorX(), $startVector->getFloorY(), $startVector->getFloorZ());
        $this->target = World::blockHash($targetVector->getFloorX(), $targetVector->getFloorY(), $targetVector->getFloorZ());
        $this->storeLocal("onCompletion", $onCompletion);
    }

    public static function findPath(Position $start, Vector3 $target, \Closure $onCompletion): void {
        Server::getInstance()->getAsyncPool()->submitTask(new self($start, $target, $start->getWorld(), $onCompletion));
    }

    public function onRun(): void{
        RoadNetwork::load();

        World::getBlockXYZ($this->start, $startX, $startY, $startZ);
        World::getBlockXYZ($this->target, $targetX, $targetY, $targetZ);

        $openList = [];
        $closedList = [];

        $startNode = Node::fromVector3(new Vector3($startX, $startY, $startZ));
        $startNode->setG(0.0);

        $targetNode = Node::fromVector3(new Vector3($targetX, $targetY, $targetZ));

        if($startNode->equals($targetNode)) {
            return;
        }

        $openList[$startNode->getHash()] = $startNode;

        $bestNode = null;

        $result = new PathResult();

        $startTime = microtime(true);
        while((microtime(true) - $startTime) < $this->timeout) {
            $key = $this->getLowestFCost($openList);
            if($key === null){
                break;
            }
            /** @var Node $currentNode */
            $currentNode = $openList[$key];
            unset($openList[$currentNode->getHash()]);
            $closedList[$currentNode->getHash()] = $currentNode;

            if($currentNode->getHash() === $targetNode->getHash()) {
                $targetNode->setParentNode($currentNode);
                $result->addNode($targetNode);
                break;
            }

            $connections = RoadNetwork::getRoadMarkerConnections($currentNode);
            if($connections !== null) {
                $positions = [];
                foreach($connections->getAll() as $vector3) {
                    $positions[World::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ())] = $vector3;
                }
                foreach($connections->getLanes() as $vector3) {
                    $positions[World::blockHash($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ())] = $vector3;
                }
                if(count($positions) <= 0) {
                    continue;
                }

                // Pathfinding through road connections
                foreach($positions as $connection) {
                    $node = Node::fromVector3($connection);
                    if(isset($closedList[$node->getHash()])) {
                        continue;
                    }
                    if(!isset($openList[$node->getHash()]) || $currentNode->getG() < $node->getG()) {
                        $node->setG($currentNode->getG());
                        $node->setH($node->distance($targetNode));
                        $node->setParentNode($currentNode);
                        $openList[$node->getHash()] = $node;
                        if($bestNode === null || $bestNode->getH() > $node->getH()) {
                            $bestNode = $node;
                        }
                    }
                }
            }
        }
        $node = $targetNode->getParentNode();
        if($node === null) {
            $node = $bestNode;
            if($node === null) {
                return;
            }
        }
        $result->addNode($node);

        /*
        $start = null;
        $clear = false;
        while(true) {
            $last = clone $node;
            $node = $node->getParentNode();
            if(!$node instanceof Node) {
                $result->addNode($last);
                break;
            }
            if($start === null) {
                $start = $last;
            }
            if($start !== null && $this->isClearBetweenPoints($start, $node)) {
                $clear = true;
                continue;
            }
            if($clear) {
                $result->addNode($last);
                $clear = false;
                $start = null;
                $node = $last;
            } else {
                $result->addNode($node);
                $start = null;
            }
        }
        */

        while(true) {
            $last = clone $node;
            $node = $node->getParentNode();
            if(!$node instanceof Node) {
                $result->addNode($last);
                break;
            }
            $result->addNode($node);
        }

        $result->nodes = array_reverse($result->nodes);
        $this->setResult($result);
    }

    protected function isSafeToStand(Vector3 $target): bool {
        $halfWidth = $this->width / 2;
        $minY = $target->getFloorY();
        $maxY = (int)floor($target->getY() + $this->height);
        $minX = (int)floor($target->getX() - $halfWidth);
        $maxX = (int)floor($target->getX() + $halfWidth);
        $minZ = (int)floor($target->getZ() - $halfWidth);
        $maxZ = (int)floor($target->getZ() + $halfWidth);
        for($x = $minX; $x <= $maxX; $x++) {
            for($z = $minZ; $z <= $maxZ; $z++) {
                for($y = $minY; $y <= $maxY; $y++) {
                    $block = $this->getBlockAt($x, $y, $z);
                    if(!$this->isPassable($block)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    protected function isPassable(Block $block): bool {
        return $block instanceof Air;
    }

    protected function getLowestFCost(array $openListCurrent): ?int {
        $openList = [];
        foreach($openListCurrent as $hash => $node) {
            $openList[$hash] = $node->getF();
        }
        asort($openList);
        return array_key_first($openList);
    }

    protected function getBlock(Vector3 $vector3): Block {
        return $this->getBlockAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ());
    }

    protected function getBlockAt(int $x, int $y, int $z): Block {
        return RuntimeBlockStateRegistry::getInstance()->fromStateId($this->getChunk($x >> 4, $z >> 4)?->getBlockStateId($x, $y, $z) ?? 0);
    }

    protected function getChunk(int $x, int $z): ?Chunk{
        $hash = World::chunkHash($x, $z);
        if(!isset($this->chunks[$hash])) {
            $this->publishProgress($hash);
            while (!isset($this->task->chunk)) {
                if($this->isTerminated()) {
                    return null;
                }
            }
            if($this->task->chunk === "") {
                return null;
            }
            $this->chunks[$hash] = FastChunkSerializer::deserializeTerrain($this->task->chunk);
            unset($this->task->chunk);
        }
        return $this->chunks[$hash];
    }

    public function onCompletion() : void{
        $onCompletion = $this->fetchLocal("onCompletion");
        ($onCompletion)($this->getResult());
    }

    public function onProgressUpdate($progress): void{
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->world);
        if($world === null) {
            $this->chunk = "";
            return;
        }
        World::getXZ($progress, $chunkX, $chunkZ);
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if($chunk === null) {
            $this->chunk = "";
            return;
        }
        $this->chunk = FastChunkSerializer::serializeTerrain($chunk);
    }

    protected function isClearBetweenPoints(Vector3 $vec1, Vector3 $vec2): bool {
        if($vec1->getFloorY() !== $vec2->getFloorY()) {
            return false;
        }
        foreach(VectorUtils::getPositionsBetween($vec1, $vec2) as $between) {
            if(!$this->isSafeToStand($between)) {
                return false;
            }
        }
        return true;
    }
}