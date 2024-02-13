<?php

declare(strict_types=1);

namespace matze\city\session\scheduler;

use matze\city\session\Session;
use pocketmine\player\Player;

abstract class SessionScheduler {
    public function __construct(
        private Player $player,
        private Session $session,
    ){}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getSession(): Session {
        return $this->session;
    }

    abstract public function tick(): void;
    abstract public function getTickInterval(): int;
}