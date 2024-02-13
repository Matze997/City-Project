<?php

declare(strict_types=1);

namespace matze\item;

use matze\item\action\TickAction;
use pocketmine\item\Item;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class ModifiedItemManager {
    public const ITEM_TAG = "custom_item";

    /** @var ModifiedItem[]  */
    private static array $items = [];

    public static array $ignoredModifiedItems = [];

    public static function init(Plugin $plugin): void {
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $plugin);

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            foreach(Server::getInstance()->getOnlinePlayers() as $player){
                $item = $player->getInventory()->getItemInHand();
                $modifiedItem = self::getByItem($item);
                if(($modifiedItem !== null) && !isset(self::$ignoredModifiedItems[$player->getName()][$modifiedItem->getId()])) {
                    $result = $modifiedItem->tick(new TickAction($player, $item));
                    if($result->isCancelled()) {
                        self::$ignoredModifiedItems[$player->getName()][$modifiedItem->getId()] = true;
                    }
                }
            }
        }), 1);
    }

    public static function getAll(): array{
        return self::$items;
    }

    public static function register(ModifiedItem $item): void {
        self::$items[$item->getId()] = $item;
    }

    public static function getById(string $id): ?ModifiedItem {
        return self::$items[$id] ?? null;
    }

    public static function getByClass(string $class): ?ModifiedItem {
        foreach(self::getAll() as $item) {
            if($item::class === $class) return $item;
        }
        return null;
    }

    public static function getByItem(Item $item): ?ModifiedItem {
        $tag = $item->getNamedTag()->getTag(self::ITEM_TAG);
        if($tag === null) {
            return null;
        }
        return self::getById($tag->getValue());
    }
}