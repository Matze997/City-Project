<?php

declare(strict_types=1);

namespace matze\city\command\tool;

use matze\city\command\BaseCommand;
use matze\city\session\Session;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class PlaySoundCommand extends BaseCommand {
    public function __construct(){
        parent::__construct("playsound");
        $this->setPermission("default.op");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player || !$this->testPermission($sender)) {
            return;
        }
        Session::get($sender)?->playSound($args[0] ?? "", (float)($args[1] ?? 1.0), (float)($args[2] ?? 1.0));
    }
}