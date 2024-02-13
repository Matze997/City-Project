<?php

declare(strict_types=1);

namespace matze\item;

use matze\item\action\AttackEntityAction;
use matze\item\action\ConsumeItemAction;
use matze\item\action\DropItemAction;
use matze\item\action\HeldItemAction;
use matze\item\action\InteractEntityAction;
use matze\item\action\InteractItemAction;
use matze\item\action\UnheldItemAction;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\player\Player;

class EventListener implements Listener {
    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();
        $action = $event->getAction();
        $face = $event->getFace();

        $itemAction = new InteractItemAction($player, $item, $block, $action, $face);
        $itemAction->setCancelled($event->isCancelled());
        if(!$itemAction->hasCooldown()) {
            if(ModifiedItemManager::getByItem($item)?->interact($itemAction)->isCancelled()) {
                $event->cancel();
            }
        } else {
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $itemAction = new DropItemAction($player, $item);
        $itemAction->setCancelled($event->isCancelled());
        if(ModifiedItemManager::getByItem($item)?->drop($itemAction)->isCancelled()) {
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $itemAction = new ConsumeItemAction($player, $item);
        $itemAction->setCancelled($event->isCancelled());
        if(ModifiedItemManager::getByItem($item)?->consume($itemAction)->isCancelled()) {
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $itemAction = new HeldItemAction($player, $item, $event->getSlot());
        $itemAction->setCancelled($event->isCancelled());
        if(ModifiedItemManager::getByItem($item)?->held($itemAction)->isCancelled()) {
            $event->cancel();
        } else {
            ModifiedItemManager::getByItem($player->getInventory()->getItemInHand())?->unheld(new UnheldItemAction($player, $item));
        }

        if(!$event->isCancelled()) {
            unset(ModifiedItemManager::$ignoredModifiedItems[$player->getName()]);
        }
    }

    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onPlayerEntityInteract(PlayerEntityInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        $entity = $event->getEntity();

        $itemAction = new InteractEntityAction($player, $item, $entity);
        $itemAction->setCancelled($event->isCancelled());
        if(ModifiedItemManager::getByItem($item)?->interactEntity($itemAction)->isCancelled()) {
            $event->cancel();
        }
    }
    /**
     * @priority MONITOR
     * @handleCancelled true
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $entity = $event->getEntity();

        if($damager instanceof Player) {
            $item = $damager->getInventory()->getItemInHand();

            $itemAction = new AttackEntityAction($damager, $item, $entity);
            $itemAction->setCancelled($event->isCancelled());
            if(ModifiedItemManager::getByItem($item)?->attackEntity($itemAction)->isCancelled()) {
                $event->cancel();
            }
        }
    }
}