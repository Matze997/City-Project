<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\item;

use matze\city\session\Session;
use matze\city\tool\streetmapper\entity\RoadMarkerEntity;
use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\util\VanillaSounds;
use matze\item\action\InteractEntityAction;
use matze\item\action\InteractItemAction;
use matze\item\action\ItemAction;
use matze\item\ModifiedItem;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;

class RemoveRoadMarkerItem extends ModifiedItem {
    protected function getInitialItem(): Item{
        return VanillaBlocks::WOOL()->setColor(DyeColor::RED)->asItem()->setCustomName("§r§aRemove Road Marker");
    }

    public function interact(InteractItemAction $action): ItemAction{
        return $action->cancel();
    }

    public function interactEntity(InteractEntityAction $action): ItemAction{
        $entity = $action->getEntity();
        if(!$entity instanceof RoadMarkerEntity) {
            return $action->cancel();
        }
        RoadNetwork::removeRoadMarker($entity->getPosition());
        $entity->flagForDespawn();
        Session::get($action->getPlayer())->playSound(VanillaSounds::RANDOM_LEVELUP, 0.4);
        return $action->cancel();
    }
}