<?php

declare(strict_types=1);

namespace matze\city\item\vanilla;

use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use RuntimeException;

class FilledMap extends Item {
    public const TAG_MAP_UUID = "map_uuid";

    public function setMapId(int $id) : void{
        $this->getNamedTag()->setLong(self::TAG_MAP_UUID, $id);
    }

    public function getMapId() : int{
        return $this->getNamedTag()->getLong(self::TAG_MAP_UUID, -1);
    }

    /**
     * @return FilledMap
     */
    public static function get(): Item {
        return StringToItemParser::getInstance()->parse("filled_map") ?? throw new RuntimeException("Filled map item should be registered!");
    }
}