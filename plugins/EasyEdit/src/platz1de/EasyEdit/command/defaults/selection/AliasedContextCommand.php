<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\SetTask;
use pocketmine\block\VanillaBlocks;

class AliasedContextCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	/**
	 * @param string           $name
	 * @param string[]         $aliases
	 * @param SelectionContext $context
	 */
	public function __construct(private SelectionContext $context, string $name, array $aliases = [])
	{
		parent::__construct($name, [KnownPermissions::PERMISSION_EDIT], $aliases);
		$this->flagOrder = ["pattern" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runSettingTask(new SetTask($session->getSelection(), $flags->getPatternFlag("pattern"), $this->context));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"pattern" => PatternCommandFlag::default(StaticBlock::from(VanillaBlocks::STONE()), "pattern", [], "p")
		];
	}
}