<?php

declare(strict_types=1);

namespace matze\item;

use Closure;
use matze\item\action\AttackEntityAction;
use pocketmine\item\Item;
use pocketmine\player\Player;
use matze\item\action\ConsumeItemAction;
use matze\item\action\DropItemAction;
use matze\item\action\HeldItemAction;
use matze\item\action\InteractEntityAction;
use matze\item\action\InteractItemAction;
use matze\item\action\ItemAction;
use matze\item\action\TickAction;
use matze\item\action\UnheldItemAction;

abstract class ModifiedItem {
    protected string $id;
    protected Item $initialItem;

    public function __construct(){
        $this->id = $this::class;
        $this->initialItem = clone $this->getInitialItem();
        $this->initialItem->setNamedTag($this->initialItem->getNamedTag()->setString(ModifiedItemManager::ITEM_TAG, $this->id));
    }

    abstract protected function getInitialItem(): Item;

    public function getItem(): Item{
        return $this->initialItem;
    }

    public function getId(): string{
        return $this->id;
    }

    public function getCustomName(Player $player): ?string {
        return null;
    }

    public function interact(InteractItemAction $action): ItemAction {
        return $action->continue();
    }

    public function drop(DropItemAction $action): ItemAction {
        return $action->continue();
    }

    public function consume(ConsumeItemAction $action): ItemAction {
        return $action->continue();
    }

    public function held(HeldItemAction $action): ItemAction {
        return $action->continue();
    }

    public function unheld(UnheldItemAction $action): ItemAction {
        return $action->continue();
    }

    public function interactEntity(InteractEntityAction $action): ItemAction {
        return $action->continue();
    }

    public function attackEntity(AttackEntityAction $action): ItemAction {
        return $action->continue();
    }

    public function tick(TickAction $action): ItemAction {
        return $action->cancel();
    }

    public static function get(Player $player): ?Item {
        $modifiedItem = ModifiedItemManager::getByClass(static::class);
        $item = $modifiedItem?->getItem();
        if($item === null) {
            return null;
        }
        if(($customName = $modifiedItem?->getCustomName($player)) !== null) {
            $item->setCustomName($customName);
        }
        return $item;
    }

    public static function give(Player $player, ?int $slot = null): void {
        $item = self::get($player);
        if($item === null) {
            return;
        }
        $inventory = $player->getInventory();
        if($slot === null) {
            $inventory->addItem($item);
        } else {
            $inventory->setItem($slot, $item);
        }
    }
}