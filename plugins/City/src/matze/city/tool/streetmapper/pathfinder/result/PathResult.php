<?php

declare(strict_types=1);

namespace matze\city\tool\streetmapper\pathfinder\result;

use matze\city\tool\streetmapper\pathfinder\Node;

class PathResult {
    /** @var Node[]  */
    public array $nodes = [];

    public function getNodes(): array{
        return $this->nodes;
    }

    public function addNode(Node $node): void {
        $this->nodes[$node->getHash()] = $node;
    }

    public function shiftNode(): ?Node {
        return array_shift($this->nodes);
    }
}