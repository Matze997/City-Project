<?php

declare(strict_types=1);

namespace matze\city\util;

class MathUtils {
    public static function nearestFloat(float $float, array $floats): float {
        $result = null;
        foreach ($floats as $possibleFloat) {
            if ($result === null || abs($float - $result) > abs($possibleFloat - $float)){
                $result = $possibleFloat;
            }
        }
        return $result ?? -1.0;
    }
}