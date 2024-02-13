<?php

declare(strict_types=1);

namespace matze\city\session\scheduler;

use matze\city\City;
use matze\city\network\packet\CustomMapItemDataPacket;
use matze\city\session\Session;
use pmmp\thread\ThreadSafeArray;
use pocketmine\block\Block;
use pocketmine\block\Flower;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\TallGrass;
use pocketmine\block\utils\CopperOxidation;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

class RenderMapTask extends SessionScheduler {
    public static bool $finished = true;

    public const ORIENTATION_POSITIONS = [
        Facing::NORTH => [62, 1],
        Facing::SOUTH => [62, 122],
        Facing::EAST => [122, 62],
        Facing::WEST => [1, 62],
    ];

    public const ORIENTATION_DESIGNS = [
        Facing::NORTH => [//North
            [1, 0, 0, 0, 1],
            [1, 1, 0, 0, 1],
            [1, 0, 1, 0, 1],
            [1, 0, 0, 1, 1],
            [1, 0, 0, 0, 1],
        ],
        Facing::SOUTH => [//South
            [1, 1, 1, 1, 1],
            [1, 0, 0, 0, 0],
            [1, 1, 1, 1, 1],
            [0, 0, 0, 0, 1],
            [1, 1, 1, 1, 1],
        ],
        Facing::EAST => [//East
            [1, 1, 1, 1, 1],
            [1, 0, 0, 0, 0],
            [1, 1, 1, 1, 0],
            [1, 0, 0, 0, 0],
            [1, 1, 1, 1, 1],
        ],
        Facing::WEST => [//West
            [1, 0, 0, 0, 1],
            [1, 0, 0, 0, 1],
            [1, 0, 0, 0, 1],
            [1, 0, 1, 0, 1],
            [1, 1, 0, 1, 1],
        ],
    ];

    public const FACE = [
        [1, 1, 1, 1, 1, 1, 1, 1],
        [1, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 1, 0, 0, 1, 0, 1],
        [1, 0, 1, 0, 0, 1, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 1, 1, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1],
        [1, 1, 1, 1, 1, 1, 1, 1],
    ];

    public function tick(): void{
        $speed = $this->getSession()->getAverageMovementSpeed();
        $interval = match (true) {
            ($speed > 0.4) => 3,
            ($speed > 0.2) => 15,
            default => 30,
        };
        if(!self::$finished || Server::getInstance()->getTick() % $interval !== 0) {
            return;
        }

        self::$finished = false;

        $id = $this->getSession()->getId();
        $position = $this->getPlayer()->getLocation();
        Server::getInstance()->getAsyncPool()->submitTask(new class($id, $position->getFloorX(), $position->getFloorZ(), (int)floor($position->getYaw()), $this->getSession()->getMapSize(), ThreadSafeArray::fromArray($this->getChunks())) extends AsyncTask {
            public function __construct(
                private int $player,
                private int $x,
                private int $z,
                private int $yaw,
                private int $size,
                private ThreadSafeArray $chunks,
            ){}

            public function onRun(): void{
                $chunks = [];
                foreach($this->chunks as $hash => $chunk) {
                    $chunks[$hash] = FastChunkSerializer::deserializeTerrain($chunk);
                }

                $baseVector3 = (new Vector3($this->x - $this->size / 2, 0, $this->z - $this->size / 2))->floor();

                $fallback = (new Color(0, 0, 0));

                $image = imagecreatetruecolor(128, 128);

                // Render blocks and other visuals
                for ($y = 0; $y < $this->size; ++$y) {
                    for ($x = 0; $x < $this->size; ++$x) {
                        $position = $baseVector3->add($x, 0, $y);
                        $floorX = $position->getFloorX();
                        $floorZ = $position->getFloorZ();
                        $chunkX = $floorX >> 4;
                        $chunkZ = $floorZ >> 4;
                        $hash = World::chunkHash($chunkX, $chunkZ);

                        if(!isset($chunks[$hash])) {
                            $color = $fallback;
                        } else {
                            /** @var Chunk $chunk */
                            $chunk = $chunks[$hash];
                            $block = RuntimeBlockStateRegistry::getInstance()->fromStateId($chunk->getBlockStateId($floorX, $chunk->getHighestBlockAt($floorX, $floorZ), $floorZ));
                            $color = $this->blockToColor($block);
                        }

                        $index = imagecolorallocate($image, $color->getR(), $color->getG(), $color->getB());
                        if($index !== false) {
                            imagesetpixel($image, $x, $y, $index);
                        }
                    }
                }

                $playerIcon = City::getAsset("direction_icon");
                $playerIcon = imagerotate($playerIcon, $this->yaw, imagecolorallocatealpha($playerIcon, 0, 0, 0, 0));
                imagecopymerge($image, $playerIcon, 60, 60, 0, 0, 8, 8, 100);

                $serializer = new BinaryStream();
                for ($y = 0; $y < $this->size; ++$y) {
                    for ($x = 0; $x < $this->size; ++$x) {
                        $rgb = imagecolorat($image, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        $color = (new Color($r, $g, $b))->toRGBA();
                        $color |= ($r << 16) | ($g << 8) | $b | (255 << 24);
                        $serializer->putUnsignedVarInt(Binary::flipIntEndianness($color));
                    }
                }
                $this->setResult($serializer->getBuffer());
            }

            public function blockToColor(Block $block): Color {
                if($block->isSameState(VanillaBlocks::STONE()) || $block->isSameState(VanillaBlocks::STONE_STAIRS()) || $block->isSameState(VanillaBlocks::STONE_SLAB())) {
                    return new Color(132, 132, 132);
                }
                if($block->isSameState(VanillaBlocks::ANDESITE())) {
                    return new Color(119, 119, 119);
                }
                if($block->isSameState(VanillaBlocks::POLISHED_ANDESITE())) {
                    return new Color(144, 144, 144);
                }
                if($block->isSameState(VanillaBlocks::SMOOTH_BASALT())) {
                    return new Color(138, 105, 92);
                }
                if($block->isSameState(VanillaBlocks::SPRUCE_SLAB()) || $block->isSameState(VanillaBlocks::SPRUCE_PLANKS()) || $block->isSameState(VanillaBlocks::SPRUCE_STAIRS())) {
                    return new Color(113, 89, 59);
                }
                if($block->isSameState(VanillaBlocks::BIRCH_PLANKS()) || $block->isSameState(VanillaBlocks::BIRCH_SLAB()) || $block->isSameState(VanillaBlocks::BIRCH_STAIRS())) {
                    return new Color(168, 144, 89);
                }
                if($block->isSameState(VanillaBlocks::BRICKS()) || $block->isSameState(VanillaBlocks::BRICK_SLAB()) || $block->isSameState(VanillaBlocks::BRICK_STAIRS())) {
                    return new Color(124, 78, 38);
                }
                if($block->isSameState(VanillaBlocks::CHERRY_LOG()->setStripped(true))) {
                    return new Color(185, 81, 65);
                }
                if($block->isSameState(VanillaBlocks::CHERRY_TRAPDOOR())) {
                    return new Color(141, 141, 141);
                }
                if($block->isSameState(VanillaBlocks::SAND())) {
                    return new Color(195, 174, 136);
                }
                if($block->isSameState(VanillaBlocks::COBBLESTONE())) {
                    return new Color(113, 109, 103);
                }
                if($block->isSameState(VanillaBlocks::CUT_COPPER()->setWaxed(true)->setOxidation(CopperOxidation::EXPOSED))) {
                    return new Color(222, 148, 131);
                }
                if($block instanceof Flower || $block instanceof TallGrass || $block->isSameState(VanillaBlocks::GRASS())) {
                    return new Color(0, 154, 23);
                }
                if($block->isSameState(VanillaBlocks::SMOOTH_STONE())) {
                    return new Color(181, 181, 181);
                }
                if($block->isSameState(VanillaBlocks::CUT_SANDSTONE())) {
                    return new Color(211, 190, 150);
                }
                if($block->isSameState(VanillaBlocks::DARK_PRISMARINE()) || $block->isSameState(VanillaBlocks::DARK_PRISMARINE_SLAB()) || $block->isSameState(VanillaBlocks::DARK_PRISMARINE_STAIRS())) {
                    return new Color(97, 160, 147);
                }
                if($block->isSameState(VanillaBlocks::QUARTZ()) || $block->isSameState(VanillaBlocks::QUARTZ_SLAB()) || $block->isSameState(VanillaBlocks::QUARTZ_STAIRS())) {
                    return new Color(225, 217, 196);
                }
                if(str_contains($block->getName(), "Water")) {
                    return new Color(64, 64, 255);
                }
                if($block->isSameState(VanillaBlocks::WOOL()->setColor(DyeColor::WHITE))) {
                    return new Color(201, 201, 201);
                }
                if($block->isSameState(VanillaBlocks::WOOL()->setColor(DyeColor::GRAY))) {
                    return new Color(69, 69, 69);
                }
                if($block->isSameState(VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW))) {
                    return new Color(214, 182, 31);
                }
                if($block->isSameState(VanillaBlocks::CONCRETE()->setColor(DyeColor::GRAY))) {
                    return new Color(108, 106, 102);
                }
                if($block->isSameState(VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::BLACK))) {
                    return new Color(51, 51, 51);
                }
                if($block->isSameState(VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN))) {
                    return new Color(106, 117, 87);
                }
                return new Color(0, 0, 0);
            }

            public function onCompletion(): void{
                //Session::getUnsafe($this->player)?->getPlayer()->getNetworkSession()->sendDataPacket(CustomMapItemDataPacket::create($this->player, $this->getResult()));
                RenderMapTask::$finished = true;
            }
        });
    }

    public function getChunks(): array {
        $player = $this->getPlayer();
        $chunks = [];
        $halfSize = (int)($this->getSession()->getMapSize() / 2);
        $location = $player->getLocation();
        for ($x = ($location->getFloorX() - $halfSize) >> 4; $x <= ($location->getFloorX() + $halfSize) >> 4; $x++) {
            for ($z = ($location->getFloorZ() - $halfSize) >> 4; $z <= ($location->getFloorZ() + $halfSize) >> 4; $z++) {
                $chunk = $location->world->getChunk($x, $z);
                if($chunk === null){
                    continue;
                }
                $chunks[World::chunkHash($x, $z)] = FastChunkSerializer::serializeTerrain($chunk);
            }
        }
        return $chunks;
    }

    public function getTickInterval(): int{
        return 1;
    }
}