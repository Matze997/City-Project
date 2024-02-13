<?php

declare(strict_types=1);

namespace Katsu\ShadowWarps\Commands;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;

use Katsu\ShadowWarps\WarpDelay;
use Katsu\ShadowWarps\Forms\WarpForms;
use Katsu\ShadowWarps\Task\TeleportationTask;
use Katsu\ShadowWarps\API\WarpAPI;

class Warp extends Command
{
    public function __construct()
    {
        $command = explode(":", WarpDelay::getConfigValue("warp_cmd"));
        parent::__construct($command[0]);
        if (isset($command[1])) $this->setDescription($command[1]);
        $this->setAliases(WarpDelay::getConfigValue("warp_aliases"));
        $this->setPermission("shadowwarps.cmd.warp");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            $command = explode(":", WarpDelay::getConfigValue("warp_cmd"));
            if ((isset($command[2])) and (WarpDelay::hasPermissionPlayer($sender, $command[2]))) return;
            if ((isset($args[0])) and (WarpAPI::existWarp($args[0]))) {
                $sender->teleport(WarpAPI::getWarp($args[0]));
                $sender->sendMessage(WarpDelay::getConfigReplace("warp_msg_teleport"));
            } else {
                if (WarpDelay::getConfigValue("form")) {
                    $sender->sendForm(WarpForms::warpForm());
                    return;
                }

                $warps = implode(", ", WarpAPI::getAllWarps());
                $sender->sendMessage(WarpDelay::getConfigReplace("warp_msg_list", ["{warp}"], [$warps]));
            }
        }
    }
}