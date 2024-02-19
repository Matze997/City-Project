<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\item;

use matze\city\session\Session;
use matze\city\tool\streetmapper\entity\RoadMarkerEntity;
use matze\city\tool\streetmapper\player\StreetMappingSession;
use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\tool\streetmapper\util\RoadConnections;
use matze\city\util\VanillaSounds;
use matze\item\action\AttackEntityAction;
use matze\item\action\DropItemAction;
use matze\item\action\InteractItemAction;
use matze\item\action\ItemAction;
use matze\item\action\TickAction;
use matze\item\ModifiedItem;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\world\particle\CriticalParticle;
use RuntimeException;

class AddLaneSwitchMarkerItem extends ModifiedItem {
    protected function getInitialItem(): Item{
        return VanillaBlocks::STONE()->asItem()->setCustomName("§r§aAdd Lane Switch");
    }

    public function attackEntity(AttackEntityAction $action): ItemAction{
        $entity = $action->getEntity();
        if(!$entity instanceof RoadMarkerEntity) {
            return $action->cancel();
        }
        StreetMappingSession::get($action->getPlayer())->addLaneSwitchSelection($entity->getPosition());
        Session::get($action->getPlayer())->playSound(VanillaSounds::RANDOM_ORB, 0.4);
        return $action->cancel();
    }

    public function interact(InteractItemAction $action): ItemAction{
        $player = $action->getPlayer();
        $streetSession = StreetMappingSession::get($player);

        foreach($streetSession->getLaneSwitchSelections() as $selection) {
            $connections = RoadNetwork::getRoadMarkerConnections($selection);
            if($connections === null) {
                throw new RuntimeException("Connections must no be null!");
            }
            $connections->setType(RoadConnections::TYPE_LANE_CHANGE);

            foreach($streetSession->getLaneSwitchSelections() as $otherSelection) {
                $otherConnections = RoadNetwork::getRoadMarkerConnections($otherSelection);
                if($otherConnections === null) {
                    throw new RuntimeException("Connections must no be null!");
                }
                foreach($otherConnections->getAll() as $target) {
                    $connections->addLane($otherSelection, $target);
                }
            }
        }

        $streetSession->clearLaneSwitchSelections();
        Session::get($player)->playSound(VanillaSounds::RANDOM_LEVELUP, 2);

        return $action->cancel();
    }

    public function drop(DropItemAction $action): ItemAction{
        StreetMappingSession::get($action->getPlayer())->clearLaneSwitchSelections();
        Session::get($action->getPlayer())->playSound(VanillaSounds::NOTE_BASS, 0.7);
        return $action->cancel();
    }

    public function tick(TickAction $action): ItemAction{
        $world = $action->getPlayer()->getWorld();
        foreach(StreetMappingSession::get($action->getPlayer())->getLaneSwitchSelections() as $vector3) {
            $world->addParticle($vector3->add(0.5, 1, 0.5), new CriticalParticle());
            $world->addParticle($vector3->add(0.5, 1.5, 0.5), new CriticalParticle());
            $world->addParticle($vector3->add(0.5, 2, 0.5), new CriticalParticle());
        }
        return $action->continue();
    }
}