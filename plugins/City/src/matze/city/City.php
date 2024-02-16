<?php

declare(strict_types=1);

namespace matze\city;

use customiesdevs\customies\entity\CustomiesEntityFactory;
use matze\city\command\tool\PlaySoundCommand;
use matze\city\command\tool\TestCommand;
use matze\city\component\vehicle\VehicleEntity;
use matze\city\item\vanilla\FilledMap;
use matze\city\network\packet\CustomMapItemDataPacket;
use matze\city\scheduler\SpawnVehiclesTask;
use matze\city\session\Session;
use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\tool\streetmapper\StreetMapperTool;
use matze\city\world\format\io\LevelDB;
use matze\item\ModifiedItemManager;
use matze\worldmanager\WorldManager;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\ResourceProvider;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\ChunkLoader;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\world\format\io\WritableWorldProviderManagerEntry;

class City extends PluginBase {
    public const PREFIX = "§8§l»§r§a ";

    public static bool $DEBUG = true;

    private static self $instance;

    private static ChunkLoader $chunkLoader;

    public function __construct(PluginLoader $loader, Server $server, PluginDescription $description, string $dataFolder, string $file, ResourceProvider $resourceProvider){
        parent::__construct($loader, $server, $description, $dataFolder, $file, $resourceProvider);
        //$this->registerBullshit();
    }

    protected function onEnable(): void{
        self::$instance = $this;
        self::$chunkLoader = new class() implements ChunkLoader {};

        if(self::$DEBUG) {
            Server::getInstance()->getLogger()->warning("Debug is enabled!");
        }

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            $tick = Server::getInstance()->getTick();
            foreach(Session::getAll() as $session) {
                $session->update($tick);
            }

            foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world) {
                $reflection = new \ReflectionClass($world);
                $property = $reflection->getProperty("scheduledBlockUpdateQueue");
                $property->setAccessible(true);
                $value = $property->getValue($world);
                while(!$value->isEmpty()) {
                    $value->extract();
                }
            }
        }), 1);

        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);

        PacketPool::getInstance()->registerPacket(new CustomMapItemDataPacket());

        ModifiedItemManager::init($this);

        $this->registerVanillaItems();
        $this->registerTools();
        $this->registerCustomEntities();
        $this->registerCommands();

        $this->startSchedulers();
    }

    protected function onDisable(): void{
        RoadNetwork::save();

        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), "timings paste");
    }

    public static function getInstance(): City{
        return self::$instance;
    }

    public static function getChunkLoader(): ChunkLoader{
        return self::$chunkLoader;
    }

    public static function getAsset(string $name): \GdImage {
        return imagecreatefrompng(__DIR__."/../../../../../assets/".$name.".png");
    }

    public static function getDataPath(): string {
        return __DIR__."/../../../../../data/";
    }

    private function registerCommands(): void {
        Server::getInstance()->getCommandMap()->registerAll($this->getName(), [
            // Tool
            new PlaySoundCommand(),
            new TestCommand(),
        ]);
    }

    private function registerVanillaItems(): void {
        $item = new FilledMap(new ItemIdentifier(ItemTypeIds::newId()));
        GlobalItemDataHandlers::getDeserializer()->map(ItemTypeNames::FILLED_MAP, fn() => clone $item);
        StringToItemParser::getInstance()->register("filled_map", fn() => clone $item);
        GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData(ItemTypeNames::FILLED_MAP));
    }

    private function registerCustomEntities(): void {
        $factory = CustomiesEntityFactory::getInstance();
        $factory->registerEntity(VehicleEntity::class, "matadora:car_test");
    }

    private function registerTools(): void {
        StreetMapperTool::init();
    }

    private function startSchedulers(): void {
        $scheduler = $this->getScheduler();
        $scheduler->scheduleRepeatingTask(new SpawnVehiclesTask(), 2);
    }

    private function registerBullshit(): void {
        //TODO: This error message drives me insane
        $leveldb = new WritableWorldProviderManagerEntry(LevelDB::isValid(...), fn(string $path, \Logger $logger) => new LevelDB($path, $logger), LevelDB::generate(...));
        $manager = Server::getInstance()->getWorldManager()->getProviderManager();
        $manager->setDefault($leveldb);
        $manager->addProvider($leveldb, "leveldb", true);
    }
}