<?php

declare(strict_types=1);

namespace matze\city;

use matze\city\session\Session;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\world\ChunkLoadEvent;

class EventListener implements Listener {
    public function onPlayerLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $session = Session::get($player);
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $event->setJoinMessage("");
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $event->setQuitMessage("");

        Session::remove($player);
    }

    public function onChunkLoad(ChunkLoadEvent $event): void {
        $event->getWorld()->registerChunkLoader(City::getChunkLoader(), $event->getChunkX(), $event->getChunkZ());
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void{
        $event->cancel();
    }
}