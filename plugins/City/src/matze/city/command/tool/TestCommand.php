<?php

declare(strict_types=1);

namespace matze\city\command\tool;

use matze\city\City;
use matze\city\command\BaseCommand;
use matze\city\entity\TextEntity;
use matze\city\tool\streetmapper\pathfinder\result\PathResult;
use matze\city\tool\streetmapper\pathfinder\StreetPathfinder;
use matze\city\tool\streetmapper\player\StreetMappingSession;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;

class TestCommand extends BaseCommand {
    public function __construct(){
        parent::__construct("test");
        $this->setPermission("default.op");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player || !$this->testPermission($sender)) {
            return;
        }
        $start = StreetMappingSession::get($sender)->getCurrentStreetEntity()?->getPosition();
        if($start === null) {
            return;
        }
        StreetPathfinder::findPath($start, $sender->getPosition(), function(?PathResult $result) use ($sender): void {
            if($result === null) {
                $sender->sendMessage(City::PREFIX."No path found.");
                return;
            }
            $id = 0;
            $last = null;
            foreach($result->getNodes() as $node) {
                $entity = new TextEntity(Position::fromObject($node->up(1), $sender->getWorld()), "§l§6Node: ".(++$id), true);
                $entity->setCanSaveWithChunk(false);
                $entity->setDespawnAfter(20 * 120);
                $entity->spawnToAll();
                $last = $node;
            }
            if($last !== null) {
                $sender->teleport($last);
            }
            $sender->sendMessage(City::PREFIX."Path found!");
        });
    }
}