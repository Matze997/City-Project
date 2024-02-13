<?php

declare(strict_types=1);

namespace matze\city\session;

use matze\city\component\vehicle\controller\TaskForceController;
use matze\city\component\vehicle\VehicleEntity;
use matze\city\item\vanilla\FilledMap;
use matze\city\session\scheduler\RenderMapTask;
use matze\city\session\scheduler\SessionScheduler;
use matze\city\tool\streetmapper\player\StreetMappingSession;
use matze\city\world\sound\PlaySound;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class Session {
    /** @var Session[]  */
    private static array $sessions = [];

    /**
     * @return Session[]
     */
    public static function getAll(): array{
        return array_filter(self::$sessions, function(Session $session): bool {
            return $session->isInitialized();
        });
    }

    public static function get(Player $player): Session {
        return self::$sessions[$player->getXuid()] ??= new self($player);
    }

    public static function getUnsafe(Player|string|int $player): ?Session {
        if(is_string($player)) {
            $player = Server::getInstance()->getPlayerExact($player) ?? (array_filter(Server::getInstance()->getOnlinePlayers(), function(Player $onlinePlayer) use ($player): bool {
                return $onlinePlayer->getXuid() === $player;
            })[0] ?? null);
        } elseif(is_int($player)) {
            foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                if($onlinePlayer->getId() === $player) {
                    $player = $onlinePlayer;
                    break;
                }
            }
        }
        if(!$player instanceof Player) {
            return null;
        }
        return self::get($player);
    }

    public static function remove(Player $player): void {
        self::get($player)->close();
        unset(self::$sessions[$player->getXuid()]);
    }

    private bool $initialized = false;

    private StreetMappingSession $streetMappingSession;

    /** @var SessionScheduler[]  */
    private array $scheduler = [];

    private array $movement = [];
    private Vector3 $lastPosition;

    public function __construct(
        private Player $player,
    ){
        $this->streetMappingSession = new StreetMappingSession($this->player);
        $this->initialize();
    }

    private function initialize(): void {
        $player = $this->getPlayer();

        $this->lastPosition = $player->getPosition();

        //TODO
        /*
        $map = FilledMap::get();
        $map->setMapId($player->getId());
        $player->getOffHandInventory()->setItem(0, $map);
        $this->addScheduler(new RenderMapTask($player, $this));
        */

        $this->initialized = true;
    }

    private function close(): void {
        //TODO
        $this->initialized = false;
    }

    public function update(int $tick): void {
        foreach($this->scheduler as $interval => $scheduler) {
            if($tick % $interval !== 0) {
                continue;
            }
            foreach($scheduler as $task) {
                $task->tick();
            }
        }

        $position = $this->player->getPosition();
        $this->movement[] = $position->distance($this->lastPosition);
        if(count($this->movement) > 20) {
            array_shift($this->movement);
        }
        $this->lastPosition = $position;

        if(Server::getInstance()->isOp($this->player->getName())) {
            $this->player->sendTip(implode("\n", [
                Server::getInstance()->getTicksPerSecondAverage()." (".Server::getInstance()->getTickUsageAverage()."%)",
                "Vehicles: ".count(array_filter($this->player->getWorld()->getEntities(), function(Entity $entity): bool {
                    return $entity instanceof VehicleEntity;
                })),
                "Task Force: ".count(array_filter($this->player->getWorld()->getEntities(), function(Entity $entity): bool {
                    return $entity instanceof VehicleEntity && $entity->getController() instanceof TaskForceController;
                })),
            ]));

            $this->getStreetMappingSession()->update($tick);
        }
    }

    public function isInitialized(): bool{
        return $this->initialized;
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getId(): int {
        return $this->player->getId();
    }

    public function addScheduler(SessionScheduler $scheduler): void {
        $this->scheduler[$scheduler->getTickInterval()][] = $scheduler;
    }

    public function getAverageMovementSpeed(): float {
        return array_sum($this->movement) / max(1, count($this->movement));
    }

    public function getMapSize(): int {
        return 128;//This value should be probably not changed
    }

    public function getStreetMappingSession(): StreetMappingSession{
        return $this->streetMappingSession;
    }

    public function playSound(string $sound, float $pitch = 1.0, float $volume = 1.0, array $targets = null, ?Vector3 $position = null): void{
        $this->player->getWorld()->addSound(($position ?? $this->player->getPosition()), new PlaySound($sound, $volume, $pitch), ($targets ?? [$this->player]));
    }
}