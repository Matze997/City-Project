<?php

declare(strict_types=1);

namespace matze\city\component\vehicle\controller;

use matze\city\component\vehicle\VehicleEntity;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

abstract class Controller {
    abstract public function update(VehicleEntity $vehicle, int $tick): void;

    protected function move(Vector3 $motion, VehicleEntity $vehicle): void {
        if($vehicle->isCollidedHorizontally) {
            $motion->y = 1;
        } elseif(!$vehicle->isOnGround()) {
            $motion->y = -1;
        }
        $vehicle->motion($motion);
    }
}