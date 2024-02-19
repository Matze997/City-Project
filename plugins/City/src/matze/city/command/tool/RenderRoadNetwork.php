<?php

declare(strict_types=1);

namespace matze\city\command\tool;

use matze\city\City;
use matze\city\command\BaseCommand;
use matze\city\tool\streetmapper\RoadNetwork;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\World;

class RenderRoadNetwork extends BaseCommand {
    public function __construct(){
        parent::__construct("renderroadnetwork");
        $this->setPermission("default.op");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$this->testPermission($sender)) {
            return;
        }
        RoadNetwork::save();
        Server::getInstance()->getAsyncPool()->submitTask(new RenderRoadNetworkTask());
    }
}

class RenderRoadNetworkTask extends AsyncTask {
    public function onRun(): void{
        RoadNetwork::load();

        $xOffset = 0;
        $zOffset = 0;

        $xSize = 0;
        $zSize = 0;

        foreach(RoadNetwork::getAll() as $connection) {
            $vector3 = $connection->getVector3();

            if($xOffset > $vector3->getX()) {
                $xOffset = $vector3->getFloorX();
            }
            if(($vector3->getX()) > $xSize) {
                $xSize = ($vector3->getFloorX());
            }

            if($zOffset > $vector3->getZ()) {
                $zOffset = $vector3->getFloorZ();
            }
            if(($vector3->getZ()) > $zSize) {
                $zSize = ($vector3->getFloorZ());
            }
        }

        $xOffset = (int)floor(abs($xOffset));
        $zOffset = (int)floor(abs($zOffset));

        $xSize += $xOffset;
        $zSize += $zOffset;

        $image = imagecreate($xSize, $zSize);
        imagecolorallocate($image, 0, 0, 0);

        $white = imagecolorallocate($image, 255, 255, 255);
        $green = imagecolorallocate($image, 0, 255, 0);

        $connections = [];
        foreach(RoadNetwork::getAll() as $connection) {
            $vector3 = $connection->getVector3();
            $connections[$vector3->getFloorY()][] = $connection;
        }
        ksort($connections, SORT_NUMERIC);
        $toRender = [];
        foreach($connections as $list) {
            foreach($list as $key) {
                $toRender[] = $key;
            }
        }

        foreach($toRender as $connection) {
            $vector3 = $connection->getVector3();
            $color = $white;
            if(count($connection->getLanes()) > 0) {
                $color = $green;
            }
            foreach($connection->getAll() as $target) {
                imageline($image, $vector3->getFloorX() + $xOffset, $vector3->getFloorZ() + $zOffset, $target->getFloorX() + $xOffset, $target->getFloorZ() + $zOffset, $color);
            }
        }
        imagepng($image, City::getDataPath()."road_network.png");
        imagedestroy($image);
    }

    public function onCompletion(): void{
        Server::getInstance()->getLogger()->info("Successfully rendered road network!");
    }
}