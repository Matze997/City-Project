<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\item;

use matze\city\session\Session;
use matze\city\tool\streetmapper\player\StreetMappingSession;
use matze\city\util\VanillaSounds;
use matze\item\action\InteractItemAction;
use matze\item\action\ItemAction;
use matze\item\ModifiedItem;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class ClearCurrentStreetEntityItem extends ModifiedItem {
    protected function getInitialItem(): Item{
        return VanillaBlocks::STONECUTTER()->asItem()->setCustomName("§r§aClear Current Street Entity Selection");
    }

    public function interact(InteractItemAction $action): ItemAction{
        StreetMappingSession::get($action->getPlayer())->clearCurrentStreetEntity();
        Session::get($action->getPlayer())->playSound(VanillaSounds::RANDOM_LEVELUP, 2.0);
        return $action->cancel();
    }
}