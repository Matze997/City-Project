<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\item;

use matze\item\action\AttackEntityAction;
use matze\item\action\DropItemAction;
use matze\item\action\InteractItemAction;
use matze\item\action\ItemAction;
use matze\item\ModifiedItem;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;

class AddLaneSwitchMarkerItem extends ModifiedItem {
    protected function getInitialItem(): Item{
        return VanillaBlocks::STONE()->asItem()->setCustomName("§r§aAdd Lane Switch");
    }

    public function attackEntity(AttackEntityAction $action): ItemAction{
        //TODO: Selection
        return $action->cancel();
    }

    public function interact(InteractItemAction $action): ItemAction{
        //TODO: Clear cache
        return $action->cancel();
    }

    public function drop(DropItemAction $action): ItemAction{
        //TODO: Save
        return $action->cancel();
    }
}