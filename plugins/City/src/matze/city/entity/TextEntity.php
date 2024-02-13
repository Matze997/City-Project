<?php

declare(strict_types=1);

namespace matze\city\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Position;

class TextEntity extends Entity {
    private int $despawnAfter = -1;

    public function __construct(Position $position, string $text, bool $nameTagAlwaysVisible = true){
        parent::__construct(Location::fromObject($position, $position->getWorld(), 0, 0));
        $this->setNameTagVisible();
        $this->setNameTag($text);
        $this->setNameTagAlwaysVisible($nameTagAlwaysVisible);
        $this->setScale(0.000001);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(0.000001, 0.00001);
    }

    protected function getInitialDragMultiplier(): float{
        return 0.0;
    }

    protected function getInitialGravity(): float{
        return 0.0;
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::GLOW_SQUID;
    }

    public function setDespawnAfter(int $despawnAfter): void{
        $this->despawnAfter = $despawnAfter;
    }

    public function onUpdate(int $currentTick): bool{
        if($this->despawnAfter !== -1) {
            if(--$this->despawnAfter <= 0) {
                $this->flagForDespawn();
            }
        }

        $changedProperties = $this->getDirtyNetworkData();
        if(count($changedProperties) > 0){
            $this->sendData(null, $changedProperties);
            $this->getNetworkProperties()->clearDirtyProperties();
        }
        return true;
    }
}