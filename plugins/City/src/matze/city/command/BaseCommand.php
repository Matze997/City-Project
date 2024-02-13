<?php

declare(strict_types=1);

namespace matze\city\command;

use matze\city\City;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

abstract class BaseCommand extends Command {
    public const PERMISSION_ADMIN = "default.op";
    public const PERMISSION_ALL = "default.all";

    public function testPermission(CommandSender $target, ?string $permission = null): bool{
        if($this->testPermissionSilent($target, $permission)){
            return true;
        }
        $target->sendMessage(City::PREFIX."Unknown Command!");
        return false;
    }

    abstract public function execute(CommandSender $sender, string $commandLabel, array $args): void;
}