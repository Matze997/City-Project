<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\item;

use matze\city\session\Session;
use matze\city\tool\streetmapper\entity\RoadMarkerEntity;
use matze\city\tool\streetmapper\player\StreetMappingSession;
use matze\city\tool\streetmapper\RoadNetwork;
use matze\city\util\VanillaSounds;
use matze\city\util\VectorUtils;
use matze\item\action\AttackEntityAction;
use matze\item\action\InteractEntityAction;
use matze\item\action\InteractItemAction;
use matze\item\action\ItemAction;
use matze\item\action\TickAction;
use matze\item\ModifiedItem;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\world\particle\DustParticle;

class AddRoadMarkerItem extends ModifiedItem {
    protected function getInitialItem(): Item{
        return VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW)->asItem()->setCustomName("§r§aAdd Road Marker");
    }

    public function interact(InteractItemAction $action): ItemAction{
        if($action->getAction() === null) {
            return $action->cancel();
        }
        $position = $action->getBlock()?->getPosition()->getSide($action->getFace())->floor()->add(0.5, 0, 0.5);
        $world = $action->getPlayer()->getWorld();

        $streetSession = StreetMappingSession::get($action->getPlayer());
        $currentStreetEntity = $streetSession->getCurrentStreetEntity();
        if($currentStreetEntity !== null) {
            RoadNetwork::addRoadMarker($currentStreetEntity->getPosition(), $position);
        }

        $location = Location::fromObject($position, $world, 0, 0);

        $entity = new RoadMarkerEntity($location);
        $entity->spawnToAll();
        $streetSession->setCurrentStreetEntity($entity);

        Session::get($action->getPlayer())->playSound(VanillaSounds::RANDOM_ORB, 2);

        return $action->cancel();
    }

    public function interactEntity(InteractEntityAction $action): ItemAction{
        $entity = $action->getEntity();
        if($entity instanceof RoadMarkerEntity) {
            $streetSession = StreetMappingSession::get($action->getPlayer());
            $currentStreetEntity = $streetSession->getCurrentStreetEntity();
            if($currentStreetEntity !== null) {
                RoadNetwork::addRoadMarker($currentStreetEntity->getPosition(), $entity->getPosition());
            }
            Session::get($action->getPlayer())->playSound(VanillaSounds::RANDOM_ORB, 2);
            $streetSession->setCurrentStreetEntity($entity);
        }
        return $action->cancel();
    }

    public function attackEntity(AttackEntityAction $action): ItemAction{
        $entity = $action->getEntity();
        if($entity instanceof RoadMarkerEntity) {
            Session::get($action->getPlayer())->playSound(VanillaSounds::RANDOM_LEVELUP, 2);
            StreetMappingSession::get($action->getPlayer())->setCurrentStreetEntity($entity);
        }
        return $action->cancel();
    }

    public function tick(TickAction $action): ItemAction{
        $player = $action->getPlayer();
        $session = StreetMappingSession::get($player);
        $world = $player->getWorld();
        $currentStreetEntity = $session->getCurrentStreetEntity();
        if($currentStreetEntity !== null) {
            $target = $player->getTargetBlock(10)?->getPosition()->getSide(Facing::UP)->add(0.5, 0.5, 0.5);
            if($target !== null) {
                $particle = new DustParticle(new Color(0, 255, 0));
                foreach(VectorUtils::getPositionsBetween($currentStreetEntity->getPosition()->add(0, 0.5, 0), $target) as $vector3) {
                    $world->addParticle($vector3, $particle);
                }
            }
        }
        return $action->continue();
    }
}