<?php

declare(strict_types=1);

namespace Katsu\ShadowWarps\Commands;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;

use Katsu\ShadowWarps\WarpDelay;
use Katsu\ShadowWarps\API\WarpAPI;

class SetWarp extends Command
{
    public function __construct()
    {
        $command = explode(":", WarpDelay::getConfigValue("setwarp_cmd"));
        parent::__construct($command[0]);
        if (isset($command[1])) $this->setDescription($command[1]);
        $this->setAliases(WarpDelay::getConfigValue("setwarp_aliases"));
        $this->setPermission("shadowwarps.cmd.setwarp");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            $command = explode(":", WarpDelay::getConfigValue("setwarp_cmd"));
            if ((isset($command[2])) and (WarpDelay::hasPermissionPlayer($sender, $command[2]))) return;
            if (isset($args[0])) {
                if (!WarpAPI::existWarp($args[0])) {
                    WarpAPI::addWarp($sender, $args[0]);
                    $sender->sendMessage(WarpDelay::getConfigReplace("setwarp_good"));
                } else $sender->sendMessage(WarpDelay::getConfigReplace("setwarp_msg_exist"));
            } else $sender->sendMessage(WarpDelay::getConfigReplace("setwarp_on_warp"));
        }
    }
}