<?php

declare(strict_types=1);

namespace Katsu\ShadowWarps\Commands;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;

use Katsu\ShadowWarps\API\WarpAPI;
use Katsu\ShadowWarps\WarpDelay;

class DelWarp extends Command
{
    public function __construct()
    {
        $command = explode(":", WarpDelay::getConfigValue("delwarp_cmd"));
        parent::__construct($command[0]);
        if (isset($command[1])) $this->setDescription($command[1]);
        $this->setAliases(WarpDelay::getConfigValue("delwarp_aliases"));
        $this->setPermission("shadowwarps.cmd.delwarp");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            $command = explode(":", WarpDelay::getConfigValue("delwarp_cmd"));
            if ((isset($command[2])) and (WarpDelay::hasPermissionPlayer($sender, $command[2]))) return;
            if (isset($args[0])) {
                if (WarpAPI::existWarp($args[0])) {
                    WarpAPI::delWarp($args[0]);
                    $sender->sendMessage(WarpDelay::getConfigReplace("delwarp_good"));
                } else $sender->sendMessage(WarpDelay::getConfigReplace("delwarp_msg_no_exist"));
            } else $sender->sendMessage(WarpDelay::getConfigReplace("delwarp_on_warp"));
        }
    }
}