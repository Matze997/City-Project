<?php
/*
 *   _____           _        _   __  __
 *  |  __ \         | |      | | |  \/  |
 *  | |__) |__   ___| | _____| |_| \  / | __ _ _ __
 *  |  ___/ _ \ / __| |/ / _ \ __| |\/| |/ _` | '_ \
 *  | |  | (_) | (__|   <  __/ |_| |  | | (_| | |_) |
 *  |_|   \___/ \___|_|\_\___|\__|_|  |_|\__,_| .__/
 *                                            | |
 *                                            |_|
 *
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\commands\marker;

use Hebbinkpro\PocketMap\libs\CortexPE\Commando\args\BlockPositionArgument;
use Hebbinkpro\PocketMap\libs\CortexPE\Commando\args\IntegerArgument;
use Hebbinkpro\PocketMap\libs\CortexPE\Commando\args\RawStringArgument;
use Hebbinkpro\PocketMap\libs\CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\PocketMap\libs\CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MarkerAddCircleCommand extends BaseSubCommand
{

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed>|array{name: string, radius: int, pos?: Vector3, world?: string, id?: string} $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        if ($sender instanceof Player) {
            if (!isset($args["pos"])) $args["pos"] = $sender->getPosition();
            if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();
        } else if (sizeof($args) < 4) {
            $sender->sendMessage("§cInvalid amount of arguments given");
            return;
        }
        $world = $plugin->getLoadedWorld($args["world"]);
        if ($world === null) {
            $sender->sendMessage("§cInvalid world given");
            return;
        }

        $name = $args["name"];
        $r = $args["radius"];
        $pos = Position::fromObject($args["pos"], $world);
        $id = $args["id"] ?? null;

        $res = PocketMap::getMarkers()->addCircleMarker($name, $pos, $r, $id);
        if ($res) $sender->sendMessage("[PocketMap] Marker '$name' is added to world '{$args["world"]}'");
        else $sender->sendMessage("§cSomething went wrong");
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.marker.add"]);

        $this->registerArgument(0, new RawStringArgument("name"));
        $this->registerArgument(1, new IntegerArgument("radius"));
        $this->registerArgument(2, new BlockPositionArgument("pos", true));
        $this->registerArgument(3, new RawStringArgument("world", true));
        $this->registerArgument(4, new RawStringArgument("id", true));
    }
}