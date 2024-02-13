<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;

class FacingCommandFlag extends IntegerCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return FacingCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): self
	{
		$this->setArgument(ArgumentParser::parseFacing($session, $argument));
		return $this;
	}
}