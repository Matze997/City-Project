<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\util;

use pocketmine\math\Vector3;

class Lane {
    public function __construct(
        public Vector3 $start,
        public Vector3 $end,
    ){}
}