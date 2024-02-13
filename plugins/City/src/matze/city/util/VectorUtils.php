<?php

declare(strict_types=1);

namespace matze\city\util;

use pocketmine\math\Vector3;

class VectorUtils {
    public static function getPositionsBetween(Vector3 $v1, Vector3 $v2): array {
        $distance = $v1->distance($v2);
        if($distance <= 1) {
            return [];
        }

        $dx = ($v2->getX() - $v1->getX()) / $distance;
        $dy = ($v2->getY() - $v1->getY()) / $distance;
        $dz = ($v2->getZ() - $v1->getZ()) / $distance;

        $positions = [];

        $position = clone $v1;
        while($position->distance($v2) > 1) {
            $position->x += $dx;
            $position->y += $dy;
            $position->z += $dz;

            $positions[] = clone $position;
        }
        return $positions;
    }

    public static function getYaw(Vector3 $v1, Vector3 $v2): float {
        $xDist = $v1->x - $v2->x;
        $zDist = $v1->z - $v2->z;
        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($yaw < 0){
            $yaw += 360.0;
        }
        return $yaw;
    }
}