<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper;

use matze\city\City;
use matze\city\command\BaseCommand;
use matze\city\tool\streetmapper\item\AddRoadMarkerItem;
use matze\city\tool\streetmapper\item\ClearCurrentStreetEntityItem;
use matze\city\tool\streetmapper\item\RemoveRoadMarkerItem;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StreetMapperCommand extends BaseCommand {
    public function __construct(){
        parent::__construct("streetmapper", "Admin Tool");
        $this->setPermission(self::PERMISSION_ADMIN);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player || !$this->testPermission($sender)) {
            return;
        }
        switch($args[0] ?? "") {
            case "save": {
                RoadNetwork::save();
                $sender->sendMessage(City::PREFIX."Successfully saved road network");
                break;
            }
            default: {
                AddRoadMarkerItem::give($sender);
                ClearCurrentStreetEntityItem::give($sender);
                RemoveRoadMarkerItem::give($sender);
            }
        }
    }
}