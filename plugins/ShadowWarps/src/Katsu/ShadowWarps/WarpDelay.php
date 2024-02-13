<?php

declare(strict_types=1);

namespace Katsu\ShadowWarps;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;

use Katsu\ShadowWarps\API\WarpAPI;
use Katsu\ShadowWarps\Commands\Warp;
use Katsu\ShadowWarps\Commands\DelWarp;
use Katsu\ShadowWarps\Commands\SetWarp;

class WarpDelay extends PluginBase
{
    private static WarpDelay $main;

    public function onEnable(): void
    {
        self::$main = $this;
        $this->saveDefaultConfig();

        new WarpAPI();

        $this->getServer()->getCommandMap()->registerAll("WarpsCommand", [new DelWarp(), new SetWarp(), new Warp()]);
    }

    public static function getInstance(): WarpDelay
    {
        return self::$main;
    }

    public function onDisable(): void
    {
        WarpAPI::$data->save();
    }

    public static function getConfigReplace(string $path, array|string $replace = [], array|string $replacer = []): string
    {
        $return = str_replace("{prefix}", self::$main->getConfig()->get("prefix"), self::$main->getConfig()->get($path));
        return str_replace($replace, $replacer, $return);
    }

    public static function hasPermissionPlayer(Player $player, string $perm): bool
    {
        if (self::$main->getServer()->isOp($player->getName())) return false;
        if ($player->hasPermission($perm)) return false; else $player->sendMessage(self::getConfigReplace("no_perm"));
        return true;
    }

    public static function getConfigValue(string $path): mixed
    {
        return self::$main->getConfig()->get($path);
    }
}